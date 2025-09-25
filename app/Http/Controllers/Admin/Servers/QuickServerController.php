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
                'data' => $validated,
                'server_data' => $serverData ?? null,
                'environment' => $environment ?? null
            ]);
            
            return response()->json([
                'error' => 'Validation failed: This egg requires additional configuration that Quick Create cannot automatically provide.',
                'details' => $e->errors(),
                'debug_info' => [
                    'egg_name' => $egg->name ?? 'Unknown',
                    'missing_requirements' => 'Check server logs for detailed validation errors'
                ]
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
        
        \Log::debug('Processing egg variables', [
            'egg_id' => $egg->id,
            'egg_name' => $egg->name,
            'variable_count' => $variables->count(),
            'variables' => $variables->pluck('env_variable', 'name')->toArray()
        ]);
        
        foreach ($variables as $variable) {
            $defaultValue = '';
            
            // First try the variable's default value
            if (!empty($variable->default_value)) {
                $defaultValue = $variable->default_value;
            } else {
                // Generate smart default
                $defaultValue = $this->getSmartDefault($variable);
            }
            
            $environment[$variable->env_variable] = $defaultValue;
            
            \Log::debug('Set environment variable', [
                'env_variable' => $variable->env_variable,
                'name' => $variable->name,
                'value' => $defaultValue,
                'user_viewable' => $variable->user_viewable,
                'user_editable' => $variable->user_editable
            ]);
        }

        \Log::info('Generated environment variables for quick creation', [
            'egg_id' => $egg->id,
            'variable_count' => count($environment),
            'environment' => $environment
        ]);

        return $environment;
    }

    /**
     * Get smart defaults for common environment variables
     */
    private function getSmartDefault($variable): string
    {
        $envVar = strtoupper($variable->env_variable);
        $name = strtolower($variable->name ?? '');
        $description = strtolower($variable->description ?? '');
        
        // Log the variable we're trying to default for debugging
        \Log::debug('Generating smart default for variable', [
            'env_variable' => $envVar,
            'name' => $name,
            'description' => $description,
            'user_viewable' => $variable->user_viewable,
            'user_editable' => $variable->user_editable,
            'rules' => $variable->rules
        ]);
        
        // Common password/secret fields
        if (str_contains($envVar, 'PASSWORD') || str_contains($envVar, 'PASS') || 
            str_contains($envVar, 'SECRET') || str_contains($envVar, 'TOKEN')) {
            return 'quicktest' . rand(1000, 9999);
        }
        
        // Common server names
        if (str_contains($envVar, 'SERVER_NAME') || str_contains($envVar, 'SESSION_NAME') ||
            str_contains($envVar, 'MOTD') || str_contains($name, 'server name')) {
            return 'Quick Test Server ' . rand(100, 999);
        }
        
        // World/Map names
        if (str_contains($envVar, 'WORLD') || str_contains($envVar, 'MAP') || 
            str_contains($envVar, 'LEVEL') || str_contains($name, 'world')) {
            return 'quicktest' . rand(100, 999);
        }
        
        // Player/User limits
        if (str_contains($envVar, 'PLAYER') || str_contains($envVar, 'SLOT') ||
            str_contains($envVar, 'MAX') && (str_contains($name, 'player') || str_contains($name, 'user'))) {
            return '20';
        }
        
        // Port numbers (avoid conflicts)
        if (str_contains($envVar, 'PORT') && !str_contains($envVar, 'RCON')) {
            return (string) rand(25000, 30000);
        }
        
        // RCON ports specifically  
        if (str_contains($envVar, 'RCON_PORT')) {
            return (string) rand(30001, 35000);
        }
        
        // Memory settings
        if (str_contains($envVar, 'MEMORY') || str_contains($envVar, 'RAM')) {
            return '1024';
        }
        
        // Version numbers
        if (str_contains($envVar, 'VERSION') || str_contains($name, 'version')) {
            return 'latest';
        }
        
        // Boolean values (true/false or 1/0)
        if (str_contains($name, 'enable') || str_contains($name, 'auto') ||
            str_contains($envVar, 'ENABLE') || str_contains($envVar, 'AUTO') ||
            str_contains($description, 'enable') || str_contains($description, 'true/false')) {
            return '1';
        }
        
        // Minecraft specific
        if (str_contains($envVar, 'GAMEMODE')) {
            return 'survival';
        }
        if (str_contains($envVar, 'DIFFICULTY')) {
            return 'normal';
        }
        if (str_contains($envVar, 'SEED')) {
            return (string) rand(1000000, 9999999);
        }
        
        // Source engine specific
        if (str_contains($envVar, 'TICKRATE')) {
            return '64';
        }
        if (str_contains($envVar, 'FPS_MAX')) {
            return '300';
        }
        
        // Database connections
        if (str_contains($envVar, 'DB_') || str_contains($envVar, 'DATABASE')) {
            if (str_contains($envVar, 'HOST')) return 'localhost';
            if (str_contains($envVar, 'PORT')) return '3306';
            if (str_contains($envVar, 'USER')) return 'quicktest';
            if (str_contains($envVar, 'NAME')) return 'quicktest_db';
        }
        
        // Common URLs/endpoints
        if (str_contains($envVar, 'URL') || str_contains($envVar, 'ENDPOINT')) {
            return 'http://localhost';
        }
        
        // API Keys (generate random string)
        if (str_contains($envVar, 'API') && (str_contains($envVar, 'KEY') || str_contains($envVar, 'TOKEN'))) {
            return 'quicktest_' . bin2hex(random_bytes(16));
        }
        
        // Default fallback - check variable rules for hints
        if (!empty($variable->rules)) {
            $rules = strtolower($variable->rules);
            if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
                return '1';
            }
            if (str_contains($rules, 'boolean')) {
                return '1';
            }
        }
        
        // Final fallback
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