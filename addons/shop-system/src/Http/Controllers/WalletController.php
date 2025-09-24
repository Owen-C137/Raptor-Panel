<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use PterodactylAddons\ShopSystem\Repositories\UserWalletRepository;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use PterodactylAddons\ShopSystem\Models\WalletTransaction;
use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PterodactylAddons\ShopSystem\Mail\WalletFundsAddedMail;
use PterodactylAddons\ShopSystem\Services\ShopConfigService;
use PterodactylAddons\ShopSystem\Services\CurrencyService;

class WalletController extends BaseShopController
{
    public function __construct(
        private UserWalletRepository $walletRepository,
        private PaymentGatewayManager $paymentGatewayManager,
        ShopConfigService $shopConfigService,
        WalletService $walletService,
        CurrencyService $currencyService
    ) {
        parent::__construct($shopConfigService, $walletService, $currencyService);
    }

    /**
     * Display wallet dashboard.
     */
    public function index(): View
    {
        $this->checkShopAvailability();

        $wallet = $this->walletService->getWallet(auth()->id());
        $transactions = $this->walletRepository->getTransactions(
            userId: auth()->id(),
            perPage: 15
        );
        
        $statistics = $this->walletRepository->getUserStatistics(auth()->id());
        $monthlySpending = $statistics['monthly_spending'] ?? 0;

        return $this->view('shop::wallet.index', compact('wallet', 'transactions', 'statistics', 'monthlySpending'));
    }

    /**
     * Display add funds page.
     */
    public function addFunds(): View
    {
        $this->checkShopAvailability();

        $paymentMethods = $this->getAvailablePaymentMethods();
        $minimumDeposit = config('shop.wallet.minimum_deposit', 5.00);
        $maximumBalance = config('shop.wallet.maximum_balance', 10000.00);
        
        $currentBalance = $this->walletService->getBalance(auth()->user());

        return $this->view('shop::wallet.add-funds', compact(
            'paymentMethods', 
            'minimumDeposit', 
            'maximumBalance',
            'currentBalance'
        ));
    }

    /**
     * Process add funds request.
     */
    public function processAddFunds(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:' . config('shop.wallet.minimum_deposit', 5.00),
                'max:1000',
            ],
            'payment_method' => 'required|string|in:stripe,paypal',
        ]);

        $amount = $request->amount;
        $currentBalance = $this->walletService->getBalance(auth()->user());
        $maxBalance = config('shop.wallet.maximum_balance', 10000.00);

        // Check if adding funds would exceed maximum balance
        if ($currentBalance + $amount > $maxBalance) {
            return $this->errorResponse("Adding this amount would exceed your maximum wallet balance of " . $this->formatCurrency($maxBalance));
        }

        try {
            DB::beginTransaction();

            // Create pending transaction
            $transaction = $this->walletService->createPendingDeposit(
                auth()->id(),
                $amount,
                $request->payment_method
            );

            // Process payment
            $paymentResult = $this->processPayment($transaction, $request->payment_method, $request->all());

            if ($paymentResult['success']) {
                DB::commit();

                $this->logActivity('Wallet deposit initiated', null, [
                    'amount' => $amount,
                    'payment_method' => $request->payment_method,
                    'transaction_id' => $transaction->id,
                ]);

                // Handle different response formats for different payment methods
                $response = ['success' => true];
                
                if (isset($paymentResult['client_secret'])) {
                    // Stripe payment intent
                    $response['client_secret'] = $paymentResult['client_secret'];
                    $response['payment_intent_id'] = $paymentResult['payment_intent_id'];
                } elseif (isset($paymentResult['approval_url'])) {
                    // PayPal payment
                    $response['redirect_url'] = $paymentResult['approval_url'];
                } elseif (isset($paymentResult['redirect_url'])) {
                    // Legacy format
                    $response['redirect_url'] = $paymentResult['redirect_url'];
                }

                return response()->json($response);
            } else {
                DB::rollBack();
                return $this->errorResponse($paymentResult['message'] ?? 'Payment processing failed.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Wallet deposit failed', [
                'user_id' => auth()->id(),
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('An error occurred while processing your deposit.');
        }
    }

    /**
     * Get wallet balance via AJAX.
     */
    public function getBalance(): JsonResponse
    {
        $balance = $this->walletService->getBalance(auth()->user());

        return $this->successResponse([
            'balance' => $balance,
            'formatted_balance' => $this->formatCurrency($balance),
        ]);
    }

    /**
     * Get wallet transactions via AJAX.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'from_date', 'to_date']);
        $transactions = $this->walletRepository->getTransactions(
            userId: auth()->id(),
            filters: $filters,
            perPage: $request->integer('per_page', 15)
        );

        return $this->successResponse([
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Transfer funds to another user (if enabled).
     */
    public function transfer(Request $request): JsonResponse
    {
        if (!config('shop.wallet.transfers_enabled', false)) {
            return $this->errorResponse('Wallet transfers are not enabled.');
        }

        $request->validate([
            'recipient' => 'required|string',
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:' . $this->walletService->getBalance(auth()->user()),
            ],
            'note' => 'nullable|string|max:255',
        ]);

        try {
            // Find recipient user
            $recipient = \Pterodactyl\Models\User::where('username', $request->recipient)
                ->orWhere('email', $request->recipient)
                ->first();

            if (!$recipient) {
                return $this->errorResponse('Recipient user not found.');
            }

            if ($recipient->id === auth()->id()) {
                return $this->errorResponse('You cannot transfer funds to yourself.');
            }

            $result = $this->walletService->transfer(
                fromUserId: auth()->id(),
                toUserId: $recipient->id,
                amount: $request->amount,
                note: $request->input('note', '')
            );

            if ($result) {
                $this->logActivity('Wallet transfer sent', $recipient, [
                    'amount' => $request->amount,
                    'recipient' => $recipient->username,
                    'note' => $request->input('note'),
                ]);

                return $this->successResponse(null, 'Transfer completed successfully!');
            } else {
                return $this->errorResponse('Transfer failed. Please check your balance.');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Get available payment methods for deposits.
     */
    protected function getAvailablePaymentMethods(): array
    {
        $methods = [];

        if (config('shop.payment_gateways.stripe.enabled')) {
            $methods['stripe'] = [
                'name' => 'Credit Card',
                'icon' => 'credit-card',
                'description' => 'Add funds using your credit or debit card',
                'fees' => '2.9% + $0.30',
            ];
        }

        if (config('shop.payment_gateways.paypal.enabled')) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'icon' => 'paypal',
                'description' => 'Add funds using your PayPal account',
                'fees' => '3.5% + $0.30',
            ];
        }

        return $methods;
    }

    /**
     * Process payment for wallet deposit.
     */
    private function processPayment($transaction, string $paymentMethod, array $paymentData): array
    {
        try {
            switch ($paymentMethod) {
                case 'stripe':
                    return $this->processStripePayment($transaction, $paymentData);
                    
                case 'paypal':
                    return $this->processPayPalPayment($transaction, $paymentData);
                    
                default:
                    return ['success' => false, 'message' => 'Invalid payment method.'];
            }
        } catch (\Exception $e) {
            Log::error('Wallet deposit payment error', [
                'transaction_id' => $transaction->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => 'Payment processing failed.'];
        }
    }

    /**
     * Process Stripe payment for wallet deposit.
     */
    private function processStripePayment($transaction, array $paymentData): array
    {
        try {
            $gateway = $this->paymentGatewayManager->gateway('stripe');
            $currency = $this->shopConfigService->getSetting('currency', 'USD');
            
            // Get Stripe configuration
            $stripeConfig = $gateway->getConfig();
            if (!isset($stripeConfig['secret_key'])) {
                throw new \Exception('Stripe secret key not configured');
            }
            
            // Debug log the available config keys
            \Log::info('Stripe config keys available: ' . implode(', ', array_keys($stripeConfig)));
            
            // Create a simplified payment intent directly with Stripe
            \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);
            
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => intval($transaction->amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'description' => "Wallet deposit - " . $this->formatCurrency($transaction->amount),
                'metadata' => [
                    'type' => 'wallet_deposit',
                    'user_id' => $transaction->user_id,
                    'transaction_id' => $transaction->id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];

        } catch (\Exception $e) {
            \Log::error('Stripe wallet payment failed', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process PayPal payment for wallet deposit.
     */
    private function processPayPalPayment($transaction, array $paymentData): array
    {
        try {
            $gateway = $this->paymentGatewayManager->gateway('paypal');
            $currency = $this->shopConfigService->getSetting('currency', 'USD');
            
            // Get PayPal configuration
            $paypalConfig = $gateway->getConfig();
            if (!isset($paypalConfig['client_id']) || !isset($paypalConfig['client_secret'])) {
                throw new \Exception('PayPal credentials not configured');
            }
            
            // Debug log the available config keys
            \Log::info('PayPal config keys available: ' . implode(', ', array_keys($paypalConfig)));
            
            // Create PayPal environment - default to sandbox if mode not set
            $mode = $paypalConfig['mode'] ?? 'sandbox';
            $environment = $mode === 'live' 
                ? new \PayPalCheckoutSdk\Core\LiveEnvironment($paypalConfig['client_id'], $paypalConfig['client_secret'])
                : new \PayPalCheckoutSdk\Core\SandboxEnvironment($paypalConfig['client_id'], $paypalConfig['client_secret']);
            
            $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
            
            $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
            $request->headers["prefer"] = "return=representation";
            $request->body = [
                "intent" => "CAPTURE",
                "purchase_units" => [[
                    "reference_id" => "wallet_deposit_{$transaction->id}",
                    "amount" => [
                        "value" => number_format($transaction->amount, 2, '.', ''),
                        "currency_code" => $currency
                    ],
                    "description" => "Wallet deposit - " . $this->formatCurrency($transaction->amount)
                ]],
                "application_context" => [
                    "brand_name" => $this->shopConfigService->getSetting('shop_name', 'Game Server Shop'),
                    "cancel_url" => route('shop.wallet.index'),
                    "return_url" => route('shop.wallet.deposit.paypal.return', ['transaction' => $transaction->id]),
                ]
            ];

            $response = $client->execute($request);
            $order = $response->result;

            // Find approval URL
            $approvalUrl = null;
            foreach ($order->links as $link) {
                if ($link->rel === 'approve') {
                    $approvalUrl = $link->href;
                    break;
                }
            }

            return [
                'success' => true,
                'approval_url' => $approvalUrl,
                'order_id' => $order->id,
            ];

        } catch (\Exception $e) {
            \Log::error('PayPal wallet payment failed', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle auto top-up settings.
     */
    public function autoTopup(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $userId = auth()->id();
            
            // Store auto-topup settings in user preferences or a dedicated table
            // For now, we'll return success as this is a placeholder implementation
            // In a real implementation, you'd store these settings in the database
            
            Log::info('Auto top-up settings updated', [
                'user_id' => $userId,
                'enabled' => $request->enabled,
                'threshold' => $request->threshold,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Auto top-up settings saved successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Auto top-up error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save auto top-up settings.',
            ], 500);
        }
    }

    /**
     * Export wallet transactions as CSV.
     */
    public function exportTransactions(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->checkShopAvailability();
        
        $userId = auth()->id();
        $transactions = $this->walletRepository->getTransactions($userId, 1000); // Get up to 1000 transactions
        
        $filename = 'wallet_transactions_' . date('Y-m-d') . '.csv';
        
        return response()->streamDownload(function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Type',
                'Description',
                'Amount',
                'Balance After',
                'Status',
                'Reference'
            ]);
            
            // Add transaction data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    ucfirst($transaction->type),
                    $transaction->description ?? 'N/A',
                    $this->formatCurrency($transaction->amount),
                    $this->formatCurrency($transaction->balance_after ?? 0),
                    ucfirst($transaction->status ?? 'completed'),
                    $transaction->reference ?? 'N/A'
                ]);
            }
            
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Handle Stripe deposit return/completion.
     */
    public function stripeDepositReturn(Request $request): RedirectResponse
    {
        try {
            $paymentIntentId = $request->get('payment_intent');
            $transactionId = $request->get('transaction_id');

            if (!$paymentIntentId || !$transactionId) {
                Log::error('Missing payment_intent or transaction_id in Stripe deposit return');
                return redirect()->route('shop.wallet.index')->with('error', 'Invalid payment return data.');
            }

            $payment = ShopPayment::findOrFail($transactionId);
            
            // Verify the payment with Stripe
            $gateway = $this->paymentGatewayManager->gateway('stripe');
            $paymentResult = $gateway->verifyPayment($paymentIntentId);

            if ($paymentResult['success'] && $paymentResult['status'] === 'succeeded') {
                $this->completeWalletPayment($payment, $paymentIntentId);
                return redirect()->route('shop.wallet.index')->with('success', 'Funds added successfully!');
            } else {
                Log::error('Stripe wallet deposit verification failed', [
                    'transaction_id' => $transactionId,
                    'payment_intent' => $paymentIntentId,
                    'result' => $paymentResult
                ]);
                return redirect()->route('shop.wallet.index')->with('error', 'Payment verification failed.');
            }

        } catch (\Exception $e) {
            Log::error('Stripe wallet deposit return error: ' . $e->getMessage());
            return redirect()->route('shop.wallet.index')->with('error', 'An error occurred processing your payment.');
        }
    }

    /**
     * Handle PayPal deposit return/completion.
     */
    public function paypalDepositReturn(Request $request): RedirectResponse
    {
        try {
            $token = $request->get('token');
            $transactionParam = $request->get('transaction');

            if (!$token || !$transactionParam) {
                Log::error('Missing token or transaction in PayPal deposit return');
                return redirect()->route('shop.wallet.index')->with('error', 'Invalid payment return data.');
            }

            $payment = ShopPayment::findOrFail($transactionParam);
            
            // Capture the PayPal order
            $gateway = $this->paymentGatewayManager->gateway('paypal');
            
            // Get PayPal configuration and capture order
            $paypalConfig = $gateway->getConfig();
            $mode = $paypalConfig['mode'] ?? 'sandbox';
            $environment = $mode === 'live' 
                ? new \PayPalCheckoutSdk\Core\LiveEnvironment($paypalConfig['client_id'], $paypalConfig['client_secret'])
                : new \PayPalCheckoutSdk\Core\SandboxEnvironment($paypalConfig['client_id'], $paypalConfig['client_secret']);
            
            $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);
            
            $request_capture = new \PayPalCheckoutSdk\Orders\OrdersCaptureRequest($token);
            $request_capture->headers["prefer"] = "return=representation";
            
            $response = $client->execute($request_capture);
            $order = $response->result;

            if ($order->status === 'COMPLETED') {
                $this->completeWalletPayment($payment, $token);
                return redirect()->route('shop.wallet.index')->with('success', 'Funds added successfully!');
            } else {
                Log::error('PayPal wallet deposit capture failed', [
                    'transaction_id' => $transactionParam,
                    'token' => $token,
                    'status' => $order->status
                ]);
                return redirect()->route('shop.wallet.index')->with('error', 'Payment capture failed.');
            }

        } catch (\Exception $e) {
            Log::error('PayPal wallet deposit return error: ' . $e->getMessage());
            return redirect()->route('shop.wallet.index')->with('error', 'An error occurred processing your payment.');
        }
    }

    /**
     * Handle PayPal deposit cancellation.
     */
    public function paypalDepositCancel(Request $request): RedirectResponse
    {
        $transactionId = $request->get('transaction_id');

        if ($transactionId) {
            try {
                $transaction = WalletTransaction::findOrFail($transactionId);
                $transaction->update([
                    'status' => 'cancelled',
                    'notes' => 'Payment cancelled by user'
                ]);

                Log::info('PayPal wallet deposit cancelled', ['transaction_id' => $transactionId]);
            } catch (\Exception $e) {
                Log::error('Error updating cancelled wallet deposit: ' . $e->getMessage());
            }
        }

        return redirect()->route('shop.wallet.index')->with('info', 'Payment was cancelled.');
    }

    /**
     * Complete a wallet deposit from ShopPayment.
     */
    private function completeWalletPayment(ShopPayment $payment, string $paymentReference): void
    {
        try {
            DB::beginTransaction();

            // Get current wallet balance before update
            $wallet = $this->walletService->getWallet($payment->user_id);
            $balanceBefore = $wallet->balance;

            // Add funds to wallet with payment method metadata
            $walletTransaction = $this->walletService->addFunds(
                $wallet,
                $payment->amount,
                "Deposit via " . ucfirst($payment->gateway),
                'credit'
            );

            // Update transaction with payment method metadata
            $walletTransaction->update([
                'metadata' => [
                    'payment_method' => $payment->gateway,
                    'payment_reference' => $paymentReference,
                    'shop_payment_id' => $payment->id
                ]
            ]);

            // Update payment status
            $payment->update([
                'status' => ShopPayment::STATUS_COMPLETED,
                'gateway_transaction_id' => $paymentReference,
                'processed_at' => now()
            ]);

            DB::commit();

            // Send email notification using the wallet transaction
            $this->sendFundsAddedEmail($walletTransaction);

            Log::info('Wallet deposit completed successfully', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
                'gateway' => $payment->gateway,
                'payment_reference' => $paymentReference,
                'wallet_transaction_id' => $walletTransaction->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete wallet payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Complete a wallet deposit transaction.
     */
    private function completeWalletDeposit(WalletTransaction $transaction, string $paymentReference): void
    {
        try {
            DB::beginTransaction();

            // Get current wallet balance before update
            $wallet = $this->walletService->getWallet($transaction->user_id);
            $balanceBefore = $wallet->balance;

            // Add funds to wallet
            $this->walletService->addFunds(
                $wallet,
                $transaction->amount,
                "Deposit via " . ucfirst($transaction->payment_method),
                'credit'
            );

            // Update transaction status
            $transaction->update([
                'status' => 'completed',
                'reference' => $paymentReference,
                'balance_before' => $balanceBefore,
                'completed_at' => now()
            ]);

            DB::commit();

            // Send email notification
            $this->sendFundsAddedEmail($transaction);

            Log::info('Wallet deposit completed successfully', [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'payment_reference' => $paymentReference
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete wallet deposit', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send email notification for wallet funds added from ShopPayment.
     */
    private function sendWalletFundsAddedEmail(ShopPayment $payment): void
    {
        try {
            $user = \Pterodactyl\Models\User::findOrFail($payment->user_id);
            $wallet = $this->walletService->getWallet($payment->user_id);

            Mail::to($user->email)->send(new WalletFundsAddedMail(
                user: $user,
                amount: $payment->amount,
                newBalance: $wallet->balance,
                paymentMethod: ucfirst($payment->gateway),
                transactionId: $payment->id
            ));

            Log::info('Wallet funds added email sent', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send wallet funds added email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - email failure shouldn't fail the transaction
        }
    }

    /**
     * Send email notification for successful funds addition.
     */
    private function sendFundsAddedEmail(WalletTransaction $transaction): void
    {
        try {
            // Reload transaction with wallet and its user relationship
            $transaction->load(['wallet.user']);
            
            Mail::to($transaction->wallet->user->email)->send(new WalletFundsAddedMail($transaction));
            
            Log::info('Wallet funds added email sent successfully', [
                'transaction_id' => $transaction->id,
                'user_email' => $transaction->wallet->user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send wallet funds added email', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - we don't want email failure to break the deposit completion
        }
    }
}
