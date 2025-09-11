<?php

namespace PterodactylAddons\ShopSystem\Transformers;

use PterodactylAddons\ShopSystem\Models\UserWallet;
use Illuminate\Support\Collection;

class WalletTransformer
{
    /**
     * Transform a single wallet.
     */
    public static function make(UserWallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'total_earned' => $wallet->total_earned,
            'total_spent' => $wallet->total_spent,
            'created_at' => $wallet->created_at->toISOString(),
            'updated_at' => $wallet->updated_at->toISOString(),
            
            // Additional calculated fields
            'formatted_balance' => self::formatCurrency($wallet->balance, $wallet->currency),
            'formatted_earned' => self::formatCurrency($wallet->total_earned, $wallet->currency),
            'formatted_spent' => self::formatCurrency($wallet->total_spent, $wallet->currency),
        ];
    }

    /**
     * Transform a collection of wallets.
     */
    public static function collection($wallets): array
    {
        if ($wallets instanceof Collection) {
            return $wallets->map(fn($wallet) => self::make($wallet))->toArray();
        }

        return array_map(fn($wallet) => self::make($wallet), $wallets);
    }

    /**
     * Format currency amount.
     */
    private static function formatCurrency(float $amount, string $currency = 'USD'): string
    {
        $symbol = config('shop.currency.symbol', '$');
        $precision = config('shop.currency.precision', 2);
        $position = config('shop.currency.position', 'before');

        $formatted = number_format($amount, $precision);

        return $position === 'before' ? $symbol . $formatted : $formatted . $symbol;
    }
}
