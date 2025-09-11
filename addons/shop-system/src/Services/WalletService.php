<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\UserWallet;
use PterodactylAddons\ShopSystem\Models\WalletTransaction;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create a wallet for a user.
     */
    public function getOrCreateWallet(User $user, string $currency = null): UserWallet
    {
        $currency = $currency ?? config('shop.currency', 'USD');
        
        return UserWallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $currency],
            ['balance' => 0]
        );
    }

    /**
     * Get a wallet for a user by user ID.
     */
    public function getWallet(int $userId, string $currency = null): UserWallet
    {
        $user = User::findOrFail($userId);
        return $this->getOrCreateWallet($user, $currency);
    }

    /**
     * Add funds to a user's wallet.
     */
    public function addFunds(UserWallet $wallet, float $amount, string $description = '', string $type = 'credit'): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $type) {
            $transaction = $wallet->addFunds($amount, $description, $type);
            
            // Log activity
            activity()
                ->performedOn($wallet)
                ->causedBy($wallet->user)
                ->withProperties([
                    'amount' => $amount,
                    'new_balance' => $wallet->fresh()->balance,
                    'transaction_id' => $transaction->id,
                ])
                ->log("Wallet credited: {$description}");

            return $transaction;
        });
    }

    /**
     * Deduct funds from a user's wallet.
     */
    public function deductFunds(UserWallet $wallet, float $amount, string $description = '', string $type = 'debit'): ?WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $description, $type) {
            $transaction = $wallet->deductFunds($amount, $description, $type);
            
            if ($transaction) {
                // Log activity
                activity()
                    ->performedOn($wallet)
                    ->causedBy($wallet->user)
                    ->withProperties([
                        'amount' => $amount,
                        'new_balance' => $wallet->fresh()->balance,
                        'transaction_id' => $transaction->id,
                    ])
                    ->log("Wallet debited: {$description}");
            }

            return $transaction;
        });
    }

    /**
     * Transfer funds between wallets.
     */
    public function transfer(UserWallet $fromWallet, UserWallet $toWallet, float $amount, string $description = ''): bool
    {
        if (!$fromWallet->hasSufficientFunds($amount)) {
            return false;
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // Deduct from sender
            $debitTransaction = $this->deductFunds(
                $fromWallet,
                $amount,
                "Transfer to {$toWallet->user->username}: {$description}",
                'transfer_out'
            );

            if (!$debitTransaction) {
                return false;
            }

            // Add to recipient
            $this->addFunds(
                $toWallet,
                $amount,
                "Transfer from {$fromWallet->user->username}: {$description}",
                'transfer_in'
            );

            return true;
        });
    }

    /**
     * Process a refund to a user's wallet.
     */
    public function refund(UserWallet $wallet, float $amount, string $description = ''): WalletTransaction
    {
        return $this->addFunds($wallet, $amount, $description, 'refund');
    }

    /**
     * Get wallet transaction history.
     */
    public function getTransactionHistory(UserWallet $wallet, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $wallet->transactions()
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Get wallet statistics for a user.
     */
    public function getWalletStatistics(UserWallet $wallet): array
    {
        $transactions = $wallet->transactions();
        
        return [
            'total_credits' => $transactions->clone()->credits()->sum('amount'),
            'total_debits' => abs($transactions->clone()->debits()->sum('amount')),
            'transaction_count' => $transactions->count(),
            'last_transaction' => $transactions->latest()->first(),
        ];
    }

    /**
     * Check if a user has sufficient funds for an amount.
     */
    public function hasSufficientFunds(User $user, float $amount, string $currency = null): bool
    {
        $wallet = $this->getOrCreateWallet($user, $currency);
        
        return $wallet->hasSufficientFunds($amount);
    }

    /**
     * Get or create wallet and return balance.
     */
    public function getBalance(User $user, string $currency = null): float
    {
        $wallet = $this->getOrCreateWallet($user, $currency);
        
        return $wallet->balance;
    }

    /**
     * Process automatic wallet top-up if enabled and configured.
     */
    public function processAutoTopup(UserWallet $wallet): bool
    {
        $autoTopupConfig = config('shop.wallet.auto_topup');
        
        if (!$autoTopupConfig['enabled']) {
            return false;
        }

        $threshold = $autoTopupConfig['threshold'];
        $amount = $autoTopupConfig['amount'];

        if ($wallet->balance <= $threshold) {
            // Here you would integrate with payment gateway to charge the user
            // For now, we'll just return false indicating no auto-topup occurred
            return false;
        }

        return false;
    }

    /**
     * Calculate wallet usage statistics for admin.
     */
    public function getSystemWalletStatistics(): array
    {
        return [
            'total_wallets' => UserWallet::count(),
            'total_balance' => UserWallet::sum('balance'),
            'total_transactions' => WalletTransaction::count(),
            'total_volume' => WalletTransaction::sum(\DB::raw('ABS(amount)')),
            'average_balance' => UserWallet::avg('balance'),
        ];
    }
}
