<?php

namespace PterodactylAddons\ShopSystem\Commands;

use Illuminate\Console\Command;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Pterodactyl\Services\Servers\ServerDeletionService;
use Illuminate\Support\Facades\Log;

class CleanupCancelledServersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:cleanup-cancelled-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete servers with cancelled orders that have passed their auto-deletion date';

    /**
     * Execute the console command.
     */
    public function handle(ServerDeletionService $deletionService): int
    {
        $this->info('Starting cleanup of cancelled servers...');

        // Find orders with cancelled status that have passed their auto-deletion date
        $ordersToDelete = ShopOrder::query()
            ->where('status', ShopOrder::STATUS_CANCELLED)
            ->whereNotNull('auto_delete_at')
            ->where('auto_delete_at', '<=', now())
            ->whereNotNull('server_id')
            ->with('server', 'user')
            ->get();

        if ($ordersToDelete->isEmpty()) {
            $this->info('No cancelled servers found that need cleanup.');
            return self::SUCCESS;
        }

        $this->info("Found {$ordersToDelete->count()} servers to delete.");

        $deletedCount = 0;
        $errorCount = 0;

        foreach ($ordersToDelete as $order) {
            try {
                if (!$order->server) {
                    $this->warn("Order {$order->id} has no server - skipping");
                    continue;
                }

                $serverName = $order->server->name ?? $order->server->uuidShort;
                $this->line("Deleting server: {$serverName} (UUID: {$order->server->uuidShort})");

                // Delete the server using Pterodactyl's service
                $deletionService->handle($order->server);

                // Update order status to terminated
                $order->update([
                    'status' => ShopOrder::STATUS_TERMINATED,
                    'terminated_at' => now(),
                    'server_id' => null,
                ]);

                $this->info("✓ Successfully deleted server: {$serverName}");
                
                // Log the deletion
                Log::info('Auto-deleted cancelled server', [
                    'order_id' => $order->id,
                    'server_uuid' => $order->server->uuid,
                    'user_id' => $order->user_id,
                    'auto_delete_at' => $order->auto_delete_at,
                ]);

                $deletedCount++;

            } catch (\Exception $e) {
                $this->error("Failed to delete server {$order->server->uuidShort}: " . $e->getMessage());
                
                Log::error('Failed to auto-delete cancelled server', [
                    'order_id' => $order->id,
                    'server_uuid' => $order->server->uuid ?? 'unknown',
                    'user_id' => $order->user_id,
                    'error' => $e->getMessage(),
                ]);

                $errorCount++;
            }
        }

        $this->info("\nCleanup completed:");
        $this->line("- Deleted: {$deletedCount} servers");
        if ($errorCount > 0) {
            $this->line("- Errors: {$errorCount} servers");
        }

        return self::SUCCESS;
    }
}