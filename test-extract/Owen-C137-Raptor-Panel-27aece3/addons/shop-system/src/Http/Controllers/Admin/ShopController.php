<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class ShopController extends Controller
{
    /**
     * Display the admin shop dashboard
     */
    public function dashboard()
    {
        $stats = $this->getDashboardStats();
        $recentOrders = $this->getRecentOrders();
        $recentPayments = $this->getRecentPayments();
        $topCategories = $this->getTopCategories();
        
        return view('shop::admin.dashboard', compact(
            'stats',
            'recentOrders', 
            'recentPayments',
            'topCategories'
        ));
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        
        return [
            'total_revenue' => ShopPayment::where('status', 'completed')->sum('amount'),
            'monthly_revenue' => ShopPayment::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('amount'),
            'daily_revenue' => ShopPayment::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('amount'),
            'total_orders' => ShopOrder::count(),
            'monthly_orders' => ShopOrder::where('created_at', '>=', $thisMonth)->count(),
            'daily_orders' => ShopOrder::where('created_at', '>=', $today)->count(),
            'active_orders' => ShopOrder::where('status', 'active')->count(),
            'suspended_orders' => ShopOrder::where('status', 'suspended')->count(),
            'cancelled_orders' => ShopOrder::where('status', 'cancelled')->count(),
            'total_categories' => ShopCategory::count(),
            'active_categories' => ShopCategory::where('active', true)->count(),
            'total_customers' => User::whereHas('shopOrders')->count(),
            'new_customers_this_month' => User::whereHas('shopOrders', function ($query) use ($thisMonth) {
                $query->where('created_at', '>=', $thisMonth);
            })->count(),
            'pending_payments' => ShopPayment::where('status', 'pending')->count(),
            'failed_payments' => ShopPayment::where('status', 'failed')->count(),
            'total_coupons' => ShopCoupon::count(),
            'active_coupons' => ShopCoupon::where('active', true)->count(),
            'average_order_value' => ShopOrder::selectRaw('AVG(amount + setup_fee) as avg_total')->value('avg_total') ?? 0,
        ];
    }

    /**
     * Get recent orders
     */
    private function getRecentOrders()
    {
        return ShopOrder::with(['user', 'product', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get recent payments
     */
    private function getRecentPayments()
    {
        return ShopPayment::with(['order', 'order.user', 'order.product'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get top categories by orders
     */
    private function getTopCategories()
    {
        return ShopCategory::select('shop_categories.*')
            ->join('shop_plans', 'shop_categories.id', '=', 'shop_plans.category_id')
            ->join('shop_orders', 'shop_plans.id', '=', 'shop_orders.plan_id')
            ->select(
                'shop_categories.id',
                'shop_categories.name',
                'shop_categories.description',
                DB::raw('COUNT(shop_orders.id) as order_count'),
                DB::raw('SUM(shop_orders.amount + shop_orders.setup_fee) as total_revenue')
            )
            ->groupBy('shop_categories.id', 'shop_categories.name', 'shop_categories.description')
            ->orderBy('order_count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get system status
     */
    public function status()
    {
        $systemStatus = [
            'database_connection' => $this->checkDatabaseConnection(),
            'payment_gateways' => $this->checkPaymentGateways(),
            'queue_status' => $this->checkQueueStatus(),
            'cache_status' => $this->checkCacheStatus(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        return view('shop::admin.status', compact('systemStatus'));
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection is working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check payment gateway status
     */
    private function checkPaymentGateways()
    {
        $gateways = [];
        
        // Check PayPal
        if (config('shop.gateways.paypal.enabled', false)) {
            $gateways['paypal'] = [
                'enabled' => true,
                'status' => 'configured',
                'message' => 'PayPal gateway is configured'
            ];
        }
        
        // Check Stripe
        if (config('shop.gateways.stripe.enabled', false)) {
            $gateways['stripe'] = [
                'enabled' => true,
                'status' => 'configured', 
                'message' => 'Stripe gateway is configured'
            ];
        }
        
        return $gateways;
    }

    /**
     * Check queue status
     */
    private function checkQueueStatus()
    {
        try {
            $failedJobs = DB::table('failed_jobs')->count();
            return [
                'status' => 'healthy',
                'failed_jobs' => $failedJobs,
                'message' => "Queue is running. Failed jobs: {$failedJobs}"
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check cache status
     */
    private function checkCacheStatus()
    {
        try {
            cache()->put('shop_health_check', 'test', 60);
            $result = cache()->get('shop_health_check');
            
            return [
                'status' => $result === 'test' ? 'healthy' : 'warning',
                'message' => $result === 'test' ? 'Cache is working' : 'Cache may not be working properly'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace()
    {
        $bytes = disk_free_space('/');
        $gb = round($bytes / 1024 / 1024 / 1024, 2);
        
        return [
            'status' => $gb > 5 ? 'healthy' : 'warning',
            'free_space' => $gb . ' GB',
            'message' => "Free disk space: {$gb} GB"
        ];
    }

    /**
     * Get revenue chart data
     */
    public function revenueChart(Request $request)
    {
        $days = $request->get('days', 30);
        
        $data = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    /**
     * Get orders chart data
     */
    public function ordersChart(Request $request)
    {
        $days = $request->get('days', 30);
        
        $data = ShopOrder::where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }
}
