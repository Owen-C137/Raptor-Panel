<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property decimal $value
 * @property array|null $applicable_plans
 * @property int|null $usage_limit
 * @property int|null $usage_limit_per_user
 * @property int $used_count
 * @property bool $active
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property decimal|null $minimum_amount
 * @property bool $first_order_only
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection|\PterodactylAddons\ShopSystem\Models\ShopCouponUsage[] $usage
 */
class ShopCoupon extends Model
{
    use HasUuids;

    /**
     * Get the columns that should receive a unique identifier.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'shop_coupon';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_coupons';

    /**
     * Coupon type constants.
     */
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'applicable_plans',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'active',
        'valid_from',
        'valid_until',
        'minimum_amount',
        'first_order_only',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'value' => 'decimal:2',
        'applicable_plans' => 'array',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'minimum_amount' => 'decimal:2',
        'first_order_only' => 'boolean',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'code' => 'required|string|max:50|unique:shop_coupons,code',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string|max:65535',
        'type' => 'required|in:percentage,fixed_amount',
        'value' => 'required|numeric|min:0',
        'applicable_plans' => 'nullable|array',
        'applicable_plans.*' => 'integer|exists:shop_plans,id',
        'usage_limit' => 'nullable|integer|min:1',
        'usage_limit_per_user' => 'nullable|integer|min:1',
        'active' => 'boolean',
        'valid_from' => 'nullable|date',
        'valid_until' => 'nullable|date|after:valid_from',
        'minimum_amount' => 'nullable|numeric|min:0',
        'first_order_only' => 'boolean',
    ];

    /**
     * Get all usage records for this coupon.
     */
    public function usage(): HasMany
    {
        return $this->hasMany(ShopCouponUsage::class, 'coupon_id');
    }

    /**
     * Alias for usage() method (plural form).
     */
    public function usages(): HasMany
    {
        return $this->usage();
    }

    /**
     * Scope to only include active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to only include valid coupons (within date range).
     */
    public function scopeValid($query)
    {
        $now = now();
        
        return $query->where('active', true)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('valid_from')
                          ->orWhere('valid_from', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', $now);
                    });
    }

    /**
     * Check if the coupon is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the coupon has reached its usage limit.
     */
    public function hasReachedUsageLimit(): bool
    {
        if (!$this->usage_limit) {
            return false;
        }

        return $this->used_count >= $this->usage_limit;
    }

    /**
     * Check if the user has reached their usage limit for this coupon.
     */
    public function hasUserReachedUsageLimit(int $userId): bool
    {
        if (!$this->usage_limit_per_user) {
            return false;
        }

        $userUsageCount = $this->usage()->where('user_id', $userId)->count();
        
        return $userUsageCount >= $this->usage_limit_per_user;
    }

    /**
     * Check if the coupon is applicable to the given plan.
     */
    public function isApplicableToPlan(int $planId): bool
    {
        if (empty($this->applicable_plans)) {
            return true; // No restrictions
        }

        return in_array($planId, $this->applicable_plans);
    }

    /**
     * Calculate the discount amount for the given amount.
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return ($amount * $this->value) / 100;
        }

        return min($this->value, $amount); // Fixed amount can't exceed the total
    }

    /**
     * Check if the coupon can be used for the given amount and user.
     */
    public function canBeUsed(float $amount, int $userId, ?int $planId = null, bool $isFirstOrder = false): bool
    {
        // Check if coupon is valid
        if (!$this->isValid()) {
            return false;
        }

        // Check usage limits
        if ($this->hasReachedUsageLimit() || $this->hasUserReachedUsageLimit($userId)) {
            return false;
        }

        // Check minimum amount
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }

        // Check if applicable to plan
        if ($planId && !$this->isApplicableToPlan($planId)) {
            return false;
        }

        // Check first order only restriction
        if ($this->first_order_only && !$isFirstOrder) {
            return false;
        }

        return true;
    }

    /**
     * Use the coupon (increment usage count).
     */
    public function use(): void
    {
        $this->increment('used_count');
    }

    /**
     * Get formatted discount value for display.
     */
    public function getFormattedDiscountAttribute(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $this->value . '%';
        }

        return '$' . number_format($this->value, 2);
    }

    /**
     * Get the remaining usage count.
     */
    public function getRemainingUsageAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null; // Unlimited
        }

        return max(0, $this->usage_limit - $this->used_count);
    }

    /**
     * Check if the coupon is expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if the coupon is not yet valid.
     */
    public function isNotYetValid(): bool
    {
        return $this->valid_from && $this->valid_from->isFuture();
    }

    /**
     * Get the current status of the coupon.
     */
    public function getStatus(): string
    {
        // Check if coupon is inactive
        if (!$this->active) {
            return 'inactive';
        }

        // Check if coupon is not yet valid (future start date)
        if ($this->isNotYetValid()) {
            return 'inactive';
        }

        // Check if coupon is expired
        if ($this->isExpired()) {
            return 'expired';
        }

        // Check if coupon has reached usage limit
        if ($this->hasReachedUsageLimit()) {
            return 'used_up';
        }

        // If all checks pass, coupon is active
        return 'active';
    }
}
