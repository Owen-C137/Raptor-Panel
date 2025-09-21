<?php

namespace PterodactylAddons\ShopSystem\Jobs;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderRenewalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Execute the job.
     */
    public function handle(
        ShopOrderRepository $orderRepository,
        ShopOrderService $orderService,
        PaymentGatewayManager $paymentManager
    ): void {
        Log::info('Starting order renewals processing job');

        // Get orders due for renewal (within the next hour)
        $dueOrders = $orderRepository->getDueForRenewal(1);

        $processed = 0;
        $failed = 0;

        foreach ($dueOrders as $order) {
            try {
                $this->processOrderRenewal($order, $orderService, $paymentManager);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process renewal for order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Order renewals processing completed', [
            'total_due' => $dueOrders->count(),
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }

    /**
     * Process renewal for a single order.
     */
    private function processOrderRenewal(
        ShopOrder $order,
        ShopOrderService $orderService,
        PaymentGatewayManager $paymentManager
    ): void {
        // Check if order is still active and due for renewal
        if (!$order->isActive() || !$order->next_due_at || $order->next_due_at->isFuture()) {
            return;
        }

        // Get user's preferred payment method or default to wallet
        $paymentMethod = $this->getPreferredPaymentMethod($order);

        if ($paymentMethod === 'wallet') {
            $this->processWalletRenewal($order, $orderService, $paymentManager);
        } else {
            $this->processGatewayRenewal($order, $paymentMethod, $paymentManager);
        }
    }

    /**
     * Process wallet-based renewal.
     */
    private function processWalletRenewal(
        ShopOrder $order,
        ShopOrderService $orderService,
        PaymentGatewayManager $paymentManager
    ): void {
        $result = $paymentManager->processWalletPayment($order);

        if ($result['success']) {
            Log::info('Order renewed successfully via wallet', [
                'order_id' => $order->id,
                'amount' => $order->amount,
            ]);
        } else {
            // Wallet renewal failed, try to process with saved payment method
            $this->handleRenewalFailure($order, $result['error']);
        }
    }

    /**
     * Process gateway-based renewal (for saved payment methods).
     */
    private function processGatewayRenewal(
        ShopOrder $order,
        string $paymentMethod,
        PaymentGatewayManager $paymentManager
    ): void {
        // TODO: Implement saved payment method processing
        // This would involve storing customer payment methods and charging them
        Log::info('Gateway renewal attempted', [
            'order_id' => $order->id,
            'method' => $paymentMethod,
        ]);

        // For now, fall back to wallet or manual renewal
        $this->handleRenewalFailure($order, 'Automatic gateway renewal not yet implemented');
    }

    /**
     * Handle renewal failure.
     */
    private function handleRenewalFailure(ShopOrder $order, string $reason): void
    {
        Log::warning('Order renewal failed', [
            'order_id' => $order->id,
            'reason' => $reason,
        ]);

        // Send renewal reminder notification
        SendRenewalNotificationJob::dispatch($order, 'renewal_failed');

        // Schedule suspension if overdue by grace period
        $gracePeriodHours = config('shop.billing.grace_period_hours', 24);
        
        if ($order->next_due_at->diffInHours(now()) > $gracePeriodHours) {
            SuspendOverdueOrdersJob::dispatch()->delay(now()->addMinutes(5));
        }
    }

    /**
     * Get preferred payment method for user.
     */
    private function getPreferredPaymentMethod(ShopOrder $order): string
    {
        // Check user preferences (this would come from user settings)
        // For now, default to wallet
        return config('shop.billing.default_renewal_method', 'wallet');
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Order renewals job failed completely', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Notify administrators about the failure
        // TODO: Send admin notification
    }
}
