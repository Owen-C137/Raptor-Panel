<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopPayment;

class PaymentController extends Controller
{
    /**
     * Display all payments (API endpoint)
     */
    public function index(Request $request)
    {
        $payments = ShopPayment::with(['order', 'order.user'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->gateway, function ($query, $gateway) {
                return $query->where('gateway', $gateway);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Display specific payment (API endpoint)
     */
    public function show(ShopPayment $payment)
    {
        $payment->load(['order', 'order.user', 'order.items']);
        
        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Process payment refund (API endpoint)
     */
    public function refund(Request $request, ShopPayment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string|max:255'
        ]);

        try {
            // Logic would integrate with PaymentGatewayManager
            $payment->update([
                'status' => 'refunded',
                'refunded_amount' => $request->amount,
                'refund_reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => $payment->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund: ' . $e->getMessage()
            ], 500);
        }
    }
}
