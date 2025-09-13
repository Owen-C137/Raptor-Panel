<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository;
use PterodactylAddons\ShopSystem\Services\ShopOrderService;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Services\WalletService;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(
        private ShopOrderRepository $orderRepository,
        private ShopOrderService $orderService,
        private PaymentGatewayManager $paymentManager,
        private WalletService $walletService
    ) {}

    /**
     * Display user's orders.
     */
    public function index(Request $request): View
    {
        $this->checkShopAvailability();

        $filters = $request->only(['status', 'search']);
        $orders = $this->orderRepository->getByUser(
            userId: auth()->id(),
            filters: $filters,
            perPage: 15
        );

        return view('shop::orders.index', compact('orders', 'filters'));
    }

    /**
     * Display specific order.
     */
    public function show(ShopOrder $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['plan.category', 'server', 'payments', 'user']);
        
        $paymentMethods = [];
        if ($order->isPending() || $order->isOverdue()) {
            $paymentMethods = $this->getAvailablePaymentMethods();
        }

        return view('shop::orders.show', compact('order', 'paymentMethods'));
    }

    /**
     * Cancel an order.
     */
    public function cancel(ShopOrder $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        if (!$order->canBeCancelled()) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        try {
            $this->orderService->cancelOrder($order->id, 'Cancelled by user');

            $this->logActivity('Order cancelled', $order, [
                'order_id' => $order->id,
                'reason' => 'User cancellation',
            ]);

            return back()->with('success', 'Order cancelled successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    /**
     * Renew an order.
     */
    public function renew(ShopOrder $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        if (!$order->canBeRenewed()) {
            return back()->with('error', 'This order cannot be renewed.');
        }

        try {
            $renewed = $this->orderService->renewOrder($order->id);

            if ($renewed) {
                $this->logActivity('Order renewed', $order, [
                    'order_id' => $order->id,
                    'new_due_date' => $order->fresh()->next_due_at,
                ]);

                return back()->with('success', 'Order renewed successfully.');
            } else {
                return back()->with('error', 'Failed to renew order. Please check your payment methods.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to renew order: ' . $e->getMessage());
        }
    }

    /**
     * Process manual payment for an order.
     */
    public function processPayment(Request $request, ShopOrder $order): JsonResponse
    {
        Gate::authorize('update', $order);

        $request->validate([
            'payment_method' => 'required|string|in:stripe,paypal,wallet',
        ]);

        if (!$order->isPending() && !$order->isOverdue()) {
            return $this->errorResponse('This order does not require payment.');
        }

        try {
            $paymentResult = $this->processOrderPayment($order, $request->payment_method, $request->all());

            if ($paymentResult['success']) {
                $this->logActivity('Manual payment processed', $order, [
                    'order_id' => $order->id,
                    'payment_method' => $request->payment_method,
                    'amount' => $order->amount,
                ]);

                return $this->successResponse([
                    'redirect_url' => $paymentResult['redirect_url'] ?? null,
                    'requires_payment_action' => $paymentResult['requires_action'] ?? false,
                    'payment_intent' => $paymentResult['payment_intent'] ?? null,
                ], 'Payment processed successfully!');
            } else {
                return $this->errorResponse($paymentResult['message'] ?? 'Payment processing failed.');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('An error occurred while processing payment.');
        }
    }

    /**
     * Download order invoice.
     */
    public function downloadInvoice(ShopOrder $order): \Illuminate\Http\Response
    {
        Gate::authorize('view', $order);

        // Load necessary relationships for the invoice
        $order->load(['plan.category', 'user', 'server.allocation', 'payments']);

        // Get the domain for the support email
        $domain = request()->getHost();

        // Render the view first to ensure Blade processing
        $html = view('shop::invoice', compact('order', 'domain'))->render();

        // Generate PDF invoice using DomPDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        // Set PDF options for better rendering
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download("invoice-{$order->id}.pdf");
    }

    /**
     * Suspend order (admin action).
     */
    public function suspend(ShopOrder $order, Request $request): JsonResponse
    {
        Gate::authorize('manage', $order);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->orderService->suspendOrder($order->id, $request->input('reason', 'Manual suspension'));

            $this->logActivity('Order suspended', $order, [
                'order_id' => $order->id,
                'reason' => $request->input('reason'),
            ]);

            return $this->successResponse(null, 'Order suspended successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to suspend order: ' . $e->getMessage());
        }
    }

    /**
     * Unsuspend order (admin action).
     */
    public function unsuspend(ShopOrder $order, Request $request): JsonResponse
    {
        Gate::authorize('manage', $order);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->orderService->unsuspendOrder($order->id, $request->input('reason', 'Manual unsuspension'));

            $this->logActivity('Order unsuspended', $order, [
                'order_id' => $order->id,
                'reason' => $request->input('reason'),
            ]);

            return $this->successResponse(null, 'Order unsuspended successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to unsuspend order: ' . $e->getMessage());
        }
    }

    /**
     * Get available payment methods.
     */
    private function getAvailablePaymentMethods(): array
    {
        $methods = [];

        if (config('shop.payment_gateways.stripe.enabled')) {
            $methods['stripe'] = [
                'name' => 'Credit Card',
                'icon' => 'credit-card',
                'description' => 'Pay with credit or debit card',
            ];
        }

        if (config('shop.payment_gateways.paypal.enabled')) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Pay with PayPal',
            ];
        }

        if (config('shop.wallet.enabled')) {
            $wallet = $this->walletService->getWallet(auth()->id());
            $methods['wallet'] = [
                'name' => 'Wallet Balance',
                'icon' => 'wallet',
                'description' => 'Current balance: ' . $this->formatCurrency($wallet->balance ?? 0),
                'available' => ($wallet->balance ?? 0) > 0,
            ];
        }

        return $methods;
    }

    /**
     * Process payment for order.
     */
    private function processOrderPayment($order, string $paymentMethod, array $paymentData): array
    {
        switch ($paymentMethod) {
            case 'wallet':
                return $this->processWalletPayment($order);
                
            case 'stripe':
                return $this->processStripePayment($order, $paymentData);
                
            case 'paypal':
                return $this->processPayPalPayment($order, $paymentData);
                
            default:
                return ['success' => false, 'message' => 'Invalid payment method.'];
        }
    }

    /**
     * Process wallet payment.
     */
    private function processWalletPayment($order): array
    {
        $result = $this->walletService->debit(
            userId: $order->user_id,
            amount: $order->amount,
            description: "Order #{$order->id} payment",
            relatedType: get_class($order),
            relatedId: $order->id
        );

        if ($result) {
            $this->orderService->markAsPaid($order->id, 'wallet');
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Insufficient wallet balance.'];
    }

    /**
     * Process Stripe payment.
     */
    private function processStripePayment($order, array $paymentData): array
    {
        $gateway = $this->paymentManager->getGateway('stripe');
        
        return $gateway->createPayment([
            'amount' => $order->amount,
            'currency' => $order->currency,
            'description' => "Order #{$order->id} payment",
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
            'payment_method_data' => $paymentData['payment_method_data'] ?? [],
        ]);
    }

    /**
     * Process PayPal payment.
     */
    private function processPayPalPayment($order, array $paymentData): array
    {
        $gateway = $this->paymentManager->getGateway('paypal');
        
        return $gateway->createPayment([
            'amount' => $order->amount,
            'currency' => $order->currency,
            'description' => "Order #{$order->id} payment",
            'return_url' => route('shop.orders.show', $order->uuid),
            'cancel_url' => route('shop.orders.show', $order->uuid),
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
        ]);
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }

    /**
     * Create server with user-provided variables.
     */
    public function createServer(Request $request, ShopOrder $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        // Validate that this order can have a server created
        if ($order->status !== 'processing') {
            return redirect()
                ->route('shop.orders.show', $order)
                ->with('error', 'This order is not in a state where a server can be created.');
        }

        if (!$order->requiresVariableInput()) {
            return redirect()
                ->route('shop.orders.show', $order)
                ->with('error', 'This order does not require variable input.');
        }

        if ($order->server_id) {
            return redirect()
                ->route('shop.orders.show', $order)
                ->with('error', 'A server has already been created for this order.');
        }

        // Validate the variables
        $requiredVariables = $order->getRequiredVariables();
        $userVariables = $request->input('variables', []);

        $request->validate([
            'variables' => 'required|array',
        ]);

        // Validate required variables are provided
        foreach ($requiredVariables as $variable) {
            if ($variable['type'] === 'steam_token' && empty($userVariables[$variable['env_variable']])) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['variables.' . $variable['env_variable'] => $variable['user_friendly_name'] . ' is required.']);
            }
        }

        try {
            // Store user variables in the order
            $config = is_string($order->server_config) ? json_decode($order->server_config, true) : $order->server_config;
            $config['user_variables'] = $userVariables;
            $order->update(['server_config' => json_encode($config)]);

            // Create the server with user variables
            $server = $this->orderService->createServerWithVariables($order, $userVariables);

            if ($server) {
                return redirect()
                    ->route('shop.orders.show', $order)
                    ->with('success', 'Server created successfully! Your server is now being set up.');
            } else {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Failed to create server. Please try again or contact support.');
            }

        } catch (\Exception $e) {
            \Log::error('Failed to create server for order ' . $order->id . ': ' . $e->getMessage());
            
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An error occurred while creating your server. Please try again or contact support.');
        }
    }
}
