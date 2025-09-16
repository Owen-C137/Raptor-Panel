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
 * @property array|null $billing_details
 * @property string|null $payment_method
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
        'billing_details',
        'payment_method',
        'is_renewal',
        'original_order_id',
        'discount_amount',
        'cancellation_reason',
        'cancelled_at',
        'auto_delete_at',
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
        'discount_amount' => 'decimal:2',
        'next_due_at' => 'datetime',
        'last_renewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'auto_delete_at' => 'datetime',
        'server_config' => 'array',
        'billing_details' => 'array',
        'is_renewal' => 'boolean',
    ];

    /**
     * Bootstrap the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Set auto-deletion date when order status changes to cancelled
        static::updating(function ($order) {
            if ($order->isDirty('status') && $order->status === self::STATUS_CANCELLED) {
                // Set auto-deletion date to 7 days from now if not already set
                if (!$order->auto_delete_at) {
                    $order->auto_delete_at = now()->addDays(7);
                }
                // Also set cancelled_at if not already set
                if (!$order->cancelled_at) {
                    $order->cancelled_at = now();
                }
            }
            
            // Clear auto-deletion date if order becomes active again (renewal)
            if ($order->isDirty('status') && $order->status === self::STATUS_ACTIVE) {
                $order->auto_delete_at = null;
            }
        });
    }

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
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
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

    /**
     * Get status color for Bootstrap badges.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_ACTIVE => 'success',
            self::STATUS_SUSPENDED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_TERMINATED => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabel(): string
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
     * Get customer full name from billing details.
     */
    public function getCustomerName(): ?string
    {
        if (!$this->billing_details) {
            return null;
        }

        $firstName = $this->billing_details['first_name'] ?? '';
        $lastName = $this->billing_details['last_name'] ?? '';
        
        return trim($firstName . ' ' . $lastName) ?: null;
    }

    /**
     * Get customer email from billing details.
     */
    public function getCustomerEmail(): ?string
    {
        return $this->billing_details['email'] ?? $this->user->email ?? null;
    }

    /**
     * Get billing address as formatted string.
     */
    public function getBillingAddress(): ?string
    {
        if (!$this->billing_details) {
            return null;
        }

        $address = [];
        
        if (!empty($this->billing_details['address'])) {
            $address[] = $this->billing_details['address'];
        }
        
        if (!empty($this->billing_details['address2'])) {
            $address[] = $this->billing_details['address2'];
        }
        
        $cityStateZip = [];
        if (!empty($this->billing_details['city'])) {
            $cityStateZip[] = $this->billing_details['city'];
        }
        
        if (!empty($this->billing_details['state'])) {
            $cityStateZip[] = $this->billing_details['state'];
        }
        
        if (!empty($this->billing_details['postal_code'])) {
            $cityStateZip[] = $this->billing_details['postal_code'];
        }
        
        if (!empty($cityStateZip)) {
            $address[] = implode(', ', $cityStateZip);
        }
        
        if (!empty($this->billing_details['country'])) {
            $address[] = $this->billing_details['country'];
        }
        
        return !empty($address) ? implode("\n", $address) : null;
    }

    /**
     * Check if billing details are complete.
     */
    public function hasBillingDetails(): bool
    {
        return !empty($this->billing_details) && 
               !empty($this->billing_details['first_name']) && 
               !empty($this->billing_details['last_name']);
    }

    /**
     * Check if this order requires variable input from the user.
     */
    public function requiresVariableInput(): bool
    {
        $config = is_string($this->server_config) ? json_decode($this->server_config, true) : $this->server_config;
        return is_array($config) && 
               isset($config['requires_variables']) && 
               $config['requires_variables'] === true;
    }

    /**
     * Get the required variables for this order.
     */
    public function getRequiredVariables(): array
    {
        $config = is_string($this->server_config) ? json_decode($this->server_config, true) : $this->server_config;
        
        if (!is_array($config) || !isset($config['required_variables'])) {
            return [];
        }
        
        return $config['required_variables'];
    }

    /**
     * Check if user has provided all required variables.
     */
    public function hasProvidedVariables(): bool
    {
        $config = is_string($this->server_config) ? json_decode($this->server_config, true) : $this->server_config;
        return is_array($config) && isset($config['user_variables']);
    }

    /**
     * Get user-provided variables.
     */
    public function getUserVariables(): array
    {
        $config = is_string($this->server_config) ? json_decode($this->server_config, true) : $this->server_config;
        
        if (!is_array($config) || !isset($config['user_variables'])) {
            return [];
        }
        
        return $config['user_variables'];
    }

    /**
     * Check if the payment for this order has been completed.
     */
    public function isPaymentCompleted(): bool
    {
        return $this->payments()->where('status', 'completed')->exists();
    }

    /**
     * Get the payment status for this order.
     */
    public function getPaymentStatus(): string
    {
        $latestPayment = $this->payments()->latest()->first();
        
        if (!$latestPayment) {
            return 'pending';
        }
        
        return $latestPayment->status;
    }

    /**
     * Check if the order can be manually processed (e.g., for stuck orders).
     */
    public function canProcess(): bool
    {
        // Orders can be processed if they are:
        // 1. Paid but stuck in processing status (likely need variable input)
        // 2. Have failed server creation and need retry
        return $this->isPaymentCompleted() && in_array($this->status, [
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Check if the order can be cancelled by the user.
     */
    public function canCancel(): bool
    {
        // Orders can be cancelled if they are:
        // 1. Pending payment
        // 2. Processing but not yet completed
        // Cannot cancel active, suspended, already cancelled, or terminated orders
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Check if the order has active subscriptions (recurring billing).
     */
    public function hasActiveSubscriptions(): bool
    {
        // An order has active subscriptions if:
        // 1. It's in active status
        // 2. It has a recurring billing cycle (not one-time)
        // 3. It has a future due date
        return $this->status === self::STATUS_ACTIVE && 
               !is_null($this->next_due_at) && 
               $this->next_due_at->isFuture();
    }

    /**
     * Get the next renewal date for this order.
     */
    public function getNextRenewalDate(): ?\Carbon\Carbon
    {
        return $this->next_due_at;
    }

    /**
     * Calculate the monthly equivalent amount based on billing cycle
     */
    public function getMonthlyAmount()
    {
        $amount = $this->amount;
        
        switch ($this->billing_cycle) {
            case 'hourly':
                return $amount * 24 * 30; // 24 hours * 30 days
            case 'daily':
                return $amount * 30; // 30 days
            case 'weekly':
                return $amount * 4.33; // ~4.33 weeks per month
            case 'monthly':
                return $amount;
            case 'quarterly':
                return $amount / 3; // 3 months
            case 'semi-annually':
                return $amount / 6; // 6 months
            case 'annually':
                return $amount / 12; // 12 months
            default:
                return $amount; // fallback to original amount
        }
    }

    /**
     * Check if renewal can be cancelled
     */
    public function canCancelRenewal()
    {
        // Can cancel renewal if:
        // 1. Order is completed/active
        // 2. Has active subscriptions
        // 3. Not already cancelled
        return $this->status === 'completed' && 
               $this->hasActiveSubscriptions() && 
               !in_array($this->status, ['cancelled', 'refunded']);
    }

    /**
     * Check if the user can download an invoice for this order.
     */
    public function canDownloadInvoice(): bool
    {
        // Allow invoice download for paid orders or orders in processing
        return $this->isPaymentCompleted() || in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_PROCESSING
        ]);
    }
}
