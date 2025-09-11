<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use PterodactylAddons\ShopSystem\Models\ShopCouponUsage;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;
use Pterodactyl\Services\Servers\ServerCreationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopOrderService
{
    public function __construct(
        private ShopOrderRepository $repository,
        private ServerCreationService $serverCreationService,
        private WalletService $walletService,
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

        return true;
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

            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($order->user)
                ->log('Order activated and server created');

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

            // Log activity
            activity()
                ->performedOn($order)
                ->causedBy($order->user)
                ->log('Order renewed');

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

            // Log activity
            activity()
                ->performedOn($order)
                ->log("Order suspended: {$reason}");

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

            // Log activity
            activity()
                ->performedOn($order)
                ->log('Order unsuspended');

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

            // Log activity
            activity()
                ->performedOn($order)
                ->log('Order terminated');

            return true;
        });
    }

    /**
     * Create a server for the order.
     */
    private function createServer(ShopOrder $order): Server
    {
        $plan = $order->plan;
        $config = $order->server_config;

        // Determine the best node for this plan
        $node = $this->selectNodeForPlan($plan);
        
        if (!$node) {
            throw new \RuntimeException('No available nodes for this plan.');
        }

        $serverData = [
            'name' => $this->generateServerName($order),
            'description' => "Server for {$plan->name} plan",
            'user_id' => $order->user_id,
            'egg_id' => $plan->egg_id,
            'node_id' => $node->id,
            'allocation_id' => $this->selectAllocation($node),
            'memory' => $config['memory'],
            'swap' => $config['swap'],
            'disk' => $config['disk'],
            'io' => $config['io'],
            'cpu' => $config['cpu'],
            'database_limit' => $config['databases'],
            'backup_limit' => $config['backups'],
            'allocation_limit' => $config['allocations'],
            'startup' => $plan->egg->startup ?? '',
            'environment' => [],
            'start_on_completion' => true,
        ];

        return $this->serverCreationService->handle($serverData);
    }

    /**
     * Select the best node for a plan.
     */
    private function selectNodeForPlan(ShopPlan $plan): ?\Pterodactyl\Models\Node
    {
        $query = \Pterodactyl\Models\Node::query()->where('public', true);

        // Filter by allowed nodes if specified
        if (!empty($plan->allowed_nodes)) {
            $query->whereIn('id', $plan->allowed_nodes);
        }

        // Filter by allowed locations if specified
        if (!empty($plan->allowed_locations)) {
            $query->whereIn('location_id', $plan->allowed_locations);
        }

        // Select node with most available memory (basic load balancing)
        return $query->orderByRaw('memory - memory_overallocate DESC')->first();
    }

    /**
     * Select an allocation for the server.
     */
    private function selectAllocation(\Pterodactyl\Models\Node $node): int
    {
        $allocation = \Pterodactyl\Models\Allocation::query()
            ->where('node_id', $node->id)
            ->whereNull('server_id')
            ->first();

        if (!$allocation) {
            throw new \RuntimeException('No available allocations on the selected node.');
        }

        return $allocation->id;
    }

    /**
     * Generate a server name for the order.
     */
    private function generateServerName(ShopOrder $order): string
    {
        $prefix = config('shop.server.name_prefix', 'srv');
        
        return sprintf(
            '%s-%s-%s',
            $prefix,
            $order->user->username,
            substr($order->uuid, 0, 8)
        );
    }
}
