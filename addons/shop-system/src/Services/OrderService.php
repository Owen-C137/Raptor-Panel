<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Services\Servers\ServerCreationService;
use Carbon\Carbon;

class OrderService
{
    protected ServerCreationService $serverCreationService;

    public function __construct(ServerCreationService $serverCreationService)
    {
        $this->serverCreationService = $serverCreationService;
    }

    /**
     * Activate an order and provision resources
     */
    public function activateOrder(ShopOrder $order): void
    {
        if ($order->status === 'active') {
            return;
        }

        $order->update(['status' => 'processing']);

        try {
            // Create server if it's a server product
            if ($order->product->type === 'server' && !$order->server_id) {
                $server = $this->createServerForOrder($order);
                $order->update(['server_id' => $server->id]);
            }

            // Set next billing date if recurring
            if ($order->product->billing_cycle !== 'one_time') {
                $order->update([
                    'next_billing_date' => $this->calculateNextBillingDate($order)
                ]);
            }

            $order->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

        } catch (\Exception $e) {
            $order->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(ShopOrder $order, string $status): void
    {
        $oldStatus = $order->status;
        $order->update(['status' => $status]);

        // Handle status-specific actions
        match($status) {
            'suspended' => $this->handleSuspension($order),
            'active' => $this->handleActivation($order),
            'cancelled' => $this->handleCancellation($order),
            default => null
        };

        // Log status change
        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $status,
            ])
            ->log('order_status_changed');
    }

    /**
     * Process refund for order
     */
    public function processRefund(ShopOrder $order, float $amount, string $reason): void
    {
        // Validate refund amount
        if ($amount > $order->total_amount) {
            throw new \Exception('Refund amount cannot exceed order total');
        }

        // Add to user wallet
        $wallet = \PterodactylAddons\ShopSystem\Models\UserWallet::firstOrCreate(
            ['user_id' => $order->user_id],
            ['balance' => 0]
        );

        $wallet->increment('balance', $amount);

        // Create wallet transaction
        \Pterodactyl\Models\WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'refund',
            'amount' => $amount,
            'description' => $reason,
            'reference_type' => 'order_refund',
            'reference_id' => $order->id,
        ]);

        // Update order status if fully refunded
        if ($amount >= $order->total_amount) {
            $order->update(['status' => 'refunded']);
        }
    }

    /**
     * Suspend order and associated resources
     */
    public function suspendOrder(ShopOrder $order): void
    {
        if ($order->server) {
            $order->server->update(['suspended' => true]);
        }

        $order->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);
    }

    /**
     * Create server for order
     */
    protected function createServerForOrder(ShopOrder $order): Server
    {
        $product = $order->product;
        $user = $order->user;

        // Select appropriate node and location
        $node = $this->selectNodeForOrder($order);
        $allocation = $this->selectAllocationForOrder($node);

        // Prepare server data
        $serverData = [
            'name' => $product->name . ' - ' . $user->username,
            'description' => "Server created from shop order #{$order->id}",
            'memory' => $product->memory,
            'disk' => $product->disk,
            'cpu' => $product->cpu,
            'swap' => $product->swap ?? 0,
            'io' => $product->io ?? 500,
            'threads' => null,
            'oom_disabled' => false,
            'allocation_id' => $allocation->id,
            'node_id' => $node->id,
            'egg_id' => $product->egg_id,
            'environment' => [],
            'startup' => '',
            'image' => '',
            'databases' => $product->databases ?? 0,
            'allocations' => $product->allocations ?? 1,
            'backups' => $product->backups ?? 0,
        ];

        return $this->serverCreationService->create($serverData, $user);
    }

    /**
     * Select appropriate node for order
     */
    protected function selectNodeForOrder(ShopOrder $order): \Pterodactyl\Models\Node
    {
        $product = $order->product;
        
        // Use allowed nodes if specified
        if (!empty($product->allowed_nodes)) {
            $nodeId = collect($product->allowed_nodes)->random();
            $node = \Pterodactyl\Models\Node::find($nodeId);
            if ($node) return $node;
        }

        // Use allowed locations if specified
        if (!empty($product->allowed_locations)) {
            $locationId = collect($product->allowed_locations)->random();
            $nodes = \Pterodactyl\Models\Node::where('location_id', $locationId)->get();
            if ($nodes->isNotEmpty()) {
                return $nodes->random();
            }
        }

        // Fall back to any available node
        return \Pterodactyl\Models\Node::first();
    }

    /**
     * Select allocation for order
     */
    protected function selectAllocationForOrder(\Pterodactyl\Models\Node $node): \Pterodactyl\Models\Allocation
    {
        $allocation = \Pterodactyl\Models\Allocation::where('node_id', $node->id)
            ->whereNull('server_id')
            ->first();

        if (!$allocation) {
            throw new \Exception("No available allocations on node {$node->name}");
        }

        return $allocation;
    }

    /**
     * Calculate next billing date
     */
    protected function calculateNextBillingDate(ShopOrder $order): Carbon
    {
        return match($order->product->billing_cycle) {
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'yearly' => now()->addYear(),
            default => now()->addMonth()
        };
    }

    /**
     * Handle order suspension
     */
    protected function handleSuspension(ShopOrder $order): void
    {
        if ($order->server) {
            $order->server->update(['suspended' => true]);
        }
    }

    /**
     * Handle order activation
     */
    protected function handleActivation(ShopOrder $order): void
    {
        if ($order->server) {
            $order->server->update(['suspended' => false]);
        }
    }

    /**
     * Handle order cancellation
     */
    protected function handleCancellation(ShopOrder $order): void
    {
        if ($order->server) {
            // Optionally delete server or just suspend it
            $order->server->update(['suspended' => true]);
        }
    }
}
