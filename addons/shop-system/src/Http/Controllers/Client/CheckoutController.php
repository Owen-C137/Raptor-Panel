<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCartItem;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Services\OrderService;
use PterodactylAddons\ShopSystem\Services\PaymentService;
use Carbon\Carbon;

class CheckoutController extends Controller
{
    protected OrderService $orderService;
    protected PaymentService $paymentService;

    public function __construct(OrderService $orderService, PaymentService $paymentService)
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    /**
     * Display the checkout page
     */
    public function index()
    {
        $cartItems = ShopCartItem::where('user_id', auth()->id())
            ->with('product')
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.cart')
                ->with('error', 'Your cart is empty');
        }
        
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $taxRate = config('shop.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;
        
        // Get available payment methods
        $paymentMethods = $this->getAvailablePaymentMethods();
        
        return view('client.shop.checkout', compact(
            'cartItems', 
            'wallet', 
            'subtotal', 
            'tax', 
            'total',
            'taxRate',
            'paymentMethods'
        ));
    }

    /**
     * Process the checkout
     */
    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required|in:wallet,stripe,paypal',
            'coupon_code' => 'nullable|string',
        ]);
        
        $cartItems = ShopCartItem::where('user_id', auth()->id())
            ->with('product')
            ->get();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.cart')
                ->with('error', 'Your cart is empty');
        }
        
        try {
            // Calculate totals
            $subtotal = $cartItems->sum(function ($item) {
                return $item->quantity * $item->price;
            });
            
            $discount = 0;
            $couponId = null;
            
            // Apply coupon if provided
            if ($request->coupon_code) {
                $coupon = $this->validateCoupon($request->coupon_code, $subtotal);
                $discount = $this->calculateDiscount($coupon, $subtotal);
                $couponId = $coupon->id;
            }
            
            $taxRate = config('shop.tax_rate', 0);
            $discountedSubtotal = $subtotal - $discount;
            $tax = $discountedSubtotal * ($taxRate / 100);
            $total = $discountedSubtotal + $tax;
            
            // Process each cart item as separate order (or combine if preferred)
            $orders = [];
            
            foreach ($cartItems as $cartItem) {
                $itemTotal = ($cartItem->quantity * $cartItem->price);
                $itemDiscount = $discount > 0 ? ($itemTotal / $subtotal) * $discount : 0;
                $itemTax = ($itemTotal - $itemDiscount) * ($taxRate / 100);
                $itemFinalTotal = $itemTotal - $itemDiscount + $itemTax;
                
                // Create order
                $order = ShopOrder::create([
                    'user_id' => auth()->id(),
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'subtotal' => $itemTotal,
                    'tax_amount' => $itemTax,
                    'discount_amount' => $itemDiscount,
                    'total_amount' => $itemFinalTotal,
                    'coupon_id' => $couponId,
                    'status' => 'pending',
                    'billing_cycle' => $cartItem->product->billing_cycle,
                    'next_billing_date' => $this->calculateNextBillingDate($cartItem->product),
                ]);
                
                $orders[] = $order;
            }
            
            // Process payment
            if ($request->payment_method === 'wallet') {
                $this->processWalletPayment($orders, $total);
            } else {
                return $this->processGatewayPayment($orders, $request->payment_method, $total);
            }
            
            // Clear cart after successful payment
            ShopCartItem::where('user_id', auth()->id())->delete();
            
            // Record coupon usage
            if ($couponId) {
                $this->recordCouponUsage($couponId);
            }
            
            return redirect()->route('shop.checkout.success')
                ->with('success', 'Payment processed successfully! Your order is being activated.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Display checkout success page
     */
    public function success(Request $request)
    {
        $recentOrders = ShopOrder::where('user_id', auth()->id())
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->with('product')
            ->get();
        
        return view('client.shop.checkout.success', compact('recentOrders'));
    }

    /**
     * Display checkout cancel page
     */
    public function cancel(Request $request)
    {
        return view('client.shop.checkout.cancel');
    }

    /**
     * Apply coupon code
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string'
        ]);
        
        try {
            $cartItems = ShopCartItem::where('user_id', auth()->id())->get();
            $subtotal = $cartItems->sum(function ($item) {
                return $item->quantity * $item->price;
            });
            
            $coupon = $this->validateCoupon($request->coupon_code, $subtotal);
            $discount = $this->calculateDiscount($coupon, $subtotal);
            
            return response()->json([
                'success' => true,
                'discount' => $discount,
                'coupon_code' => $coupon->code,
                'message' => 'Coupon applied successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Validate coupon code
     */
    protected function validateCoupon(string $code, float $subtotal): ShopCoupon
    {
        $coupon = ShopCoupon::where('code', $code)
            ->where('status', 'active')
            ->first();
        
        if (!$coupon) {
            throw new \Exception('Invalid coupon code');
        }
        
        if ($coupon->starts_at && $coupon->starts_at > now()) {
            throw new \Exception('Coupon is not yet active');
        }
        
        if ($coupon->expires_at && $coupon->expires_at < now()) {
            throw new \Exception('Coupon has expired');
        }
        
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            throw new \Exception('Coupon usage limit reached');
        }
        
        if ($coupon->minimum_amount && $subtotal < $coupon->minimum_amount) {
            throw new \Exception('Order does not meet coupon minimum amount');
        }
        
        return $coupon;
    }

    /**
     * Calculate discount amount
     */
    protected function calculateDiscount(ShopCoupon $coupon, float $subtotal): float
    {
        if ($coupon->type === 'percentage') {
            $discount = $subtotal * ($coupon->value / 100);
        } else {
            $discount = $coupon->value;
        }
        
        // Apply maximum discount limit if set
        if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
            $discount = $coupon->max_discount_amount;
        }
        
        return min($discount, $subtotal);
    }

    /**
     * Process wallet payment
     */
    protected function processWalletPayment(array $orders, float $total): void
    {
        $wallet = UserWallet::where('user_id', auth()->id())->first();
        
        if (!$wallet || $wallet->balance < $total) {
            throw new \Exception('Insufficient wallet balance');
        }
        
        // Deduct from wallet
        $wallet->decrement('balance', $total);
        
        // Activate orders
        foreach ($orders as $order) {
            $this->orderService->activateOrder($order);
        }
    }

    /**
     * Process gateway payment (Stripe/PayPal)
     */
    protected function processGatewayPayment(array $orders, string $method, float $total)
    {
        // This would redirect to payment gateway or return payment intent
        // Implementation depends on specific gateway
        
        return redirect()->route('shop.checkout.success')
            ->with('info', 'Payment processing...');
    }

    /**
     * Get available payment methods
     */
    protected function getAvailablePaymentMethods(): array
    {
        $methods = ['wallet'];
        
        if (config('shop.stripe_enabled')) {
            $methods[] = 'stripe';
        }
        
        if (config('shop.paypal_enabled')) {
            $methods[] = 'paypal';
        }
        
        return $methods;
    }

    /**
     * Calculate next billing date
     */
    protected function calculateNextBillingDate($product): ?Carbon
    {
        if ($product->billing_cycle === 'one_time') {
            return null;
        }
        
        $interval = match($product->billing_cycle) {
            'hourly' => 'hour',
            'daily' => 'day',
            'weekly' => 'week',
            'monthly' => 'month',
            'quarterly' => 'month',
            'yearly' => 'year',
            default => 'month'
        };
        
        $amount = $product->billing_cycle === 'quarterly' ? 3 : 1;
        
        return Carbon::now()->add($amount, $interval);
    }

    /**
     * Record coupon usage
     */
    protected function recordCouponUsage(int $couponId): void
    {
        \PterodactylAddons\ShopSystem\Models\ShopCouponUsage::create([
            'coupon_id' => $couponId,
            'user_id' => auth()->id(),
            'used_at' => now(),
        ]);
        
        ShopCoupon::where('id', $couponId)->increment('usage_count');
    }
}
