<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use PterodactylAddons\ShopSystem\Models\ShopCouponUsage;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use PterodactylAddons\ShopSystem\Mail\PurchaseConfirmationMail;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Models\EggVariable;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Services\Allocations\AssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ShopOrderService
{
    public function __construct(
        private ShopOrderRepository $repository,
        private ServerCreationService $serverCreationService,
        private AssignmentService $assignmentService,
        private WalletService $walletService,
        private ShopConfigService $configService,
    ) {}

    /**
     * Create a new order for a user.
     */
    public function create(User $user, ShopPlan $plan, array $data): ShopOrder
    {
        $billing = $plan->getPriceForCycle($data['billing_cycle']);
        
        if (!$billing) {
            throw new \InvalidArgumentException('Invalid billing cycle selected.');
        }

        $orderData = [
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => ShopOrder::STATUS_PENDING,
            'billing_cycle' => $data['billing_cycle'],
            'amount' => $billing['price'],
            'setup_fee' => $billing['setup_fee'] ?? 0,
            'currency' => config('shop.currency', 'USD'),
            'server_config' => [
                'memory' => $plan->server_limits['memory'],
                'swap' => $plan->server_limits['swap'],
                'disk' => $plan->server_limits['disk'],
                'io' => $plan->server_limits['io'],
                'cpu' => $plan->server_limits['cpu'],
                'databases' => $plan->server_feature_limits['databases'] ?? null,
                'backups' => $plan->server_feature_limits['backups'] ?? null,
                'allocations' => $plan->server_feature_limits['allocations'] ?? null,
            ],
        ];

        // Calculate next due date if not one-time
        if ($data['billing_cycle'] !== ShopOrder::CYCLE_ONE_TIME) {
            $order = new ShopOrder($orderData);
            $orderData['next_due_at'] = $order->calculateNextDueDate();
        }

        $order = $this->repository->create($orderData);

        // Apply coupon if provided
        if (!empty($data['coupon_code'])) {
            $this->applyCoupon($order, $data['coupon_code']);
        }

        return $order->fresh();
    }

    /**
     * Create an order from cart data (used by checkout process).
     */
    public function createOrder(array $data): ShopOrder
    {
        $user = User::findOrFail($data['user_id']);
        $cartItems = $data['items'];
        $totals = $data['totals'];
        
        // For now, create a single order for the first item
        // TODO: Support multiple items or create separate orders for each
        $firstItem = $cartItems[0];
        $plan = ShopPlan::findOrFail($firstItem['plan_id']);
        
        // Get currency from shop config
        $shopConfig = app(\PterodactylAddons\ShopSystem\Services\ShopConfigService::class);
        $currency = $shopConfig->getShopConfig()['currency'] ?? 'USD';
        
        $orderData = [
            'uuid' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => ShopOrder::STATUS_PENDING,
            'billing_cycle' => $firstItem['billing_cycle'] ?? 'monthly',
            'amount' => $totals['total'],
            'setup_fee' => $totals['setup_total'] ?? 0,
            'currency' => $currency,
            'server_config' => [
                'memory' => $plan->memory,
                'swap' => $plan->swap,
                'disk' => $plan->disk,
                'io' => $plan->io,
                'cpu' => $plan->cpu,
                'databases' => $plan->server_feature_limits['databases'] ?? null,
                'allocations' => $plan->server_feature_limits['allocations'] ?? null,
                'backups' => $plan->server_feature_limits['backups'] ?? null,
            ],
            'expires_at' => now()->addMonth(), // Default 1 month
        ];

        // Add billing details if provided
        if (!empty($data['billing_details'])) {
            $orderData['billing_details'] = $data['billing_details'];
        }

        // Add payment method if provided
        if (!empty($data['payment_method'])) {
            $orderData['payment_method'] = $data['payment_method'];
        }

        $order = $this->repository->create($orderData);

        // Apply coupon if provided
        if (!empty($data['coupon'])) {
            $this->applyCoupon($order, $data['coupon']->code);
        }

        return $order->fresh();
    }

    /**
     * Mark an order as paid.
     */
    public function markAsPaid(int $orderId, string $paymentMethod = null): bool
    {
        $order = ShopOrder::findOrFail($orderId);
        
        // Calculate next due date based on billing cycle
        $nextDueAt = $order->calculateNextDueDate(now());
        
        $order->update([
            'status' => ShopOrder::STATUS_ACTIVE,
            'last_renewed_at' => now(),
            'next_due_at' => $nextDueAt,
        ]);

        // Update the corresponding payment record to completed
        $payment = $order->payments()->where('type', 'order_payment')->first();
        if ($payment) {
            $payment->update([
                'status' => ShopPayment::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);
        }

        // Create server if auto setup is enabled
        if ($this->configService->isAutoSetupEnabled()) {
            try {
                $this->handleServerCreation($order);
            } catch (\Exception $e) {
                // Log error but don't fail the payment
                \Log::error('Failed to create server for order ' . $order->id . ': ' . $e->getMessage());
                
                // Optionally, you could set order status to 'processing' to indicate manual intervention needed
                // $order->update(['status' => ShopOrder::STATUS_PROCESSING]);
            }
        }
        
        // Always send purchase confirmation email after successful payment
        $this->sendPurchaseConfirmationEmail($order);

        return true;
    }

    /**
     * Handle server creation with variable checking
     */
    protected function handleServerCreation(ShopOrder $order): void
    {
        // Load plan and egg
        $order->load(['plan', 'plan.egg']);
        
        if (!$order->plan || !$order->plan->egg) {
            \Log::info("Skipping server creation for order {$order->id}: No egg configured");
            return;
        }

        // Check if egg needs user input for variables
        $requiredVariables = $this->getRequiredVariablesForEgg($order->plan->egg_id);
        
        if (empty($requiredVariables)) {
            // No user input needed, create server immediately
            \Log::info("Auto-creating server for order {$order->id}: No user variables required");
            $this->createServerForOrder($order);
        } else {
            // User input required, mark order as needing variable input
            \Log::info("Order {$order->id} requires user input for variables", [
                'required_variables' => array_column($requiredVariables, 'env_variable')
            ]);
            
            $order->update([
                'status' => ShopOrder::STATUS_PROCESSING,
                'server_config' => json_encode([
                    'auto_create' => false,
                    'requires_variables' => true,
                    'required_variables' => $requiredVariables
                ])
            ]);
        }
    }

    /**
     * Get required variables for an egg that need user input
     */
    protected function getRequiredVariablesForEgg(int $eggId): array
    {
        $variables = EggVariable::where('egg_id', $eggId)
            ->where('user_viewable', true)
            ->where(function($query) {
                $query->whereNull('default_value')
                      ->orWhere('default_value', '');
            })
            ->get();

        $requiredVariables = [];
        
        foreach ($variables as $variable) {
            // Only include variables that are truly required
            if ($this->isVariableTrulyRequired($variable)) {
                $requiredVariables[] = [
                    'name' => $variable->name,
                    'env_variable' => $variable->env_variable,
                    'description' => $variable->description,
                    'rules' => $variable->rules,
                    'type' => $this->determineVariableType($variable),
                    'user_friendly_name' => $this->getUserFriendlyName($variable),
                    'help_text' => $this->getHelpText($variable),
                ];
            }
        }

        return $requiredVariables;
    }

    /**
     * Determine if a variable is truly required for server functionality
     */
    protected function isVariableTrulyRequired(EggVariable $variable): bool
    {
        $rules = strtolower($variable->rules ?? '');
        $envVar = strtolower($variable->env_variable);
        $name = strtolower($variable->name);

        // Variables that are explicitly marked as required in rules
        if (str_contains($rules, 'required')) {
            return true;
        }

        // Critical authentication variables
        $criticalVariables = [
            'rcon_pass', 'rcon_password', 'admin_password', 'server_password',
            'steam_acc', 'steam_user', 'steam_pass', 'api_key', 'token',
            'srcds_appid', 'srcds_game', 'app_id', 'game_id'
        ];

        foreach ($criticalVariables as $critical) {
            if (str_contains($envVar, $critical) || str_contains($name, $critical)) {
                return true;
            }
        }

        // Variables that typically break server functionality if missing
        $functionalVariables = [
            'jarfile', 'server_jar', 'executable', 'binary', 'mod_version',
            'game_version', 'build_number'
        ];

        foreach ($functionalVariables as $functional) {
            if (str_contains($envVar, $functional) || str_contains($name, $functional)) {
                return true;
            }
        }

        // All other variables are considered optional
        return false;
    }

    /**
     * Determine the type of variable for UI purposes
     */
    protected function determineVariableType(EggVariable $variable): string
    {
        $envVar = $variable->env_variable;
        $name = strtolower($variable->name);
        $rules = $variable->rules;

        if (str_contains($envVar, 'STEAM_ACC') || str_contains($variable->name, 'Steam Account')) {
            return 'steam_token';
        }
        
        if (str_contains($envVar, 'PASSWORD') || str_contains($envVar, 'PASS')) {
            return 'password';
        }
        
        if (str_contains($envVar, 'TOKEN') || str_contains($name, 'token')) {
            return 'token';
        }
        
        if (str_contains($rules, 'url')) {
            return 'url';
        }
        
        if (str_contains($rules, 'boolean')) {
            return 'boolean';
        }
        
        if (str_contains($rules, 'numeric') || str_contains($rules, 'integer')) {
            return 'number';
        }

        return 'text';
    }

    /**
     * Get user friendly name for variable
     */
    protected function getUserFriendlyName(EggVariable $variable): string
    {
        $name = $variable->name;
        
        // Convert common patterns to user-friendly names
        $friendlyNames = [
            'STEAM_ACC' => 'Steam Account Token',
            'RCON_PASS' => 'RCON Password',
            'SRCDS_APPID' => 'Steam App ID',
            'SRCDS_MAP' => 'Starting Map',
            'WORKSHOP_ID' => 'Workshop Collection ID',
        ];

        return $friendlyNames[$variable->env_variable] ?? $name;
    }

    /**
     * Get help text for variable
     */
    protected function getHelpText(EggVariable $variable): string
    {
        $helpTexts = [
            'STEAM_ACC' => 'Your Steam account token for downloading game files. Get this from your Steam account settings.',
            'RCON_PASS' => 'Password for remote console access to your server.',
            'SRCDS_APPID' => 'The Steam application ID for your game.',
            'SRCDS_MAP' => 'The map your server will start with.',
            'WORKSHOP_ID' => 'Steam Workshop collection ID for mods/addons.',
        ];

        return $helpTexts[$variable->env_variable] ?? $variable->description;
    }

    /**
     * Create server with user-provided variables
     */
    public function createServerWithVariables(ShopOrder $order, array $userVariables): ?Server
    {
        \Log::info("Creating server with user variables for order {$order->id}", [
            'variables' => array_keys($userVariables)
        ]);

        // Check if plan has an egg configured
        $order->load(['plan', 'plan.egg']);
        
        if (!$order->plan || !$order->plan->egg) {
            \Log::error("Cannot create server for order {$order->id}: No egg configured");
            return null;
        }

        try {
            // Create the server with user variables
            $server = $this->createServerWithCustomVariables($order, $userVariables);
            
            if (!$server) {
                \Log::error("createServerWithCustomVariables returned null for order {$order->id}");
                return null;
            }
            
            // Update order with server details and mark as active
            $order->update([
                'server_id' => $server->id,
                'status' => ShopOrder::STATUS_ACTIVE,
            ]);

            \Log::info("Server created successfully with user variables for order {$order->id}", [
                'server_id' => $server->id
            ]);
            
            return $server;

        } catch (\Exception $e) {
            \Log::error("Server creation with variables failed for order {$order->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create server with custom user variables
     */
    protected function createServerWithCustomVariables(ShopOrder $order, array $userVariables): ?Server
    {
        \Log::info("🚀 Creating server with custom variables for order {$order->id}");
        
        // Load dependencies
        $order->load(['user', 'plan', 'plan.egg']);
        $plan = $order->plan;
        $egg = $plan->egg;
        $user = $order->user;

        \Log::info("📦 Dependencies loaded for custom variables", [
            'plan_name' => $plan->name,
            'egg_name' => $egg->name,
            'user_email' => $user->email,
            'user_variables_count' => count($userVariables)
        ]);

        // Get a simple node and allocation
        $nodeId = 1; // Use the first available node
        $node = \Pterodactyl\Models\Node::find($nodeId);
        if (!$node) {
            throw new \RuntimeException('No available nodes for server creation');
        }

        // Smart allocation selection - find or create allocation
        $allocation = $this->findOrCreateAllocation($node);
        
        \Log::info("🎯 Allocation selected", [
            'allocation_id' => $allocation->id,
            'ip' => $allocation->ip,
            'port' => $allocation->port
        ]);

        // Merge user variables with default egg variables
        $environment = $this->mergeEnvironmentVariables($egg, $userVariables);

        \Log::info("🔧 Environment variables prepared", [
            'total_variables' => count($environment),
            'user_provided' => array_keys($userVariables),
            'environment' => $environment
        ]);

        // Simple server configuration with user variables
        $serverData = [
            'name' => 'srv-' . strtolower($user->username) . '-' . Str::random(8) . '-' . time(),
            'description' => "Server for {$plan->name} plan with custom configuration",
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'nest_id' => $egg->nest_id,
            'node_id' => $nodeId,
            'allocation_id' => $allocation->id,
            'memory' => $plan->memory,
            'swap' => $plan->swap,
            'disk' => $plan->disk,
            'io' => $plan->io,
            'cpu' => $plan->cpu,
            'threads' => null,
            'oom_disabled' => false,
            'allocation_additional' => [],
            'database_limit' => $plan->databases,
            'backup_limit' => $plan->backups,
            'allocation_limit' => $plan->allocations,
            'startup' => $egg->startup,
            'image' => $this->getDefaultDockerImage($egg),
            'environment' => $environment,
            'skip_scripts' => false,
            'start_on_completion' => false,
        ];

        \Log::info("⚙️ Server configuration with custom variables", [
            'name' => $serverData['name'],
            'node_id' => $serverData['node_id'],
            'allocation_id' => $serverData['allocation_id'],
            'image' => $serverData['image'],
            'environment_count' => count($serverData['environment'])
        ]);

        // Create the server
        $server = $this->serverCreationService->handle($serverData);

        \Log::info("✅ Server created successfully with custom variables", [
            'server_id' => $server->id,
            'server_uuid' => $server->uuid
        ]);

        return $server;
    }

    /**
     * Merge user variables with default egg variables
     */
    protected function mergeEnvironmentVariables($egg, array $userVariables): array
    {
        $environment = [];
        
        // Start with all egg variables
        foreach ($egg->variables as $variable) {
            if (isset($userVariables[$variable->env_variable])) {
                // Use user-provided value
                $environment[$variable->env_variable] = $userVariables[$variable->env_variable];
            } else {
                // Use default value
                $environment[$variable->env_variable] = $variable->default_value ?? '';
            }
        }
        
        return $environment;
    }

    /**
     * Apply a coupon to an order.
     */
    public function applyCoupon(ShopOrder $order, string $couponCode): float
    {
        $coupon = ShopCoupon::where('code', $couponCode)->first();
        
        if (!$coupon) {
            throw new \InvalidArgumentException('Invalid coupon code.');
        }

        $isFirstOrder = $this->repository->isFirstOrderForUser($order->user_id);
        
        if (!$coupon->canBeUsed($order->total_amount, $order->user_id, $order->plan_id, $isFirstOrder)) {
            throw new \InvalidArgumentException('Coupon cannot be used for this order.');
        }

        $discountAmount = $coupon->calculateDiscount($order->amount);
        
        // Create coupon usage record
        ShopCouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
        ]);

        // Update order amount
        $order->update(['amount' => $order->amount - $discountAmount]);
        
        // Increment coupon usage
        $coupon->use();

        return $discountAmount;
    }

    /**
     * Create a server for an order (called after payment).
     */
    public function createServerForOrder(ShopOrder $order): ?Server
    {
        // Check if server already exists
        if ($order->server_id) {
            return Server::findOrFail($order->server_id);
        }

        // Check if plan has an egg configured
        if (!$order->plan->egg_id) {
            \Log::info("Server creation skipped for order {$order->id}: Plan does not have an egg configured");
            return null;
        }

        try {
            \Log::info("Starting server creation for order {$order->id}");
            
            // Create the server WITHOUT transaction to avoid Wings API timing issues
            $server = $this->createServer($order);
            \Log::info("Server created with ID: " . ($server ? $server->id : 'null'));
            
            if (!$server) {
                \Log::error("createServer returned null for order {$order->id}");
                throw new \RuntimeException("Server creation returned null");
            }
            
            // Update order with server details in a separate transaction
            DB::transaction(function () use ($order, $server) {
                $order->update([
                    'server_id' => $server->id,
                ]);
            });

            \Log::info("Server creation completed successfully for order {$order->id}");
            
            return $server;
        } catch (\Exception $e) {
            // Log the error but don't fail the payment
            \Log::error("Server creation failed for order {$order->id}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            return null;
        }
    }

    /**
     * Process payment and activate order.
     */
    public function activate(ShopOrder $order): Server
    {
        return DB::transaction(function () use ($order) {
            // Create the server
            $server = $this->createServer($order);
            
            // Update order with server details
            $order->update([
                'server_id' => $server->id,
                'status' => ShopOrder::STATUS_ACTIVE,
                'last_renewed_at' => now(),
            ]);

            return $server;
        });
    }

    /**
     * Renew an order (process renewal payment).
     */
    public function renew(ShopOrder $order, string $paymentMethod = 'wallet'): bool
    {
        if ($order->billing_cycle === ShopOrder::CYCLE_ONE_TIME) {
            throw new \InvalidArgumentException('One-time orders cannot be renewed.');
        }

        return DB::transaction(function () use ($order, $paymentMethod) {
            // Process payment
            if ($paymentMethod === 'wallet') {
                $wallet = $this->walletService->getOrCreateWallet($order->user);
                
                if (!$wallet->hasSufficientFunds($order->amount)) {
                    return false;
                }

                $this->walletService->deductFunds(
                    $wallet,
                    $order->amount,
                    "Renewal payment for order #{$order->id}"
                );
            }

            // Update order renewal details
            $order->update([
                'last_renewed_at' => now(),
                'next_due_at' => $order->calculateNextDueDate(),
            ]);

            return true;
        });
    }

    /**
     * Suspend an order.
     */
    public function suspend(ShopOrder $order, string $reason = 'Non-payment'): bool
    {
        if (!$order->isActive()) {
            return false;
        }

        return DB::transaction(function () use ($order, $reason) {
            // Suspend the server if it exists
            if ($order->server) {
                $order->server->update(['status' => 'suspended']);
            }

            // Update order status
            $order->update([
                'status' => ShopOrder::STATUS_SUSPENDED,
                'suspended_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Unsuspend an order.
     */
    public function unsuspend(ShopOrder $order): bool
    {
        if (!$order->isSuspended()) {
            return false;
        }

        return DB::transaction(function () use ($order) {
            // Unsuspend the server if it exists
            if ($order->server) {
                $order->server->update(['status' => null]);
            }

            // Update order status
            $order->update([
                'status' => ShopOrder::STATUS_ACTIVE,
                'suspended_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Terminate an order.
     */
    public function terminate(ShopOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            // Delete the server if it exists
            if ($order->server) {
                // This will trigger Pterodactyl's server deletion process
                $order->server->delete();
            }

            // Update order status
            $order->update([
                'status' => ShopOrder::STATUS_TERMINATED,
                'terminated_at' => now(),
            ]);

            return true;
        });
    }

    /**
     * Create a server for the order - simplified robust approach.
     */
    private function createServer(ShopOrder $order): Server
    {
        \Log::info("🚀 Starting simplified robust server creation for order {$order->id}");
        
        $plan = $order->plan;
        $user = $order->user;
        $egg = $plan->egg;

        if (!$plan || !$user || !$egg) {
            throw new \RuntimeException('Order dependencies not found (plan/user/egg)');
        }

        \Log::info("📦 Dependencies loaded", [
            'plan_name' => $plan->name,
            'egg_name' => $egg->name,
            'user_email' => $user->email
        ]);

        // Simple server data - minimal but robust like ServerProvisioningService
        $serverData = [
            'name' => $this->generateServerName($order),
            'owner_id' => $user->id,
            'egg_id' => $egg->id,
            'memory' => (int) $plan->memory,
            'swap' => (int) ($plan->swap ?? 0),
            'disk' => (int) $plan->disk,
            'io' => (int) ($plan->io ?? 500),
            'cpu' => (int) $plan->cpu,
            'allocation_limit' => (int) ($plan->allocations ?? 1),
            'database_limit' => (int) ($plan->databases ?? 0),
            'backup_limit' => (int) ($plan->backups ?? 0),
            'startup' => $egg->startup ?? '',
            'image' => $this->getSimpleDockerImage($egg),
            'environment' => $this->getSimpleEnvironmentVariables($egg),
        ];

        // Simple node/allocation selection with auto-creation
        $node = $this->selectSimpleNode($plan);
        if (!$node) {
            throw new \RuntimeException('No available nodes for this plan');
        }
        
        $allocation = $this->selectSimpleAllocation($node);

        $serverData['node_id'] = $node->id;
        $serverData['allocation_id'] = $allocation->id;

        \Log::info("⚙️ Simple server configuration", [
            'name' => $serverData['name'],
            'node_id' => $serverData['node_id'],
            'allocation_id' => $serverData['allocation_id'],
            'image' => $serverData['image']
        ]);

        // Create server using minimal data
        $server = $this->serverCreationService->handle($serverData);
        
        \Log::info("✅ Simplified server created successfully", [
            'server_id' => $server->id,
            'server_uuid' => $server->uuid
        ]);
        
        return $server;
    }

    /**
     * Simple node selection.
     */
    private function selectSimpleNode(ShopPlan $plan): ?\Pterodactyl\Models\Node
    {
        return \Pterodactyl\Models\Node::where('public', true)
            ->where('maintenance_mode', false)
            ->first();
    }

    /**
     * Smart allocation selection with auto-creation.
     */
    private function selectSimpleAllocation(\Pterodactyl\Models\Node $node): \Pterodactyl\Models\Allocation
    {
        return $this->findOrCreateAllocation($node);
    }

    /**
     * Get simple docker image (prefer Java 17).
     */
    private function getSimpleDockerImage($egg): string
    {
        $dockerImages = $egg->docker_images ?? [];
        
        if (is_array($dockerImages)) {
            // Prefer Java 17 if available
            foreach ($dockerImages as $image) {
                if (str_contains($image, 'java_17')) {
                    return $image;
                }
            }
            // Return first available image
            return !empty($dockerImages) ? reset($dockerImages) : 'ghcr.io/pterodactyl/yolks:java_17';
        }
        
        return 'ghcr.io/pterodactyl/yolks:java_17';
    }

    /**
     * Get simple environment variables with basic defaults.
     */
    private function getSimpleEnvironmentVariables($egg): array
    {
        $variables = [];
        
        if ($egg && $egg->variables) {
            foreach ($egg->variables as $variable) {
                $value = $variable->default_value;
                
                // Provide simple defaults for missing values
                if (empty($value) && $variable->user_editable) {
                    if (str_contains($variable->env_variable, 'RCON')) {
                        $value = \Illuminate\Support\Str::random(8);
                    } elseif (str_contains($variable->env_variable, 'SEED')) {
                        $value = (string) rand(100000, 999999);
                    } else {
                        $value = '';
                    }
                }
                
                $variables[$variable->env_variable] = $value ?? '';
            }
        }
        
        return $variables;
    }

    /**
     * Generate a server name for the order.
     */
    private function generateServerName(ShopOrder $order): string
    {
        $prefix = config('shop.server.name_prefix', 'srv');
        
        // Use timestamp to ensure uniqueness and avoid conflicts
        $timestamp = time();
        
        return sprintf(
            '%s-%s-%s-%d',
            $prefix,
            $order->user->username,
            substr($order->uuid, 0, 8),
            $timestamp
        );
    }

    /**
     * Get the default Docker image for an egg.
     */
    protected function getDefaultDockerImage($egg): string
    {
        \Log::info("Getting docker image for egg {$egg->id}", [
            'docker_image' => $egg->docker_image,
            'docker_images' => $egg->docker_images
        ]);

        // If egg has specific docker images defined, prefer Java 17
        if (is_array($egg->docker_images) && !empty($egg->docker_images)) {
            // Try to find Java 17 first (most stable)
            foreach ($egg->docker_images as $tag => $image) {
                if (str_contains(strtolower($tag), 'java 17')) {
                    \Log::info("Using preferred Java 17 image: {$image}");
                    return $image;
                }
            }
            
            // Fall back to first available image
            $defaultImage = array_values($egg->docker_images)[0];
            \Log::info("Using first available image: {$defaultImage}");
            return $defaultImage;
        }

        // Use the egg's default docker_image if available
        if (!empty($egg->docker_image)) {
            \Log::info("Using egg default image: {$egg->docker_image}");
            return $egg->docker_image;
        }

        // Ultimate fallback - use a generic image
        $fallbackImage = 'ghcr.io/pterodactyl/yolks:java_17';
        \Log::info("Using fallback image: {$fallbackImage}");
        return $fallbackImage;
    }

    /**
     * Smart allocation management - find available allocation or create new ones.
     */
    private function findOrCreateAllocation(\Pterodactyl\Models\Node $node): \Pterodactyl\Models\Allocation
    {
        // First, try to find an available allocation
        $allocation = \Pterodactyl\Models\Allocation::where('node_id', $node->id)
            ->whereNull('server_id')
            ->first();

        if ($allocation) {
            \Log::info("🎯 Found existing available allocation", [
                'allocation_id' => $allocation->id,
                'ip' => $allocation->ip,
                'port' => $allocation->port
            ]);
            return $allocation;
        }

        // No available allocation found, create new ones
        \Log::info("⚠️ No available allocations found, creating new ones");
        
        try {
            $this->createAllocationsForNode($node);
            
            // Try to find an allocation again after creation
            $allocation = \Pterodactyl\Models\Allocation::where('node_id', $node->id)
                ->whereNull('server_id')
                ->first();
                
            if (!$allocation) {
                throw new \RuntimeException('Failed to create allocations for node');
            }
            
            \Log::info("✅ Created and selected new allocation", [
                'allocation_id' => $allocation->id,
                'ip' => $allocation->ip,
                'port' => $allocation->port
            ]);
            
            return $allocation;
            
        } catch (\Exception $e) {
            \Log::error("❌ Failed to create allocations for node", [
                'node_id' => $node->id,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Failed to create allocations: " . $e->getMessage());
        }
    }

    /**
     * Create allocations for a node using a standard port range.
     */
    private function createAllocationsForNode(\Pterodactyl\Models\Node $node): void
    {
        // Use the node's primary IP or fall back to a standard IP
        $nodeIp = $node->fqdn ?? $node->scheme . '://' . $node->fqdn;
        
        // Extract just the IP/hostname without protocol
        $ip = parse_url($nodeIp, PHP_URL_HOST) ?? $node->fqdn;
        if (!$ip) {
            $ip = '0.0.0.0'; // Fallback to bind all interfaces
        }

        // Create allocations with a standard port range for game servers
        $allocationData = [
            'allocation_ip' => $ip,
            'allocation_ports' => '25565-25575', // Standard Minecraft-like port range
        ];

        \Log::info("🔨 Creating allocations for node", [
            'node_id' => $node->id,
            'ip' => $ip,
            'port_range' => $allocationData['allocation_ports']
        ]);

        $this->assignmentService->handle($node, $allocationData);
        
        \Log::info("✅ Successfully created allocations for node", [
            'node_id' => $node->id,
            'ip' => $ip
        ]);
    }

    /**
     * Send purchase confirmation email after successful server creation.
     */
    protected function sendPurchaseConfirmationEmail(ShopOrder $order): void
    {
        try {
            // Check if email notifications are enabled
            if (!config('shop.notifications.email.enabled', true)) {
                \Log::info("Email notifications disabled, skipping purchase confirmation for order {$order->id}");
                return;
            }

            // Load relationships needed for email
            $order->load(['user', 'plan', 'plan.category', 'server', 'payments']);

            // Get the user's email (preference order: order email, user email)
            $recipientEmail = $order->email ?? $order->user->email;
            
            if (!$recipientEmail) {
                \Log::warning("No email address found for order {$order->id}, skipping purchase confirmation");
                return;
            }

            \Log::info("Sending purchase confirmation email for order {$order->id} to {$recipientEmail}");

            // Send the email
            Mail::to($recipientEmail)->send(new PurchaseConfirmationMail($order));

            \Log::info("✅ Purchase confirmation email sent successfully for order {$order->id}");

        } catch (\Exception $e) {
            // Log error but don't fail the order process
            \Log::error("Failed to send purchase confirmation email for order {$order->id}: " . $e->getMessage());
            \Log::error("Email error stack trace: " . $e->getTraceAsString());
        }
    }
}
