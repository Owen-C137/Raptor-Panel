<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\User;

/**
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property int $order_id
 * @property decimal $discount_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \PterodactylAddons\ShopSystem\Models\ShopCoupon $coupon
 * @property \Pterodactyl\Models\User $user
 * @property \PterodactylAddons\ShopSystem\Models\ShopOrder $order
 */
class ShopCouponUsage extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'shop_coupon_usage';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_coupon_usage';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'coupon_id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'discount_amount' => 'decimal:2',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'coupon_id' => 'required|exists:shop_coupons,id',
        'user_id' => 'required|exists:users,id',
        'order_id' => 'required|exists:shop_orders,id',
        'discount_amount' => 'required|numeric|min:0',
    ];

    /**
     * Get the coupon that was used.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(ShopCoupon::class, 'coupon_id');
    }

    /**
     * Get the user who used the coupon.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order the coupon was used on.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'order_id');
    }

    /**
     * Get formatted discount amount.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        return '$' . number_format($this->discount_amount, 2);
    }
}
