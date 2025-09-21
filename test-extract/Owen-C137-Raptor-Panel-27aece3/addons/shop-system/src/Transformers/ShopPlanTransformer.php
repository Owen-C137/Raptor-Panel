<?php

namespace PterodactylAddons\ShopSystem\Transformers;

use PterodactylAddons\ShopSystem\Models\Shop\ShopPlan;
use Illuminate\Support\Collection;

class ShopPlanTransformer
{
    /**
     * Transform a single plan.
     */
    public static function make(ShopPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'uuid' => $plan->uuid,
            'product_id' => $plan->product_id,
            'name' => $plan->name,
            'description' => $plan->description,
            'price' => $plan->price,
            'setup_fee' => $plan->setup_fee,
            'billing_cycle' => $plan->billing_cycle,
            'resources' => [
                'cpu' => $plan->cpu,
                'memory' => $plan->memory,
                'storage' => $plan->storage,
                'databases' => $plan->databases,
                'backups' => $plan->backups,
                'allocations' => $plan->allocations,
            ],
            'limits' => [
                'io' => $plan->io,
                'swap' => $plan->swap,
                'oom_disabled' => $plan->oom_disabled,
            ],
            'node_ids' => $plan->node_ids,
            'location_ids' => $plan->location_ids,
            'enabled' => $plan->enabled,
            'available' => $plan->isAvailable(),
            'stock' => $plan->stock,
            'max_orders_per_user' => $plan->max_orders_per_user,
            'metadata' => $plan->metadata,
            'created_at' => $plan->created_at->toISOString(),
            'updated_at' => $plan->updated_at->toISOString(),
        ];
    }

    /**
     * Transform a collection of plans.
     */
    public static function collection($plans): array
    {
        if ($plans instanceof Collection) {
            return $plans->map(fn($plan) => self::make($plan))->toArray();
        }

        return array_map(fn($plan) => self::make($plan), $plans);
    }
}
