<?php

namespace PterodactylAddons\ShopSystem\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use Pterodactyl\Models\User;

class ShopOrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, ShopOrder $order): bool
    {
        // Users can view their own orders
        // Admins can view all orders
        return $user->id === $order->user_id || $user->root_admin;
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, ShopOrder $order): bool
    {
        // Users can update their own orders (for cancellation, payment, etc.)
        // Admins can update all orders
        return $user->id === $order->user_id || $user->root_admin;
    }

    /**
     * Determine if the user can manage the order (admin functions).
     */
    public function manage(User $user, ShopOrder $order): bool
    {
        // Only admins can manage orders (suspend, unsuspend, etc.)
        return $user->root_admin;
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, ShopOrder $order): bool
    {
        // Only admins can delete orders
        return $user->root_admin;
    }
}
