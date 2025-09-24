<?php

namespace PterodactylAddons\ShopSystem\Repositories;

use PterodactylAddons\ShopSystem\Models\ShopPlan;
use Pterodactyl\Contracts\Repository\RepositoryInterface;
use Pterodactyl\Repositories\Eloquent\EloquentRepository;

class ShopPlanRepository extends EloquentRepository implements RepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ShopPlan::class;
    }



    /**
     * Get plans for a specific category.
     */
    public function getByCategory(int $categoryId, bool $onlyEnabled = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->getBuilder()
            ->where('category_id', $categoryId)
            ->with(['category', 'egg']);
            
        if ($onlyEnabled) {
            $query->where('status', 'active');
        }
        
        return $query->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Get a plan with its relationships for order creation.
     */
    public function getForOrder(int $id): ShopPlan
    {
        return $this->getBuilder()
            ->with(['category', 'egg'])
            ->findOrFail($id);
    }

    /**
     * Get plans available on a specific node.
     */
    public function getAvailableOnNode(int $nodeId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('visible', true)
            ->where(function ($query) use ($nodeId) {
                $query->whereNull('allowed_nodes')
                      ->orWhereJsonContains('allowed_nodes', $nodeId);
            })
            ->with(['category'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get plans available in a specific location.
     */
    public function getAvailableInLocation(int $locationId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('visible', true)
            ->where(function ($query) use ($locationId) {
                $query->whereNull('allowed_locations')
                      ->orWhereJsonContains('allowed_locations', $locationId);
            })
            ->with(['category'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get plans for admin management by category.
     */
    public function getForAdminByCategory(int $categoryId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('category_id', $categoryId)
            ->with(['category', 'egg'])
            ->withCount('orders')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Update sort orders for multiple plans.
     */
    public function updateSortOrders(array $orders): void
    {
        foreach ($orders as $id => $order) {
            $this->getBuilder()->where('id', $id)->update(['sort_order' => $order]);
        }
    }

    /**
     * Get the next sort order for new plans in a category.
     */
    public function getNextSortOrderForCategory(int $categoryId): int
    {
        return $this->getBuilder()
            ->where('category_id', $categoryId)
            ->max('sort_order') + 1;
    }

    /**
     * Search plans by name or description.
     */
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('visible', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->with(['category'])
            ->orderBy('sort_order')
            ->get();
    }
}
