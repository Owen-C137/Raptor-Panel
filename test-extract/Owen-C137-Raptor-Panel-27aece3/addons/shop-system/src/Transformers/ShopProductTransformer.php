<?php

namespace PterodactylAddons\ShopSystem\Transformers;

use PterodactylAddons\ShopSystem\Models\Shop\ShopProduct;
use Illuminate\Support\Collection;

class ShopProductTransformer
{
    /**
     * Transform a single product.
     */
    public static function make(ShopProduct $product): array
    {
        return [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'type' => $product->type,
            'image_url' => $product->image_url,
            'sort_order' => $product->sort_order,
            'visible' => $product->visible,
            'enabled' => $product->enabled,
            'featured' => $product->featured,
            'metadata' => $product->metadata,
            'created_at' => $product->created_at->toISOString(),
            'updated_at' => $product->updated_at->toISOString(),
            'plans' => $product->plans->map(function ($plan) {
                return ShopPlanTransformer::make($plan);
            }),
        ];
    }

    /**
     * Transform a collection of products.
     */
    public static function collection($products): array
    {
        if ($products instanceof Collection) {
            return $products->map(fn($product) => self::make($product))->toArray();
        }

        return array_map(fn($product) => self::make($product), $products);
    }
}
