<?php

namespace Pterodactyl\Services\Shop;

use Pterodactyl\Models\Shop\ShopOrder;
use Pterodactyl\Models\Shop\ShopPlan;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Egg;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Deployment\AllocationSelectionService;
use Pterodactyl\Services\Shop\EggVariableService;
use Pterodactyl\Repositories\Eloquent\ServerRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ServerProvisioningService
{
    protected ServerCreationService $serverCreationService;
    protected AllocationSelectionService $allocationService;
    protected ServerRepository $serverRepository;
    protected EggVariableService $eggVariableService;

    public function __construct(
        ServerCreationService $serverCreationService,
        AllocationSelectionService $allocationService,
        ServerRepository $serverRepository,
        EggVariableService $eggVariableService
    ) {
        $this->serverCreationService = $serverCreationService;
        $this->allocationService = $allocationService;
        $this->serverRepository = $serverRepository;
        $this->eggVariableService = $eggVariableService;
    }

    /**
     * Provision a server for a completed order.
     */
    public function provisionServer(ShopOrder $order): ?Server
    {
        try {
            Log::info('Starting server provisioning for order', [
                'order_id' => $order->uuid,
                'user_id' => $order->user_id,
                'plan_id' => $order->plan_id,
            ]);

            // Load required relationships
            $order->load(['user', 'plan', 'plan.category']);
            
            if (!$order->plan) {
                throw new \Exception('Order plan not found');
            }

            // Generate server details
            $serverData = $this->generateServerData($order);
            
            // Create the server
            $server = $this->serverCreationService->handle($serverData);

            // Update order with server reference
            $order->update([
                'server_id' => $server->id,
                'provisioning_started_at' => now(),
            ]);

            Log::info('Server provisioned successfully', [
                'order_id' => $order->uuid,
                'server_id' => $server->id,
                'server_identifier' => $server->identifier,
            ]);

            // Note: Installation process is handled by Wings daemon
            // The server will be automatically installed and will be ready once installation completes

            return $server;

        } catch (\Exception $e) {
            Log::error('Server provisioning failed', [
                'order_id' => $order->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update order status to indicate provisioning failure
            $order->update([
                'status' => 'provisioning_failed',
                'provisioning_error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate server creation data from order.
     */
    protected function generateServerData(ShopOrder $order): array
    {
        $plan = $order->plan;
        $user = $order->user;

        // Get appropriate node for the plan
        $node = $this->selectNode($plan);
        
        if (!$node) {
            throw new \Exception('No available nodes found for server provisioning');
        }

        // Get allocation for the server
        $allocation = $this->selectAllocation($node);
        
        if (!$allocation) {
            throw new \Exception('No available allocations found on selected node');
        }

        // Map plan resources to server limits
        $limits = $this->mapPlanToServerLimits($plan);

        // Get appropriate egg based on plan category
        $eggId = $this->selectEgg($plan);
        $egg = Egg::find($eggId);

        if (!$egg) {
            throw new \Exception("Egg with ID {$eggId} not found");
        }

        // Get the default docker image for the egg
        $dockerImages = $egg->docker_images;
        $defaultImage = '';
        if (is_array($dockerImages) && !empty($dockerImages)) {
            $defaultImage = reset($dockerImages); // Get the first image
        }

        return [
            'name' => $this->generateEnhancedServerName($order, $egg),
            'description' => $order->server_description ?: "Automatically provisioned {$plan->name} server",
            'owner_id' => $user->id,
            'egg_id' => $eggId,
            'node_id' => $node->id,
            'allocation_id' => $allocation->id,
            'allocation_additional' => [],
            'environment' => $this->getEnvironmentForOrder($order, $eggId),
            'memory' => $limits['memory'],
            'swap' => $limits['swap'],
            'disk' => $limits['disk'],
            'io' => $limits['io'],
            'cpu' => $limits['cpu'],
            'threads' => null,
            'oom_disabled' => false,
            'allocation_limit' => $limits['allocation_limit'],
            'database_limit' => $limits['database_limit'],
            'backup_limit' => $limits['backup_limit'],
            'startup' => $egg->startup,
            'image' => $defaultImage,
            'skip_scripts' => false,
            'start_on_completion' => false, // Don't start until installation is complete
        ];
    }

    /**
     * Select the best node for the plan.
     */
    protected function selectNode(ShopPlan $plan): ?Node
    {
        $nodes = Node::where('public', true)
            ->where('maintenance_mode', false)
            ->orderBy('memory_overallocate')
            ->get();

        foreach ($nodes as $node) {
            // Check if node has enough resources
            if (!$this->nodeHasCapacity($node, $plan)) {
                continue;
            }
            
            // Check if node can support the egg's port requirements
            if (!$this->ensureRequiredPortsAvailable($node, $plan->egg_id)) {
                continue;
            }
            
            return $node;
        }

        return null;
    }

    /**
     * Check if node has capacity for the plan.
     */
    protected function nodeHasCapacity(Node $node, ShopPlan $plan): bool
    {
        // Check memory capacity with overallocation
        $usedMemory = $node->servers()->sum('memory');
        $effectiveMemory = $node->memory * (1 + $node->memory_overallocate / 100);
        $availableMemory = $effectiveMemory - $usedMemory;
        
        if ($availableMemory < $plan->memory) {
            return false;
        }
        
        // Check disk capacity with overallocation
        $usedDisk = $node->servers()->sum('disk');
        $effectiveDisk = $node->disk * (1 + $node->disk_overallocate / 100);
        $availableDisk = $effectiveDisk - $usedDisk;
        
        if ($availableDisk < $plan->disk) {
            return false;
        }
        
        // Check if there are free allocations
        $freeAllocations = $node->allocations()->whereNull('server_id')->count();
        if ($freeAllocations < 1) {
            return false;
        }
        
        return true;
    }

    /**
     * Select an allocation for the server based on egg requirements.
     */
    protected function selectAllocation(Node $node): ?Allocation
    {
        return Allocation::where('node_id', $node->id)
            ->where('server_id', null)
            ->first();
    }

    /**
     * Ensure required ports are available for specific egg types.
     */
    protected function ensureRequiredPortsAvailable(Node $node, int $eggId): bool
    {
        // Get egg-specific port requirements
        $requiredPorts = $this->getRequiredPortsForEgg($eggId);
        
        if (empty($requiredPorts)) {
            return true; // No special port requirements
        }
        
        // Check if all required ports are allocated to the node
        foreach ($requiredPorts as $port) {
            $allocation = Allocation::where('node_id', $node->id)
                ->where('port', $port)
                ->first();
                
            if (!$allocation) {
                // Port not allocated to node - try to create it
                try {
                    $nodeIp = $this->getNodePrimaryIp($node);
                    Allocation::create([
                        'node_id' => $node->id,
                        'ip' => $nodeIp,
                        'port' => $port,
                    ]);
                    Log::info("Auto-created allocation for required port", [
                        'node_id' => $node->id,
                        'port' => $port,
                        'egg_id' => $eggId
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Failed to create required port allocation", [
                        'node_id' => $node->id,
                        'port' => $port,
                        'egg_id' => $eggId,
                        'error' => $e->getMessage()
                    ]);
                    return false;
                }
            } elseif ($allocation->server_id !== null) {
                // Port is occupied by another server
                Log::warning("Required port is occupied", [
                    'node_id' => $node->id,
                    'port' => $port,
                    'egg_id' => $eggId,
                    'occupied_by_server' => $allocation->server_id
                ]);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get required ports for specific egg types.
     */
    protected function getRequiredPortsForEgg(int $eggId): array
    {
        // Define egg-specific port requirements
        $eggPortRequirements = [
            13 => [10011, 10022, 10080], // TeamSpeak 3 - Query, SSH, HTTP ports
            // Add other eggs that need specific ports here
            // 5 => [27015, 27016], // Example: Source game server
            // 7 => [25565], // Example: Another Minecraft variant
        ];
        
        return $eggPortRequirements[$eggId] ?? [];
    }

    /**
     * Get the primary IP address for a node.
     */
    protected function getNodePrimaryIp(Node $node): string
    {
        // Get IP from existing allocations or use a default
        $existingAllocation = Allocation::where('node_id', $node->id)->first();
        
        if ($existingAllocation) {
            return $existingAllocation->ip;
        }
        
        // Fallback to node FQDN or a default IP
        return $node->fqdn ?? '127.0.0.1';
    }

    /**
     * Map shop plan to server resource limits.
     */
    protected function mapPlanToServerLimits(ShopPlan $plan): array
    {
        return [
            'memory' => $plan->memory, // MB
            'swap' => $plan->swap ?? $plan->memory, // MB, default to same as memory
            'disk' => $plan->disk, // MB
            'io' => $plan->io ?? 500, // IO weight
            'cpu' => $plan->cpu, // CPU percentage
            'allocation_limit' => $plan->allocation_limit ?? 1,
            'database_limit' => $plan->database_limit ?? 0,
            'backup_limit' => $plan->backup_limit ?? 0,
        ];
    }

    /**
     * Select appropriate egg based on plan category.
     */
    protected function selectEgg(ShopPlan $plan): int
    {
        // If the plan has a specific egg_id set, use that
        if ($plan->egg_id) {
            return $plan->egg_id;
        }

        // Otherwise, fall back to category mapping
        $categoryEggMap = [
            'minecraft' => 2, // Vanilla Minecraft
            'discord-bots' => 15, // Node.js (if it exists)
            'web-hosting' => 14, // Nginx (if it exists)
            'game-servers' => 2, // Default to Vanilla Minecraft
        ];

        $categorySlug = $plan->category->slug ?? 'minecraft';
        
        return $categoryEggMap[$categorySlug] ?? 2; // Default to Vanilla Minecraft (ID 2)
    }

    /**
     * Generate a unique server name.
     */
    protected function generateServerName(User $user, ShopPlan $plan): string
    {
        $baseName = $user->username . '-' . Str::slug($plan->name);
        $suffix = Str::random(4);
        
        return $baseName . '-' . $suffix;
    }

    /**
     * Generate enhanced server name with egg type prefix
     */
    protected function generateEnhancedServerName(ShopOrder $order, Egg $egg): string
    {
        $userProvidedName = $order->server_name;
        $eggSuffix = $this->getEggSuffix($egg);
        
        if ($userProvidedName) {
            // User provided a name, add egg suffix
            return $userProvidedName . '_' . $eggSuffix;
        } else {
            // No user name, generate default name with egg suffix
            $user = $order->user;
            $plan = $order->plan;
            $baseName = $user->username . '-' . Str::slug($plan->name);
            $randomSuffix = Str::random(4);
            
            return $baseName . '_' . $eggSuffix . '-' . $randomSuffix;
        }
    }

    /**
     * Get appropriate suffix for egg type
     */
    protected function getEggSuffix(Egg $egg): string
    {
        $eggMappings = [
            'Vanilla Minecraft' => 'minecraft_server',
            'Paper' => 'minecraft_server',
            'Forge Minecraft' => 'minecraft_forge_server',
            'Sponge (SpongeVanilla)' => 'minecraft_sponge_server',
            'Bungeecord' => 'minecraft_proxy_server',
            'Garrys Mod' => 'garysmod_server',
            'Team Fortress 2' => 'tf2_server',
            'Teamspeak3 Server' => 'teamspeak_server',
            'Rust' => 'rust_server',
            'Ark: Survival Evolved' => 'ark_server',
            'Counter-Strike: Global Offensive' => 'csgo_server',
            'Insurgency' => 'insurgency_server',
            'Mumble Server' => 'mumble_server',
            'Custom Source Engine Game' => 'source_server',
        ];

        return $eggMappings[$egg->name] ?? Str::slug($egg->name) . '_server';
    }

    /**
     * Check if a server installation is complete.
     */
    public function isServerInstallationComplete(Server $server): bool
    {
        try {
            // Check if the server has the required files
            // This is a basic check - in production you might want more sophisticated checking
            return $server->status === null; // Status is null when server is ready
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get server installation progress/status.
     */
    public function getServerInstallationStatus(Server $server): array
    {
        try {
            return [
                'status' => $server->status,
                'is_complete' => $this->isServerInstallationComplete($server),
                'server_id' => $server->id,
                'identifier' => $server->identifier,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'is_complete' => false,
            ];
        }
    }

    /**
     * Get default environment variables for an egg.
     */
    protected function getDefaultEnvironment(int $eggId): array
    {
        $egg = Egg::find($eggId);
        if (!$egg) {
            return [];
        }

        $environment = [];
        foreach ($egg->variables as $variable) {
            // Use the default value from the variable definition
            $environment[$variable->env_variable] = $variable->default_value;
        }
        
        return $environment;
    }

    /**
     * Get environment variables for an order, incorporating custom server variables.
     */
    protected function getEnvironmentForOrder(ShopOrder $order, int $eggId): array
    {
        // Start with default environment
        $environment = $this->getDefaultEnvironment($eggId);
        
        // If order has server variables, use those
        if ($order->server_variables) {
            // Ensure server_variables is an array (handle casting issues)
            $serverVariables = $order->server_variables;
            if (is_string($serverVariables)) {
                $serverVariables = json_decode($serverVariables, true) ?? [];
            }
            
            if (is_array($serverVariables)) {
                foreach ($serverVariables as $key => $value) {
                    $environment[$key] = $value;
                }
            }
        } else {
            // No server variables saved, generate them using the service
            // This handles cases where orders were created before the new system
            $allVariables = $this->eggVariableService->generateServerVariables($eggId, []);
            foreach ($allVariables as $key => $value) {
                $environment[$key] = $value;
            }
        }
        
        return $environment;
    }

    /**
     * Deprovision a server (for cancelled orders/refunds).
     */
    public function deprovisionServer(ShopOrder $order): bool
    {
        try {
            if (!$order->server_id) {
                Log::warning('Attempted to deprovision order without server', [
                    'order_id' => $order->uuid,
                ]);
                return true;
            }

            $server = Server::find($order->server_id);
            
            if (!$server) {
                Log::warning('Server not found for deprovisioning', [
                    'order_id' => $order->uuid,
                    'server_id' => $order->server_id,
                ]);
                return true;
            }

            // Suspend the server first
            $server->update(['status' => 'suspended']);

            Log::info('Server suspended for order cancellation', [
                'order_id' => $order->uuid,
                'server_id' => $server->id,
            ]);

            // Note: Full deletion would require additional safety checks
            // For now, we just suspend the server

            return true;

        } catch (\Exception $e) {
            Log::error('Server deprovisioning failed', [
                'order_id' => $order->uuid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
