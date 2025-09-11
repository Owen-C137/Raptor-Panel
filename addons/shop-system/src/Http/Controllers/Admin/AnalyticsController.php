<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use Carbon\Carbon;
use DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days
        
        $analytics = [
            'revenue' => $this->getRevenueAnalytics($period),
            'orders' => $this->getOrderAnalytics($period),
            'plans' => $this->getPlanAnalytics($period),
            'customers' => $this->getCustomerAnalytics($period),
        ];
        
        return view('shop::admin.analytics.index', compact('analytics', 'period'));
    }

    /**
     * Get sales analytics data
     */
    public function sales(Request $request): JsonResponse
    {
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        
        $salesData = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $chartData = [
            'labels' => $salesData->pluck('date')->toArray(),
            'revenue' => $salesData->pluck('revenue')->toArray(),
            'transactions' => $salesData->pluck('transactions')->toArray(),
        ];
        
        return response()->json($chartData);
    }

    /**
     * Get plan analytics data
     */
    public function plans(Request $request): JsonResponse
    {
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        
        // This system uses plans instead of products
        $planData = ShopOrder::join('shop_plans', 'shop_orders.plan_id', '=', 'shop_plans.id')
            ->join('shop_categories', 'shop_plans.category_id', '=', 'shop_categories.id')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('shop_plans.id as plan_id, shop_plans.name as plan_name, shop_categories.name as category_name, COUNT(*) as orders, SUM(shop_orders.amount) as revenue')
            ->groupBy('shop_plans.id', 'shop_plans.name', 'shop_categories.name')
            ->orderBy('revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'name' => $order->plan_name . ' (' . $order->category_name . ')',
                    'orders' => $order->orders,
                    'revenue' => (float) $order->revenue,
                ];
            });
        
        return response()->json($planData);
    }

    /**
     * Get revenue analytics
     */
    protected function getRevenueAnalytics(int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $totalRevenue = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        
        $previousPeriodRevenue = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', $startDate->copy()->subDays($days))
            ->where('created_at', '<', $startDate)
            ->sum('amount');
        
        $growth = $previousPeriodRevenue > 0 
            ? (($totalRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100 
            : 0;
        
        return [
            'total' => (float) $totalRevenue,
            'growth' => round($growth, 2),
            'average_daily' => round($totalRevenue / $days, 2),
        ];
    }

    /**
     * Get order analytics
     */
    protected function getOrderAnalytics(int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $totalOrders = ShopOrder::where('created_at', '>=', $startDate)->count();
        
        $statusBreakdown = ShopOrder::where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        return [
            'total' => $totalOrders,
            'average_daily' => round($totalOrders / $days, 2),
            'status_breakdown' => $statusBreakdown,
        ];
    }

    /**
     * Get plan analytics
     */
    protected function getPlanAnalytics(int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $topPlans = ShopOrder::join('shop_plans', 'shop_orders.plan_id', '=', 'shop_plans.id')
            ->join('shop_categories', 'shop_plans.category_id', '=', 'shop_categories.id')
            ->where('shop_orders.created_at', '>=', $startDate)
            ->selectRaw('shop_plans.id as plan_id, shop_plans.name as plan_name, shop_categories.name as category_name, COUNT(*) as orders, SUM(shop_orders.amount) as revenue')
            ->groupBy('shop_plans.id', 'shop_plans.name', 'shop_categories.name')
            ->orderBy('revenue', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'name' => $order->plan_name . ' (' . $order->category_name . ')',
                    'orders' => $order->orders,
                    'revenue' => (float) $order->revenue,
                ];
            });
        
        return [
            'top_plans' => $topPlans,
            'total_categories' => ShopCategory::count(),
            'active_categories' => ShopCategory::where('active', true)->count(),
        ];
    }

    /**
     * Get customer analytics
     */
    protected function getCustomerAnalytics(int $days): array
    {
        $startDate = Carbon::now()->subDays($days);
        
        $newCustomers = ShopOrder::distinct('user_id')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $returningCustomers = ShopOrder::selectRaw('user_id, COUNT(*) as order_count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('user_id')
            ->having('order_count', '>', 1)
            ->count();
        
        $totalWalletBalance = UserWallet::sum('balance');
        
        return [
            'new_customers' => $newCustomers,
            'returning_customers' => $returningCustomers,
            'total_wallet_balance' => (float) $totalWalletBalance,
            'average_order_value' => $this->getAverageOrderValue($days),
        ];
    }

    /**
     * Get average order value
     */
    protected function getAverageOrderValue(int $days): float
    {
        $startDate = Carbon::now()->subDays($days);
        
        return (float) ShopOrder::where('created_at', '>=', $startDate)
            ->selectRaw('AVG(amount + setup_fee) as avg_total')
            ->value('avg_total') ?? 0;
    }
}
