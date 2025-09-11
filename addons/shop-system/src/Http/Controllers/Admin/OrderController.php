<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Pterodactyl\Models\User;
use PterodactylAddons\ShopSystem\Services\OrderService;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $user = $request->get('user');
        
        $orders = ShopOrder::query()
            ->with(['user', 'plan.category', 'server', 'payments'])
            ->when($search, function ($query, $search) {
                return $query->whereHas('user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($user, function ($query, $user) {
                return $query->where('user_id', $user);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);
        
        $users = User::orderBy('username')->get();
        $statuses = ShopOrder::getStatuses();
        
        return view('shop::admin.orders.index', compact('orders', 'users', 'statuses', 'search', 'status', 'user'));
    }

    /**
     * Show the specified order
     */
    public function show(ShopOrder $order)
    {
        $order->load(['user', 'plan.category', 'server', 'payments']);
        
        return view('shop::admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, ShopOrder $order): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,active,suspended,cancelled,completed,refunded'
        ]);
        
        try {
            $this->orderService->updateStatus($order, $request->status);
            
            return redirect()->back()
                ->with('success', 'Order status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Process refund for order
     */
    public function refund(Request $request, ShopOrder $order): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $order->total_amount,
            'reason' => 'required|string|max:255'
        ]);
        
        try {
            $this->orderService->processRefund(
                $order, 
                $request->amount, 
                $request->reason
            );
            
            return redirect()->back()
                ->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Manually activate order
     */
    public function activate(ShopOrder $order): RedirectResponse
    {
        try {
            $this->orderService->activateOrder($order);
            
            return redirect()->back()
                ->with('success', 'Order activated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to activate order: ' . $e->getMessage());
        }
    }

    /**
     * Manually suspend order
     */
    public function suspend(ShopOrder $order): RedirectResponse
    {
        try {
            $this->orderService->suspendOrder($order);
            
            return redirect()->back()
                ->with('success', 'Order suspended successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to suspend order: ' . $e->getMessage());
        }
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $orders = ShopOrder::with(['user', 'product'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->from_date, function ($query, $date) {
                return $query->where('created_at', '>=', $date);
            })
            ->when($request->to_date, function ($query, $date) {
                return $query->where('created_at', '<=', $date . ' 23:59:59');
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        $filename = 'shop_orders_' . date('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
        
        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Order ID', 'User', 'Email', 'Product', 'Amount', 'Status', 
                'Created At', 'Updated At'
            ]);
            
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user->username,
                    $order->user->email,
                    $order->product->name,
                    $order->total_amount,
                    $order->status,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
