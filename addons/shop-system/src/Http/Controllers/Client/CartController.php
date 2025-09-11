<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCartItem;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\UserWallet;

class CartController extends Controller
{
    /**
     * Display the shopping cart
     */
    public function index()
    {
        $cartItems = ShopCartItem::where('user_id', auth()->id())
            ->with('product')
            ->get();
        
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $tax = $subtotal * (config('shop.tax_rate', 0) / 100);
        $total = $subtotal + $tax;
        
        return view('client.shop.cart', compact('cartItems', 'wallet', 'subtotal', 'tax', 'total'));
    }

    /**
     * Add product to cart
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'integer|min:1|max:10'
        ]);
        
        $plan = ShopPlan::findOrFail($request->plan_id);
        
        if ($plan->status !== 'active') {
            return response()->json(['error' => 'Plan is not available'], 400);
        }
        
        // Check if user already has an active order for this product (if it's a server/recurring product)
        if ($product->type === 'server' && $this->hasActiveOrder($product->id)) {
            return response()->json(['error' => 'You already have an active order for this product'], 400);
        }
        
        $quantity = $request->get('quantity', 1);
        
        // Check if item already in cart
        $cartItem = ShopCartItem::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();
        
        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $quantity;
            $cartItem->price = $product->price; // Update price in case it changed
            $cartItem->save();
        } else {
            // Create new cart item
            ShopCartItem::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully',
            'cart_count' => $this->getCartCount()
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'item_id' => 'required|exists:shop_cart_items,id',
            'quantity' => 'required|integer|min:1|max:10'
        ]);
        
        $cartItem = ShopCartItem::where('id', $request->item_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        
        // Check stock if applicable
        $product = $cartItem->product;
        if ($product->stock_enabled && $product->stock_quantity < $request->quantity) {
            return response()->json(['error' => 'Insufficient stock available'], 400);
        }
        
        $cartItem->update([
            'quantity' => $request->quantity,
            'price' => $product->price, // Update price in case it changed
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'item_total' => $cartItem->quantity * $cartItem->price,
            'cart_totals' => $this->getCartTotals()
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove(ShopCartItem $item): JsonResponse
    {
        if ($item->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $item->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart_count' => $this->getCartCount(),
            'cart_totals' => $this->getCartTotals()
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear(): JsonResponse
    {
        ShopCartItem::where('user_id', auth()->id())->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
            'cart_count' => 0
        ]);
    }

    /**
     * Get cart count for navbar display
     */
    public function getCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->getCartCount()
        ]);
    }

    /**
     * Get cart items for dropdown/sidebar
     */
    public function getItems(): JsonResponse
    {
        $cartItems = ShopCartItem::where('user_id', auth()->id())
            ->with('product')
            ->get();
        
        return response()->json([
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'total' => (float) ($item->quantity * $item->price),
                ];
            }),
            'count' => $cartItems->count(),
            'totals' => $this->getCartTotals()
        ]);
    }

    /**
     * Check if user has active order for product
     */
    protected function hasActiveOrder(int $productId): bool
    {
        return \PterodactylAddons\ShopSystem\Models\ShopOrder::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->whereIn('status', ['active', 'pending', 'processing'])
            ->exists();
    }

    /**
     * Get total items in cart
     */
    protected function getCartCount(): int
    {
        return ShopCartItem::where('user_id', auth()->id())->sum('quantity');
    }

    /**
     * Get cart totals
     */
    protected function getCartTotals(): array
    {
        $cartItems = ShopCartItem::where('user_id', auth()->id())->get();
        
        $subtotal = $cartItems->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $tax = $subtotal * (config('shop.tax_rate', 0) / 100);
        $total = $subtotal + $tax;
        
        return [
            'subtotal' => (float) $subtotal,
            'tax' => (float) $tax,
            'total' => (float) $total,
        ];
    }
}
