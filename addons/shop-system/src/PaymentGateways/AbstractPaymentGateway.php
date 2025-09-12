<?php

namespace PterodactylAddons\ShopSystem\PaymentGateways;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;

abstract class AbstractPaymentGateway
{
    /**
     * Gateway configuration.
     */
    protected array $config;

    /**
     * Gateway name identifier.
     */
    abstract public function getName(): string;

    /**
     * Gateway display name.
     */
    abstract public function getDisplayName(): string;

    /**
     * Check if gateway is enabled.
     */
    abstract public function isEnabled(): bool;

    /**
     * Create a payment session for checkout.
     */
    abstract public function createPaymentSession(ShopOrder $order, array $metadata = []): array;

    /**
     * Process webhook notification from gateway.
     */
    abstract public function handleWebhook(array $payload): bool;

    /**
     * Verify payment completion.
     */
    abstract public function verifyPayment(string $transactionId): array;

    /**
     * Process refund for a completed payment.
     */
    abstract public function processRefund(ShopPayment $payment, ?float $amount = null): array;

    /**
     * Get supported currencies.
     */
    abstract public function getSupportedCurrencies(): array;

    /**
     * Validate gateway configuration.
     */
    abstract public function validateConfig(): array;

    /**
     * Get gateway fees for amount calculation.
     */
    public function calculateFees(float $amount): float
    {
        $feePercentage = $this->config['fee_percentage'] ?? 0;
        $fixedFee = $this->config['fixed_fee'] ?? 0;
        
        return ($amount * $feePercentage / 100) + $fixedFee;
    }

    /**
     * Format amount for gateway (some gateways use cents).
     */
    protected function formatAmount(float $amount, string $currency = 'USD'): int|float
    {
        // Most gateways use cents for USD, EUR, etc.
        $zeroDecimalCurrencies = ['JPY', 'KRW', 'VND'];
        
        if (in_array($currency, $zeroDecimalCurrencies)) {
            return (int) $amount;
        }
        
        return (int) ($amount * 100);
    }

    /**
     * Parse amount from gateway format.
     */
    protected function parseAmount(int|float $amount, string $currency = 'USD'): float
    {
        $zeroDecimalCurrencies = ['JPY', 'KRW', 'VND'];
        
        if (in_array($currency, $zeroDecimalCurrencies)) {
            return (float) $amount;
        }
        
        return $amount / 100;
    }

    /**
     * Log gateway activity.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['gateway'] = $this->getName();
        
        logger()->{$level}($message, $context);
    }

    /**
     * Generate return URL for payment completion.
     */
    protected function getReturnUrl(ShopOrder $order): string
    {
        return route('shop.payment.return', [
            'gateway' => $this->getName(),
            'order' => $order->uuid,
        ]);
    }

    /**
     * Generate cancel URL for payment cancellation.
     */
    protected function getCancelUrl(ShopOrder $order): string
    {
        return route('shop.payment.cancel', [
            'gateway' => $this->getName(),
            'order' => $order->uuid,
        ]);
    }

    /**
     * Generate webhook URL for gateway notifications.
     */
    protected function getWebhookUrl(): string
    {
        return route('shop.webhook', ['gateway' => $this->getName()]);
    }

    /**
     * Create payment record in database.
     */
    protected function createPaymentRecord(ShopOrder $order, array $data): ShopPayment
    {
        return ShopPayment::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => ShopPayment::TYPE_ORDER_PAYMENT,
            'status' => ShopPayment::STATUS_PENDING,
            'amount' => $order->total_amount,
            'currency' => $order->currency,
            'gateway' => $this->getName(),
            'gateway_transaction_id' => $data['transaction_id'] ?? null,
            'gateway_metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Update payment with gateway response.
     */
    protected function updatePaymentRecord(ShopPayment $payment, array $data): void
    {
        $updateData = [];
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        if (isset($data['transaction_id'])) {
            $updateData['gateway_transaction_id'] = $data['transaction_id'];
        }
        
        if (isset($data['metadata'])) {
            $updateData['gateway_metadata'] = array_merge(
                $payment->gateway_metadata ?? [],
                $data['metadata']
            );
        }
        
        if (isset($data['processed_at'])) {
            $updateData['processed_at'] = $data['processed_at'];
        }
        
        if (isset($data['failed_at'])) {
            $updateData['failed_at'] = $data['failed_at'];
        }
        
        $payment->update($updateData);
    }

    /**
     * Validate required configuration keys.
     */
    protected function validateRequiredConfig(array $required): array
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }
        
        return $missing;
    }

    /**
     * Test gateway connection.
     */
    public function testConnection(): array
    {
        try {
            $validation = $this->validateConfig();
            
            if (!empty($validation['errors'])) {
                return [
                    'success' => false,
                    'message' => 'Configuration validation failed',
                    'errors' => $validation['errors'],
                ];
            }
            
            // Perform gateway-specific connection test
            return $this->performConnectionTest();
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gateway-specific connection test implementation.
     */
    protected function performConnectionTest(): array
    {
        return [
            'success' => true,
            'message' => 'Connection test not implemented for this gateway',
        ];
    }

    /**
     * Get gateway configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
