<?php

namespace PterodactylAddons\ShopSystem\Services;

class CurrencyService
{
    /**
     * Get currency symbol from currency code
     */
    public function getCurrencySymbol(string $currencyCode): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CNY' => '¥',
            'INR' => '₹',
            'KRW' => '₩',
            'RUB' => '₽',
            'BRL' => 'R$',
            'MXN' => '$',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'NOK' => 'kr',
            'SEK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'ILS' => '₪',
            'THB' => '฿',
            'MYR' => 'RM',
            'PHP' => '₱',
            'IDR' => 'Rp',
            'VND' => '₫',
            'TRY' => '₺',
            'ZAR' => 'R',
            'AED' => 'AED',
            'SAR' => 'SAR',
        ];

        return $symbols[$currencyCode] ?? $currencyCode;
    }

    /**
     * Get current currency code from settings
     */
    public function getCurrentCurrency(): string
    {
        $settings = app(\PterodactylAddons\ShopSystem\Services\ShopConfigService::class);
        return $settings->getShopConfig()['currency'] ?? 'USD';
    }

    /**
     * Get current currency symbol
     */
    public function getCurrentCurrencySymbol(): string
    {
        return $this->getCurrencySymbol($this->getCurrentCurrency());
    }

    /**
     * Format price with currency symbol
     */
    public function formatPrice(float $amount, ?string $currencyCode = null): string
    {
        $currencyCode = $currencyCode ?? $this->getCurrentCurrency();
        $symbol = $this->getCurrencySymbol($currencyCode);
        
        // For some currencies, symbol goes after the amount
        $suffixCurrencies = ['CHF', 'NOK', 'SEK', 'DKK', 'PLN', 'CZK', 'HUF', 'AED', 'SAR'];
        
        if (in_array($currencyCode, $suffixCurrencies)) {
            return number_format($amount, 2) . ' ' . $symbol;
        }
        
        return $symbol . number_format($amount, 2);
    }
}