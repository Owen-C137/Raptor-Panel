<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\User;

/**
 * @property int $id
 * @property int $user_id
 * @property decimal $balance
 * @property string $currency
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \Pterodactyl\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection|\PterodactylAddons\ShopSystem\Models\WalletTransaction[] $transactions
 */
class UserWallet extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'user_wallet';

    /**
     * The table associated with the model.
     */
    protected $table = 'user_wallets';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'balance',
        'currency',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'user_id' => 'integer',
        'balance' => 'decimal:2',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'user_id' => 'required|exists:users,id',
        'balance' => 'required|numeric|min:0',
        'currency' => 'required|string|size:3',
    ];

    /**
     * Get the user that owns this wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Add funds to the wallet.
     */
    public function addFunds(float $amount, string $description = '', string $type = 'credit'): WalletTransaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description,
        ]);
    }

    /**
     * Deduct funds from the wallet.
     */
    public function deductFunds(float $amount, string $description = '', string $type = 'debit'): ?WalletTransaction
    {
        if ($this->balance < $amount) {
            return null; // Insufficient funds
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'description' => $description,
        ]);
    }

    /**
     * Check if the wallet has sufficient funds.
     */
    public function hasSufficientFunds(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance with currency symbol.
     */
    public function getFormattedBalanceAttribute(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';
        
        return $symbol . number_format($this->balance, 2);
    }
}
