<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Pterodactyl\Models\User;

class ReportsController extends Controller
{
    /**
     * Reports dashboard
     */
    public function index()
    {
        return view('shop::admin.reports.index');
    }

    /**
     * Revenue reports
     */
    public function revenue(Request $request)
    {
        $period = $request->get('period', '30'); // days
        
        $revenueData = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $totalRevenue = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->sum('amount');

        $averageOrderValue = $revenueData->avg('total') ?? 0;

        return view('shop::admin.reports.revenue', compact('revenueData', 'totalRevenue', 'averageOrderValue', 'period'));
    }

    /**
     * Order reports
     */
    public function orders(Request $request)
    {
        $period = $request->get('period', '30');
        
        $orderStats = ShopOrder::where('created_at', '>=', now()->subDays($period))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_orders'),
                DB::raw('SUM(CASE WHEN status = "suspended" THEN 1 ELSE 0 END) as suspended_orders'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_orders')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $statusBreakdown = ShopOrder::where('created_at', '>=', now()->subDays($period))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return view('shop::admin.reports.orders', compact('orderStats', 'statusBreakdown', 'period'));
    }

    /**
     * Customer reports
     */
    public function customers(Request $request)
    {
        $period = $request->get('period', '30');
        
        // Top customers by revenue
        $topCustomers = User::select('users.*')
            ->join('shop_orders', 'users.id', '=', 'shop_orders.user_id')
            ->join('shop_payments', 'shop_orders.id', '=', 'shop_payments.order_id')
            ->where('shop_payments.status', 'completed')
            ->where('shop_payments.created_at', '>=', now()->subDays($period))
            ->select(
                'users.id',
                'users.username',
                'users.email',
                DB::raw('SUM(shop_payments.amount) as total_spent'),
                DB::raw('COUNT(DISTINCT shop_orders.id) as order_count')
            )
            ->groupBy('users.id', 'users.username', 'users.email')
            ->orderBy('total_spent', 'desc')
            ->limit(20)
            ->get();

        // New customer acquisitions
        $newCustomers = User::whereHas('shopOrders', function ($query) use ($period) {
                $query->where('created_at', '>=', now()->subDays($period));
            })
            ->whereDoesntHave('shopOrders', function ($query) use ($period) {
                $query->where('created_at', '<', now()->subDays($period));
            })
            ->count();

        // Customer lifetime value
        $customerLifetimeValue = ShopPayment::where('status', 'completed')
            ->join('shop_orders', 'shop_payments.order_id', '=', 'shop_orders.id')
            ->select('shop_orders.user_id', DB::raw('SUM(shop_payments.amount) as lifetime_value'))
            ->groupBy('shop_orders.user_id')
            ->avg('lifetime_value') ?? 0;

        return view('shop::admin.reports.customers', compact('topCustomers', 'newCustomers', 'customerLifetimeValue', 'period'));
    }

    /**
     * Export reports
     */
    public function export(Request $request, string $type)
    {
        $period = $request->get('period', '30');
        
        switch ($type) {
            case 'revenue':
                return $this->exportRevenue($period);
            case 'orders':
                return $this->exportOrders($period);
            case 'customers':
                return $this->exportCustomers($period);
            default:
                abort(404, 'Invalid export type');
        }
    }

    /**
     * Export revenue data as CSV
     */
    private function exportRevenue(int $period)
    {
        $revenueData = ShopPayment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->with(['order', 'order.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'revenue_report_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        $callback = function () use ($revenueData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Transaction ID', 'Customer', 'Amount', 'Gateway', 'Status']);
            
            foreach ($revenueData as $payment) {
                fputcsv($file, [
                    $payment->created_at->format('Y-m-d H:i:s'),
                    $payment->transaction_id,
                    $payment->order->user->email ?? 'N/A',
                    $payment->amount,
                    $payment->gateway,
                    $payment->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export orders data as CSV
     */
    private function exportOrders(int $period)
    {
        $orders = ShopOrder::where('created_at', '>=', now()->subDays($period))
            ->with(['user', 'product', 'plan'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'orders_report_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Order ID', 'Customer', 'Product', 'Plan', 'Amount', 'Status', 'Created', 'Expires']);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user->email ?? 'N/A',
                    $order->product->name ?? 'N/A',
                    $order->plan->name ?? 'N/A',
                    $order->total_amount,
                    $order->status,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->expires_at?->format('Y-m-d H:i:s') ?? 'Never'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export customers data as CSV
     */
    private function exportCustomers(int $period)
    {
        $customers = User::whereHas('shopOrders')
            ->with(['shopOrders' => function ($query) use ($period) {
                $query->where('created_at', '>=', now()->subDays($period));
            }])
            ->get();

        $filename = 'customers_report_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Customer ID', 'Username', 'Email', 'Total Orders', 'Total Spent', 'Last Order']);
            
            foreach ($customers as $customer) {
                $totalSpent = $customer->shopOrders->sum('total_amount');
                $lastOrder = $customer->shopOrders->sortByDesc('created_at')->first();
                
                fputcsv($file, [
                    $customer->id,
                    $customer->username,
                    $customer->email,
                    $customer->shopOrders->count(),
                    $totalSpent,
                    $lastOrder?->created_at->format('Y-m-d H:i:s') ?? 'Never'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
