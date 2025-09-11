<?php

namespace PterodactylAddons\ShopSystem\PaymentGateways;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

class PayPalPaymentGateway extends AbstractPaymentGateway
{
    private PayPalHttpClient $client;
    private ShopConfigService $shopConfig;

    public function __construct(ShopConfigService $shopConfig, array $config = [])
    {
        $this->shopConfig = $shopConfig;
        $settings = $this->shopConfig->getShopConfig();
        
        $this->config = array_merge([
            'client_id' => $settings['paypal_client_id'] ?? '',
            'client_secret' => $settings['paypal_client_secret'] ?? '',
            'mode' => $settings['paypal_mode'] ?? 'sandbox',
            'enabled' => $settings['paypal_enabled'] ?? false,
            'fee_percentage' => 2.9, // Default PayPal fee
            'fixed_fee' => 0.30,     // Default PayPal fixed fee
        ], $config);

        if ($this->config['client_id'] && $this->config['client_secret']) {
            $sandbox = ($this->config['mode'] === 'sandbox');
            $environment = $sandbox 
                ? new SandboxEnvironment($this->config['client_id'], $this->config['client_secret'])
                : new ProductionEnvironment($this->config['client_id'], $this->config['client_secret']);
                
            $this->client = new PayPalHttpClient($environment);
        }
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function getDisplayName(): string
    {
        return 'PayPal';
    }

    public function isEnabled(): bool
    {
        return !empty($this->config['client_id']) && 
               !empty($this->config['client_secret']) &&
               ($this->config['enabled'] ?? false);
    }

    public function createPaymentSession(ShopOrder $order, array $metadata = []): array
    {
        try {
            // Create payment record first
            $payment = $this->createPaymentRecord($order, [
                'metadata' => $metadata,
            ]);

            // Prepare order items
            $items = [
                [
                    'name' => $order->plan->name,
                    'description' => "Order #{$order->id} - {$order->plan->category->name}",
                    'quantity' => '1',
                    'unit_amount' => [
                        'currency_code' => $order->currency,
                        'value' => number_format($order->amount, 2, '.', ''),
                    ],
                ],
            ];

            // Add setup fee if applicable
            if ($order->setup_fee > 0) {
                $items[] = [
                    'name' => 'Setup Fee',
                    'description' => "Setup fee for {$order->plan->name}",
                    'quantity' => '1',
                    'unit_amount' => [
                        'currency_code' => $order->currency,
                        'value' => number_format($order->setup_fee, 2, '.', ''),
                    ],
                ];
            }

            // Calculate total amount
            $totalAmount = $order->amount + $order->setup_fee;

            // Create PayPal order request
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $order->uuid,
                        'description' => "Order #{$order->id} - {$order->plan->name}",
                        'custom_id' => $payment->id,
                        'soft_descriptor' => config('app.name', 'Shop'),
                        'amount' => [
                            'currency_code' => $order->currency,
                            'value' => number_format($totalAmount, 2, '.', ''),
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => $order->currency,
                                    'value' => number_format($totalAmount, 2, '.', ''),
                                ],
                            ],
                        ],
                        'items' => $items,
                    ],
                ],
                'application_context' => [
                    'brand_name' => config('app.name', 'Shop'),
                    'locale' => 'en-US',
                    'landing_page' => 'BILLING',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $this->getReturnUrl($order),
                    'cancel_url' => $this->getCancelUrl($order),
                ],
            ];

            $response = $this->client->execute($request);
            $paypalOrder = $response->result;

            // Find approval URL
            $approvalUrl = null;
            foreach ($paypalOrder->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            if (!$approvalUrl) {
                throw new \Exception('PayPal approval URL not found in response');
            }

            // Update payment record with PayPal order ID
            $this->updatePaymentRecord($payment, [
                'transaction_id' => $paypalOrder->id,
                'metadata' => [
                    'paypal_order_id' => $paypalOrder->id,
                    'paypal_status' => $paypalOrder->status,
                ],
            ]);

            return [
                'success' => true,
                'order_id' => $paypalOrder->id,
                'approval_url' => $approvalUrl,
                'payment_id' => $payment->id,
            ];

        } catch (\Exception $e) {
            $this->log('error', 'Failed to create PayPal payment session', [
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
            $eventType = $payload['event_type'] ?? null;
            $resource = $payload['resource'] ?? [];

            $this->log('info', 'Received PayPal webhook', [
                'event_type' => $eventType,
                'resource_id' => $resource['id'] ?? null,
            ]);

            switch ($eventType) {
                case 'CHECKOUT.ORDER.APPROVED':
                    return $this->handleOrderApproved($resource);
                
                case 'PAYMENT.CAPTURE.COMPLETED':
                    return $this->handleCaptureCompleted($resource);
                
                case 'PAYMENT.CAPTURE.DENIED':
                    return $this->handleCaptureDenied($resource);
                
                case 'PAYMENT.CAPTURE.REFUNDED':
                    return $this->handleCaptureRefunded($resource);
                
                default:
                    $this->log('info', 'Unhandled PayPal webhook event', [
                        'event_type' => $eventType,
                    ]);
                    return true;
            }

        } catch (\Exception $e) {
            $this->log('error', 'PayPal webhook processing failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function verifyPayment(string $transactionId): array
    {
        try {
            $request = new OrdersGetRequest($transactionId);
            $response = $this->client->execute($request);
            $order = $response->result;

            $captureId = null;
            $captureStatus = null;
            
            if (isset($order->purchase_units[0]->payments->captures[0])) {
                $capture = $order->purchase_units[0]->payments->captures[0];
                $captureId = $capture->id;
                $captureStatus = $capture->status;
            }

            return [
                'success' => true,
                'status' => $order->status,
                'capture_status' => $captureStatus,
                'capture_id' => $captureId,
                'amount' => (float) $order->purchase_units[0]->amount->value,
                'currency' => $order->purchase_units[0]->amount->currency_code,
                'transaction_id' => $order->id,
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
            
            // Get the capture ID from metadata
            $captureId = $payment->gateway_metadata['paypal_capture_id'] ?? null;
            
            if (!$captureId) {
                throw new \Exception('PayPal capture ID not found in payment metadata');
            }

            $request = new CapturesRefundRequest($captureId);
            $request->body = [
                'amount' => [
                    'currency_code' => $payment->currency,
                    'value' => number_format($refundAmount, 2, '.', ''),
                ],
                'note_to_payer' => 'Refund for order #' . $payment->order_id,
            ];

            $response = $this->client->execute($request);
            $refund = $response->result;

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => (float) $refund->amount->value,
                'status' => $refund->status,
            ];

        } catch (\Exception $e) {
            $this->log('error', 'PayPal refund failed', [
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
            'PLN', 'CZK', 'HUF', 'SGD', 'HKD', 'MXN', 'BRL', 'TWD', 'THB', 'ILS',
        ];
    }

    public function validateConfig(): array
    {
        $required = ['client_id', 'client_secret'];
        $missing = $this->validateRequiredConfig($required);
        
        $errors = [];
        $warnings = [];
        
        if (!empty($missing)) {
            $errors[] = 'Missing required configuration: ' . implode(', ', $missing);
        }
        
        if ($this->config['sandbox']) {
            $warnings[] = 'PayPal is configured for sandbox mode';
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
            // Test by creating a minimal order and then voiding it
            $request = new OrdersCreateRequest();
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => '1.00',
                        ],
                        'description' => 'Connection test order',
                    ],
                ],
            ];

            $response = $this->client->execute($request);
            
            return [
                'success' => true,
                'message' => 'Successfully connected to PayPal',
                'order_id' => $response->result->id,
                'environment' => $this->config['sandbox'] ? 'sandbox' : 'production',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to PayPal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Capture a PayPal order after approval.
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $request = new OrdersCaptureRequest($orderId);
            $response = $this->client->execute($request);
            $order = $response->result;

            return [
                'success' => true,
                'order' => $order,
                'capture_id' => $order->purchase_units[0]->payments->captures[0]->id ?? null,
                'status' => $order->status,
            ];

        } catch (\Exception $e) {
            $this->log('error', 'PayPal order capture failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle order approved webhook.
     */
    private function handleOrderApproved($resource): bool
    {
        $orderId = $resource['id'] ?? null;
        
        if (!$orderId) {
            return false;
        }

        // Find payment by PayPal order ID
        $payment = ShopPayment::where('gateway_transaction_id', $orderId)->first();
        
        if (!$payment) {
            $this->log('error', 'Payment not found for PayPal order', [
                'paypal_order_id' => $orderId,
            ]);
            return false;
        }

        // Capture the order
        $captureResult = $this->captureOrder($orderId);
        
        if ($captureResult['success']) {
            $this->updatePaymentRecord($payment, [
                'status' => ShopPayment::STATUS_COMPLETED,
                'processed_at' => now(),
                'metadata' => [
                    'paypal_capture_id' => $captureResult['capture_id'],
                    'paypal_order_approved' => true,
                ],
            ]);

            // Activate the order
            app(\PterodactylAddons\ShopSystem\Services\ShopOrderService::class)
                ->activate($payment->order);

            $this->log('info', 'PayPal order captured and activated', [
                'order_id' => $payment->order_id,
                'payment_id' => $payment->id,
            ]);
        }

        return $captureResult['success'];
    }

    /**
     * Handle capture completed webhook.
     */
    private function handleCaptureCompleted($resource): bool
    {
        $this->log('info', 'PayPal capture completed', [
            'capture_id' => $resource['id'] ?? null,
        ]);
        
        return true;
    }

    /**
     * Handle capture denied webhook.
     */
    private function handleCaptureDenied($resource): bool
    {
        $captureId = $resource['id'] ?? null;
        
        // Find payment by capture ID
        $payment = ShopPayment::where('gateway_metadata->paypal_capture_id', $captureId)->first();
        
        if ($payment) {
            $this->updatePaymentRecord($payment, [
                'status' => ShopPayment::STATUS_FAILED,
                'failed_at' => now(),
                'metadata' => [
                    'failure_reason' => 'Payment capture denied by PayPal',
                ],
            ]);
        }
        
        $this->log('warning', 'PayPal capture denied', [
            'capture_id' => $captureId,
        ]);
        
        return true;
    }

    /**
     * Handle capture refunded webhook.
     */
    private function handleCaptureRefunded($resource): bool
    {
        $this->log('info', 'PayPal capture refunded', [
            'refund_id' => $resource['id'] ?? null,
            'capture_id' => $resource['links'][0]['href'] ?? null,
        ]);
        
        // TODO: Handle refund processing
        // - Update payment status
        // - Create refund record
        // - Notify user
        
        return true;
    }
}
