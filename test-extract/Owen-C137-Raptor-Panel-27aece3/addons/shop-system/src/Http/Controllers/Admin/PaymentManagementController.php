<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\BaseShopController;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\CurrencyService;

class PaymentManagementController extends BaseShopController
{
    protected PaymentGatewayManager $paymentGatewayManager;

    public function __construct(
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService,
        PaymentGatewayManager $paymentGatewayManager
    ) {
        parent::__construct($shopConfigService, $walletService, $currencyService);
        $this->paymentGatewayManager = $paymentGatewayManager;
    }

    /**
     * Display all payments
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
            ->when($request->search, function ($query, $search) {
                return $query->whereHas('order.user', function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                })->orWhere('transaction_id', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return $this->view('shop::admin.payments.index', compact('payments'));
    }

    /**
     * Display specific payment
     */
    public function show(ShopPayment $payment)
    {
        $payment->load(['order', 'order.user', 'order.plan']);
        
        return $this->view('shop::admin.payments.show', compact('payment'));
    }

    /**
     * Process payment refund
     */
    public function refund(Request $request, ShopPayment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->amount,
            'reason' => 'required|string|max:255'
        ]);

        try {
            $gateway = $this->paymentGatewayManager->getGateway($payment->gateway);
            $refund = $gateway->processRefund($payment, $request->amount, $request->reason);
            
            $payment->update([
                'status' => 'refunded',
                'refunded_amount' => $request->amount,
                'refund_reason' => $request->reason
            ]);

            return redirect()
                ->route('admin.shop.payments.show', $payment)
                ->with('success', 'Payment refunded successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to process refund: ' . $e->getMessage()]);
        }
    }

    /**
     * Display gateway transactions
     */
    public function gatewayTransactions(Request $request, string $gateway)
    {
        $payments = ShopPayment::where('gateway', $gateway)
            ->with(['order', 'order.user'])
            ->when($request->date_from, function ($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return $this->view('shop::admin.payments.gateway', compact('payments', 'gateway'));
    }
}
