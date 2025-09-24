<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\ShopCoupon;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;
use Illuminate\Support\Carbon;

class ShopCouponRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ShopCoupon::class;
    }

    /**
     * Find a coupon by its code.
     */
    public function findByCode(string $code): ?ShopCoupon
    {
        return $this->getBuilder()
            ->where('code', $code)
            ->first();
    }

    /**
     * Get all active coupons.
     */
    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get valid coupons (active and within date range).
     */
    public function getValid(): \Illuminate\Database\Eloquent\Collection
    {
        $now = now();

        return $this->getBuilder()
            ->where('active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('valid_from')
                      ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', $now);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get coupons applicable to a specific plan.
     */
    public function getApplicableToPlan(int $planId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getValid()
            ->filter(function ($coupon) use ($planId) {
                return $coupon->isApplicableToPlan($planId);
            });
    }

    /**
     * Get expired coupons.
     */
    public function getExpired(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('valid_until', '<', now())
            ->orderBy('valid_until', 'desc')
            ->get();
    }

    /**
     * Get coupons that have reached their usage limit.
     */
    public function getUsageLimitReached(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->whereNotNull('usage_limit')
            ->whereRaw('used_count >= usage_limit')
            ->get();
    }

    /**
     * Get coupons expiring soon.
     */
    public function getExpiringSoon(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $futureDate = now()->addDays($days);

        return $this->getBuilder()
            ->where('active', true)
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [now(), $futureDate])
            ->orderBy('valid_until')
            ->get();
    }

    /**
     * Get coupon usage statistics.
     */
    public function getCouponStatistics(): array
    {
        $total = $this->getBuilder()->count();
        $active = $this->getBuilder()->where('active', true)->count();
        $expired = $this->getBuilder()->where('valid_until', '<', now())->count();
        $usageLimitReached = $this->getBuilder()
            ->whereNotNull('usage_limit')
            ->whereRaw('used_count >= usage_limit')
            ->count();

        $totalUsage = $this->getBuilder()->sum('used_count');
        $totalSavings = \PterodactylAddons\ShopSystem\Models\ShopCouponUsage::sum('discount_amount');

        return [
            'total_coupons' => $total,
            'active_coupons' => $active,
            'expired_coupons' => $expired,
            'usage_limit_reached' => $usageLimitReached,
            'total_usage' => $totalUsage,
            'total_savings' => $totalSavings,
            'average_usage' => $total > 0 ? $totalUsage / $total : 0,
        ];
    }

    /**
     * Get most used coupons.
     */
    public function getMostUsed(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('used_count', '>', 0)
            ->orderBy('used_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get coupons by discount type.
     */
    public function getByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get coupons for admin panel with pagination and filters.
     */
    public function getForAdmin(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = $this->getBuilder()
            ->withCount('usage');

        // Apply filters
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->where('active', true);
                    break;
                case 'inactive':
                    $query->where('active', false);
                    break;
                case 'expired':
                    $query->where('valid_until', '<', now());
                    break;
                case 'usage_limit_reached':
                    $query->whereNotNull('usage_limit')
                          ->whereRaw('used_count >= usage_limit');
                    break;
            }
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
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
     * Get coupon performance analytics.
     */
    public function getPerformanceAnalytics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $analytics = [];
        
        $coupons = $this->getBuilder()
            ->withCount(['usage' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->with(['usage' => function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }])
            ->get();

        foreach ($coupons as $coupon) {
            $totalDiscount = $coupon->usage->sum('discount_amount');
            $usageCount = $coupon->usage->count();

            $analytics[] = [
                'coupon' => $coupon,
                'usage_count' => $usageCount,
                'total_discount' => $totalDiscount,
                'average_discount' => $usageCount > 0 ? $totalDiscount / $usageCount : 0,
                'conversion_rate' => $coupon->usage_limit ? ($usageCount / $coupon->usage_limit) * 100 : null,
            ];
        }

        // Sort by usage count
        usort($analytics, function ($a, $b) {
            return $b['usage_count'] <=> $a['usage_count'];
        });

        return $analytics;
    }

    /**
     * Check if coupon code already exists.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $query = $this->getBuilder()->where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Generate unique coupon code.
     */
    public function generateUniqueCode(string $prefix = '', int $length = 8): string
    {
        do {
            $code = $prefix . strtoupper(\Illuminate\Support\Str::random($length));
        } while ($this->codeExists($code));

        return $code;
    }

    /**
     * Get user's coupon usage history.
     */
    public function getUserUsageHistory(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->whereHas('usage', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['usage' => function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->with('order');
            }])
            ->get();
    }

    /**
     * Search coupons by code or name.
     */
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where(function ($q) use ($query) {
                $q->where('code', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    /**
     * Bulk deactivate coupons.
     */
    public function bulkDeactivate(array $couponIds): int
    {
        return $this->getBuilder()
            ->whereIn('id', $couponIds)
            ->update(['active' => false]);
    }

    /**
     * Bulk activate coupons.
     */
    public function bulkActivate(array $couponIds): int
    {
        return $this->getBuilder()
            ->whereIn('id', $couponIds)
            ->update(['active' => true]);
    }
}
