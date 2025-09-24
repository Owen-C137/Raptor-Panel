<?php

namespace PterodactylAddons\ShopSystem\Transformers;

use PterodactylAddons\ShopSystem\Models\Shop\ShopPayment;
use Illuminate\Support\Collection;

class ShopPaymentTransformer
{
    /**
     * Transform a single payment.
     */
    public static function make(ShopPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'order_id' => $payment->order_id,
            'user_id' => $payment->user_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'gateway' => $payment->gateway,
            'gateway_transaction_id' => $payment->gateway_transaction_id,
            'gateway_fee' => $payment->gateway_fee,
            'metadata' => $payment->metadata,
            'created_at' => $payment->created_at->toISOString(),
            'updated_at' => $payment->updated_at->toISOString(),
            'processed_at' => $payment->processed_at?->toISOString(),
            'failed_at' => $payment->failed_at?->toISOString(),
            'refunded_at' => $payment->refunded_at?->toISOString(),
            
            // Status helpers
            'is_completed' => $payment->isCompleted(),
            'is_failed' => $payment->isFailed(),
            'is_refunded' => $payment->isRefunded(),
            'is_pending' => $payment->isPending(),
        ];
    }

    /**
     * Transform a collection of payments.
     */
    public static function collection($payments): array
    {
        if ($payments instanceof Collection) {
            return $payments->map(fn($payment) => self::make($payment))->toArray();
        }

        return array_map(fn($payment) => self::make($payment), $payments);
    }
}
