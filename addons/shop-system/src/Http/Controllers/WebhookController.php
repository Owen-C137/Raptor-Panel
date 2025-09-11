<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Models\Shop\ShopOrder;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentGatewayManager $paymentManager,
        private ShopOrderService $orderService,
        private WalletService $walletService
    ) {}

    /**
     * Handle Stripe webhook events.
     */
    public function stripe(Request $request): JsonResponse
    {
        try {
            $gateway = $this->paymentManager->getGateway('stripe');
            
            // Verify webhook signature
            $isValid = $gateway->verifyWebhookSignature(
                payload: $request->getContent(),
                signature: $request->header('Stripe-Signature'),
                secret: config('shop.payment_gateways.stripe.webhook_secret')
            );

            if (!$isValid) {
                Log::warning('Invalid Stripe webhook signature', [
                    'ip' => $request->ip(),
                    'signature' => $request->header('Stripe-Signature'),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $payload = json_decode($request->getContent(), true);
            $event = $payload['type'];
            $data = $payload['data']['object'];

            Log::info('Stripe webhook received', [
                'event' => $event,
                'object_id' => $data['id'] ?? 'unknown',
            ]);

            switch ($event) {
                case 'payment_intent.succeeded':
                    $this->handleStripePaymentSuccess($data);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleStripePaymentFailed($data);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleStripeSubscriptionPayment($data);
                    break;

                case 'invoice.payment_failed':
                    $this->handleStripeSubscriptionFailure($data);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleStripeSubscriptionCancelled($data);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event', ['event' => $event]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle PayPal webhook events.
     */
    public function paypal(Request $request): JsonResponse
    {
        try {
            $gateway = $this->paymentManager->getGateway('paypal');
            
            // Verify webhook signature
            $isValid = $gateway->verifyWebhookSignature(
                payload: $request->getContent(),
                headers: $request->headers->all(),
                webhookId: config('shop.payment_gateways.paypal.webhook_id')
            );

            if (!$isValid) {
                Log::warning('Invalid PayPal webhook signature', [
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $payload = json_decode($request->getContent(), true);
            $event = $payload['event_type'];
            $resource = $payload['resource'];

            Log::info('PayPal webhook received', [
                'event' => $event,
                'resource_id' => $resource['id'] ?? 'unknown',
            ]);

            switch ($event) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $this->handlePayPalPaymentSuccess($resource);
                    break;

                case 'PAYMENT.CAPTURE.DENIED':
                case 'PAYMENT.CAPTURE.DECLINED':
                    $this->handlePayPalPaymentFailed($resource);
                    break;

                case 'BILLING.SUBSCRIPTION.ACTIVATED':
                    $this->handlePayPalSubscriptionActivated($resource);
                    break;

                case 'BILLING.SUBSCRIPTION.CANCELLED':
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    $this->handlePayPalSubscriptionCancelled($resource);
                    break;

                default:
                    Log::info('Unhandled PayPal webhook event', ['event' => $event]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('PayPal webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful Stripe payment.
     */
    private function handleStripePaymentSuccess(array $data): void
    {
        $paymentIntentId = $data['id'];
        $metadata = $data['metadata'] ?? [];
        $amount = $data['amount'] / 100; // Convert from cents

        DB::transaction(function () use ($paymentIntentId, $metadata, $amount) {
            if (isset($metadata['order_id'])) {
                // Order payment
                $order = ShopOrder::find($metadata['order_id']);
                if ($order && $order->isPending()) {
                    $this->orderService->markAsPaid($order->id, 'stripe', $paymentIntentId);
                    
                    Log::info('Stripe order payment completed', [
                        'order_id' => $order->id,
                        'amount' => $amount,
                        'payment_intent' => $paymentIntentId,
                    ]);
                }
            } elseif (isset($metadata['transaction_id'])) {
                // Wallet deposit
                $transaction = \PterodactylAddons\ShopSystem\Models\WalletTransaction::find($metadata['transaction_id']);
                if ($transaction && $transaction->status === 'pending') {
                    $this->walletService->confirmDeposit($transaction->id, $paymentIntentId);
                    
                    Log::info('Stripe wallet deposit completed', [
                        'user_id' => $transaction->user_id,
                        'amount' => $amount,
                        'payment_intent' => $paymentIntentId,
                    ]);
                }
            }
        });
    }

    /**
     * Handle failed Stripe payment.
     */
    private function handleStripePaymentFailed(array $data): void
    {
        $paymentIntentId = $data['id'];
        $metadata = $data['metadata'] ?? [];
        $failureReason = $data['last_payment_error']['message'] ?? 'Payment failed';

        if (isset($metadata['order_id'])) {
            $order = ShopOrder::find($metadata['order_id']);
            if ($order) {
                $this->orderService->markPaymentFailed($order->id, $failureReason);
                
                Log::warning('Stripe order payment failed', [
                    'order_id' => $order->id,
                    'payment_intent' => $paymentIntentId,
                    'reason' => $failureReason,
                ]);
            }
        } elseif (isset($metadata['transaction_id'])) {
            $transaction = \PterodactylAddons\ShopSystem\Models\WalletTransaction::find($metadata['transaction_id']);
            if ($transaction) {
                $transaction->update([
                    'status' => 'failed',
                    'failure_reason' => $failureReason,
                ]);

                Log::warning('Stripe wallet deposit failed', [
                    'user_id' => $transaction->user_id,
                    'payment_intent' => $paymentIntentId,
                    'reason' => $failureReason,
                ]);
            }
        }
    }

    /**
     * Handle Stripe subscription payment success.
     */
    private function handleStripeSubscriptionPayment(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        $metadata = $data['metadata'] ?? [];

        if ($subscriptionId && isset($metadata['order_id'])) {
            $order = ShopOrder::find($metadata['order_id']);
            if ($order && $order->isActive()) {
                // Update next billing date
                $periodEnd = \Carbon\Carbon::createFromTimestamp($data['period_end']);
                $order->update(['next_due_at' => $periodEnd]);

                Log::info('Stripe subscription payment processed', [
                    'order_id' => $order->id,
                    'subscription_id' => $subscriptionId,
                    'next_due_at' => $periodEnd,
                ]);
            }
        }
    }

    /**
     * Handle Stripe subscription payment failure.
     */
    private function handleStripeSubscriptionFailure(array $data): void
    {
        $subscriptionId = $data['subscription'] ?? null;
        $metadata = $data['metadata'] ?? [];

        if ($subscriptionId && isset($metadata['order_id'])) {
            $order = ShopOrder::find($metadata['order_id']);
            if ($order) {
                // Mark order as overdue and schedule suspension
                $this->orderService->markAsOverdue($order->id, 'Subscription payment failed');

                Log::warning('Stripe subscription payment failed', [
                    'order_id' => $order->id,
                    'subscription_id' => $subscriptionId,
                ]);
            }
        }
    }

    /**
     * Handle Stripe subscription cancellation.
     */
    private function handleStripeSubscriptionCancelled(array $data): void
    {
        $subscriptionId = $data['id'];
        $metadata = $data['metadata'] ?? [];

        if (isset($metadata['order_id'])) {
            $order = ShopOrder::find($metadata['order_id']);
            if ($order) {
                $this->orderService->cancelOrder($order->id, 'Subscription cancelled in Stripe');

                Log::info('Stripe subscription cancelled', [
                    'order_id' => $order->id,
                    'subscription_id' => $subscriptionId,
                ]);
            }
        }
    }

    /**
     * Handle successful PayPal payment.
     */
    private function handlePayPalPaymentSuccess(array $resource): void
    {
        $captureId = $resource['id'];
        $customId = $resource['custom_id'] ?? null;
        $amount = floatval($resource['amount']['value'] ?? 0);

        if ($customId) {
            $metadata = json_decode($customId, true) ?: [];

            DB::transaction(function () use ($captureId, $metadata, $amount) {
                if (isset($metadata['order_id'])) {
                    $order = ShopOrder::find($metadata['order_id']);
                    if ($order && $order->isPending()) {
                        $this->orderService->markAsPaid($order->id, 'paypal', $captureId);
                        
                        Log::info('PayPal order payment completed', [
                            'order_id' => $order->id,
                            'amount' => $amount,
                            'capture_id' => $captureId,
                        ]);
                    }
                } elseif (isset($metadata['transaction_id'])) {
                    $transaction = \PterodactylAddons\ShopSystem\Models\WalletTransaction::find($metadata['transaction_id']);
                    if ($transaction && $transaction->status === 'pending') {
                        $this->walletService->confirmDeposit($transaction->id, $captureId);
                        
                        Log::info('PayPal wallet deposit completed', [
                            'user_id' => $transaction->user_id,
                            'amount' => $amount,
                            'capture_id' => $captureId,
                        ]);
                    }
                }
            });
        }
    }

    /**
     * Handle failed PayPal payment.
     */
    private function handlePayPalPaymentFailed(array $resource): void
    {
        $captureId = $resource['id'];
        $customId = $resource['custom_id'] ?? null;
        $failureReason = $resource['status_details']['reason'] ?? 'Payment failed';

        if ($customId) {
            $metadata = json_decode($customId, true) ?: [];

            if (isset($metadata['order_id'])) {
                $order = ShopOrder::find($metadata['order_id']);
                if ($order) {
                    $this->orderService->markPaymentFailed($order->id, $failureReason);
                    
                    Log::warning('PayPal order payment failed', [
                        'order_id' => $order->id,
                        'capture_id' => $captureId,
                        'reason' => $failureReason,
                    ]);
                }
            } elseif (isset($metadata['transaction_id'])) {
                $transaction = \PterodactylAddons\ShopSystem\Models\WalletTransaction::find($metadata['transaction_id']);
                if ($transaction) {
                    $transaction->update([
                        'status' => 'failed',
                        'failure_reason' => $failureReason,
                    ]);

                    Log::warning('PayPal wallet deposit failed', [
                        'user_id' => $transaction->user_id,
                        'capture_id' => $captureId,
                        'reason' => $failureReason,
                    ]);
                }
            }
        }
    }

    /**
     * Handle PayPal subscription activation.
     */
    private function handlePayPalSubscriptionActivated(array $resource): void
    {
        $subscriptionId = $resource['id'];
        $customId = $resource['custom_id'] ?? null;

        if ($customId) {
            $metadata = json_decode($customId, true) ?: [];

            if (isset($metadata['order_id'])) {
                $order = ShopOrder::find($metadata['order_id']);
                if ($order) {
                    $order->update(['external_id' => $subscriptionId]);

                    Log::info('PayPal subscription activated', [
                        'order_id' => $order->id,
                        'subscription_id' => $subscriptionId,
                    ]);
                }
            }
        }
    }

    /**
     * Handle PayPal subscription cancellation.
     */
    private function handlePayPalSubscriptionCancelled(array $resource): void
    {
        $subscriptionId = $resource['id'];
        $customId = $resource['custom_id'] ?? null;

        if ($customId) {
            $metadata = json_decode($customId, true) ?: [];

            if (isset($metadata['order_id'])) {
                $order = ShopOrder::find($metadata['order_id']);
                if ($order) {
                    $this->orderService->cancelOrder($order->id, 'Subscription cancelled in PayPal');

                    Log::info('PayPal subscription cancelled', [
                        'order_id' => $order->id,
                        'subscription_id' => $subscriptionId,
                    ]);
                }
            }
        }
    }
}
