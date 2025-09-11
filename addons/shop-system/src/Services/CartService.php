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

        return [
            'success' => true,
            'items' => $items,
            'cart_count' => $itemCount,
            'total' => $total,
            'formatted_total' => '$' . number_format($total, 2),
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
