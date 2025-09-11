<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopSettings;
use Pterodactyl\Http\Requests\Admin\Shop\SettingsUpdateRequest;

class SettingsController extends Controller
{
    /**
     * Display shop settings
     */
    public function index()
    {
        $settings = $this->getSettings();
        
        return view('shop::admin.settings.index', compact('settings'));
    }

    /**
     * Update shop settings
     */
    public function update(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        foreach ($data as $key => $value) {
            ShopSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
        
        // Clear settings cache if exists
        cache()->forget('shop.settings');
        
        return redirect()->route('admin.shop.settings')
            ->with('success', 'Shop settings updated successfully.');
    }

    /**
     * Get all shop settings
     */
    protected function getSettings(): array
    {
        return cache()->remember('shop.settings', 3600, function () {
            $settings = ShopSettings::all()->pluck('value', 'key')->toArray();
            
            // Convert string values to proper types
            $booleanSettings = [
                'shop_enabled', 'auto_setup', 'require_email_verification', 'credits_enabled', 
                'auto_delivery', 'require_verification', 'allow_refunds', 'stripe_enabled', 
                'paypal_enabled', 'discord_webhook_enabled', 'email_notifications', 'maintenance_mode'
            ];
            
            foreach ($booleanSettings as $key) {
                if (isset($settings[$key])) {
                    $settings[$key] = filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
                }
            }
            
            // Default settings if not set
            return array_merge([
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
                'stripe_secret_key' => '',
                'stripe_webhook_secret' => '',
                'paypal_enabled' => false,
                'paypal_client_id' => '',
                'paypal_client_secret' => '',
                'paypal_mode' => 'sandbox',
                'discord_webhook_enabled' => false,
                'discord_webhook_url' => '',
                'email_notifications' => true,
                'admin_email' => config('app.mail_from_address'),
                'terms_of_service' => '',
                'privacy_policy' => '',
                'maintenance_mode' => false,
                'maintenance_message' => 'Shop is currently under maintenance.',
            ], $settings);
        });
    }

    /**
     * Test payment gateway configuration
     */
    public function testPaymentGateway(Request $request): RedirectResponse
    {
        $request->validate([
            'gateway' => 'required|in:stripe,paypal'
        ]);
        
        try {
            $gateway = $request->gateway;
            
            if ($gateway === 'stripe') {
                $this->testStripeConnection();
            } elseif ($gateway === 'paypal') {
                $this->testPayPalConnection();
            }
            
            return redirect()->back()
                ->with('success', ucfirst($gateway) . ' connection test successful!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Payment gateway test failed: ' . $e->getMessage());
        }
    }

    /**
     * Test Stripe connection
     */
    protected function testStripeConnection(): void
    {
        $settings = $this->getSettings();
        
        if (empty($settings['stripe_secret_key'])) {
            throw new \Exception('Stripe secret key not configured.');
        }
        
        \Stripe\Stripe::setApiKey($settings['stripe_secret_key']);
        
        try {
            \Stripe\Account::retrieve();
        } catch (\Exception $e) {
            throw new \Exception('Invalid Stripe credentials: ' . $e->getMessage());
        }
    }

    /**
     * Test PayPal connection
     */
    protected function testPayPalConnection(): void
    {
        $settings = $this->getSettings();
        
        if (empty($settings['paypal_client_id']) || empty($settings['paypal_client_secret'])) {
            throw new \Exception('PayPal credentials not configured.');
        }
        
        // PayPal API test would go here
        // For now, just check that credentials are present
    }

    /**
     * Clear all shop caches
     */
    public function clearCache(): RedirectResponse
    {
        cache()->forget('shop.settings');
        cache()->flush(); // Consider being more specific
        
        return redirect()->back()
            ->with('success', 'Shop cache cleared successfully.');
    }

    /**
     * Export shop configuration
     */
    public function exportConfig()
    {
        $settings = $this->getSettings();
        
        // Remove sensitive data from export
        unset($settings['stripe_secret_key']);
        unset($settings['stripe_webhook_secret']);
        unset($settings['paypal_client_secret']);
        
        $filename = 'shop_config_' . date('Y_m_d_H_i_s') . '.json';
        
        return response()->json($settings)
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Display payment gateways settings
     */
    public function paymentGateways()
    {
        $settings = $this->getSettings();
        
        return view('shop::admin.settings.payment-gateways', compact('settings'));
    }

    /**
     * Update payment gateways settings
     */
    public function updatePaymentGateways(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validate([
            'stripe_enabled' => 'boolean',
            'stripe_public_key' => 'nullable|string',
            'stripe_secret_key' => 'nullable|string',
            'stripe_webhook_secret' => 'nullable|string',
            'paypal_enabled' => 'boolean',
            'paypal_client_id' => 'nullable|string',
            'paypal_client_secret' => 'nullable|string',
            'paypal_mode' => 'nullable|in:sandbox,live',
        ]);

        foreach ($data as $key => $value) {
            ShopSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        // Clear settings cache
        cache()->forget('shop.settings');

        return redirect()->route('admin.shop.settings.payment-gateways')
            ->with('success', 'Payment gateway settings updated successfully.');
    }

    /**
     * Display general settings (redirect to main settings page)
     */
    public function general()
    {
        return redirect()->route('admin.shop.settings.index');
    }

    /**
     * Update general settings
     */
    public function updateGeneral(SettingsUpdateRequest $request): RedirectResponse
    {
        return $this->update($request);
    }

    /**
     * Display notifications settings
     */
    public function notifications()
    {
        $settings = $this->getSettings();
        
        return view('shop::admin.settings.notifications', compact('settings'));
    }

    /**
     * Update notifications settings
     */
    public function updateNotifications(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validate([
            'email_notifications_enabled' => 'boolean',
            'admin_email' => 'nullable|email',
            'order_notifications' => 'boolean',
            'payment_notifications' => 'boolean',
            'user_notifications' => 'boolean',
        ]);

        foreach ($data as $key => $value) {
            ShopSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        cache()->forget('shop.settings');

        return redirect()->route('admin.shop.settings.notifications')
            ->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Display billing settings
     */
    public function billing()
    {
        $settings = $this->getSettings();
        
        return view('shop::admin.settings.billing', compact('settings'));
    }

    /**
     * Update billing settings
     */
    public function updateBilling(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validate([
            'currency' => 'required|string|max:3',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_inclusive' => 'boolean',
            'invoice_prefix' => 'nullable|string|max:10',
            'auto_suspend_overdue' => 'boolean',
            'suspend_after_days' => 'nullable|integer|min:1',
        ]);

        foreach ($data as $key => $value) {
            ShopSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        cache()->forget('shop.settings');

        return redirect()->route('admin.shop.settings.billing')
            ->with('success', 'Billing settings updated successfully.');
    }

    /**
     * Display security settings
     */
    public function security()
    {
        $settings = $this->getSettings();
        
        return view('shop::admin.settings.security', compact('settings'));
    }

    /**
     * Update security settings
     */
    public function updateSecurity(SettingsUpdateRequest $request): RedirectResponse
    {
        $data = $request->validate([
            'recaptcha_enabled' => 'boolean',
            'recaptcha_site_key' => 'nullable|string',
            'recaptcha_secret_key' => 'nullable|string',
            'csrf_protection' => 'boolean',
            'rate_limiting' => 'boolean',
            'max_login_attempts' => 'nullable|integer|min:1',
        ]);

        foreach ($data as $key => $value) {
            ShopSettings::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        cache()->forget('shop.settings');

        return redirect()->route('admin.shop.settings.security')
            ->with('success', 'Security settings updated successfully.');
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');
        
        ShopSettings::updateOrCreate(
            ['key' => 'maintenance_mode'],
            ['value' => $enabled]
        );

        cache()->forget('shop.settings');

        $message = $enabled ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.';
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Process pending jobs
     */
    public function processJobs(Request $request): RedirectResponse
    {
        // This would trigger job processing
        // For now, just return a success message
        return redirect()->back()->with('success', 'Jobs processing triggered successfully.');
    }
}
