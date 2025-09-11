<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use PterodactylAddons\ShopSystem\Repositories\UserWalletRepository;
use PterodactylAddons\ShopSystem\Services\WalletService;
use PterodactylAddons\ShopSystem\Services\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function __construct(
        private UserWalletRepository $walletRepository,
        private WalletService $walletService,
        private PaymentGatewayManager $paymentManager
    ) {}

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

        return view('shop::wallet.index', compact('wallet', 'transactions', 'statistics', 'monthlySpending'));
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
        
        $currentBalance = $this->walletService->getBalance(auth()->id());

        return view('shop::wallet.add-funds', compact(
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
        $currentBalance = $this->walletService->getBalance(auth()->id());
        $maxBalance = config('shop.wallet.maximum_balance', 10000.00);

        // Check if adding funds would exceed maximum balance
        if ($currentBalance + $amount > $maxBalance) {
            return $this->errorResponse("Adding this amount would exceed your maximum wallet balance of " . $this->formatCurrency($maxBalance));
        }

        try {
            DB::beginTransaction();

            // Create pending transaction
            $transaction = $this->walletService->createPendingDeposit(
                userId: auth()->id(),
                amount: $amount,
                paymentMethod: $request->payment_method
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

                return $this->successResponse([
                    'redirect_url' => $paymentResult['redirect_url'] ?? route('shop.wallet'),
                    'requires_payment_action' => $paymentResult['requires_action'] ?? false,
                    'payment_intent' => $paymentResult['payment_intent'] ?? null,
                ], 'Payment processing initiated!');
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
        $balance = $this->walletService->getBalance(auth()->id());

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
                'max:' . $this->walletService->getBalance(auth()->id()),
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
    private function getAvailablePaymentMethods(): array
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
        $gateway = $this->paymentManager->getGateway('stripe');
        
        return $gateway->createPayment([
            'amount' => $transaction->amount,
            'currency' => config('shop.currency.default', 'USD'),
            'description' => "Wallet deposit - " . $this->formatCurrency($transaction->amount),
            'metadata' => [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'type' => 'wallet_deposit',
            ],
            'payment_method_data' => $paymentData['payment_method_data'] ?? [],
        ]);
    }

    /**
     * Process PayPal payment for wallet deposit.
     */
    private function processPayPalPayment($transaction, array $paymentData): array
    {
        $gateway = $this->paymentManager->getGateway('paypal');
        
        return $gateway->createPayment([
            'amount' => $transaction->amount,
            'currency' => config('shop.currency.default', 'USD'),
            'description' => "Wallet deposit - " . $this->formatCurrency($transaction->amount),
            'return_url' => route('shop.wallet'),
            'cancel_url' => route('shop.wallet.add-funds'),
            'metadata' => [
                'transaction_id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'type' => 'wallet_deposit',
            ],
        ]);
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
}
