<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PterodactylAddons\ShopSystem\Repositories\ShopPlanRepository;
use PterodactylAddons\ShopSystem\Repositories\ShopCouponRepository;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\CartService;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends BaseShopController
{
    public function __construct(
        private ShopPlanRepository $planRepository,
        private ShopCouponRepository $couponRepository,
        private ShopOrderService $orderService,
        private PaymentGatewayManager $paymentManager,
        private CartService $cartService
    ) {
        parent::__construct();
    }

    /**
     * Display checkout page.
     */
    public function index(): View|RedirectResponse
    {
        $this->checkShopAvailability();

        if (!auth()->check()) {
            return redirect()->route('auth.login')
                ->with('error', 'Please login to continue with checkout.');
        }

        // Use database-based cart instead of session
        $cartSummary = $this->cartService->getCartSummary();
        
        if (!$cartSummary['success'] || empty($cartSummary['items'])) {
            return redirect()->route('shop.cart')
                ->with('error', 'Your cart is empty. Please add items before checkout.');
        }

        // Get cart data for the view
        $cartItems = $cartSummary['items'];
        $total = $cartSummary['total'];
        
        $paymentMethods = $this->getAvailablePaymentMethods();
        $userWallet = null;
        
        // Only fetch wallet if credits are enabled
        $settings = $this->shopConfigService->getShopConfig();
        if ($settings['credits_enabled'] ?? false) {
            $userWallet = $this->walletService->getWallet(auth()->id());
        }

        return $this->view('shop::checkout.index', compact(
            'cartItems',
            'total', 
            'paymentMethods',
            'userWallet'
        ));
    }

    /**
     * Apply coupon code.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $coupon = $this->couponRepository->findByCode($request->code);

        if (!$coupon) {
            return $this->errorResponse('Invalid coupon code.');
        }

        if (!$coupon->isValid()) {
            return $this->errorResponse('This coupon is no longer valid.');
        }

        // Use database-based cart instead of session
        $cartSummary = $this->cartService->getCartSummary();
        
        if (!$cartSummary['success'] || empty($cartSummary['items'])) {
            return $this->errorResponse('Your cart is empty.');
        }

        $cartItems = $cartSummary['items'];

        if (!$coupon->isApplicableToCart($cartItems)) {
            return $this->errorResponse('This coupon cannot be applied to your cart.');
        }

        session(['applied_coupon' => $coupon->code]);

        $totals = $this->calculateTotals($cartItems, $coupon);

        return $this->successResponse([
            'coupon' => [
                'code' => $coupon->code,
                'description' => $coupon->description,
                'discount_amount' => $totals['discount'],
                'type' => $coupon->type,
                'value' => $coupon->value,
            ],
            'totals' => $totals,
        ], 'Coupon applied successfully!');
    }

    /**
     * Remove applied coupon.
     */
    public function removeCoupon(): JsonResponse
    {
        session()->forget('applied_coupon');

        // Use database-based cart instead of session
        $cartSummary = $this->cartService->getCartSummary();
        
        if (!$cartSummary['success'] || empty($cartSummary['items'])) {
            return $this->errorResponse('Your cart is empty.');
        }

        $cartItems = $cartSummary['items'];
        $totals = $this->calculateTotals($cartItems);

        return $this->successResponse([
            'totals' => $totals,
        ], 'Coupon removed.');
    }

    /**
     * Get checkout summary with cart items and totals.
     */
    public function getSummary(): JsonResponse
    {
        \Log::info('🔄 Checkout getSummary called', [
            'user_id' => auth()->id(),
            'user_authenticated' => auth()->check(),
            'request_headers' => request()->headers->all(),
        ]);
        
        $summary = $this->cartService->getCartSummary();
        
        \Log::info('📊 Cart summary result', $summary);
        
        if (!$summary['success']) {
            \Log::warning('⚠️ Cart summary failed', $summary);
            return response()->json([
                'success' => false,
                'message' => 'Failed to load checkout summary.'
            ], 400);
        }

        // Calculate breakdown for checkout
        $subtotal = 0;
        $setupTotal = 0;
        
        foreach ($summary['items'] as $item) {
            $plan = $item['plan'];
            $quantity = $item['quantity'];
            
            $subtotal += $plan['price'] * $quantity;
            $setupTotal += $plan['setup_fee'] * $quantity;
        }
        
        // Get discount and tax from cart summary (calculated in CartService)
        $discount = $summary['discount'] ?? 0;
        $tax = $summary['tax'] ?? 0;
        $total = $summary['total'] ?? ($subtotal + $setupTotal - $discount + $tax);

        $response = [
            'success' => true,
            'summary' => [
                'items' => $summary['items'],
                'subtotal' => $subtotal,
                'setup_total' => $setupTotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'applied_coupon' => $summary['applied_coupon'] ?? null
            ]
        ];
        
        \Log::info('✅ Checkout summary response', $response);

        // Return the structure expected by the frontend
        return response()->json($response);
    }

    /**
     * Process the checkout and create order.
     */
    public function process(Request $request): JsonResponse
    {
        Log::info('🚀 Checkout process started', [
            'user_id' => auth()->id(),
            'payment_gateway' => $request->input('payment_gateway'),
            'request_data' => $request->all(),
        ]);

        try {
            $request->validate([
                'payment_gateway' => 'required|string|in:stripe,paypal,wallet',
                // Billing details can be either flat fields or nested in billing_details array
                'billing_details' => 'sometimes|array',
                'billing_details.name' => 'sometimes|string|max:255',
                'billing_details.email' => 'sometimes|email|max:255',
                'billing_details.address' => 'sometimes|string|max:255',
                'billing_details.city' => 'sometimes|string|max:100',
                'billing_details.country' => 'sometimes|string|size:2',
                'billing_details.postal_code' => 'sometimes|string|max:20',
                // Flat billing fields (for backward compatibility) - nullable to handle empty fields
                'first_name' => 'sometimes|nullable|string|max:255',
                'last_name' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|nullable|email|max:255',
                'company' => 'sometimes|nullable|string|max:255',
                'address' => 'sometimes|nullable|string|max:255',
                'address2' => 'sometimes|nullable|string|max:255',
                'city' => 'sometimes|nullable|string|max:100',
                'state' => 'sometimes|nullable|string|max:100',
                'country' => 'sometimes|nullable|string|size:2',
                'postal_code' => 'sometimes|nullable|string|max:20',
                'notes' => 'sometimes|nullable|string',
                'terms_accept' => 'sometimes|nullable',
            ]);
            
            Log::info('✅ Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors(),
                'messages' => $e->getMessage(),
            ]);
            throw $e;
        }

        try {
            DB::beginTransaction();

            // Use database-based cart instead of session
            $cartSummary = $this->cartService->getCartSummary();
            
            if (!$cartSummary['success'] || empty($cartSummary['items'])) {
                return $this->errorResponse('Your cart is empty.');
            }

            $cartItems = $cartSummary['items'];
            $coupon = null;
            
            if (session('applied_coupon')) {
                $coupon = $this->couponRepository->findByCode(session('applied_coupon'));
            }

            $totals = $this->calculateTotals($cartItems, $coupon);

            // Extract billing details from either nested array or flat fields
            $billingDetails = $request->input('billing_details', []);
            if (empty($billingDetails)) {
                // Build from flat fields
                $billingDetails = [
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'email' => $request->input('email'),
                    'company' => $request->input('company'),
                    'address' => $request->input('address'),
                    'address2' => $request->input('address2'),
                    'city' => $request->input('city'),
                    'state' => $request->input('state'),
                    'country' => $request->input('country'),
                    'postal_code' => $request->input('postal_code'),
                ];
                // Remove empty values
                $billingDetails = array_filter($billingDetails);
            }

            // Create the order
            $order = $this->orderService->createOrder([
                'user_id' => auth()->id(),
                'items' => $cartItems,
                'totals' => $totals,
                'coupon' => $coupon,
                'billing_details' => $billingDetails,
                'payment_method' => $request->payment_gateway,
            ]);

            // Process payment
            $paymentResult = $this->processPayment($order, $request->payment_gateway, $request->all());

            Log::info('🔍 Payment result after processing', [
                'payment_result' => $paymentResult,
                'has_redirect_url' => isset($paymentResult['redirect_url']),
            ]);

            if ($paymentResult['success']) {
                // Clear cart and coupon
                session()->forget(['shop_cart', 'applied_coupon']);

                DB::commit();

                $this->logActivity('Order created and payment processed', $order, [
                    'order_id' => $order->id,
                    'total' => $totals['total'],
                    'payment_method' => $request->payment_gateway,
                ]);

                $responseData = [
                    'order_uuid' => $order->uuid,
                    'redirect_url' => $paymentResult['redirect_url'] ?? route('shop.orders.show', ['order' => $order->uuid, 'success' => 1]),
                    'requires_payment_action' => $paymentResult['requires_action'] ?? false,
                    'payment_intent' => $paymentResult['payment_intent'] ?? null,
                ];

                Log::info('🚀 Final response data being sent to frontend', [
                    'response_data' => $responseData,
                ]);

                return $this->successResponse($responseData, 'Order created successfully!');
            } else {
                DB::rollBack();
                return $this->errorResponse($paymentResult['message'] ?? 'Payment processing failed.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout processing failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('An error occurred while processing your order. Please try again.');
        }
    }

    /**
     * Build cart items with plan details.
     */
    private function buildCartItems(array $cart): array
    {
        $cartItems = [];

        foreach ($cart as $item) {
            $plan = $this->planRepository->find($item['plan_id']);
            
            if ($plan && $plan->isAvailable()) {
                $cartItems[] = [
                    'plan' => $plan,
                    'quantity' => $item['quantity'],
                    'price' => $plan->price,
                    'setup_fee' => $plan->setup_fee,
                    'subtotal' => $plan->price * $item['quantity'],
                    'setup_total' => $plan->setup_fee * $item['quantity'],
                ];
            }
        }

        return $cartItems;
    }

    /**
     * Calculate order totals.
     */
    private function calculateTotals(array $cartItems, $coupon = null): array
    {
        $subtotal = 0;
        $setupTotal = 0;

        foreach ($cartItems as $item) {
            // Cart items from CartService have 'subtotal' which is the total price including setup fee
            $itemTotal = $item['subtotal'] ?? 0;
            
            // Separate the setup fee from the subtotal for display purposes
            if (isset($item['plan']['setup_fee']) && isset($item['quantity'])) {
                $itemSetupFee = $item['plan']['setup_fee'] * $item['quantity'];
                $itemSubtotal = $itemTotal - $itemSetupFee;
                
                $subtotal += $itemSubtotal;
                $setupTotal += $itemSetupFee;
            } else {
                // If no setup fee info available, treat entire amount as subtotal
                $subtotal += $itemTotal;
            }
        }

        $total = $subtotal + $setupTotal;
        $discount = 0;

        if ($coupon && $coupon->isValid()) {
            $discount = $coupon->calculateDiscount($total);
            $total -= $discount;
        }

        $tax = 0;
        if (config('shop.tax.enabled')) {
            $taxRate = 0.10; // TODO: Calculate based on location
            $tax = $total * $taxRate;
            $total += $tax;
        }

        return [
            'subtotal' => $subtotal,
            'setup_total' => $setupTotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => max(0, $total), // Ensure total never goes negative
        ];
    }

    /**
     * Get available payment methods.
     */
    private function getAvailablePaymentMethods(): array
    {
        $settings = $this->shopConfigService->getShopConfig();
        $methods = [];

        if ($settings['stripe_enabled'] ?? false) {
            $methods['stripe'] = [
                'name' => 'Credit Card',
                'icon' => 'credit-card',
                'description' => 'Pay securely with your credit or debit card',
            ];
        }

        if ($settings['paypal_enabled'] ?? false) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Pay with your PayPal account',
            ];
        }

        if ($settings['credits_enabled'] ?? false) {
            $methods['wallet'] = [
                'name' => 'Wallet Balance',
                'icon' => 'wallet',
                'description' => 'Use your account wallet balance',
            ];
        }

        return $methods;
    }

    /**
     * Process payment for the order.
     */
    private function processPayment($order, string $paymentMethod, array $paymentData): array
    {
        try {
            switch ($paymentMethod) {
                case 'wallet':
                    return $this->processWalletPayment($order);
                    
                case 'stripe':
                    return $this->processStripePayment($order, $paymentData);
                    
                case 'paypal':
                    return $this->processPayPalPayment($order, $paymentData);
                    
                default:
                    return ['success' => false, 'message' => 'Invalid payment method.'];
            }
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Payment processing failed.'];
        }
    }

    /**
     * Process wallet payment.
     */
    private function processWalletPayment($order): array
    {
        // Get the user's wallet
        $wallet = $this->walletService->getWallet($order->user_id);
        
        // Check if user has sufficient funds
        if (!$wallet->hasSufficientFunds($order->amount)) {
            return ['success' => false, 'message' => 'Insufficient wallet balance.'];
        }
        
        // Create payment record first
        $payment = ShopPayment::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => 'order_payment',
            'status' => ShopPayment::STATUS_PENDING,
            'amount' => $order->amount,
            'currency' => $order->currency,
            'gateway' => 'wallet',
        ]);
        
        // Deduct funds from wallet
        $transaction = $this->walletService->deductFunds(
            $wallet,
            $order->amount,
            "Order #{$order->id} payment"
        );

        if ($transaction) {
            // Update payment record to completed
            $payment->update([
                'status' => ShopPayment::STATUS_COMPLETED,
                'processed_at' => now(),
            ]);
            
            // Store completion flag for deferred processing (like PayPal)
            $order->update(['payment_method' => 'wallet']);
            
            // Return redirect URL for deferred completion (matching PayPal pattern)
            return [
                'success' => true,
                'redirect_url' => route('shop.checkout.wallet.complete', ['order' => $order->uuid])
            ];
        }

        // Update payment record to failed
        $payment->update([
            'status' => ShopPayment::STATUS_FAILED,
            'failed_at' => now(),
        ]);

        return ['success' => false, 'message' => 'Failed to process wallet payment.'];
    }

    /**
     * Process Stripe payment.
     */
    private function processStripePayment($order, array $paymentData): array
    {
        $gateway = $this->paymentManager->gateway('stripe');
        
        return $gateway->createPaymentSession($order, [
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'billing_details' => $order->billing_details ?? [],
                'payment_method' => $order->payment_method,
            ],
            'payment_method_data' => $paymentData['payment_method_data'] ?? [],
        ]);
    }

    /**
     * Process PayPal payment.
     */
    private function processPayPalPayment($order, array $paymentData): array
    {
        Log::info('🏦 Starting PayPal payment processing', [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'currency' => $order->currency,
        ]);
        
        try {
            $gateway = $this->paymentManager->gateway('paypal');
            
            Log::info('✅ PayPal gateway loaded successfully');
            
            $result = $gateway->createPaymentSession($order, [
                'return_url' => route('shop.orders.show', $order->uuid),
                'cancel_url' => route('shop.cart'),
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                    'billing_details' => $order->billing_details ?? [],
                    'payment_method' => $order->payment_method,
                ],
            ]);
            
            Log::info('🎯 PayPal payment result', ['result' => $result]);
            
            // Map PayPal response to expected format
            if ($result['success'] && isset($result['approval_url'])) {
                $mappedResult = [
                    'success' => true,
                    'redirect_url' => $result['approval_url'],
                    'payment_id' => $result['payment_id'] ?? null,
                ];
                
                Log::info('✅ PayPal response mapped successfully', [
                    'original' => $result,
                    'mapped' => $mappedResult,
                ]);
                
                return $mappedResult;
            }
            
            Log::warning('❌ PayPal response mapping failed', [
                'result' => $result,
                'has_success' => isset($result['success']),
                'success_value' => $result['success'] ?? null,
                'has_approval_url' => isset($result['approval_url']),
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('💥 PayPal payment processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['success' => false, 'message' => 'PayPal payment failed: ' . $e->getMessage()];
        }
    }

    /**
     * Handle payment return from gateway.
     */
    public function paymentReturn(string $gateway, ShopOrder $order)
    {
        Log::info('🔄 Payment return callback', [
            'gateway' => $gateway,
            'order_id' => $order->id,
            'order_uuid' => $order->uuid,
        ]);

        try {
            $paymentGateway = $this->paymentManager->gateway($gateway);
            
            // Process the return based on the gateway
            switch ($gateway) {
                case 'paypal':
                    return $this->handlePayPalReturn($order);
                case 'stripe':
                    return $this->handleStripeReturn($order);
                default:
                    return redirect()->route('shop.orders.show', $order->uuid)
                        ->with('error', 'Unknown payment gateway');
            }
        } catch (\Exception $e) {
            Log::error('💥 Payment return processing failed', [
                'gateway' => $gateway,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Payment processing failed');
        }
    }

    /**
     * Handle payment cancellation from gateway.
     */
    public function paymentCancel(string $gateway, ShopOrder $order)
    {
        Log::info('❌ Payment cancelled', [
            'gateway' => $gateway,
            'order_id' => $order->id,
        ]);

        return redirect()->route('shop.orders.show', $order->uuid)
            ->with('warning', 'Payment was cancelled');
    }

    /**
     * Handle PayPal payment return.
     */
    private function handlePayPalReturn(ShopOrder $order)
    {
        Log::info('🔄 Processing PayPal return', [
            'order_id' => $order->id,
            'order_uuid' => $order->uuid,
            'current_status' => $order->status,
        ]);

        // PayPal returns with token parameter
        $token = request('token');
        
        if (!$token) {
            Log::error('❌ PayPal return missing token', [
                'order_id' => $order->id,
                'request_params' => request()->all(),
            ]);
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Invalid PayPal return');
        }

        try {
            // Get PayPal gateway and capture payment
            $paypalGateway = $this->paymentManager->gateway('paypal');
            
            // Capture the PayPal order
            $result = $paypalGateway->captureOrder($token);
            
            if ($result['success']) {
                // Mark order as paid
                $this->orderService->markAsPaid($order->id, 'paypal');
                
                // Clear the cart since payment was successful
                $this->cartService->clearCart();
                
                Log::info('✅ PayPal payment captured successfully', [
                    'order_id' => $order->id,
                    'capture_id' => $result['capture_id'] ?? null,
                ]);
                
                return redirect()->route('shop.orders.show', $order->uuid)
                    ->with('success', 'Payment completed successfully! Your order is now active.');
            } else {
                Log::error('❌ PayPal payment capture failed', [
                    'order_id' => $order->id,
                    'result' => $result,
                ]);
                
                return redirect()->route('shop.orders.show', $order->uuid)
                    ->with('error', 'Payment verification failed. Please contact support.');
            }
        } catch (\Exception $e) {
            Log::error('💥 PayPal return processing error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Payment processing failed. Please contact support.');
        }
    }

    /**
     * Handle Stripe payment return.
     */
    private function handleStripeReturn(ShopOrder $order)
    {
        // Stripe returns with session_id parameter
        $sessionId = request('session_id');
        
        if (!$sessionId) {
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Invalid Stripe return');
        }

        // Here you would verify the Stripe session
        // For now, just mark as paid
        $this->orderService->markAsPaid($order->id, 'stripe');
        
        return redirect()->route('shop.orders.show', $order->uuid)
            ->with('success', 'Payment completed successfully!');
    }

    /**
     * Complete wallet payment with deferred server creation (matching PayPal pattern).
     */
    public function completeWalletPayment(ShopOrder $order)
    {
        Log::info('🔄 Processing wallet payment completion', [
            'order_id' => $order->id,
            'order_uuid' => $order->uuid,
            'current_status' => $order->status,
        ]);

        // Ensure this is the order owner
        if ($order->user_id !== auth()->id()) {
            Log::warning('❌ Unauthorized wallet completion attempt', [
                'order_id' => $order->id,
                'auth_user_id' => auth()->id(),
                'order_user_id' => $order->user_id,
            ]);
            abort(403, 'Unauthorized access to order');
        }

        // Verify payment method and that funds were already deducted
        if ($order->payment_method !== 'wallet') {
            Log::error('❌ Wallet completion for non-wallet order', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
            ]);
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Invalid payment completion');
        }

        // Check if payment was successful (funds already deducted)
        $payment = $order->payments()->where('gateway', 'wallet')->where('status', ShopPayment::STATUS_COMPLETED)->first();
        
        if (!$payment) {
            Log::error('❌ No completed wallet payment found', [
                'order_id' => $order->id,
                'payments' => $order->payments()->get()->toArray(),
            ]);
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Payment not found or incomplete');
        }

        try {
            // Mark order as paid (this triggers server creation)
            $this->orderService->markAsPaid($order->id, 'wallet');
            
            // Clear the cart since payment was successful
            $this->cartService->clearCart();
            
            Log::info('✅ Wallet payment completed successfully', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);
            
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('success', 'Payment completed successfully! Your order is now active.');
                
        } catch (\Exception $e) {
            Log::error('💥 Wallet completion processing error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Payment completion failed. Please contact support.');
        }
    }
}
