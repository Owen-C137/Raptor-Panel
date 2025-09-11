<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $plan_id
 * @property int|null $server_id
 * @property string $status
 * @property string $billing_cycle
 * @property decimal $amount
 * @property decimal $setup_fee
 * @property string $currency
 * @property Carbon|null $next_due_at
 * @property Carbon|null $last_renewed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $terminated_at
 * @property array $server_config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \Pterodactyl\Models\User $user
 * @property \PterodactylAddons\ShopSystem\Models\ShopPlan $plan
 * @property \Pterodactyl\Models\Server|null $server
 * @property \Illuminate\Database\Eloquent\Collection|\PterodactylAddons\ShopSystem\Models\ShopPayment[] $payments
 */
class ShopOrder extends Model
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
    public const RESOURCE_NAME = 'shop_order';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_orders';

    /**
     * Order status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_TERMINATED = 'terminated';

    /**
     * Billing cycle constants.
     */
    public const CYCLE_HOURLY = 'hourly';
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_QUARTERLY = 'quarterly';
    public const CYCLE_SEMI_ANNUALLY = 'semi_annually';
    public const CYCLE_ANNUALLY = 'annually';
    public const CYCLE_ONE_TIME = 'one_time';

    /**
     * Get all available order statuses.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_TERMINATED => 'Terminated',
        ];
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'server_id',
        'status',
        'billing_cycle',
        'amount',
        'setup_fee',
        'currency',
        'next_due_at',
        'last_renewed_at',
        'expires_at',
        'suspended_at',
        'terminated_at',
        'server_config',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'user_id' => 'integer',
        'plan_id' => 'integer',
        'server_id' => 'integer',
        'amount' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'next_due_at' => 'datetime',
        'last_renewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'server_config' => 'array',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'plan_id' => 'required|exists:shop_plans,id',
        'server_id' => 'nullable|exists:servers,id',
        'status' => 'required|in:pending,processing,active,suspended,cancelled,terminated',
        'billing_cycle' => 'required|in:hourly,monthly,quarterly,semi_annually,annually,one_time',
        'amount' => 'required|numeric|min:0',
        'setup_fee' => 'nullable|numeric|min:0',
        'currency' => 'required|string|size:3',
        'next_due_at' => 'nullable|date',
        'last_renewed_at' => 'nullable|date',
        'expires_at' => 'nullable|date',
        'suspended_at' => 'nullable|date',
        'terminated_at' => 'nullable|date',
        'server_config' => 'required|array',
    ];

    /**
     * Get the user that owns this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan associated with this order.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ShopPlan::class, 'plan_id');
    }

    /**
     * Get the server associated with this order.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get all payments for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ShopPayment::class, 'order_id');
    }

    /**
     * Scope to only include active orders.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to only include orders that are due for renewal.
     */
    public function scopeDueForRenewal($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('billing_cycle', '!=', self::CYCLE_ONE_TIME)
                    ->where('next_due_at', '<=', now());
    }

    /**
     * Scope to only include orders that are overdue.
     */
    public function scopeOverdue($query, int $gracePeriodHours = 24)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('billing_cycle', '!=', self::CYCLE_ONE_TIME)
                    ->where('next_due_at', '<=', now()->subHours($gracePeriodHours));
    }

    /**
     * Check if the order is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the order is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if the order is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_TERMINATED;
    }

    /**
     * Check if the order is overdue for payment.
     */
    public function isOverdue(int $gracePeriodHours = 24): bool
    {
        if ($this->billing_cycle === self::CYCLE_ONE_TIME || !$this->isActive()) {
            return false;
        }

        return $this->next_due_at && $this->next_due_at->isPast() && 
               $this->next_due_at->diffInHours(now()) > $gracePeriodHours;
    }

    /**
     * Calculate the next due date based on billing cycle.
     */
    public function calculateNextDueDate(?Carbon $from = null): ?Carbon
    {
        if ($this->billing_cycle === self::CYCLE_ONE_TIME) {
            return null;
        }

        $from = $from ?? $this->last_renewed_at ?? $this->created_at;

        return match ($this->billing_cycle) {
            self::CYCLE_HOURLY => $from->addHour(),
            self::CYCLE_MONTHLY => $from->addMonth(),
            self::CYCLE_QUARTERLY => $from->addMonths(3),
            self::CYCLE_SEMI_ANNUALLY => $from->addMonths(6),
            self::CYCLE_ANNUALLY => $from->addYear(),
            default => null,
        };
    }

    /**
     * Get the total amount including setup fee.
     */
    public function getTotalAmountAttribute(): float
    {
        return $this->amount + $this->setup_fee;
    }

    /**
     * Get formatted status for display.
     */
    public function getDisplayStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_TERMINATED => 'Terminated',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_SUSPENDED => 'bg-orange-100 text-orange-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            self::STATUS_TERMINATED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
