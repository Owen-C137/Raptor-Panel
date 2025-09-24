<?php

namespace PterodactylAddons\ShopSystem\Jobs;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TerminateOverdueOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * Execute the job.
     */
    public function handle(
        ShopOrderRepository $orderRepository,
        ShopOrderService $orderService
    ): void {
        $terminationDays = config('shop.billing.termination_after_days', 7);
        $gracePeriodHours = config('shop.billing.grace_period_hours', 24);
        
        Log::info('Starting overdue orders termination job', [
            'termination_after_days' => $terminationDays,
        ]);

        // Calculate the cutoff date for termination
        $terminationCutoff = now()->subDays($terminationDays)->subHours($gracePeriodHours);

        // Get suspended orders that are old enough for termination
        $ordersToTerminate = $orderRepository->getBuilder()
            ->where('status', ShopOrder::STATUS_SUSPENDED)
            ->where('suspended_at', '<=', $terminationCutoff)
            ->with(['user', 'server'])
            ->get();

        $terminated = 0;
        $failed = 0;

        foreach ($ordersToTerminate as $order) {
            try {
                if ($this->shouldTerminateOrder($order)) {
                    // Send final warning before termination
                    SendRenewalNotificationJob::dispatch($order, 'final_warning');
                    
                    // Wait a bit then terminate
                    sleep(5);
                    
                    $orderService->terminate($order);
                    
                    // Send termination notification
                    SendRenewalNotificationJob::dispatch($order, 'order_terminated');
                    
                    $terminated++;
                    
                    Log::warning('Order terminated for overdue payment', [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id,
                        'suspended_date' => $order->suspended_at->toDateTimeString(),
                        'days_suspended' => $order->suspended_at->diffInDays(now()),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to terminate overdue order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Overdue orders termination completed', [
            'eligible_for_termination' => $ordersToTerminate->count(),
            'terminated' => $terminated,
            'failed' => $failed,
        ]);
    }

    /**
     * Check if order should be terminated.
     */
    private function shouldTerminateOrder(ShopOrder $order): bool
    {
        // Only terminate suspended orders
        if (!$order->isSuspended()) {
            return false;
        }

        // Don't terminate one-time orders (they should be cancelled instead)
        if ($order->billing_cycle === ShopOrder::CYCLE_ONE_TIME) {
            return false;
        }

        // Ensure order has been suspended for the minimum period
        $terminationDays = config('shop.billing.termination_after_days', 7);
        
        if (!$order->suspended_at || $order->suspended_at->diffInDays(now()) < $terminationDays) {
            return false;
        }

        return true;
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Terminate overdue orders job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Critical failure - notify administrators immediately
        // TODO: Send critical alert to admins
    }
}
