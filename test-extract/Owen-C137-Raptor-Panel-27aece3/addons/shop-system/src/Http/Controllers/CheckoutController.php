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
use PterodactylAddons\ShopSystem\Services\CurrencyService;
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
        private CartService $cartService,
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        parent::__construct($shopConfigService, $walletService, $currencyService);
    }

    /**
     * Display checkout page.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->checkShopAvailability();

        if (!auth()->check()) {
            return redirect()->route('auth.login')
                ->with('error', 'Please login to continue with checkout.');
        }

        // Check if this is a renewal request
        $renewServerUuid = $request->query('renew');
        
        if ($renewServerUuid) {
            return $this->handleRenewalCheckout($renewServerUuid);
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
     * Handle renewal checkout flow
     */
    private function handleRenewalCheckout(string $serverUuid): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Find the cancelled order for this server
        $cancelledOrder = ShopOrder::query()
            ->where('user_id', $user->id)
            ->where('status', ShopOrder::STATUS_CANCELLED)
            ->whereHas('server', function($query) use ($serverUuid) {
                $query->where('uuid', 'LIKE', $serverUuid . '%')
                      ->orWhere('uuidShort', $serverUuid);
            })
            ->with(['server', 'plan'])
            ->first();
        
        if (!$cancelledOrder) {
            return redirect()->route('shop.index')->with('error', 'No cancelled server plan found for renewal.');
        }
        
        $paymentMethods = $this->getAvailablePaymentMethods();
        $userWallet = null;
        
        // Only fetch wallet if credits are enabled
        $settings = $this->shopConfigService->getShopConfig();
        if ($settings['credits_enabled'] ?? false) {
            $userWallet = $this->walletService->getWallet(auth()->id());
        }
        
        // Calculate renewal pricing (default to monthly, but could be extended)
        $renewalPrice = $cancelledOrder->plan->price;
        $timeRemaining = $cancelledOrder->auto_delete_at ? 
            now()->diffInDays($cancelledOrder->auto_delete_at, false) : null;
        
        return $this->view('shop::checkout.renewal', compact(
            'cancelledOrder',
            'renewalPrice',
            'timeRemaining',
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
            'is_renewal' => $request->has('renewal_order_id'),
            'request_data' => $request->all(),
        ]);

        // Check if this is a renewal
        if ($request->has('renewal_order_id')) {
            return $this->processRenewal($request);
        }

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
                // Check if this is a renewal payment
                $renewalData = session('renewal_data');
                
                if ($renewalData && $renewalData['order_id'] == $order->id) {
                    // This is a renewal completion - reactivate the order
                    Log::info('✅ PayPal renewal payment captured successfully', [
                        'order_id' => $order->id,
                        'capture_id' => $result['capture_id'] ?? null,
                        'renewal_data' => $renewalData
                    ]);
                    
                    // Mark the order as paid first
                    $this->orderService->markAsPaid($order->id, 'paypal');
                    
                    // Then reactivate the order with renewal data
                    $this->reactivateOrder(
                        $order, 
                        $renewalData['billing_cycle'], 
                        \Carbon\Carbon::parse($renewalData['next_due_at']), 
                        $renewalData['amount']
                    );
                    
                    // Clear renewal session data
                    session()->forget('renewal_data');
                    
                    return redirect()->route('shop.orders.show', $order->uuid)
                        ->with('success', 'Server plan renewed successfully! Your server is now active.');
                        
                } else {
                    // Regular order completion
                    $this->orderService->markAsPaid($order->id, 'paypal');
                    
                    // Clear the cart since payment was successful
                    $this->cartService->clearCart();
                    
                    Log::info('✅ PayPal payment captured successfully', [
                        'order_id' => $order->id,
                        'capture_id' => $result['capture_id'] ?? null,
                    ]);
                    
                    return redirect()->route('shop.orders.show', $order->uuid)
                        ->with('success', 'Payment completed successfully! Your order is now active.');
                }
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

        try {
            // Check if this is a renewal payment
            $renewalData = session('renewal_data');
            
            if ($renewalData && $renewalData['order_id'] == $order->id) {
                // This is a renewal completion - reactivate the order
                Log::info('✅ Stripe renewal payment completed successfully', [
                    'order_id' => $order->id,
                    'session_id' => $sessionId,
                    'renewal_data' => $renewalData
                ]);
                
                // Mark the order as paid first
                $this->orderService->markAsPaid($order->id, 'stripe');
                
                // Then reactivate the order with renewal data
                $this->reactivateOrder(
                    $order, 
                    $renewalData['billing_cycle'], 
                    \Carbon\Carbon::parse($renewalData['next_due_at']), 
                    $renewalData['amount']
                );
                
                // Clear renewal session data
                session()->forget('renewal_data');
                
                return redirect()->route('shop.orders.show', $order->uuid)
                    ->with('success', 'Server plan renewed successfully! Your server is now active.');
                    
            } else {
                // Regular order completion
                // Here you would verify the Stripe session
                // For now, just mark as paid
                $this->orderService->markAsPaid($order->id, 'stripe');
                
                return redirect()->route('shop.orders.show', $order->uuid)
                    ->with('success', 'Payment completed successfully!');
            }
            
        } catch (\Exception $e) {
            Log::error('💥 Stripe return processing error', [
                'order_id' => $order->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('shop.orders.show', $order->uuid)
                ->with('error', 'Payment processing failed. Please contact support.');
        }
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

    /**
     * Process renewal checkout
     */
    private function processRenewal(Request $request): JsonResponse
    {
        $request->validate([
            'renewal_order_id' => 'required|integer|exists:shop_orders,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,annually',
            'payment_method' => 'required|string|in:stripe,paypal,wallet',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Find the cancelled order to renew
            $cancelledOrder = ShopOrder::query()
                ->where('id', $request->renewal_order_id)
                ->where('user_id', $user->id)
                ->where('status', ShopOrder::STATUS_CANCELLED)
                ->with(['plan', 'server'])
                ->first();

            if (!$cancelledOrder) {
                return $this->errorResponse('Order not found or not eligible for renewal.');
            }

            // Calculate renewal amount
            $plan = $cancelledOrder->plan;
            $billingCycle = $request->billing_cycle;
            $multiplier = match($billingCycle) {
                'monthly' => 1,
                'quarterly' => 3,
                'annually' => 12,
                default => 1,
            };
            $renewalAmount = $plan->price * $multiplier;

            // Calculate next due date based on billing cycle
            $nextDueAt = match($billingCycle) {
                'monthly' => now()->addMonth(),
                'quarterly' => now()->addMonths(3),
                'annually' => now()->addYear(),
                default => now()->addMonth(),
            };

            Log::info('Processing renewal', [
                'order_id' => $cancelledOrder->id,
                'billing_cycle' => $billingCycle,
                'amount' => $renewalAmount,
                'next_due_at' => $nextDueAt,
                'payment_method' => $request->payment_method
            ]);

            // Process payment first
            $paymentMethod = $request->payment_method;
            $paymentData = [];

            // Handle wallet payments immediately
            if ($paymentMethod === 'wallet') {
                $settings = $this->shopConfigService->getShopConfig();
                if (!($settings['credits_enabled'] ?? false)) {
                    return $this->errorResponse('Wallet payments are disabled.');
                }

                $userWallet = $this->walletService->getWallet($user->id);
                if ($userWallet->balance < $renewalAmount) {
                    return $this->errorResponse('Insufficient wallet balance.');
                }

                // Deduct from wallet
                $transaction = $this->walletService->deductFunds(
                    $userWallet, 
                    $renewalAmount, 
                    "Renewal payment for server {$cancelledOrder->server->uuidShort}", 
                    'order_payment'
                );

                if (!$transaction) {
                    return $this->errorResponse('Failed to process wallet payment.');
                }

                // Mark the order as paid first
                $this->orderService->markAsPaid($cancelledOrder->id, 'wallet');

                // Then reactivate the order immediately
                $this->reactivateOrder($cancelledOrder, $billingCycle, $nextDueAt, $renewalAmount);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Server plan renewed successfully!',
                    'redirect' => route('shop.orders.show', $cancelledOrder->uuid)
                ]);

            } else {
                // For external payment methods (Stripe/PayPal), create payment session
                $paymentResult = $this->processRenewalPayment($cancelledOrder, $paymentMethod, $renewalAmount, $billingCycle, $nextDueAt);
                
                if (!$paymentResult['success']) {
                    return $this->errorResponse($paymentResult['message']);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'redirect' => $paymentResult['redirect_url']
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Renewal processing error', [
                'order_id' => $request->renewal_order_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Renewal processing failed. Please try again.');
        }
    }

    /**
     * Reactivate cancelled order
     */
    private function reactivateOrder(ShopOrder $order, string $billingCycle, $nextDueAt, float $amount): void
    {
        // Update the order with new billing information
        $order->update([
            'status' => ShopOrder::STATUS_ACTIVE,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'next_due_at' => $nextDueAt,
            'last_renewed_at' => now(),
            'auto_delete_at' => null, // Clear auto-deletion date
            'suspended_at' => null,
            'cancelled_at' => null,
        ]);

        Log::info('Order reactivated', [
            'order_id' => $order->id,
            'server_id' => $order->server_id,
            'billing_cycle' => $billingCycle,
            'next_due_at' => $nextDueAt
        ]);
    }

    /**
     * Process renewal payment for external gateways
     */
    private function processRenewalPayment(ShopOrder $order, string $paymentMethod, float $amount, string $billingCycle, $nextDueAt): array
    {
        try {
            // Create a temporary renewal data session to handle callback
            session([
                'renewal_data' => [
                    'order_id' => $order->id,
                    'billing_cycle' => $billingCycle,
                    'next_due_at' => $nextDueAt->toISOString(),
                    'amount' => $amount
                ]
            ]);

            // Use the existing payment processing logic but with renewal context
            $paymentData = [
                'amount' => $amount,
                'currency' => config('shop.currency', 'USD'),
                'description' => "Renewal for server {$order->server->uuidShort} - {$order->plan->name}",
                'metadata' => [
                    'type' => 'renewal',
                    'order_id' => $order->id,
                    'server_uuid' => $order->server->uuid,
                    'billing_cycle' => $billingCycle
                ]
            ];

            if ($paymentMethod === 'stripe') {
                return $this->processStripeRenewalPayment($order, $paymentData);
            } elseif ($paymentMethod === 'paypal') {
                return $this->processPayPalRenewalPayment($order, $paymentData);
            }

            return ['success' => false, 'message' => 'Invalid payment method'];

        } catch (\Exception $e) {
            Log::error('Renewal payment processing error', [
                'order_id' => $order->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'Payment processing failed'];
        }
    }

    /**
     * Process Stripe renewal payment
     */
    private function processStripeRenewalPayment(ShopOrder $order, array $paymentData): array
    {
        // Temporarily update order amount for renewal (no setup fees)
        $originalAmount = $order->amount;
        $originalSetupFee = $order->setup_fee;
        
        Log::info('Stripe renewal payment setup', [
            'order_id' => $order->id,
            'original_amount' => $originalAmount,
            'original_setup_fee' => $originalSetupFee,
            'renewal_amount' => $paymentData['amount']
        ]);
        
        // Set renewal amount and clear setup fee
        $order->amount = $paymentData['amount'];
        $order->setup_fee = 0;
        
        try {
            // Process payment with updated amounts
            $stripeResult = $this->processStripePayment($order, $paymentData);
            
            return $stripeResult;
        } finally {
            // Restore original amounts regardless of success/failure
            $order->amount = $originalAmount;
            $order->setup_fee = $originalSetupFee;
        }
    }

    /**
     * Process PayPal renewal payment  
     */
    private function processPayPalRenewalPayment(ShopOrder $order, array $paymentData): array
    {
        // Temporarily update order amount for renewal (no setup fees)
        $originalAmount = $order->amount;
        $originalSetupFee = $order->setup_fee;
        
        Log::info('PayPal renewal payment setup', [
            'order_id' => $order->id,
            'original_amount' => $originalAmount,
            'original_setup_fee' => $originalSetupFee,
            'renewal_amount' => $paymentData['amount']
        ]);
        
        // Set renewal amount and clear setup fee
        $order->amount = $paymentData['amount'];
        $order->setup_fee = 0;
        
        try {
            // Process payment with updated amounts
            $paypalResult = $this->processPayPalPayment($order, $paymentData);
            
            return $paypalResult;
        } finally {
            // Restore original amounts regardless of success/failure
            $order->amount = $originalAmount;
            $order->setup_fee = $originalSetupFee;
        }
    }
}
