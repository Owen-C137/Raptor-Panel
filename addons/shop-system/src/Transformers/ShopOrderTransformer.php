<?php

namespace PterodactylAddons\ShopSystem\Transformers;

use PterodactylAddons\ShopSystem\Models\Shop\ShopOrder;
use Illuminate\Support\Collection;

class ShopOrderTransformer
{
    /**
     * Transform a single order.
     */
    public static function make(ShopOrder $order): array
    {
        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'user_id' => $order->user_id,
            'plan_id' => $order->plan_id,
            'server_id' => $order->server_id,
            'status' => $order->status,
            'amount' => $order->amount,
            'setup_fee' => $order->setup_fee,
            'currency' => $order->currency,
            'billing_cycle' => $order->billing_cycle,
            'quantity' => $order->quantity,
            'coupon_code' => $order->coupon_code,
            'discount_amount' => $order->discount_amount,
            'payment_method' => $order->payment_method,
            'external_id' => $order->external_id,
            'billing_details' => $order->billing_details,
            'metadata' => $order->metadata,
            'notes' => $order->notes,
            'created_at' => $order->created_at->toISOString(),
            'updated_at' => $order->updated_at->toISOString(),
            'next_due_at' => $order->next_due_at?->toISOString(),
            'suspended_at' => $order->suspended_at?->toISOString(),
            'cancelled_at' => $order->cancelled_at?->toISOString(),
            'terminated_at' => $order->terminated_at?->toISOString(),
            
            // Relationships
            'plan' => $order->plan ? ShopPlanTransformer::make($order->plan) : null,
            'server' => $order->server ? self::transformServer($order->server) : null,
            'payments' => $order->payments ? $order->payments->map(function ($payment) {
                return ShopPaymentTransformer::make($payment);
            }) : [],
            
            // Status helpers
            'is_active' => $order->isActive(),
            'is_pending' => $order->isPending(),
            'is_suspended' => $order->isSuspended(),
            'is_cancelled' => $order->isCancelled(),
            'is_terminated' => $order->isTerminated(),
            'is_overdue' => $order->isOverdue(),
            'can_be_cancelled' => $order->canBeCancelled(),
            'can_be_renewed' => $order->canBeRenewed(),
            
            // Time calculations
            'days_until_due' => $order->next_due_at ? now()->diffInDays($order->next_due_at, false) : null,
            'days_overdue' => $order->isOverdue() ? $order->next_due_at->diffInDays(now()) : 0,
        ];
    }

    /**
     * Transform a collection of orders.
     */
    public static function collection($orders): array
    {
        if ($orders instanceof Collection) {
            return $orders->map(fn($order) => self::make($order))->toArray();
        }

        return array_map(fn($order) => self::make($order), $orders);
    }

    /**
     * Transform server information (basic details only for API).
     */
    private static function transformServer($server): array
    {
        return [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'uuidShort' => $server->uuidShort,
            'name' => $server->name,
            'description' => $server->description,
            'status' => $server->status,
            'node_id' => $server->node_id,
            'allocation_id' => $server->allocation_id,
            'created_at' => $server->created_at->toISOString(),
            'updated_at' => $server->updated_at->toISOString(),
        ];
    }
}
