<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Illuminate\Support\Facades\Auth;

class RenewalController extends Controller
{
    /**
     * Show renewal options for a specific server
     */
    public function show(Request $request): View
    {
        $serverUuid = $request->query('renew');
        
        if (!$serverUuid) {
            return redirect()->route('shop.index')->with('error', 'No server specified for renewal.');
        }
        
        $user = Auth::user();
        
        // Find the cancelled order for this server
        $cancelledOrder = ShopOrder::query()
            ->where('user_id', $user->id)
            ->where('status', ShopOrder::STATUS_CANCELLED)
            ->whereHas('server', function($query) use ($serverUuid) {
                $query->where('uuid', 'LIKE', $serverUuid . '%')
                      ->orWhere('uuidShort', $serverUuid);
            })
            ->with(['server', 'plan'])
            ->first();
        
        if (!$cancelledOrder) {
            return redirect()->route('shop.index')->with('error', 'No cancelled server plan found for renewal.');
        }
        
        return view('shop::client.renewal.show', [
            'order' => $cancelledOrder,
            'server' => $cancelledOrder->server,
            'plan' => $cancelledOrder->plan,
            'timeRemaining' => $cancelledOrder->auto_delete_at ? 
                now()->diffInDays($cancelledOrder->auto_delete_at, false) : null
        ]);
    }
    
    /**
     * Process a renewal request
     */
    public function renew(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:shop_orders,id',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semi_annually,annually',
        ]);
        
        $user = Auth::user();
        
        // Find the cancelled order
        $cancelledOrder = ShopOrder::query()
            ->where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->where('status', ShopOrder::STATUS_CANCELLED)
            ->with('plan')
            ->first();
        
        if (!$cancelledOrder) {
            return response()->json(['error' => 'Order not found or not eligible for renewal.'], 404);
        }
        
        // Calculate renewal amount based on selected billing cycle
        $plan = $cancelledOrder->plan;
        $renewalAmount = $this->calculateRenewalAmount($plan, $request->billing_cycle);
        
        // Create a new renewal order
        $renewalOrder = ShopOrder::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'server_id' => $cancelledOrder->server_id,
            'status' => ShopOrder::STATUS_PENDING,
            'billing_cycle' => $request->billing_cycle,
            'amount' => $renewalAmount,
            'setup_fee' => 0, // No setup fee for renewals
            'currency' => config('shop.currency', 'USD'),
            'server_config' => $cancelledOrder->server_config,
            'is_renewal' => true,
            'original_order_id' => $cancelledOrder->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Renewal order created successfully.',
            'order_id' => $renewalOrder->id,
            'amount' => $renewalAmount,
            'redirect_url' => route('shop.checkout', ['order' => $renewalOrder->uuid])
        ]);
    }
    
    /**
     * Calculate renewal amount based on plan and billing cycle
     */
    private function calculateRenewalAmount($plan, $billingCycle): float
    {
        $multiplier = match($billingCycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };
        
        return $plan->price * $multiplier;
    }
}