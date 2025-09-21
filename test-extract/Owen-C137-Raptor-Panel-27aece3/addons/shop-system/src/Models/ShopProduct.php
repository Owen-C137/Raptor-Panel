<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;
use Pterodactyl\Models\Egg;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $description
 * @property bool $visible
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \Illuminate\Database\Eloquent\Collection|\PterodactylAddons\ShopSystem\Models\ShopPlan[] $plans
 */
class ShopProduct extends Model
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
    public const RESOURCE_NAME = 'shop_product';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_products';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'image',
        'price',
        'type',
        'status',
        'visible',
        'sort_order',
        'server_config',
        'metadata',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'description' => 'nullable|string|max:65535',
        'visible' => 'boolean',
        'sort_order' => 'integer|min:0',
    ];

    /**
     * Get the category that this product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'category_id');
    }

    /**
     * Get all plans for this product.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(ShopPlan::class, 'product_id');
    }

    /**
     * Get only visible plans for this product.
     */
    public function visiblePlans(): HasMany
    {
        return $this->plans()->where('visible', true)->orderBy('sort_order');
    }

    /**
     * Get all orders for this product through its plans.
     * Note: This is a hasManyThrough relationship via plans.
     */
    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(
            ShopOrder::class,
            ShopPlan::class,
            'product_id', // Foreign key on plans table
            'plan_id',    // Foreign key on orders table
            'id',         // Local key on products table
            'id'          // Local key on plans table
        );
    }

    /**
     * Scope to only include visible products.
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    /**
     * Get the product name with plan count for display.
     */
    public function getDisplayNameAttribute(): string
    {
        $planCount = $this->plans()->count();
        return sprintf('%s (%d %s)', $this->name, $planCount, $planCount === 1 ? 'plan' : 'plans');
    }
}
