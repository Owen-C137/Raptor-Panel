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

class SuspendOverdueOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(
        ShopOrderRepository $orderRepository,
        ShopOrderService $orderService
    ): void {
        $gracePeriodHours = config('shop.billing.grace_period_hours', 24);
        
        Log::info('Starting overdue orders suspension job', [
            'grace_period_hours' => $gracePeriodHours,
        ]);

        // Get orders that are overdue beyond grace period
        $overdueOrders = $orderRepository->getOverdue($gracePeriodHours);

        $suspended = 0;
        $failed = 0;

        foreach ($overdueOrders as $order) {
            try {
                if ($this->shouldSuspendOrder($order)) {
                    $orderService->suspend($order, 'Overdue payment - automatic suspension');
                    
                    // Send suspension notification
                    SendRenewalNotificationJob::dispatch($order, 'order_suspended');
                    
                    // Schedule termination if configured
                    $this->scheduleTerminationIfNeeded($order);
                    
                    $suspended++;
                    
                    Log::info('Order suspended for overdue payment', [
                        'order_id' => $order->id,
                        'days_overdue' => $order->next_due_at->diffInDays(now()),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to suspend overdue order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Overdue orders suspension completed', [
            'total_overdue' => $overdueOrders->count(),
            'suspended' => $suspended,
            'failed' => $failed,
        ]);
    }

    /**
     * Check if order should be suspended.
     */
    private function shouldSuspendOrder(ShopOrder $order): bool
    {
        // Don't suspend if already suspended or terminated
        if (!$order->isActive()) {
            return false;
        }

        // Don't suspend one-time orders
        if ($order->billing_cycle === ShopOrder::CYCLE_ONE_TIME) {
            return false;
        }

        // Check if order is actually overdue
        if (!$order->next_due_at || $order->next_due_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Schedule termination if configured.
     */
    private function scheduleTerminationIfNeeded(ShopOrder $order): void
    {
        $terminationDays = config('shop.billing.termination_after_days');
        
        if ($terminationDays > 0) {
            TerminateOverdueOrdersJob::dispatch()
                ->delay(now()->addDays($terminationDays));
                
            Log::info('Termination scheduled for suspended order', [
                'order_id' => $order->id,
                'termination_date' => now()->addDays($terminationDays)->toDateTimeString(),
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Suspend overdue orders job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
