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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        private ShopPlanRepository $planRepository,
        private ShopCouponRepository $couponRepository,
        private ShopOrderService $orderService,
        private PaymentGatewayManager $paymentManager,
        private WalletService $walletService,
        private CartService $cartService,
        private ShopConfigService $shopConfig
    ) {}

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
        $userWallet = $this->walletService->getWallet(auth()->id());

        return view('shop::checkout.index', compact(
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
        
        $discount = 0; // TODO: Add coupon/discount logic
        $tax = 0; // TODO: Add tax calculation if needed
        $total = $subtotal + $setupTotal - $discount + $tax;

        $response = [
            'success' => true,
            'summary' => [
                'items' => $summary['items'],
                'subtotal' => $subtotal,
                'setup_total' => $setupTotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total
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
        $request->validate([
            'payment_method' => 'required|string|in:stripe,paypal,wallet',
            'billing_details' => 'sometimes|array',
            'billing_details.name' => 'sometimes|string|max:255',
            'billing_details.email' => 'sometimes|email|max:255',
            'billing_details.address' => 'sometimes|string|max:255',
            'billing_details.city' => 'sometimes|string|max:100',
            'billing_details.country' => 'sometimes|string|size:2',
            'billing_details.postal_code' => 'sometimes|string|max:20',
        ]);

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

            // Create the order
            $order = $this->orderService->createOrder([
                'user_id' => auth()->id(),
                'items' => $cartItems,
                'totals' => $totals,
                'coupon' => $coupon,
                'billing_details' => $request->input('billing_details', []),
                'payment_method' => $request->payment_method,
            ]);

            // Process payment
            $paymentResult = $this->processPayment($order, $request->payment_method, $request->all());

            if ($paymentResult['success']) {
                // Clear cart and coupon
                session()->forget(['shop_cart', 'applied_coupon']);

                DB::commit();

                $this->logActivity('Order created and payment processed', $order, [
                    'order_id' => $order->id,
                    'total' => $totals['total'],
                    'payment_method' => $request->payment_method,
                ]);

                return $this->successResponse([
                    'order_uuid' => $order->uuid,
                    'redirect_url' => $paymentResult['redirect_url'] ?? route('shop.orders.show', $order->uuid),
                    'requires_payment_action' => $paymentResult['requires_action'] ?? false,
                    'payment_intent' => $paymentResult['payment_intent'] ?? null,
                ], 'Order created successfully!');
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
            $subtotal += $item['subtotal'];
            $setupTotal += $item['setup_total'];
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
        $settings = $this->shopConfig->getShopConfig();
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
        $result = $this->walletService->debit(
            userId: $order->user_id,
            amount: $order->amount,
            description: "Order #{$order->id} payment",
            relatedType: get_class($order),
            relatedId: $order->id
        );

        if ($result) {
            $this->orderService->markAsPaid($order->id, 'wallet');
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Insufficient wallet balance.'];
    }

    /**
     * Process Stripe payment.
     */
    private function processStripePayment($order, array $paymentData): array
    {
        $gateway = $this->paymentManager->getGateway('stripe');
        
        return $gateway->createPayment([
            'amount' => $order->amount,
            'currency' => $order->currency,
            'description' => "Order #{$order->id}",
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
            'payment_method_data' => $paymentData['payment_method_data'] ?? [],
        ]);
    }

    /**
     * Process PayPal payment.
     */
    private function processPayPalPayment($order, array $paymentData): array
    {
        $gateway = $this->paymentManager->getGateway('paypal');
        
        return $gateway->createPayment([
            'amount' => $order->amount,
            'currency' => $order->currency,
            'description' => "Order #{$order->id}",
            'return_url' => route('shop.orders.show', $order->uuid),
            'cancel_url' => route('shop.cart'),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
        ]);
    }
}
