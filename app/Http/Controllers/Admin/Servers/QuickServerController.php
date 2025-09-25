<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Contracts\Repository\NestRepositoryInterface;
use Pterodactyl\Contracts\Repository\NodeRepositoryInterface;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Allocation;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class QuickServerController extends Controller
{
    public function __construct(
        private NestRepositoryInterface $nestRepository,
        private NodeRepositoryInterface $nodeRepository,
        private ServerCreationService $serverCreationService
    ) {}

    /**
     * Return data needed for quick server creation modal
     */
    public function data(): JsonResponse
    {
        try {
            $nests = $this->nestRepository->getWithEggs();
            $nodes = $this->nodeRepository->all();
            
            // Filter to only public nodes not in maintenance with available allocations
            $availableNodes = $nodes->filter(function ($node) {
                return $node->public 
                    && !$node->maintenance_mode 
                    && $node->allocations()->whereNull('server_id')->exists();
            });
            
            return response()->json([
                'nests' => $nests->map(function ($nest) {
                    return [
                        'id' => $nest->id,
                        'name' => $nest->name,
                        'description' => $nest->description,
                        'eggs' => $nest->eggs->map(function ($egg) {
                            return [
                                'id' => $egg->id,
                                'name' => $egg->name,
                                'description' => $egg->description,
                                'docker_image' => $egg->docker_image,
                                'startup' => $egg->startup,
                                'config' => $egg->config,
                            ];
                        }),
                    ];
                }),
                'nodes' => $availableNodes->map(function ($node) {
                    return [
                        'id' => $node->id,
                        'name' => $node->name,
                        'description' => $node->description,
                        'memory' => $node->memory,
                        'disk' => $node->disk,
                        'location_id' => $node->location_id,
                        'public' => $node->public,
                        'maintenance_mode' => $node->maintenance_mode,
                        'available_allocations' => $node->allocations()
                            ->whereNull('server_id')
                            ->count(),
                    ];
                }),
                'resource_presets' => [
                    'low' => [
                        'name' => 'Low (Testing)',
                        'memory' => 512,
                        'disk' => 1024,
                        'cpu' => 100,
                        'swap' => 0,
                        'io' => 500,
                    ],
                    'medium' => [
                        'name' => 'Medium (Development)', 
                        'memory' => 2048,
                        'disk' => 4096,
                        'cpu' => 200,
                        'swap' => 0,
                        'io' => 500,
                    ],
                    'high' => [
                        'name' => 'High (Production)',
                        'memory' => 4096,
                        'disk' => 8192,
                        'cpu' => 300,
                        'swap' => 0,
                        'io' => 500,
                    ],
                ],
                'warnings' => $this->getSystemWarnings($availableNodes, $nests),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to load quick server data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load server creation data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system warnings for quick creation
     */
    private function getSystemWarnings($availableNodes, $nests): array
    {
        $warnings = [];
        
        if ($availableNodes->isEmpty()) {
            $warnings[] = [
                'type' => 'danger',
                'message' => 'No available nodes found. Ensure at least one node is public, not in maintenance, and has available allocations.'
            ];
        } elseif ($availableNodes->count() === 1) {
            $warnings[] = [
                'type' => 'info',
                'message' => "Only 1 node available: {$availableNodes->first()->name}"
            ];
        }
        
        if ($nests->isEmpty()) {
            $warnings[] = [
                'type' => 'danger',
                'message' => 'No nests found. Please create at least one nest with eggs before using Quick Create.'
            ];
        }
        
        $totalAllocations = $availableNodes->sum(function ($node) {
            return $node->allocations()->whereNull('server_id')->count();
        });
        
        if ($totalAllocations < 5) {
            $warnings[] = [
                'type' => 'warning',
                'message' => "Only {$totalAllocations} allocations available across all nodes."
            ];
        }
        
        return $warnings;
    }

    /**
     * Create a quick server for testing
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nest_id' => 'required|integer|exists:nests,id',
            'egg_id' => 'required|integer|exists:eggs,id',
            'preset' => 'required|in:low,medium,high',
            'auto_start' => 'boolean',
            'random_name' => 'boolean',
            'custom_name' => 'nullable|string|max:255',
        ]);

        try {
            // Get the selected egg with nest relationship and variables
            $egg = Egg::with(['nest', 'variables'])->find($validated['egg_id']);
            if (!$egg) {
                return response()->json(['error' => 'Selected egg not found'], 404);
            }

            // Validate that egg belongs to the selected nest
            if ($egg->nest_id != $validated['nest_id']) {
                return response()->json(['error' => 'Egg does not belong to selected nest'], 400);
            }

            // Get resource preset
            $presets = [
                'low' => ['memory' => 512, 'disk' => 1024, 'cpu' => 100, 'swap' => 0, 'io' => 500],
                'medium' => ['memory' => 2048, 'disk' => 4096, 'cpu' => 200, 'swap' => 0, 'io' => 500],
                'high' => ['memory' => 4096, 'disk' => 8192, 'cpu' => 300, 'swap' => 0, 'io' => 500],
            ];
            $preset = $presets[$validated['preset']];

            // Auto-select first available node
            $node = Node::where('public', true)
                ->where('maintenance_mode', false)
                ->whereHas('allocations', function ($query) {
                    $query->whereNull('server_id');
                })
                ->first();

            if (!$node) {
                return response()->json(['error' => 'No available nodes found. Please ensure at least one node is public, not in maintenance, and has available allocations.'], 400);
            }

            // Auto-select first available allocation
            $allocation = $node->allocations()
                ->whereNull('server_id')
                ->first();

            if (!$allocation) {
                return response()->json(['error' => "No available allocations found on node '{$node->name}'. Please create allocations for this node."], 400);
            }

            // Generate server name
            if ($validated['random_name'] ?? true) {
                $serverName = $this->generateRandomServerName($egg->name);
            } else {
                $serverName = $validated['custom_name'] ?? $this->generateRandomServerName($egg->name);
            }

            // Get default environment variables
            $environment = $this->getDefaultEnvironmentVariables($egg);

            // Get current user or fall back to first admin user
            $userId = auth()->user()->id ?? User::where('root_admin', true)->first()->id ?? 1;

            // Select Docker image with logging
            $dockerImage = $this->selectDockerImage($egg);
            \Log::debug('Selected Docker image for egg', [
                'egg_id' => $egg->id,
                'egg_name' => $egg->name,
                'docker_image' => $dockerImage,
                'available_images' => $egg->docker_images
            ]);

            // Server data for creation
            $serverData = [
                'name' => $serverName,
                'description' => "Quick test server for {$egg->name} - Created via Quick Create",
                'owner_id' => $userId, // Use owner_id instead of user_id
                'nest_id' => $validated['nest_id'],
                'egg_id' => $validated['egg_id'],
                'node_id' => $node->id,
                'allocation_id' => $allocation->id,
                'memory' => $preset['memory'],
                'disk' => $preset['disk'],
                'cpu' => $preset['cpu'],
                'swap' => $preset['swap'],
                'io' => $preset['io'],
                'image' => $dockerImage,
                'startup' => $egg->startup,
                'environment' => $environment,
                'start_on_completion' => $validated['auto_start'] ?? false,
                'skip_scripts' => false,
                'oom_disabled' => true,
            ];

            // Log the attempt
            \Log::info('Quick server creation attempt', [
                'owner_id' => $userId, // Updated to match the field name
                'server_name' => $serverName,
                'nest_id' => $validated['nest_id'],
                'egg_id' => $validated['egg_id'],
                'node_id' => $node->id,
                'allocation_id' => $allocation->id,
            ]);

            // Create the server
            $server = $this->serverCreationService->handle($serverData);

            \Log::info('Quick server created successfully', [
                'server_id' => $server->id,
                'server_uuid' => $server->uuid,
                'server_name' => $server->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quick server created successfully!',
                'server' => [
                    'id' => $server->id,
                    'uuid' => $server->uuid,
                    'name' => $server->name,
                    'node' => $node->name,
                    'allocation' => $allocation->ip . ':' . $allocation->port,
                    'preset' => ucfirst($validated['preset']),
                    'memory' => $preset['memory'] . ' MB',
                    'disk' => $preset['disk'] . ' MB',
                    'view_url' => route('admin.servers.view', $server->id),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Quick server creation validation failed', [
                'errors' => $e->errors(),
                'data' => $validated
            ]);
            
            return response()->json([
                'error' => 'Validation failed: ' . implode(', ', array_flatten($e->errors()))
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Quick server creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated
            ]);

            // Return user-friendly error message
            $userMessage = $e->getMessage();
            
            // Handle common error scenarios
            if (str_contains($e->getMessage(), 'allocation')) {
                $userMessage = 'No available server allocations found. Please contact an administrator.';
            } elseif (str_contains($e->getMessage(), 'node')) {
                $userMessage = 'No available nodes found. Please contact an administrator.';
            } elseif (str_contains($e->getMessage(), 'memory') || str_contains($e->getMessage(), 'disk')) {
                $userMessage = 'Insufficient resources available on the selected node. Try a lower preset.';
            }

            return response()->json([
                'error' => 'Failed to create server: ' . $userMessage
            ], 500);
        }
    }

    /**
     * Generate a random server name based on egg type
     */
    private function generateRandomServerName(string $eggName): string
    {
        $adjectives = [
            'Agile', 'Bold', 'Clever', 'Dynamic', 'Epic', 'Fast', 'Great', 'Happy', 
            'Incredible', 'Jolly', 'Kind', 'Lively', 'Mighty', 'Noble', 'Optimal',
            'Perfect', 'Quick', 'Rapid', 'Smart', 'Turbo', 'Ultimate', 'Vivid',
            'Wild', 'Xenial', 'Youthful', 'Zealous'
        ];
        
        $nouns = [
            'Server', 'Instance', 'Node', 'Host', 'Machine', 'Container', 'Service',
            'Engine', 'Platform', 'System', 'Cluster', 'Gateway', 'Portal', 'Hub'
        ];

        // Clean egg name for use in server name
        $cleanEggName = preg_replace('/[^a-zA-Z0-9]/', '', $eggName);
        $shortEggName = substr($cleanEggName, 0, 10);

        $adjective = $adjectives[array_rand($adjectives)];
        $noun = $nouns[array_rand($nouns)];
        $number = rand(100, 999);

        return "{$adjective} {$shortEggName} {$noun} {$number}";
    }

    /**
     * Get default environment variables for an egg
     */
    private function getDefaultEnvironmentVariables(Egg $egg): array
    {
        $environment = [];
        
        // Ensure variables are loaded
        if (!$egg->relationLoaded('variables')) {
            $egg->load('variables');
        }
        
        $variables = $egg->variables ?? collect();
        
        foreach ($variables as $variable) {
            if (!empty($variable->default_value)) {
                $environment[$variable->env_variable] = $variable->default_value;
            } else {
                // Provide sensible defaults for common variables
                $environment[$variable->env_variable] = $this->getSmartDefault($variable);
            }
        }

        return $environment;
    }

    /**
     * Get smart defaults for common environment variables
     */
    private function getSmartDefault($variable): string
    {
        $envVar = strtoupper($variable->env_variable);
        $name = strtolower($variable->name ?? '');
        
        // Common password fields
        if (str_contains($envVar, 'PASSWORD') || str_contains($envVar, 'PASS')) {
            return 'quicktest' . rand(100, 999);
        }
        
        // Common server names
        if (str_contains($envVar, 'SERVER_NAME') || str_contains($envVar, 'SESSION_NAME')) {
            return 'Quick Test Server ' . rand(100, 999);
        }
        
        // World/Map names
        if (str_contains($envVar, 'WORLD') || str_contains($envVar, 'MAP')) {
            return 'world' . rand(100, 999);
        }
        
        // Player limits
        if (str_contains($envVar, 'PLAYER') || str_contains($envVar, 'SLOT')) {
            return '10';
        }
        
        // Port numbers (avoid conflicts)
        if (str_contains($envVar, 'PORT')) {
            return (string) rand(20000, 30000);
        }
        
        // Boolean values
        if (str_contains($name, 'enable') || str_contains($name, 'auto')) {
            return '1';
        }
        
        // Default fallback
        return 'quicktest';
    }

    /**
     * Select appropriate Docker image for egg
     */
    private function selectDockerImage(Egg $egg): string
    {
        $images = $egg->docker_images ?? [];
        
        if (is_array($images) && !empty($images)) {
            // Prefer Java 17 if available
            foreach ($images as $image) {
                if (!empty($image) && (str_contains($image, 'java_17') || str_contains($image, 'java:17'))) {
                    return $image;
                }
            }
            
            // Prefer latest versions
            foreach ($images as $image) {
                if (!empty($image) && str_contains($image, 'latest')) {
                    return $image;
                }
            }
            
            // Return first available non-empty image
            foreach ($images as $image) {
                if (!empty($image)) {
                    return $image;
                }
            }
        }

        // Fallback to egg's docker_image field
        if (!empty($egg->docker_image)) {
            return $egg->docker_image;
        }

        // Ultimate fallback - common base image
        return 'ghcr.io/pterodactyl/panel:latest';
    }
}