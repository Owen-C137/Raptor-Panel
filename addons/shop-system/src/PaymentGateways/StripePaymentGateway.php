<?php

namespace PterodactylAddons\ShopSystem\PaymentGateways;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use Stripe\StripeClient;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripePaymentGateway extends AbstractPaymentGateway
{
    private StripeClient $stripe;
    private ShopConfigService $shopConfig;

    public function __construct(ShopConfigService $shopConfig, array $config = [])
    {
        $this->shopConfig = $shopConfig;
        $settings = $this->shopConfig->getShopConfig();
        
        $this->config = array_merge([
            'secret_key' => $settings['stripe_secret_key'] ?? '',
            'publishable_key' => $settings['stripe_publishable_key'] ?? '',
            'webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
            'mode' => $settings['stripe_mode'] ?? 'test',
            'enabled' => $settings['stripe_enabled'] ?? false,
            'fee_percentage' => 2.9, // Default Stripe fee
            'fixed_fee' => 0.30,     // Default Stripe fixed fee
        ], $config);

        if ($this->config['secret_key']) {
            $this->stripe = new StripeClient($this->config['secret_key']);
        }
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function isEnabled(): bool
    {
        return !empty($this->config['secret_key']) && 
               !empty($this->config['publishable_key']) &&
               ($this->config['enabled'] ?? false);
    }

    public function createPaymentSession(ShopOrder $order, array $metadata = []): array
    {
        try {
            // Create payment record first
            $payment = $this->createPaymentRecord($order, [
                'metadata' => $metadata,
            ]);

            // Create Stripe checkout session
            $sessionData = [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($order->currency),
                            'product_data' => [
                                'name' => $order->plan->name,
                                'description' => "Order #{$order->id} - {$order->plan->category->name}",
                            ],
                            'unit_amount' => $this->formatAmount($order->amount, $order->currency),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => $this->getReturnUrl($order) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->getCancelUrl($order),
                'customer_email' => $order->user->email,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_uuid' => $order->uuid,
                    'payment_id' => $payment->id,
                    'user_id' => $order->user_id,
                ],
            ];

            // Add setup fee if applicable
            if ($order->setup_fee > 0) {
                $sessionData['line_items'][] = [
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'product_data' => [
                            'name' => 'Setup Fee',
                            'description' => "Setup fee for {$order->plan->name}",
                        ],
                        'unit_amount' => $this->formatAmount($order->setup_fee, $order->currency),
                    ],
                    'quantity' => 1,
                ];
            }

            $session = $this->stripe->checkout->sessions->create($sessionData);

            // Update payment record with session ID
            $this->updatePaymentRecord($payment, [
                'transaction_id' => $session->id,
                'metadata' => [
                    'stripe_session_id' => $session->id,
                    'stripe_payment_intent' => $session->payment_intent,
                ],
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'session_url' => $session->url,
                'payment_id' => $payment->id,
            ];

        } catch (\Exception $e) {
            $this->log('error', 'Failed to create Stripe payment session', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(array $payload): bool
    {
        try {
            $signature = request()->header('Stripe-Signature');
            
            if (empty($this->config['webhook_secret'])) {
                $this->log('warning', 'Webhook secret not configured for Stripe');
                return false;
            }

            // Verify webhook signature
            $event = Webhook::constructEvent(
                request()->getContent(),
                $signature,
                $this->config['webhook_secret']
            );

            $this->log('info', 'Received Stripe webhook', [
                'event_type' => $event['type'],
                'event_id' => $event['id'],
            ]);

            switch ($event['type']) {
                case 'checkout.session.completed':
                    return $this->handleCheckoutCompleted($event['data']['object']);
                
                case 'payment_intent.succeeded':
                    return $this->handlePaymentSucceeded($event['data']['object']);
                
                case 'payment_intent.payment_failed':
                    return $this->handlePaymentFailed($event['data']['object']);
                
                case 'charge.dispute.created':
                    return $this->handleChargeDispute($event['data']['object']);
                
                default:
                    $this->log('info', 'Unhandled Stripe webhook event', [
                        'event_type' => $event['type'],
                    ]);
                    return true;
            }

        } catch (SignatureVerificationException $e) {
            $this->log('error', 'Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;

        } catch (\Exception $e) {
            $this->log('error', 'Stripe webhook processing failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function verifyPayment(string $transactionId): array
    {
        try {
            $session = $this->stripe->checkout->sessions->retrieve($transactionId);
            
            return [
                'success' => true,
                'status' => $session->payment_status,
                'amount' => $this->parseAmount($session->amount_total, $session->currency),
                'currency' => strtoupper($session->currency),
                'transaction_id' => $session->id,
                'payment_intent' => $session->payment_intent,
                'metadata' => $session->metadata->toArray(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function processRefund(ShopPayment $payment, ?float $amount = null): array
    {
        try {
            $refundAmount = $amount ?? $payment->amount;
            
            // Get the payment intent from metadata
            $paymentIntentId = $payment->gateway_metadata['stripe_payment_intent'] ?? null;
            
            if (!$paymentIntentId) {
                throw new \Exception('Payment intent ID not found in payment metadata');
            }

            $refund = $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => $this->formatAmount($refundAmount, $payment->currency),
                'metadata' => [
                    'order_id' => $payment->order_id,
                    'payment_id' => $payment->id,
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $this->parseAmount($refund->amount, $refund->currency),
                'status' => $refund->status,
            ];

        } catch (\Exception $e) {
            $this->log('error', 'Stripe refund failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getSupportedCurrencies(): array
    {
        return [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK',
            'PLN', 'CZK', 'HUF', 'BGN', 'RON', 'HRK', 'MXN', 'BRL', 'SGD', 'HKD',
        ];
    }

    public function validateConfig(): array
    {
        $required = ['secret_key', 'publishable_key'];
        $missing = $this->validateRequiredConfig($required);
        
        $errors = [];
        $warnings = [];
        
        if (!empty($missing)) {
            $errors[] = 'Missing required configuration: ' . implode(', ', $missing);
        }
        
        if (empty($this->config['webhook_secret'])) {
            $warnings[] = 'Webhook secret not configured - webhook verification will be disabled';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function performConnectionTest(): array
    {
        try {
            // Test by retrieving account information
            $account = $this->stripe->accounts->retrieve();
            
            return [
                'success' => true,
                'message' => 'Successfully connected to Stripe',
                'account_id' => $account->id,
                'country' => $account->country,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to Stripe: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle checkout session completed webhook.
     */
    private function handleCheckoutCompleted($session): bool
    {
        $orderId = $session['metadata']['order_id'] ?? null;
        $paymentId = $session['metadata']['payment_id'] ?? null;
        
        if (!$orderId || !$paymentId) {
            $this->log('warning', 'Missing order or payment ID in checkout session metadata');
            return false;
        }
        
        $payment = ShopPayment::find($paymentId);
        if (!$payment) {
            $this->log('error', 'Payment not found for checkout session', [
                'payment_id' => $paymentId,
                'session_id' => $session['id'],
            ]);
            return false;
        }
        
        // Update payment status
        $this->updatePaymentRecord($payment, [
            'status' => ShopPayment::STATUS_COMPLETED,
            'processed_at' => now(),
            'metadata' => [
                'stripe_session_completed' => true,
                'stripe_payment_status' => $session['payment_status'],
            ],
        ]);
        
        // Activate the order
        app(\PterodactylAddons\ShopSystem\Services\ShopOrderService::class)
            ->activate($payment->order);
        
        $this->log('info', 'Stripe checkout session completed and order activated', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
        ]);
        
        return true;
    }

    /**
     * Handle payment succeeded webhook.
     */
    private function handlePaymentSucceeded($paymentIntent): bool
    {
        // Additional processing if needed
        $this->log('info', 'Stripe payment succeeded', [
            'payment_intent_id' => $paymentIntent['id'],
        ]);
        
        return true;
    }

    /**
     * Handle payment failed webhook.
     */
    private function handlePaymentFailed($paymentIntent): bool
    {
        // Find payment by payment intent ID
        $payment = ShopPayment::where('gateway_metadata->stripe_payment_intent', $paymentIntent['id'])->first();
        
        if ($payment) {
            $this->updatePaymentRecord($payment, [
                'status' => ShopPayment::STATUS_FAILED,
                'failed_at' => now(),
                'metadata' => [
                    'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
                ],
            ]);
        }
        
        $this->log('warning', 'Stripe payment failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
        ]);
        
        return true;
    }

    /**
     * Handle charge dispute webhook.
     */
    private function handleChargeDispute($dispute): bool
    {
        $this->log('warning', 'Stripe charge dispute created', [
            'dispute_id' => $dispute['id'],
            'charge_id' => $dispute['charge'],
            'amount' => $dispute['amount'],
            'reason' => $dispute['reason'],
        ]);
        
        // TODO: Implement dispute handling logic
        // - Suspend related order
        // - Notify administrators
        // - Update payment status
        
        return true;
    }
}
