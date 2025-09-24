<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\ShopPayment;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;
use Illuminate\Support\Carbon;

class ShopPaymentRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ShopPayment::class;
    }

    /**
     * Get payments for a specific user.
     */
    public function getForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['order.plan.category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get payments by status.
     */
    public function getByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('status', $status)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending payments (for processing).
     */
    public function getPendingPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('status', ShopPayment::STATUS_PENDING)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get payments by gateway.
     */
    public function getByGateway(string $gateway): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('gateway', $gateway)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find payment by gateway transaction ID.
     */
    public function findByGatewayTransaction(string $gateway, string $transactionId): ?ShopPayment
    {
        return $this->getBuilder()
            ->where('gateway', $gateway)
            ->where('gateway_transaction_id', $transactionId)
            ->first();
    }

    /**
     * Get failed payments for retry processing.
     */
    public function getFailedPaymentsForRetry(int $maxAttempts = 3): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('status', ShopPayment::STATUS_FAILED)
            ->whereRaw('JSON_EXTRACT(gateway_metadata, "$.retry_count") < ?', [$maxAttempts])
            ->with(['user', 'order'])
            ->get();
    }

    /**
     * Get payment statistics for admin dashboard.
     */
    public function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $allPayments = $this->getBuilder()
            ->where('created_at', '>=', $startDate);

        $completedPayments = clone $allPayments;
        $completedPayments->where('status', ShopPayment::STATUS_COMPLETED);

        $failedPayments = clone $allPayments;
        $failedPayments->where('status', ShopPayment::STATUS_FAILED);

        return [
            'total_payments' => $allPayments->count(),
            'completed_payments' => $completedPayments->count(),
            'failed_payments' => $failedPayments->count(),
            'pending_payments' => $this->getBuilder()
                ->where('status', ShopPayment::STATUS_PENDING)
                ->count(),
            'total_revenue' => $completedPayments->sum('amount'),
            'average_payment' => $completedPayments->avg('amount'),
            'success_rate' => $allPayments->count() > 0 ? 
                ($completedPayments->count() / $allPayments->count()) * 100 : 0,
        ];
    }

    /**
     * Get revenue by gateway.
     */
    public function getRevenueByGateway(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        return $this->getBuilder()
            ->selectRaw('gateway, COUNT(*) as payment_count, SUM(amount) as total_revenue')
            ->where('status', ShopPayment::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->groupBy('gateway')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get revenue by payment type.
     */
    public function getRevenueByType(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        return $this->getBuilder()
            ->selectRaw('type, COUNT(*) as payment_count, SUM(amount) as total_revenue')
            ->where('status', ShopPayment::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->groupBy('type')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get daily revenue for charts.
     */
    public function getDailyRevenue(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        return $this->getBuilder()
            ->selectRaw('DATE(created_at) as date, SUM(amount) as revenue, COUNT(*) as payment_count')
            ->where('status', ShopPayment::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get payments for admin panel with pagination and filters.
     */
    public function getForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getBuilder()
            ->with(['user', 'order.plan.category']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['gateway'])) {
            $query->where('gateway', $filters['gateway']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        if (isset($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (isset($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate(25);
    }

    /**
     * Get payment trends for analytics.
     */
    public function getPaymentTrends(int $months = 12): array
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            $monthData = $this->getBuilder()
                ->selectRaw('
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = ? THEN 1 END) as successful_payments,
                    SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as revenue
                ', [ShopPayment::STATUS_COMPLETED, ShopPayment::STATUS_COMPLETED])
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->first();
            
            $trends[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('M Y'),
                'total_payments' => $monthData->total_payments,
                'successful_payments' => $monthData->successful_payments,
                'revenue' => $monthData->revenue,
                'success_rate' => $monthData->total_payments > 0 ? 
                    ($monthData->successful_payments / $monthData->total_payments) * 100 : 0,
            ];
        }
        
        return $trends;
    }

    /**
     * Get top customers by payment volume.
     */
    public function getTopCustomers(int $limit = 10, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $startDate = now()->subDays($days);

        return $this->getBuilder()
            ->selectRaw('user_id, COUNT(*) as payment_count, SUM(amount) as total_spent')
            ->where('status', ShopPayment::STATUS_COMPLETED)
            ->where('created_at', '>=', $startDate)
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search payments by various criteria.
     */
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where(function ($q) use ($query) {
                $q->where('gateway_transaction_id', 'LIKE', "%{$query}%")
                  ->orWhere('uuid', 'LIKE', "%{$query}%")
                  ->orWhereHas('user', function ($userQuery) use ($query) {
                      $userQuery->where('username', 'LIKE', "%{$query}%")
                               ->orWhere('email', 'LIKE', "%{$query}%");
                  })
                  ->orWhereHas('order', function ($orderQuery) use ($query) {
                      $orderQuery->where('uuid', 'LIKE', "%{$query}%");
                  });
            })
            ->with(['user', 'order.plan.category'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
