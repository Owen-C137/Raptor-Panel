<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\PaymentGateways\AbstractPaymentGateway;
use PterodactylAddons\ShopSystem\PaymentGateways\StripePaymentGateway;
use PterodactylAddons\ShopSystem\PaymentGateways\PayPalPaymentGateway;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Illuminate\Support\Facades\Log;

class PaymentGatewayManager
{
    /**
     * Registered payment gateways.
     */
    private array $gateways = [];

    public function __construct()
    {
        $this->registerDefaultGateways();
    }

    /**
     * Register default payment gateways.
     */
    private function registerDefaultGateways(): void
    {
        $this->register('stripe', StripePaymentGateway::class);
        $this->register('paypal', PayPalPaymentGateway::class);
    }

    /**
     * Register a payment gateway.
     */
    public function register(string $name, string $gatewayClass): void
    {
        if (!is_subclass_of($gatewayClass, AbstractPaymentGateway::class)) {
            throw new \InvalidArgumentException("Gateway class must extend AbstractPaymentGateway");
        }

        $this->gateways[$name] = $gatewayClass;
    }

    /**
     * Get a payment gateway instance.
     */
    public function gateway(string $name): AbstractPaymentGateway
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' not found");
        }

        return app($this->gateways[$name]);
    }

    /**
     * Get all available payment gateways.
     */
    public function getAvailableGateways(): array
    {
        $available = [];

        foreach ($this->gateways as $name => $gatewayClass) {
            $gateway = app($gatewayClass);
            
            if ($gateway->isEnabled()) {
                $available[$name] = [
                    'name' => $gateway->getName(),
                    'display_name' => $gateway->getDisplayName(),
                    'supported_currencies' => $gateway->getSupportedCurrencies(),
                    'instance' => $gateway,
                ];
            }
        }

        return $available;
    }

    /**
     * Get enabled gateway names.
     */
    public function getEnabledGatewayNames(): array
    {
        return array_keys($this->getAvailableGateways());
    }

    /**
     * Check if a gateway is available.
     */
    public function isGatewayAvailable(string $name): bool
    {
        return in_array($name, $this->getEnabledGatewayNames());
    }

    /**
     * Create payment session for an order.
     */
    public function createPaymentSession(string $gatewayName, ShopOrder $order, array $metadata = []): array
    {
        if (!$this->isGatewayAvailable($gatewayName)) {
            return [
                'success' => false,
                'error' => "Payment gateway '{$gatewayName}' is not available",
            ];
        }

        $gateway = $this->gateway($gatewayName);

        // Check currency support
        $supportedCurrencies = $gateway->getSupportedCurrencies();
        if (!in_array($order->currency, $supportedCurrencies)) {
            return [
                'success' => false,
                'error' => "Currency '{$order->currency}' is not supported by {$gateway->getDisplayName()}",
            ];
        }

        return $gateway->createPaymentSession($order, $metadata);
    }

    /**
     * Process webhook for a gateway.
     */
    public function processWebhook(string $gatewayName, array $payload): bool
    {
        try {
            if (!$this->isGatewayAvailable($gatewayName)) {
                Log::warning("Webhook received for unavailable gateway: {$gatewayName}");
                return false;
            }

            $gateway = $this->gateway($gatewayName);
            return $gateway->handleWebhook($payload);

        } catch (\Exception $e) {
            Log::error("Webhook processing failed for gateway {$gatewayName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify payment completion.
     */
    public function verifyPayment(string $gatewayName, string $transactionId): array
    {
        if (!$this->isGatewayAvailable($gatewayName)) {
            return [
                'success' => false,
                'error' => "Payment gateway '{$gatewayName}' is not available",
            ];
        }

        $gateway = $this->gateway($gatewayName);
        return $gateway->verifyPayment($transactionId);
    }

    /**
     * Process refund through a gateway.
     */
    public function processRefund(ShopPayment $payment, ?float $amount = null): array
    {
        $gatewayName = $payment->gateway;

        if (!$this->isGatewayAvailable($gatewayName)) {
            return [
                'success' => false,
                'error' => "Payment gateway '{$gatewayName}' is not available for refunds",
            ];
        }

        $gateway = $this->gateway($gatewayName);
        return $gateway->processRefund($payment, $amount);
    }

    /**
     * Test connection for all gateways.
     */
    public function testAllConnections(): array
    {
        $results = [];

        foreach ($this->gateways as $name => $gatewayClass) {
            $gateway = app($gatewayClass);
            $results[$name] = $gateway->testConnection();
        }

        return $results;
    }

    /**
     * Test connection for specific gateway.
     */
    public function testConnection(string $gatewayName): array
    {
        if (!isset($this->gateways[$gatewayName])) {
            return [
                'success' => false,
                'message' => "Gateway '{$gatewayName}' not found",
            ];
        }

        $gateway = app($this->gateways[$gatewayName]);
        return $gateway->testConnection();
    }

    /**
     * Validate configuration for all gateways.
     */
    public function validateAllConfigurations(): array
    {
        $results = [];

        foreach ($this->gateways as $name => $gatewayClass) {
            $gateway = app($gatewayClass);
            $results[$name] = $gateway->validateConfig();
        }

        return $results;
    }

    /**
     * Get gateway statistics for admin dashboard.
     */
    public function getGatewayStatistics(int $days = 30): array
    {
        $stats = [];
        $paymentRepository = app(\PterodactylAddons\ShopSystem\Repositories\ShopPaymentRepository::class);
        
        $revenueByGateway = $paymentRepository->getRevenueByGateway($days);
        
        foreach ($this->getAvailableGateways() as $name => $gateway) {
            $gatewayRevenue = $revenueByGateway->where('gateway', $name)->first();
            
            $stats[$name] = [
                'display_name' => $gateway['display_name'],
                'enabled' => true,
                'payment_count' => $gatewayRevenue->payment_count ?? 0,
                'total_revenue' => $gatewayRevenue->total_revenue ?? 0,
                'supported_currencies' => $gateway['supported_currencies'],
            ];
        }

        return $stats;
    }

    /**
     * Calculate fees for amount across all gateways.
     */
    public function calculateFeesForAllGateways(float $amount): array
    {
        $fees = [];

        foreach ($this->getAvailableGateways() as $name => $gatewayData) {
            $gateway = $gatewayData['instance'];
            $fees[$name] = [
                'display_name' => $gateway->getDisplayName(),
                'fee_amount' => $gateway->calculateFees($amount),
                'total_amount' => $amount + $gateway->calculateFees($amount),
            ];
        }

        return $fees;
    }

    /**
     * Get the best gateway for a currency.
     */
    public function getBestGatewayForCurrency(string $currency): ?string
    {
        $availableGateways = $this->getAvailableGateways();
        
        foreach ($availableGateways as $name => $gateway) {
            if (in_array($currency, $gateway['supported_currencies'])) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get gateway configuration requirements.
     */
    public function getConfigurationRequirements(): array
    {
        $requirements = [];

        $requirements['stripe'] = [
            'display_name' => 'Stripe',
            'required_fields' => [
                'secret_key' => 'Secret Key (sk_...)',
                'publishable_key' => 'Publishable Key (pk_...)',
            ],
            'optional_fields' => [
                'webhook_secret' => 'Webhook Secret (whsec_...)',
                'fee_percentage' => 'Fee Percentage (default: 2.9)',
                'fixed_fee' => 'Fixed Fee (default: 0.30)',
            ],
            'description' => 'Stripe provides secure credit card processing with instant payouts.',
        ];

        $requirements['paypal'] = [
            'display_name' => 'PayPal',
            'required_fields' => [
                'client_id' => 'Client ID',
                'client_secret' => 'Client Secret',
            ],
            'optional_fields' => [
                'sandbox' => 'Sandbox Mode (default: true)',
                'fee_percentage' => 'Fee Percentage (default: 2.9)',
                'fixed_fee' => 'Fixed Fee (default: 0.30)',
            ],
            'description' => 'PayPal allows customers to pay using their PayPal account or credit card.',
        ];

        return $requirements;
    }

    /**
     * Process wallet payment (internal method).
     */
    public function processWalletPayment(ShopOrder $order): array
    {
        try {
            $walletService = app(WalletService::class);
            $wallet = $walletService->getOrCreateWallet($order->user, $order->currency);

            if (!$wallet->hasSufficientFunds($order->total_amount)) {
                return [
                    'success' => false,
                    'error' => 'Insufficient wallet balance',
                    'required' => $order->total_amount,
                    'available' => $wallet->balance,
                ];
            }

            // Create payment record
            $payment = ShopPayment::create([
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'type' => ShopPayment::TYPE_ORDER_PAYMENT,
                'status' => ShopPayment::STATUS_COMPLETED,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'gateway' => 'wallet',
                'processed_at' => now(),
            ]);

            // Deduct from wallet
            $transaction = $walletService->deductFunds(
                $wallet,
                $order->total_amount,
                "Payment for order #{$order->id}"
            );

            if (!$transaction) {
                $payment->update(['status' => ShopPayment::STATUS_FAILED]);
                return [
                    'success' => false,
                    'error' => 'Failed to deduct funds from wallet',
                ];
            }

            // Activate order
            $orderService = app(\PterodactylAddons\ShopSystem\Services\ShopOrderService::class);
            $orderService->activate($order);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'new_balance' => $wallet->fresh()->balance,
            ];

        } catch (\Exception $e) {
            Log::error('Wallet payment processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Wallet payment processing failed: ' . $e->getMessage(),
            ];
        }
    }
}
