<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\User;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $order_id
 * @property string $type
 * @property string $status
 * @property decimal $amount
 * @property string $currency
 * @property string $gateway
 * @property string|null $gateway_transaction_id
 * @property array|null $gateway_metadata
 * @property Carbon|null $processed_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \Pterodactyl\Models\User $user
 * @property \PterodactylAddons\ShopSystem\Models\ShopOrder|null $order
 */
class ShopPayment extends Model
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
    public const RESOURCE_NAME = 'shop_payment';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_payments';

    /**
     * Payment type constants.
     */
    public const TYPE_ORDER_PAYMENT = 'order_payment';
    public const TYPE_RENEWAL = 'renewal';
    public const TYPE_SETUP_FEE = 'setup_fee';
    public const TYPE_WALLET_TOPUP = 'wallet_topup';
    public const TYPE_REFUND = 'refund';

    /**
     * Payment status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    /**
     * Gateway constants.
     */
    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_PAYPAL = 'paypal';
    public const GATEWAY_MANUAL = 'manual';
    public const GATEWAY_WALLET = 'wallet';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'status',
        'amount',
        'currency',
        'gateway',
        'gateway_transaction_id',
        'gateway_metadata',
        'processed_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'decimal:2',
        'gateway_metadata' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'order_id' => 'nullable|exists:shop_orders,id',
        'type' => 'required|in:order_payment,renewal,setup_fee,wallet_topup,refund',
        'status' => 'required|in:pending,processing,completed,failed,cancelled,refunded',
        'amount' => 'required|numeric|min:0',
        'currency' => 'required|string|size:3',
        'gateway' => 'required|string|max:50',
        'gateway_transaction_id' => 'nullable|string|max:255',
        'gateway_metadata' => 'nullable|array',
        'processed_at' => 'nullable|date',
        'failed_at' => 'nullable|date',
    ];

    /**
     * Get the user that owns this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with this payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    /**
     * Scope to only include completed payments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to only include failed payments.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if the payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the payment failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(?string $transactionId = null, ?array $metadata = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();
        
        if ($transactionId) {
            $this->gateway_transaction_id = $transactionId;
        }
        
        if ($metadata) {
            $this->gateway_metadata = array_merge($this->gateway_metadata ?? [], $metadata);
        }

        return $this->save();
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(?array $metadata = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        
        if ($metadata) {
            $this->gateway_metadata = array_merge($this->gateway_metadata ?? [], $metadata);
        }

        return $this->save();
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        
        return $symbol . number_format($this->amount, 2);
    }

    /**
     * Get display type for UI.
     */
    public function getDisplayTypeAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_ORDER_PAYMENT => 'Order Payment',
            self::TYPE_RENEWAL => 'Renewal Payment',
            self::TYPE_SETUP_FEE => 'Setup Fee',
            self::TYPE_WALLET_TOPUP => 'Wallet Top-up',
            self::TYPE_REFUND => 'Refund',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get display status for UI.
     */
    public function getDisplayStatusAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
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
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
            self::STATUS_REFUNDED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
