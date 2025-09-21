<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\ShopProduct;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;

class ShopProductRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ShopProduct::class;
    }

    /**
     * Get all active products with their plans.
     */
    public function getVisibleProducts($perPage = 12): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->getBuilder()
            ->where('status', 'active')
            ->with(['plans'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a product with all its plans.
     */
    public function getWithPlans(int $id): ShopProduct
    {
        return $this->getBuilder()
            ->with(['plans' => function ($query) {
                $query->orderBy('sort_order');
            }])
            ->findOrFail($id);
    }

    /**
     * Get products for admin panel with plan counts.
     */
    public function getForAdmin(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->withCount('plans')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Update sort orders for multiple products.
     */
    public function updateSortOrders(array $orders): void
    {
        foreach ($orders as $id => $order) {
            $this->getBuilder()->where('id', $id)->update(['sort_order' => $order]);
        }
    }

    /**
     * Get the next sort order for new products.
     */
    public function getNextSortOrder(): int
    {
        return $this->getBuilder()->max('sort_order') + 1;
    }
}
