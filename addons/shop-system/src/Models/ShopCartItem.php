<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopCartItem extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'shop_cart_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'product_options',
        'server_config',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_options' => 'array',
        'server_config' => 'array',
    ];

    /**
     * Get the cart this item belongs to.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(ShopCart::class, 'cart_id');
    }

    // Product relationship removed since we now use plans directly

    /**
     * Calculate total price based on quantity and unit price.
     */
    public function calculateTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Update quantity and recalculate total.
     */
    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->total_price = $this->calculateTotal();
        $this->save();
    }
}
