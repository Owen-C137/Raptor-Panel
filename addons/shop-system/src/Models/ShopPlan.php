<?php

namespace PterodactylAddons\ShopSystem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Support\Carbon;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $category_id
 * @property int|null $egg_id
 * @property string $name
 * @property string|null $description
 * @property bool $visible
 * @property int $sort_order
 * @property array $billing_cycles
 * @property array $server_limits
 * @property array $server_feature_limits
 * @property array|null $allowed_nodes
 * @property array|null $allowed_locations
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property \PterodactylAddons\ShopSystem\Models\ShopCategory|null $category
 * @property \Pterodactyl\Models\Egg|null $egg
 * @property \Illuminate\Database\Eloquent\Collection|\PterodactylAddons\ShopSystem\Models\ShopOrder[] $orders
 */
class ShopPlan extends Model
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
    public const RESOURCE_NAME = 'shop_plan';

    /**
     * The table associated with the model.
     */
    protected $table = 'shop_plans';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category_id',
        'egg_id',
        'name',
        'description',
        'visible',
        'status',
        'sort_order',
        'billing_cycles',
        'server_limits',
        'server_feature_limits',
        'allowed_nodes',
        'allowed_locations',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'category_id' => 'integer',
        'egg_id' => 'integer',
        'visible' => 'boolean',
        'sort_order' => 'integer',
        'billing_cycles' => 'array',
        'server_limits' => 'array',
        'server_feature_limits' => 'array',
        'allowed_nodes' => 'array',
        'allowed_locations' => 'array',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static array $validationRules = [
        'category_id' => 'required|exists:shop_categories,id',
        'egg_id' => 'nullable|exists:eggs,id',
        'name' => 'required|string|max:191',
        'description' => 'nullable|string|max:65535',
        'status' => 'required|in:active,inactive,archived',
        'sort_order' => 'integer|min:0',
        'billing_cycles' => 'required|array',
        'billing_cycles.*.cycle' => 'required|in:hourly,monthly,quarterly,semi_annually,annually,one_time',
        'billing_cycles.*.price' => 'required|numeric|min:0',
        'billing_cycles.*.setup_fee' => 'nullable|numeric|min:0',
        'server_limits' => 'required|array',
        'server_limits.memory' => 'required|integer|min:0',
        'server_limits.swap' => 'required|integer|min:-1',
        'server_limits.disk' => 'required|integer|min:0',
        'server_limits.io' => 'required|integer|min:10|max:1000',
        'server_limits.cpu' => 'required|integer|min:0',
        'server_feature_limits' => 'required|array',
        'server_feature_limits.databases' => 'nullable|integer|min:0',
        'server_feature_limits.backups' => 'nullable|integer|min:0',
        'server_feature_limits.allocations' => 'nullable|integer|min:0',
        'allowed_nodes' => 'nullable|array',
        'allowed_nodes.*' => 'integer|exists:nodes,id',
        'allowed_locations' => 'nullable|array',
        'allowed_locations.*' => 'integer|exists:locations,id',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();
        
        // Sync JSON columns to individual columns when saving
        static::saving(function (ShopPlan $plan) {
            $plan->syncJsonToIndividualColumns();
        });
    }

    /**
     * Sync server_limits JSON to individual columns for backward compatibility.
     */
    protected function syncJsonToIndividualColumns(): void
    {
        // Sync server limits
        if ($this->server_limits) {
            $limits = is_string($this->server_limits) ? json_decode($this->server_limits, true) : $this->server_limits;
            
            $this->memory = $limits['memory'] ?? 0;
            $this->swap = $limits['swap'] ?? 0; 
            $this->disk = $limits['disk'] ?? 0;
            $this->io = $limits['io'] ?? 500;
            $this->cpu = $limits['cpu'] ?? 0;
            $this->threads = $limits['threads'] ?? null;
        }
        
        // Sync server feature limits  
        if ($this->server_feature_limits) {
            $featureLimits = is_string($this->server_feature_limits) ? json_decode($this->server_feature_limits, true) : $this->server_feature_limits;
            
            $this->allocation_limit = $featureLimits['allocations'] ?? null;
            $this->database_limit = $featureLimits['databases'] ?? null;
            $this->backup_limit = $featureLimits['backups'] ?? 0;
        }
    }

    /**
     * Get the category that owns this plan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\PterodactylAddons\ShopSystem\Models\ShopCategory::class, 'category_id');
    }



    /**
     * Get the egg associated with this plan.
     */
    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    /**
     * Get all orders for this plan.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'plan_id');
    }

    /**
     * Get all active orders for this plan.
     */
    public function activeOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class, 'plan_id')
                    ->whereIn('status', ['active', 'processing']);
    }

    /**
     * Get servers associated with this plan through active orders.
     */
    public function servers()
    {
        return $this->hasManyThrough(
            \Pterodactyl\Models\Server::class,
            ShopOrder::class,
            'plan_id',   // Foreign key on ShopOrder table
            'id',        // Foreign key on Server table  
            'id',        // Local key on ShopPlan table
            'server_id'  // Local key on ShopOrder table
        )->select('servers.id', 'servers.name', 'servers.uuid')
         ->whereIn('shop_orders.status', ['active', 'processing'])
         ->whereNotNull('shop_orders.server_id');
    }

    /**
     * Get servers associated with this plan (alternative method using joins).
     */
    public function getAssociatedServers()
    {
        return \Pterodactyl\Models\Server::whereIn('id', function ($query) {
            $query->select('server_id')
                  ->from('shop_orders')
                  ->where('plan_id', $this->id)
                  ->whereIn('status', ['active', 'processing'])
                  ->whereNotNull('server_id');
        })->select('id', 'name', 'uuid')->get();
    }

    /**
     * Get the count of active servers for this plan.
     */
    public function getActiveServersCountAttribute()
    {
        return $this->servers()->count();
    }

    /**
     * Get the allowed nodes for this plan as an accessor.
     */
    public function getAllowedNodeModelsAttribute()
    {
        if (empty($this->allowed_nodes)) {
            return collect();
        }

        return Node::whereIn('id', $this->allowed_nodes)->get();
    }

    /**
     * Get the allowed nodes for this plan (method version).
     */
    public function getAllowedNodes()
    {
        if (empty($this->allowed_nodes)) {
            return collect();
        }

        return Node::whereIn('id', $this->allowed_nodes)->get();
    }

    /**
     * Get the allowed locations for this plan as an accessor.
     */
    public function getAllowedLocationModelsAttribute()
    {
        if (empty($this->allowed_locations)) {
            return collect();
        }

        return Location::whereIn('id', $this->allowed_locations)->get();
    }

    /**
     * Get the allowed locations for this plan (method version).
     */
    public function getAllowedLocations()
    {
        if (empty($this->allowed_locations)) {
            return collect();
        }

        return Location::whereIn('id', $this->allowed_locations)->get();
    }

    /**
     * Scope to only include visible plans.
     */
    public function scopeVisible($query)
    {
        return $query->where('status', 'active');
    }



    /**
     * Get the price for a specific billing cycle.
     */
    public function getPriceForCycle(string $cycle): ?array
    {
        foreach ($this->billing_cycles as $billingCycle) {
            if ($billingCycle['cycle'] === $cycle) {
                return $billingCycle;
            }
        }

        return null;
    }

    /**
     * Get the cheapest billing cycle.
     */
    public function getCheapestCycle(): ?array
    {
        if (empty($this->billing_cycles)) {
            return null;
        }

        return collect($this->billing_cycles)->sortBy('price')->first();
    }

    /**
     * Get formatted server limits for display.
     */
    public function getFormattedLimitsAttribute(): array
    {
        return [
            'memory' => $this->server_limits['memory'] > 0 ? $this->server_limits['memory'] . ' MB' : 'Unlimited',
            'disk' => $this->server_limits['disk'] > 0 ? $this->server_limits['disk'] . ' MB' : 'Unlimited',
            'cpu' => $this->server_limits['cpu'] > 0 ? $this->server_limits['cpu'] . '%' : 'Unlimited',
            'swap' => $this->server_limits['swap'] === -1 ? 'Unlimited' : ($this->server_limits['swap'] > 0 ? $this->server_limits['swap'] . ' MB' : 'Disabled'),
            'io' => $this->server_limits['io'] . '/500',
            'databases' => $this->server_feature_limits['databases'] ?? 'Unlimited',
            'backups' => $this->server_feature_limits['backups'] ?? 'Unlimited',
            'allocations' => $this->server_feature_limits['allocations'] ?? 'Unlimited',
        ];
    }

    /**
     * Check if this plan is available on the given node.
     */
    public function isAvailableOnNode(int $nodeId): bool
    {
        if (empty($this->allowed_nodes)) {
            return true; // No restrictions
        }

        return in_array($nodeId, $this->allowed_nodes);
    }

    /**
     * Check if this plan is available in the given location.
     */
    public function isAvailableInLocation(int $locationId): bool
    {
        if (empty($this->allowed_locations)) {
            return true; // No restrictions
        }

        return in_array($locationId, $this->allowed_locations);
    }

    /**
     * Check if this plan is available for purchase.
     */
    public function isAvailable(): bool
    {
        // Use getRawOriginal to bypass any accessor issues
        $status = $this->getRawOriginal('status');
        $visible = (bool) $this->getRawOriginal('visible');
        $billing_cycles = $this->getRawOriginal('billing_cycles');
        
        // Parse billing cycles if it's a JSON string
        if (is_string($billing_cycles)) {
            $billing_cycles = json_decode($billing_cycles, true);
        }
        
        return $status === 'active' && $visible && !empty($billing_cycles);
    }

    /**
     * Get the price attribute (compatibility method).
     * Returns the cheapest cycle price for backward compatibility.
     */
    public function getPriceAttribute(): float
    {
        $cheapest = $this->getCheapestCycle();
        return $cheapest ? (float) $cheapest['price'] : 0.0;
    }

    /**
     * Get the setup fee attribute (compatibility method).
     * Returns the setup fee of the cheapest cycle for backward compatibility.
     */
    public function getSetupFeeAttribute(): float
    {
        $cheapest = $this->getCheapestCycle();
        return $cheapest && isset($cheapest['setup_fee']) ? (float) $cheapest['setup_fee'] : 0.0;
    }

    /**
     * Get the billing cycle attribute (compatibility method).
     * Returns the cheapest cycle type for backward compatibility.
     */
    public function getBillingCycleAttribute(): string
    {
        $cheapest = $this->getCheapestCycle();
        return $cheapest ? $cheapest['cycle'] : 'monthly';
    }

    /**
     * Get the storage attribute (alias for disk).
     */
    public function getStorageAttribute(): int
    {
        return $this->disk ?? 0;
    }

    /**
     * Get the databases attribute (alias for database_limit).
     */
    public function getDatabasesAttribute(): int
    {
        return $this->database_limit ?? 0;
    }

    /**
     * Get the backups attribute (alias for backup_limit).
     */
    public function getBackupsAttribute(): int
    {
        return $this->backup_limit ?? 0;
    }

    /**
     * Get the allocations attribute (alias for allocation_limit).
     */
    public function getAllocationsAttribute(): int
    {
        return $this->allocation_limit ?? 1;
    }
}
