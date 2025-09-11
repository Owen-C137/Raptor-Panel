<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Pterodactyl\Models\User;

class ShopCart extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shop_cart';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'status',
        'total_amount',
        'metadata',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user that owns the cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cart items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShopCartItem::class, 'cart_id');
    }

    /**
     * Scope to get active carts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get expired carts.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Check if cart is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if cart is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Calculate total amount from items.
     */
    public function calculateTotal(): float
    {
        return $this->items->sum('total_price');
    }

    /**
     * Get item count.
     */
    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }
}
