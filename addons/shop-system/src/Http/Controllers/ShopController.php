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
use PterodactylAddons\ShopSystem\Services\CartService;
use Pterodactyl\Models\Location;
use Pterodactyl\Models\Node;

class ShopController extends BaseShopController
{
    public function __construct(
        private ShopCategoryRepository $categoryRepository,
        private ShopPlanRepository $planRepository,
        private ShopOrderService $orderService,
        private CartService $cartService
    ) {
        parent::__construct();
    }

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

        return $this->view('shop::catalog.index', compact('products', 'categories', 'featured'));
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
        $cartData = $this->cartService->getCartSummary();
        return view('shop::cart.index', $cartData);
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'integer|min:1|max:10',
            'billing_cycle' => 'string|in:monthly,quarterly,semi-annually,annually',
        ]);

        $success = $this->cartService->addItem(
            $request->plan_id,
            $request->integer('quantity', 1),
            $request->string('billing_cycle', 'monthly')
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'This plan is not available.',
            ], 400);
        }

        $cartData = $this->cartService->getCartSummary();

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully.',
            'cart_count' => $cartData['cart_count'],
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

        $success = $this->cartService->removeItem($request->plan_id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found in cart.',
            ], 404);
        }

        $cartData = $this->cartService->getCartSummary();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart.',
            'cart_count' => $cartData['cart_count'],
        ]);
    }

        /**
     * Update cart item quantity.
     */
    public function updateCartQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:shop_plans,id',
            'quantity' => 'required|integer|min:0|max:10',
        ]);

        $success = $this->cartService->updateQuantity($request->plan_id, $request->quantity);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item.',
            ], 400);
        }

        $cartData = $this->cartService->getCartSummary();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully.',
            'cart_count' => $cartData['cart_count'],
        ]);
    }

    /**
     * Clear all items from cart.
     */
    public function clearCart(): JsonResponse
    {
        $this->cartService->clearCart();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully.',
            'cart_count' => 0,
        ]);
    }

    /**
     * Get cart summary for AJAX requests.
     */
    public function getCartSummary(): JsonResponse
    {
        return response()->json($this->cartService->getCartSummary());
    }

    /**
     * Get cart items for dropdown/AJAX requests.
     */
    public function getCartItems(): JsonResponse
    {
        $cartData = $this->cartService->getCartItems();
        
        if (empty($cartData['items'])) {
            return response()->json([
                'success' => true,
                'cart' => [
                    'items' => [],
                    'total_items' => 0,
                    'total_amount' => '0.00',
                    'currency_symbol' => config('shop.currency_symbol', '$')
                ]
            ]);
        }
        
        return response()->json([
            'success' => true,
            'cart' => [
                'items' => $cartData['items'],
                'total_items' => $cartData['total_quantity'],
                'total_amount' => number_format($cartData['total'], 2),
                'subtotal' => number_format($cartData['subtotal'], 2),
                'tax' => number_format($cartData['tax'], 2),
                'currency_symbol' => config('shop.currency_symbol', '$')
            ]
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

    /**
     * Get server locations for API calls.
     */
    public function getLocations(): JsonResponse
    {
        try {
            // Get locations from Pterodactyl database
            $locations = Location::orderBy('long')->get();
            
            $formattedLocations = $locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'name' => $location->long ?? $location->short ?? "Location {$location->id}",
                    'short' => $location->short ?? '',
                    'long' => $location->long ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'locations' => $formattedLocations
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load locations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load server locations',
                'locations' => []
            ], 500);
        }
    }

    /**
     * Get plan details for API calls.
     */
    public function getPlanDetails(ShopPlan $plan): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'price' => $plan->price,
                    'billing_cycle' => $plan->billing_cycle,
                    'cpu' => $plan->cpu,
                    'memory' => $plan->memory,
                    'storage' => $plan->storage,
                    'databases' => $plan->databases,
                    'allocations' => $plan->allocations,
                    'backups' => $plan->backups,
                    'features' => $plan->features,
                    'location_ids' => $plan->location_ids,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load plan details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Plan not found'
            ], 404);
        }
    }
}
