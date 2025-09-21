<?php

namespace PterodactylAddons\ShopSystem\Services;

use PterodactylAddons\ShopSystem\Models\ShopSettings;

class ShopConfigService
{
    /**
     * Get all shop configuration settings
     */
    public function getShopConfig(): array
    {
        return cache()->remember('shop.config', 3600, function () {
            $settings = ShopSettings::all()->pluck('value', 'key')->toArray();
            
            // Convert string values to proper types
            $booleanSettings = [
                'shop_enabled', 'auto_setup', 'require_email_verification', 'credits_enabled', 
                'auto_delivery', 'require_verification', 'allow_refunds', 'stripe_enabled', 
                'paypal_enabled', 'discord_webhook_enabled', 'email_notifications', 'maintenance_mode',
                'wallet_enabled'
            ];
            
            foreach ($booleanSettings as $key) {
                if (isset($settings[$key])) {
                    // Explicit boolean conversion - treat "1", "true", 1, true as true, everything else as false
                    $value = $settings[$key];
                    $settings[$key] = ((string) $value === '1' || (string) $value === 'true' || $value === true || $value === 1);
                }
            }
            
            // Ensure numeric values are properly typed
            $numericSettings = ['tax_rate', 'credit_bonus', 'minimum_credit_purchase'];
            foreach ($numericSettings as $key) {
                if (isset($settings[$key])) {
                    $settings[$key] = (float) $settings[$key];
                }
            }
            
            // Default settings if not set
            $defaults = [
                'shop_enabled' => true,
                'shop_name' => 'Game Server Shop',
                'shop_description' => 'Purchase game servers and hosting plans',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'tax_rate' => 0,
                'min_deposit' => 5.00,
                'max_deposit' => 500.00,
                'auto_setup' => true,
                'require_email_verification' => false,
                'credits_enabled' => true,
                'auto_delivery' => true,
                'require_verification' => false,
                'allow_refunds' => true,
                'refund_days' => 7,
                'grace_period_hours' => 72,
                'suspension_grace_hours' => 24,
                'payment_methods' => ['stripe', 'paypal'],
                'stripe_enabled' => false,
                'stripe_public_key' => '',
                'stripe_publishable_key' => '',
                'stripe_secret_key' => '',
                'stripe_webhook_secret' => '',
                'stripe_mode' => 'test',
                'paypal_enabled' => false,
                'paypal_client_id' => '',
                'paypal_client_secret' => '',
                'paypal_mode' => 'sandbox',
                'discord_webhook_enabled' => false,
                'discord_webhook_url' => '',
                'email_notifications' => true,
                'admin_email' => config('mail.from.address'),
                'terms_of_service' => '',
                'privacy_policy' => '',
                'payment_terms' => '',
                'maintenance_mode' => false,
                'maintenance_message' => 'Shop is currently under maintenance.',
                'wallet_enabled' => true,
                
                // Branding defaults
                'logo_url' => '',
                'favicon_url' => '',
                'custom_css' => '',
                'footer_text' => '',
                
                // Additional settings
                'order_prefix' => 'ORD',
                'credit_bonus' => 0,
                'minimum_credit_purchase' => 5,
            ];
            
            return array_merge($defaults, $settings);
        });
    }
    
    /**
     * Get a specific shop setting
     */
    public function getSetting(string $key, $default = null)
    {
        $config = $this->getShopConfig();
        return $config[$key] ?? $default;
    }
    
    /**
     * Clear shop configuration cache
     */
    public function clearCache(): void
    {
        cache()->forget('shop.config');
    }
    
    /**
     * Get payment gateway configuration
     */
    public function getPaymentConfig(): array
    {
        $config = $this->getShopConfig();
        
        return [
            'stripe' => [
                'enabled' => $config['stripe_enabled'] ?? false,
                'mode' => $config['stripe_mode'] ?? 'test',
                'publishable_key' => $config['stripe_publishable_key'] ?? '',
                'secret_key' => $config['stripe_secret_key'] ?? '',
                'webhook_secret' => $config['stripe_webhook_secret'] ?? '',
            ],
            'paypal' => [
                'enabled' => $config['paypal_enabled'] ?? false,
                'mode' => $config['paypal_mode'] ?? 'sandbox',
                'client_id' => $config['paypal_client_id'] ?? '',
                'client_secret' => $config['paypal_client_secret'] ?? '',
            ],
            'currency' => $config['currency'] ?? 'USD',
            'currency_symbol' => $this->getCurrencySymbol($config['currency'] ?? 'USD'),
            'tax_rate' => $config['tax_rate'] ?? 0,
        ];
    }
    
    /**
     * Get currency symbol for a given currency code
     */
    private function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CHF' => 'CHF',
            'CNY' => '¥',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'CZK' => 'Kč',
            'HUF' => 'Ft',
            'RUB' => '₽',
            'BRL' => 'R$',
            'INR' => '₹',
            'KRW' => '₩',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'NZD' => 'NZ$',
            'MXN' => '$',
            'ZAR' => 'R',
        ];
        
        return $symbols[$currency] ?? $currency;
    }
    
    /**
     * Check if shop is enabled and available
     */
    public function isShopAvailable(): bool
    {
        $config = $this->getShopConfig();
        return ($config['shop_enabled'] ?? true) && !($config['maintenance_mode'] ?? false);
    }
    
    /**
     * Check if shop is enabled (alias for isShopAvailable for backward compatibility)
     */
    public function isShopEnabled(): bool
    {
        return $this->isShopAvailable();
    }
    
    /**
     * Check if automatic server setup is enabled
     */
    public function isAutoSetupEnabled(): bool
    {
        return $this->getSetting('auto_setup', true);
    }
    
    /**
     * Get enabled payment methods
     */
    public function getEnabledPaymentMethods(): array
    {
        $config = $this->getPaymentConfig();
        $methods = [];
        
        if ($config['stripe']['enabled']) {
            $methods[] = 'stripe';
        }
        
        if ($config['paypal']['enabled']) {
            $methods[] = 'paypal';
        }
        
        return $methods;
    }
}
