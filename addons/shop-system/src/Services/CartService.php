<?php

namespace PterodactylAddons\ShopSystem\Services;

use Illuminate\Support\Facades\Auth;
use PterodactylAddons\ShopSystem\Models\ShopCart;
use PterodactylAddons\ShopSystem\Models\ShopCartItem;
use PterodactylAddons\ShopSystem\Models\ShopPlan;

class CartService
{
    /**
     * Get or create cart for current user.
     */
    public function getCart(): ShopCart
    {
        return ShopCart::firstOrCreate([
            'user_id' => Auth::id(),
            'status' => 'active',
        ], [
            'total_amount' => 0,
            'expires_at' => now()->addDays(7), // Cart expires in 7 days
        ]);
    }

    /**
     * Add item to cart.
     */
    public function addItem(int $planId, int $quantity = 1, string $billingCycle = 'monthly'): bool
    {
        $plan = ShopPlan::find($planId);
        if (!$plan || !$plan->isAvailable()) {
            return false;
        }

        $cart = $this->getCart();
        
        // Get price for selected billing cycle
        $billingCycles = $plan->billing_cycles ?? [];
        $cycleData = collect($billingCycles)->firstWhere('cycle', $billingCycle) ?? $billingCycles[0] ?? null;
        
        if (!$cycleData) {
            return false;
        }

        $unitPrice = (float) $cycleData['price'];
        $setupFee = (float) ($cycleData['setup_fee'] ?? 0);
        $totalUnitPrice = $unitPrice + $setupFee;

        // Check if item already exists in cart
        $existingItem = $cart->items()->where('plan_id', $planId)->first();
        
        if ($existingItem) {
            // Update existing item
            $existingItem->quantity += $quantity;
            $existingItem->total_price = $existingItem->quantity * $totalUnitPrice;
            $existingItem->save();
        } else {
            // Create new item
            $cart->items()->create([
                'plan_id' => $planId,
                'quantity' => $quantity,
                'unit_price' => $totalUnitPrice,
                'total_price' => $quantity * $totalUnitPrice,
                'plan_options' => [
                    'billing_cycle' => $billingCycle,
                    'plan_name' => $plan->name,
                ],
                'server_config' => null,
            ]);
        }

        $this->updateCartTotal($cart);
        return true;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(int $planId): bool
    {
        $cart = $this->getCart();
        $item = $cart->items()->where('plan_id', $planId)->first();
        
        if ($item) {
            $item->delete();
            $this->updateCartTotal($cart);
            return true;
        }
        
        return false;
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity(int $planId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($planId);
        }

        $cart = $this->getCart();
        $item = $cart->items()->where('plan_id', $planId)->first();
        
        if ($item) {
            $item->updateQuantity($quantity);
            $this->updateCartTotal($cart);
            return true;
        }
        
        return false;
    }

    /**
     * Clear cart.
     */
    public function clearCart(): void
    {
        $cart = $this->getCart();
        $cart->items()->delete();
        $cart->total_amount = 0;
        $cart->save();
    }

    /**
     * Get cart summary.
     */
    public function getCartSummary(): array
    {
        $cart = $this->getCart();
        $items = [];
        $total = 0;
        $itemCount = 0;

        foreach ($cart->items as $item) {
            $plan = ShopPlan::find($item->plan_id);
            if ($plan) {
                $options = $item->plan_options ?? [];
                $billingCycle = $options['billing_cycle'] ?? 'monthly';
                
                // Get current price data (in case prices changed)
                $billingCycles = $plan->billing_cycles ?? [];
                $cycleData = collect($billingCycles)->firstWhere('cycle', $billingCycle) ?? $billingCycles[0] ?? null;
                
                if ($cycleData) {
                    $currentPrice = (float) $cycleData['price'];
                    $currentSetupFee = (float) ($cycleData['setup_fee'] ?? 0);
                    
                    $items[] = [
                        'plan_id' => $plan->id,
                        'quantity' => $item->quantity,
                        'billing_cycle' => $billingCycle,
                        'plan' => [
                            'id' => $plan->id,
                            'name' => $plan->name,
                            'description' => $plan->description,
                            'price' => $currentPrice,
                            'setup_fee' => $currentSetupFee,
                            'billing_cycle' => $billingCycle,
                            'billing_cycles' => $billingCycles,
                            'features' => $plan->server_feature_limits ?? [],
                            'resources' => [
                                'cpu' => $plan->cpu,
                                'memory' => $plan->memory,
                                'disk' => $plan->disk,
                                'swap' => $plan->swap,
                                'io' => $plan->io,
                            ],
                        ],
                        'subtotal' => $item->total_price,
                    ];
                    
                    $total += $item->total_price;
                    $itemCount += $item->quantity;
                }
            }
        }

        // Get applied coupon info
        $appliedCoupon = $this->getAppliedCouponInfo($total);
        $discount = $appliedCoupon ? $appliedCoupon['discount_amount'] : 0;
        
        // Calculate tax (if configured)
        $taxRate = config('shop.tax_rate', 0);
        $discountedSubtotal = $total - $discount;
        $tax = $discountedSubtotal * ($taxRate / 100);
        $finalTotal = $discountedSubtotal + $tax;

        return [
            'success' => true,
            'items' => $items,
            'cart_count' => $itemCount,
            'subtotal' => $total,
            'setup_total' => 0, // TODO: Add setup fee calculation if needed
            'discount' => $discount,
            'tax' => $tax,
            'total' => $finalTotal,
            'formatted_total' => '$' . number_format($finalTotal, 2),
            'applied_coupon' => $appliedCoupon,
        ];
    }

    /**
     * Get cart items for dropdown/API display.
     */
    public function getCartItems(): array
    {
        $cart = $this->getCart();
        $items = [];
        $total = 0;
        $itemCount = 0;

        foreach ($cart->items as $item) {
            $plan = ShopPlan::find($item->plan_id);
            if ($plan) {
                $options = $item->plan_options ?? [];
                $billingCycle = $options['billing_cycle'] ?? 'monthly';
                
                // Get current price data
                $billingCycles = $plan->billing_cycles ?? [];
                $cycleData = collect($billingCycles)->firstWhere('cycle', $billingCycle) ?? $billingCycles[0] ?? null;
                
                if ($cycleData) {
                    $currentPrice = (float) $cycleData['price'];
                    
                    $items[] = [
                        'id' => $item->id,
                        'plan_id' => $plan->id,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'price' => $currentPrice,
                        'quantity' => $item->quantity,
                        'billing_cycle' => $billingCycle,
                        'total' => $item->total_price,
                    ];
                    
                    $total += $item->total_price;
                    $itemCount += $item->quantity;
                }
            }
        }

        // Calculate tax
        $taxRate = config('shop.tax_rate', 0);
        $tax = $total * ($taxRate / 100);
        $finalTotal = $total + $tax;

        return [
            'items' => $items,
            'total_quantity' => $itemCount,
            'subtotal' => $total,
            'tax' => $tax,
            'total' => $finalTotal,
        ];
    }

    /**
     * Get applied coupon information with calculated discount.
     */
    private function getAppliedCouponInfo(float $cartTotal): ?array
    {
        $couponCode = session('applied_coupon');
        if (!$couponCode) {
            return null;
        }

        $coupon = \PterodactylAddons\ShopSystem\Models\ShopCoupon::where('code', $couponCode)
            ->where('active', true)
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            // Coupon is no longer valid, remove from session
            session()->forget('applied_coupon');
            return null;
        }

        $discount = $coupon->calculateDiscount($cartTotal);
        $newTotal = max(0, $cartTotal - $discount);

        return [
            'code' => $coupon->code,
            'description' => $coupon->description,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount_amount' => $discount,
            'formatted_discount' => '$' . number_format($discount, 2),
            'new_total' => $newTotal,
            'formatted_new_total' => '$' . number_format($newTotal, 2),
        ];
    }

    /**
     * Update cart total amount.
     */
    private function updateCartTotal(ShopCart $cart): void
    {
        $cart->total_amount = $cart->calculateTotal();
        $cart->save();
    }
}
