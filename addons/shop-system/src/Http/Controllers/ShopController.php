<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Repositories\ShopCategoryRepository;
use PterodactylAddons\ShopSystem\Repositories\ShopPlanRepository;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;

class ShopController extends Controller
{
    public function __construct(
        private ShopCategoryRepository $categoryRepository,
        private ShopPlanRepository $planRepository,
        private ShopOrderService $orderService
    ) {}

    /**
     * Display the main shop catalog.
     */
    public function index(Request $request): View
    {
        if (!config('shop.enabled')) {
            abort(503, config('shop.maintenance_message', 'Shop is temporarily unavailable.'));
        }

        // Handle search
        if ($request->filled('search')) {
            $products = $this->categoryRepository->searchCategories($request->search, 12);
        } else {
            $products = $this->categoryRepository->getVisibleCategories(12);
        }
        
        $categories = $this->categoryRepository->getCategoryNames();
        $featured = $this->categoryRepository->getFeaturedCategories(5);

        return view('shop::catalog.index', compact('products', 'categories', 'featured'));
    }

    /**
     * Display a specific category and its plans.
     */
    public function showCategory(ShopCategory $category): View
    {
        if (!$category->active) {
            abort(404);
        }

        $plans = $this->planRepository->getByCategory($category->id, onlyEnabled: true);
        $relatedCategories = $this->categoryRepository->getRelatedCategories($category, limit: 4);

        return view('shop::catalog.category', compact('category', 'plans', 'relatedCategories'));
    }

    /**
     * Display a specific category and its plans (legacy method for backward compatibility).
     */
    public function showProduct(ShopCategory $product): View
    {
        if (!$product->active) {
            abort(404);
        }

        $plans = $this->planRepository->getByCategory($product->id, onlyEnabled: true);
        $relatedProducts = $this->categoryRepository->getRelatedCategories($product, limit: 4);

        return view('shop::catalog.category', [
            'category' => $product,
            'plans' => $plans,
            'relatedCategories' => $relatedProducts
        ]);
    }

    /**
     * Display a specific plan details page.
     */
    public function showPlan(ShopPlan $plan): View
    {
        if (!$plan->isAvailable()) {
            abort(404, 'Plan not available');
        }

        // Get the category this plan belongs to
        $category = $plan->category;
        
        // Get related plans from the same category
        $relatedPlans = $this->planRepository->getByCategory($category->id, onlyEnabled: true)
            ->where('id', '!=', $plan->id)
            ->take(3);

        return view('shop::catalog.plan', [
            'plan' => $plan,
            'category' => $category,
            'relatedPlans' => $relatedPlans
        ]);
    }

    /**
     * Get category plans via AJAX.
     */
    public function getCategoryPlans(ShopCategory $category, Request $request): JsonResponse
    {
        $nodeId = $request->integer('node_id');
        $locationId = $request->integer('location_id');

        $plansQuery = $this->planRepository->getBuilder()
            ->where('category_id', $category->id)
            ->where('status', 'active');

        if ($nodeId) {
            $plansQuery->where(function ($query) use ($nodeId) {
                $query->whereNull('allowed_nodes')
                      ->orWhereJsonContains('allowed_nodes', $nodeId);
            });
        }

        if ($locationId) {
            $plansQuery->where(function ($query) use ($locationId) {
                $query->whereNull('allowed_locations')
                      ->orWhereJsonContains('allowed_locations', $locationId);
            });
        }

        $plans = $plansQuery->orderBy('sort_order')->orderBy('price_monthly')->get();

        return response()->json([
            'success' => true,
            'plans' => $plans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price_monthly' => $plan->price_monthly,
                    'price_hourly' => $plan->price_hourly,
                    'setup_fee' => $plan->setup_fee,
                    'memory' => $plan->memory,
                    'disk' => $plan->disk,
                    'cpu' => $plan->cpu,
                    'available' => $plan->stock_limit ? $plan->stock_limit > 0 : true,
                ];
            }),
        ]);
    }



    /**
     * Display the cart/checkout page.
     */
    public function cart(): View
    {
        $cart = session('shop_cart', []);
        $cartItems = [];
        $total = 0;

        foreach ($cart as $item) {
            $plan = $this->planRepository->find($item['plan_id']);
            if ($plan && $plan->isAvailable()) {
                $cartItem = [
                    'plan' => $plan,
                    'quantity' => $item['quantity'],
                    'subtotal' => $plan->price * $item['quantity'],
                    'setup_fee' => $plan->setup_fee * $item['quantity'],
                ];
                $cartItems[] = $cartItem;
                $total += $cartItem['subtotal'] + $cartItem['setup_fee'];
            }
        }

        return view('shop::cart.index', compact('cartItems', 'total'));
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'integer|min:1|max:10',
        ]);

        $plan = $this->planRepository->find($request->plan_id);
        
        if (!$plan || !$plan->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not available.',
            ], 400);
        }

        $cart = session('shop_cart', []);
        $planId = $request->plan_id;
        $quantity = $request->integer('quantity', 1);

        // Check if item already in cart
        $existingIndex = collect($cart)->search(function ($item) use ($planId) {
            return $item['plan_id'] == $planId;
        });

        if ($existingIndex !== false) {
            $cart[$existingIndex]['quantity'] += $quantity;
        } else {
            $cart[] = [
                'plan_id' => $planId,
                'quantity' => $quantity,
                'added_at' => now()->timestamp,
            ];
        }

        session(['shop_cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully.',
            'cart_count' => collect($cart)->sum('quantity'),
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
        ]);

        $cart = session('shop_cart', []);
        $planId = $request->plan_id;

        $cart = collect($cart)->filter(function ($item) use ($planId) {
            return $item['plan_id'] != $planId;
        })->values()->all();

        session(['shop_cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => collect($cart)->sum('quantity'),
        ]);
    }

    /**
     * Update cart item quantity.
     */
    public function updateCartQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'required|integer|min:1|max:10',
        ]);

        $cart = session('shop_cart', []);
        $planId = $request->plan_id;
        $quantity = $request->quantity;

        foreach ($cart as &$item) {
            if ($item['plan_id'] == $planId) {
                $item['quantity'] = $quantity;
                break;
            }
        }

        session(['shop_cart' => $cart]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
        ]);
    }

    /**
     * Clear entire cart.
     */
    public function clearCart(): JsonResponse
    {
        session()->forget('shop_cart');

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }

    /**
     * Get cart summary for AJAX requests.
     */
    public function getCartSummary(): JsonResponse
    {
        $cart = session('shop_cart', []);
        $itemCount = collect($cart)->sum('quantity');
        
        $total = 0;
        foreach ($cart as $item) {
            $plan = $this->planRepository->find($item['plan_id']);
            if ($plan) {
                $total += ($plan->price + $plan->setup_fee) * $item['quantity'];
            }
        }

        return response()->json([
            'success' => true,
            'cart_count' => $itemCount,
            'total' => $total,
            'formatted_total' => '$' . number_format($total, 2),
        ]);
    }

    /**
     * Apply promo code to cart.
     */
    public function applyPromoCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = $request->input('code');
        
        // For now, return a simple response since coupon system would need more implementation
        // This prevents the route error while maintaining cart functionality
        if (in_array(strtoupper($code), ['TEST', 'DEMO', 'SAVE10'])) {
            session(['shop_promo_code' => $code]);
            
            return response()->json([
                'success' => true,
                'message' => 'Promo code applied successfully!',
                'discount' => 10.00,
                'code' => $code,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid promo code.',
        ], 400);
    }

    /**
     * Remove promo code from cart.
     */
    public function removePromoCode(): JsonResponse
    {
        session()->forget('shop_promo_code');
        
        return response()->json([
            'success' => true,
            'message' => 'Promo code removed successfully!',
        ]);
    }
}
