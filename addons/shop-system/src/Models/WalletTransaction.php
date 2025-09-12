<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $wallet_id
 * @property string $type
 * @property decimal $amount
 * @property decimal $balance_after
 * @property string|null $description
 * @property array|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \PterodactylAddons\ShopSystem\Models\UserWallet $wallet
 */
class WalletTransaction extends Model
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
    public const RESOURCE_NAME = 'wallet_transaction';

    /**
     * The table associated with the model.
     */
    protected $table = 'wallet_transactions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'wallet_id' => 'integer',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'wallet_id' => 'required|exists:user_wallets,id',
        'type' => 'required|in:credit,debit,refund,payment,topup,adjustment',
        'amount' => 'required|numeric',
        'balance_before' => 'required|numeric|min:0',
        'balance_after' => 'required|numeric|min:0',
        'description' => 'nullable|string|max:255',
        'metadata' => 'nullable|array',
    ];

    /**
     * Get the wallet this transaction belongs to.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(UserWallet::class, 'wallet_id');
    }

    /**
     * Get the user that owns the wallet for this transaction.
     * This is an accessor that loads the relationship dynamically.
     */
    public function getUserAttribute()
    {
        if (!$this->relationLoaded('wallet')) {
            $this->load('wallet.user');
        }
        return $this->wallet?->user;
    }

    /**
     * Scope to only include credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->whereIn('type', ['credit', 'topup', 'refund']);
    }

    /**
     * Scope to only include debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->whereIn('type', ['debit', 'payment']);
    }

    /**
     * Get the formatted amount with proper sign.
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = number_format(abs($this->amount), 2);
        $prefix = $this->amount >= 0 ? '+' : '-';
        
        return $prefix . $amount;
    }

    /**
     * Get the transaction type for display.
     */
    public function getDisplayTypeAttribute(): string
    {
        return match ($this->type) {
            'credit' => 'Credit',
            'debit' => 'Debit',
            'refund' => 'Refund',
            'payment' => 'Payment',
            'topup' => 'Top-up',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the CSS class for the transaction type.
     */
    public function getTypeClassAttribute(): string
    {
        return match ($this->type) {
            'credit', 'topup', 'refund' => 'text-green-600',
            'debit', 'payment' => 'text-red-600',
            'adjustment' => 'text-yellow-600',
            default => 'text-gray-600',
        };
    }
}
