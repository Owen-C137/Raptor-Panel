<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\UserWallet;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;
use Pterodactyl\Models\User;

class UserWalletRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return UserWallet::class;
    }

    /**
     * Get or create a wallet for a user.
     */
    public function getOrCreateForUser(User $user, string $currency = 'USD'): UserWallet
    {
        return $this->getBuilder()
            ->firstOrCreate(
                ['user_id' => $user->id, 'currency' => $currency],
                ['balance' => 0]
            );
    }

    /**
     * Get wallet with transaction history.
     */
    public function getWithTransactions(int $walletId, int $transactionLimit = 50): UserWallet
    {
        return $this->getBuilder()
            ->with(['transactions' => function ($query) use ($transactionLimit) {
                $query->orderBy('created_at', 'desc')->limit($transactionLimit);
            }])
            ->findOrFail($walletId);
    }

    /**
     * Get wallet transactions for a user.
     */
    public function getTransactions(int $userId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $wallet = $this->getBuilder()
            ->where('user_id', $userId)
            ->first();

        if (!$wallet) {
            // Return empty paginator if no wallet exists
            return new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $perPage,
                1,
                ['path' => request()->url()]
            );
        }

        return $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user wallet statistics.
     */
    public function getUserStatistics(int $userId): array
    {
        $wallet = $this->getBuilder()
            ->where('user_id', $userId)
            ->first();

        if (!$wallet) {
            return [
                'balance' => 0,
                'total_spent' => 0,
                'total_deposited' => 0,
                'transaction_count' => 0,
            ];
        }

        $transactions = $wallet->transactions();
        $credits = $transactions->where('amount', '>', 0);
        $debits = $transactions->where('amount', '<', 0);
        
        // Calculate this month's spending
        $monthlyDebits = $wallet->transactions()
            ->where('amount', '<', 0)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        return [
            'balance' => $wallet->balance,
            'total_spent' => abs($debits->sum('amount')),
            'total_deposited' => $credits->sum('amount'),
            'transaction_count' => $transactions->count(),
            'monthly_spending' => abs($monthlyDebits->sum('amount')),
        ];
    }

    /**
     * Get users with wallet balance above threshold.
     */
    public function getUsersWithBalanceAbove(float $threshold, string $currency = 'USD'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('currency', $currency)
            ->where('balance', '>', $threshold)
            ->with('user')
            ->get();
    }

    /**
     * Get users with wallet balance below threshold (for auto top-up).
     */
    public function getUsersWithBalanceBelow(float $threshold, string $currency = 'USD'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('currency', $currency)
            ->where('balance', '<', $threshold)
            ->with('user')
            ->get();
    }

    /**
     * Get wallet statistics for admin dashboard.
     */
    public function getSystemStatistics(string $currency = null): array
    {
        $query = $this->getBuilder();
        
        if ($currency) {
            $query->where('currency', $currency);
        }

        return [
            'total_wallets' => $query->count(),
            'total_balance' => $query->sum('balance'),
            'average_balance' => $query->avg('balance'),
            'wallets_with_balance' => $query->where('balance', '>', 0)->count(),
            'highest_balance' => $query->max('balance'),
            'lowest_balance' => $query->min('balance'),
        ];
    }

    /**
     * Get top wallets by balance.
     */
    public function getTopWalletsByBalance(int $limit = 10, string $currency = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->getBuilder()
            ->with('user')
            ->orderBy('balance', 'desc');

        if ($currency) {
            $query->where('currency', $currency);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get wallet activity statistics.
     */
    public function getActivityStatistics(int $walletId, int $days = 30): array
    {
        $wallet = $this->find($walletId);
        
        $startDate = now()->subDays($days);
        
        $transactions = $wallet->transactions()
            ->where('created_at', '>=', $startDate)
            ->get();

        $credits = $transactions->where('amount', '>', 0);
        $debits = $transactions->where('amount', '<', 0);

        return [
            'total_transactions' => $transactions->count(),
            'total_credits' => $credits->sum('amount'),
            'total_debits' => abs($debits->sum('amount')),
            'credit_count' => $credits->count(),
            'debit_count' => $debits->count(),
            'net_change' => $credits->sum('amount') + $debits->sum('amount'),
            'average_transaction' => $transactions->avg('amount'),
        ];
    }

    /**
     * Search wallets by user information.
     */
    public function searchByUser(string $search): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->whereHas('user', function ($query) use ($search) {
                $query->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
            })
            ->with('user')
            ->get();
    }

    /**
     * Get wallets for admin panel with pagination and filters.
     */
    public function getForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getBuilder()->with('user');

        // Apply filters
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (isset($filters['min_balance'])) {
            $query->where('balance', '>=', $filters['min_balance']);
        }

        if (isset($filters['max_balance'])) {
            $query->where('balance', '<=', $filters['max_balance']);
        }

        if (!empty($filters['user_search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $search = $filters['user_search'];
                $q->where('username', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('balance', 'desc')
                    ->paginate(25);
    }

    /**
     * Get currency distribution.
     */
    public function getCurrencyDistribution(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->selectRaw('currency, COUNT(*) as wallet_count, SUM(balance) as total_balance')
            ->groupBy('currency')
            ->orderBy('total_balance', 'desc')
            ->get();
    }

    /**
     * Get users who haven't used their wallet recently.
     */
    public function getInactiveWallets(int $days = 90): \Illuminate\Database\Eloquent\Collection
    {
        $cutoffDate = now()->subDays($days);

        return $this->getBuilder()
            ->whereDoesntHave('transactions', function ($query) use ($cutoffDate) {
                $query->where('created_at', '>=', $cutoffDate);
            })
            ->where('balance', '>', 0)
            ->with('user')
            ->get();
    }
}
