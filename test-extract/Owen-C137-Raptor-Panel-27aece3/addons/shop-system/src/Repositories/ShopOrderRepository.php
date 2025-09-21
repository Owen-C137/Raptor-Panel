<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;
use Illuminate\Support\Carbon;

class ShopOrderRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ShopOrder::class;
    }

    /**
     * Get orders for a specific user.
     */
    public function getForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['plan.category', 'server'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active orders for a user.
     */
    public function getActiveForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->with(['plan.category', 'server'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get orders for a specific user with filters and pagination.
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getBuilder()
            ->where('user_id', $userId)
            ->with(['plan.category', 'server']);

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('plan', function ($planQuery) use ($search) {
                    $planQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('plan.category', function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%");
                })
                ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get orders due for renewal.
     */
    public function getDueForRenewal(int $limitHours = 1): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->where('billing_cycle', '!=', ShopOrder::CYCLE_ONE_TIME)
            ->where('next_due_at', '<=', now()->addHours($limitHours))
            ->with(['user', 'plan'])
            ->get();
    }

    /**
     * Get overdue orders.
     */
    public function getOverdue(int $gracePeriodHours = 24): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('status', ShopOrder::STATUS_ACTIVE)
            ->where('billing_cycle', '!=', ShopOrder::CYCLE_ONE_TIME)
            ->where('next_due_at', '<=', now()->subHours($gracePeriodHours))
            ->with(['user', 'plan', 'server'])
            ->get();
    }

    /**
     * Get orders for admin panel with pagination.
     */
    public function getForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getBuilder()
            ->with(['user', 'plan.category', 'server']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['billing_cycle'])) {
            $query->where('billing_cycle', $filters['billing_cycle']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate(25);
    }

    /**
     * Get order statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->getBuilder()->count();
        $active = $this->getBuilder()->where('status', ShopOrder::STATUS_ACTIVE)->count();
        $suspended = $this->getBuilder()->where('status', ShopOrder::STATUS_SUSPENDED)->count();
        $terminated = $this->getBuilder()->where('status', ShopOrder::STATUS_TERMINATED)->count();
        
        $totalRevenue = $this->getBuilder()
            ->where('status', '!=', ShopOrder::STATUS_CANCELLED)
            ->sum('amount');

        $monthlyRevenue = $this->getBuilder()
            ->where('status', '!=', ShopOrder::STATUS_CANCELLED)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return [
            'total_orders' => $total,
            'active_orders' => $active,
            'suspended_orders' => $suspended,
            'terminated_orders' => $terminated,
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
        ];
    }

    /**
     * Get revenue by billing cycle.
     */
    public function getRevenueByCycle(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->selectRaw('billing_cycle, SUM(amount) as revenue, COUNT(*) as orders')
            ->where('status', '!=', ShopOrder::STATUS_CANCELLED)
            ->groupBy('billing_cycle')
            ->get();
    }

    /**
     * Check if user has any orders for a specific plan.
     */
    public function userHasOrdersForPlan(int $userId, int $planId): bool
    {
        return $this->getBuilder()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->whereIn('status', [ShopOrder::STATUS_ACTIVE, ShopOrder::STATUS_SUSPENDED])
            ->exists();
    }

    /**
     * Get the user's first order (for coupon validation).
     */
    public function isFirstOrderForUser(int $userId): bool
    {
        return !$this->getBuilder()
            ->where('user_id', $userId)
            ->whereNotIn('status', [ShopOrder::STATUS_CANCELLED])
            ->exists();
    }
}
