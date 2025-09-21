<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Api;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Transformers\ProductTransformer;
use PterodactylAddons\ShopSystem\Transformers\OrderTransformer;
use PterodactylAddons\ShopSystem\Transformers\WalletTransformer;
use Pterodactyl\Http\Requests\Api\ApiRequest;

class ShopController extends Controller
{
    /**
     * Get all available categories
     */
    public function categories(ApiRequest $request)
    {
        $categories = ShopCategory::where('active', true)
            ->with(['plans'])
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            })
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $categories->items(),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
            ]
        ]);
    }

    /**
     * Get user orders
     */
    public function orders(ApiRequest $request)
    {
        $user = $request->user();
        
        $orders = ShopOrder::where('user_id', $user->id)
            ->with(['product', 'plan', 'server'])
            ->when($request->filled('status'), function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->filled('product'), function ($query) use ($request) {
                return $query->where('product_id', $request->product);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 25));

        return $this->fractal->collection($orders->getCollection())
            ->transformWith(new OrderTransformer())
            ->withResourceName('order')
            ->paginateWith(new \League\Fractal\Pagination\IlluminatePaginatorAdapter($orders))
            ->toArray();
    }

    /**
     * Get user wallet information
     */
    public function wallet(ApiRequest $request)
    {
        $user = $request->user();
        
        $wallet = ShopWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'USD']
        );

        return $this->fractal->item($wallet)
            ->transformWith(new WalletTransformer())
            ->withResourceName('wallet')
            ->toArray();
    }

    /**
     * Get category details
     */
    public function category(ApiRequest $request, ShopCategory $category)
    {
        $category->load(['plans']);
        
        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Get order details
     */
    public function order(ApiRequest $request, ShopOrder $order)
    {
        // Ensure user can only access their own orders
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to access this order.');
        }

        $order->load(['product', 'plan', 'server', 'payments']);
        
        return $this->fractal->item($order)
            ->transformWith(new OrderTransformer())
            ->withResourceName('order')
            ->toArray();
    }

    /**
     * Get shop statistics (admin only)
     */
    public function stats(ApiRequest $request)
    {
        // This would typically check for admin permissions
        $stats = [
            'total_categories' => ShopCategory::count(),
            'active_categories' => ShopCategory::where('active', true)->count(),
            'total_orders' => ShopOrder::count(),
            'active_orders' => ShopOrder::where('status', 'active')->count(),
            'total_revenue' => ShopOrder::selectRaw('SUM(amount + setup_fee) as total')->value('total') ?? 0,
            'monthly_revenue' => ShopOrder::where('created_at', '>=', now()->subMonth())->selectRaw('SUM(amount + setup_fee) as total')->value('total') ?? 0,
        ];

        return response()->json($stats);
    }
}
