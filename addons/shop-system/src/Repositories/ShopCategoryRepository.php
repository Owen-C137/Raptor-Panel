<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use Illuminate\Pagination\LengthAwarePaginator;

class ShopCategoryRepository
{
    /**
     * Get all visible categories with their plans.
     */
    public function getVisibleCategories(int $perPage = 12): LengthAwarePaginator
    {
        return ShopCategory::query()
            ->where('active', true)
            ->with(['plans' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('sort_order')
                      ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get all category names for filtering.
     */
    public function getCategoryNames(): SupportCollection
    {
        return ShopCategory::query()
            ->where('active', true)
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Get featured categories.
     */
    public function getFeaturedCategories(int $limit = 5): Collection
    {
        return ShopCategory::query()
            ->where('active', true)
            ->with(['plans' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('sort_order')
                      ->orderBy('name')
                      ->limit(3);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get related categories (same type or similar).
     */
    public function getRelatedCategories(ShopCategory $category, int $limit = 4): Collection
    {
        return ShopCategory::query()
            ->where('active', true)
            ->where('id', '!=', $category->id)
            ->with(['plans' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('sort_order')
                      ->orderBy('name')
                      ->limit(3);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Search categories by name or description.
     */
    public function searchCategories(string $search, int $perPage = 12): LengthAwarePaginator
    {
        return ShopCategory::query()
            ->where('active', true)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->with(['plans' => function ($query) {
                $query->where('status', 'active')
                      ->orderBy('sort_order')
                      ->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }
}
