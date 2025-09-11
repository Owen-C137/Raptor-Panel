<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Models\ShopPlan;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin shop dashboard
     */
    public function index(Request $request)
    {
        // Get dashboard statistics
        $metrics = $this->getDashboardStats();
        
        // Get today's statistics
        $todayStats = $this->getTodayStats();
        
        // Get recent orders
        $recentOrders = ShopOrder::with(['user', 'plan.category'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get revenue data for charts
        $revenueData = $this->getRevenueData();
        
        // Get top plans
        $topPlans = $this->getTopPlans();
        
        // Get system health metrics
        $systemHealth = $this->getSystemHealth();
        
        // Get top categories
        $topCategories = $this->getTopCategories();
        
        return view('shop::admin.dashboard', compact('metrics', 'todayStats', 'recentOrders', 'revenueData', 'topPlans', 'systemHealth', 'topCategories'));
    }

    /**
     * Get dashboard statistics
     */
    protected function getDashboardStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'total_revenue' => ShopPayment::where('status', 'completed')->sum('amount'),
            'monthly_revenue' => ShopPayment::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('amount'),
            'daily_revenue' => ShopPayment::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('amount'),
            'total_orders' => ShopOrder::count(),
            'pending_orders' => ShopOrder::where('status', 'pending')->count(),
            'active_orders' => ShopOrder::where('status', 'active')->count(),
            'total_categories' => ShopCategory::count(),
            'active_categories' => ShopCategory::where('active', true)->count(),
            'total_customers' => UserWallet::distinct('user_id')->count(),
            'wallet_balance' => UserWallet::sum('balance'),
            'active_subscriptions' => ShopOrder::where('status', 'active')->count(),
        ];
    }

    /**
     * Get today's statistics
     */
    protected function getTodayStats(): array
    {
        $today = Carbon::today();
        
        return [
            'orders' => ShopOrder::whereDate('created_at', $today)->count(),
            'revenue' => ShopPayment::where('status', 'completed')
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'servers' => ShopOrder::where('status', 'active')
                ->whereDate('created_at', $today)
                ->count(),
            'failed_payments' => ShopPayment::where('status', 'failed')
                ->whereDate('created_at', $today)
                ->count(),
        ];
    }

    /**
     * Get revenue data for charts
     */
    protected function getRevenueData(): array
    {
        $last30Days = collect();
        
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = ShopPayment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            $last30Days->push([
                'date' => $date->format('Y-m-d'),
                'revenue' => (float) $revenue,
            ]);
        }
        
        return [
            'daily_revenue' => $last30Days->toArray(),
            'monthly_labels' => $last30Days->pluck('date')->toArray(),
            'monthly_data' => $last30Days->pluck('revenue')->toArray(),
        ];
    }

    /**
     * Get dashboard data via AJAX
     */
    public function getData(Request $request): JsonResponse
    {
        return response()->json([
            'stats' => $this->getDashboardStats(),
            'revenue_data' => $this->getRevenueData(),
        ]);
    }

    /**
     * Get top plans by order count
     */
    protected function getTopPlans()
    {
        return ShopOrder::join('shop_plans', 'shop_orders.plan_id', '=', 'shop_plans.id')
            ->join('shop_categories', 'shop_plans.category_id', '=', 'shop_categories.id')
            ->select('shop_plans.name as plan_name', 'shop_categories.name as category_name')
            ->selectRaw('COUNT(shop_orders.id) as order_count')
            ->groupBy('shop_plans.id', 'shop_plans.name', 'shop_categories.name')
            ->orderBy('order_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get system health metrics
     */
    protected function getSystemHealth()
    {
        $totalPayments = ShopPayment::count();
        $successfulPayments = ShopPayment::where('status', 'completed')->count();
        $totalOrders = ShopOrder::count();
        $successfulProvisions = ShopOrder::whereIn('status', ['active', 'suspended'])->count();
        
        return [
            'payment_success_rate' => $totalPayments > 0 ? ($successfulPayments / $totalPayments) * 100 : 100,
            'provision_success_rate' => $totalOrders > 0 ? ($successfulProvisions / $totalOrders) * 100 : 100,
            'queue_healthy' => true, // TODO: Implement actual queue health check
            'renewal_success_rate' => 95.0, // TODO: Implement actual renewal success rate calculation
        ];
    }

    /**
     * Get top categories by orders
     */
    protected function getTopCategories()
    {
        return ShopCategory::select('shop_categories.*')
            ->join('shop_plans', 'shop_categories.id', '=', 'shop_plans.category_id')
            ->join('shop_orders', 'shop_plans.id', '=', 'shop_orders.plan_id')
            ->select(
                'shop_categories.id',
                'shop_categories.name',
                'shop_categories.description',
                \DB::raw('COUNT(shop_orders.id) as order_count'),
                \DB::raw('SUM(shop_orders.amount) as total_revenue')
            )
            ->groupBy('shop_categories.id', 'shop_categories.name', 'shop_categories.description')
            ->orderBy('order_count', 'desc')
            ->limit(5)
            ->get();
    }
}
