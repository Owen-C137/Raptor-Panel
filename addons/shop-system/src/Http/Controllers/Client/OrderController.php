<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Client;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\BaseShopController;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\UserWallet;

class OrderController extends BaseShopController
{
    /**
     * Display user's order history
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('search');
        
        $orders = ShopOrder::where('user_id', auth()->id())
            ->with(['product', 'server', 'payments'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                return $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('id', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $wallet = UserWallet::firstOrCreate(
            ['user_id' => auth()->id()],
            ['balance' => 0]
        );
        
        // Get order statistics
        $stats = [
            'total_orders' => ShopOrder::where('user_id', auth()->id())->count(),
            'active_orders' => ShopOrder::where('user_id', auth()->id())
                ->where('status', 'active')->count(),
            'pending_orders' => ShopOrder::where('user_id', auth()->id())
                ->where('status', 'pending')->count(),
            'total_spent' => ShopOrder::where('user_id', auth()->id())
                ->whereIn('status', ['active', 'completed'])
                ->sum('amount'),
        ];
        
        $statuses = ShopOrder::getStatuses();
        
        return view('client.shop.orders.index', compact(
            'orders', 
            'wallet', 
            'stats', 
            'statuses',
            'status', 
            'search'
        ));
    }

    /**
     * Display a specific order
     */
    public function show(ShopOrder $order)
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        $order->load(['product', 'server', 'payments.gateway', 'coupon']);
        
        return view('client.shop.orders.show', compact('order'));
    }

    /**
     * Cancel an order
     */
    public function cancel(ShopOrder $order)
    {
        // Ensure user can only cancel their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        // Check if order can be cancelled
        if (!in_array($order->status, ['pending', 'processing'])) {
            return redirect()->back()
                ->with('error', 'This order cannot be cancelled.');
        }
        
        try {
            $order->update(['status' => 'cancelled']);
            
            // Refund to wallet if payment was processed
            if ($order->payments()->where('status', 'completed')->exists()) {
                $wallet = UserWallet::firstOrCreate(
                    ['user_id' => auth()->id()],
                    ['balance' => 0]
                );
                
                $wallet->increment('balance', $order->total_amount);
                
                // Create wallet transaction record
                \Pterodactyl\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'refund',
                    'amount' => $order->total_amount,
                    'description' => "Refund for cancelled order #{$order->id}",
                    'reference_type' => 'order_refund',
                    'reference_id' => $order->id,
                ]);
            }
            
            return redirect()->route('shop.orders')
                ->with('success', 'Order cancelled successfully. Refund has been added to your wallet.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Renew a subscription order
     */
    public function renew(ShopOrder $order)
    {
        // Ensure user can only renew their own orders
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        // Check if order can be renewed
        if ($order->billing_cycle === 'one_time' || $order->status !== 'active') {
            return redirect()->back()
                ->with('error', 'This order cannot be renewed.');
        }
        
        try {
            $wallet = UserWallet::where('user_id', auth()->id())->first();
            
            if (!$wallet || $wallet->balance < $order->total_amount) {
                return redirect()->back()
                    ->with('error', 'Insufficient wallet balance for renewal.');
            }
            
            // Process renewal payment
            $wallet->decrement('balance', $order->total_amount);
            
            // Update next billing date
            $order->update([
                'next_billing_date' => $this->calculateNextBillingDate($order),
                'updated_at' => now(),
            ]);
            
            // Create wallet transaction
            \Pterodactyl\Models\WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'purchase',
                'amount' => -$order->total_amount,
                'description' => "Renewal payment for order #{$order->id}",
                'reference_type' => 'order_renewal',
                'reference_id' => $order->id,
            ]);
            
            return redirect()->back()
                ->with('success', 'Order renewed successfully.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to renew order: ' . $e->getMessage());
        }
    }

    /**
     * Download order invoice
     */
    public function invoice(ShopOrder $order)
    {
        // Ensure user can only download their own invoices
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order.');
        }
        
        $order->load(['product', 'user', 'payments']);
        
        // Get currency symbol for PDF
        $currencySymbol = $this->currencyService->getCurrentCurrencySymbol();
        
        // Generate PDF invoice (simplified version)
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('client.shop.invoice', compact('order', 'currencySymbol'));
        
        return $pdf->download("invoice-{$order->id}.pdf");
    }

    /**
     * Get order data via AJAX
     */
    public function getData(ShopOrder $order)
    {
        // Ensure user can only access their own order data
        if ($order->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $order->load(['product', 'server', 'payments']);
        
        return response()->json([
            'id' => $order->id,
            'product_name' => $order->product->name,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'created_at' => $order->created_at->format('M d, Y H:i'),
            'next_billing_date' => $order->next_billing_date?->format('M d, Y'),
            'server_info' => $order->server ? [
                'id' => $order->server->id,
                'name' => $order->server->name,
                'status' => $order->server->status,
            ] : null,
            'can_cancel' => in_array($order->status, ['pending', 'processing']),
            'can_renew' => $order->billing_cycle !== 'one_time' && $order->status === 'active',
        ]);
    }

    /**
     * Calculate next billing date based on billing cycle
     */
    protected function calculateNextBillingDate(ShopOrder $order): ?\Carbon\Carbon
    {
        if ($order->billing_cycle === 'one_time') {
            return null;
        }
        
        $currentDate = $order->next_billing_date ?: now();
        
        return match($order->billing_cycle) {
            'hourly' => $currentDate->addHour(),
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth()
        };
    }
}
