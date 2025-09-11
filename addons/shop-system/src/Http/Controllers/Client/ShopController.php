<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Client;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\UserWallet;

class ShopController extends Controller
{
    /**
     * Display the main shop page
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $sort = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        
        // Get user's wallet balance
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        // Get categories for navigation
        $categories = ShopCategory::where('active', true)
            ->withCount(['plans' => function ($query) {
                $query->where('active', true);
            }])
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('sort_order')
            ->paginate(12);
        
        // Get featured categories (if there's a featured field)
        $featuredCategories = ShopCategory::where('active', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();
        
        return view('client.shop.index', compact(
            'categories', 
            'featuredCategories',
            'wallet', 
            'search', 
            'category', 
            'sort', 
            'order'
        ));
    }

    /**
     * Display products in a specific category
     */
    public function category(Request $request, ShopCategory $category)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'name');
        $order = $request->get('order', 'asc');
        
        // Get user's wallet balance
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        // Get all categories for navigation
        $categories = ShopCategory::where('active', true)
            ->withCount(['plans' => function ($query) {
                $query->where('status', 'active');
            }])
            ->orderBy('sort_order')
            ->get();
        
        // Get plans in this category
        $plans = $category->plans()
            ->where('status', 'active')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy($sort, $order)
            ->paginate(12);
        
        return view('client.shop.category', compact(
            'category',
            'plans', 
            'categories',
            'wallet', 
            'search', 
            'sort', 
            'order'
        ));
    }

    // Product method removed since we now use categories directly

    /**
     * Get user's current orders and wallet info via AJAX
     */
    public function getUserData(Request $request)
    {
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        $activeOrders = ShopOrder::where('user_id', auth()->id())
            ->whereIn('status', ['active', 'pending', 'processing'])
            ->with('product')
            ->get();
        
        return response()->json([
            'wallet_balance' => (float) $wallet->balance,
            'active_orders' => $activeOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'product_name' => $order->product->name,
                    'status' => $order->status,
                    'amount' => (float) $order->total_amount,
                    'created_at' => $order->created_at->format('M d, Y'),
                ];
            }),
        ]);
    }

    /**
     * Search products via AJAX
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $categories = ShopCategory::where('active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->with('plans')
            ->limit(10)
            ->get();
        
        return response()->json([
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'plans_count' => $category->plans->count(),
                    'url' => route('shop.category', $category),
                ];
            })
        ]);
    }
}
