<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\UserWallet;

class PaymentService
{
    /**
     * Process payment for orders
     */
    public function processPayment(array $orders, string $method, float $amount): ShopPayment
    {
        $payment = ShopPayment::create([
            'user_id' => $orders[0]->user_id,
            'gateway' => $method,
            'gateway_id' => null,
            'amount' => $amount,
            'currency' => config('shop.currency', 'USD'),
            'status' => 'pending',
            'metadata' => [
                'order_ids' => collect($orders)->pluck('id')->toArray(),
            ],
        ]);

        try {
            if ($method === 'wallet') {
                $this->processWalletPayment($payment, $amount);
            } else {
                $this->processGatewayPayment($payment, $method, $amount);
            }

            $payment->update(['status' => 'completed']);

            // Activate associated orders
            foreach ($orders as $order) {
                app(OrderService::class)->activateOrder($order);
            }

        } catch (\Exception $e) {
            $payment->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            
            throw $e;
        }

        return $payment;
    }

    /**
     * Process wallet payment
     */
    protected function processWalletPayment(ShopPayment $payment, float $amount): void
    {
        $wallet = UserWallet::where('user_id', $payment->user_id)->first();

        if (!$wallet || $wallet->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $wallet->decrement('balance', $amount);

        // Create wallet transaction
        \Pterodactyl\Models\WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'purchase',
            'amount' => -$amount,
            'description' => "Payment for shop order(s)",
            'reference_type' => 'shop_payment',
            'reference_id' => $payment->id,
        ]);
    }

    /**
     * Process gateway payment (Stripe/PayPal)
     */
    protected function processGatewayPayment(ShopPayment $payment, string $method, float $amount): void
    {
        // This is a simplified version - real implementation would integrate with payment gateways
        
        if ($method === 'stripe') {
            $this->processStripePayment($payment, $amount);
        } elseif ($method === 'paypal') {
            $this->processPayPalPayment($payment, $amount);
        }
    }

    /**
     * Process Stripe payment
     */
    protected function processStripePayment(ShopPayment $payment, float $amount): void
    {
        // Placeholder for Stripe integration
        // In a real implementation, this would create a Stripe payment intent
        
        $payment->update([
            'gateway_id' => 'stripe_' . uniqid(),
            'status' => 'completed',
        ]);
    }

    /**
     * Process PayPal payment
     */
    protected function processPayPalPayment(ShopPayment $payment, float $amount): void
    {
        // Placeholder for PayPal integration
        // In a real implementation, this would create a PayPal order
        
        $payment->update([
            'gateway_id' => 'paypal_' . uniqid(),
            'status' => 'completed',
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(ShopPayment $payment, float $amount, string $reason): void
    {
        if ($payment->gateway === 'wallet') {
            $this->processWalletRefund($payment, $amount, $reason);
        } else {
            $this->processGatewayRefund($payment, $amount, $reason);
        }
    }

    /**
     * Process wallet refund
     */
    protected function processWalletRefund(ShopPayment $payment, float $amount, string $reason): void
    {
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => $payment->user_id],
            ['balance' => 0]
        );

        $wallet->increment('balance', $amount);

        // Create wallet transaction
        \Pterodactyl\Models\WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'refund',
            'amount' => $amount,
            'description' => $reason,
            'reference_type' => 'payment_refund',
            'reference_id' => $payment->id,
        ]);
    }

    /**
     * Process gateway refund
     */
    protected function processGatewayRefund(ShopPayment $payment, float $amount, string $reason): void
    {
        // Placeholder for gateway refund processing
        // Real implementation would call gateway APIs
    }
}
