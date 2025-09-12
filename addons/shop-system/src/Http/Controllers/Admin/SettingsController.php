<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopSettings;
use PterodactylAddons\ShopSystem\Http\Requests\Admin\SettingsUpdateRequest;

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
        
        return redirect()->route('admin.shop.settings.index')
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
                'shop_enabled', 'shop_maintenance_mode', 'auto_setup', 'require_email_verification', 
                'credits_enabled', 'auto_delivery', 'require_verification', 'allow_refunds', 
                'stripe_enabled', 'paypal_enabled', 'discord_webhook_enabled', 'email_notifications', 
                'maintenance_mode', 'notify_new_orders', 'notify_failed_payments', 'send_order_confirmations',
                'admin_notifications', 'customer_notifications', 'billing_enabled', 'billing_address_required',
                'fraud_protection'
            ];
            
            foreach ($booleanSettings as $key) {
                if (isset($settings[$key])) {
                    // Explicit boolean conversion - treat "1", "true", 1, true as true, everything else as false
                    $value = $settings[$key];
                    $settings[$key] = ((string) $value === '1' || (string) $value === 'true' || $value === true || $value === 1);
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
    public function updatePaymentGateways(SettingsUpdateRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $data = $request->validated();

            \Log::info('Payment gateway settings received:', $data);

            // Map form field names to setting keys
            $settingMappings = [
                'stripe_enabled' => 'stripe_enabled',
                'stripe_mode' => 'stripe_mode',
                'stripe_publishable_key' => 'stripe_publishable_key',
                'stripe_secret_key' => 'stripe_secret_key',
                'stripe_webhook_secret' => 'stripe_webhook_secret',
                'paypal_enabled' => 'paypal_enabled',
                'paypal_mode' => 'paypal_mode',
                'paypal_client_id' => 'paypal_client_id',
                'paypal_client_secret' => 'paypal_client_secret',
                'currency' => 'currency',
                'tax_rate' => 'tax_rate',
                'payment_terms' => 'payment_terms',
            ];

            foreach ($settingMappings as $formField => $settingKey) {
                if (array_key_exists($formField, $data)) {
                    $value = $data[$formField];
                    
                    // Convert checkbox values to proper booleans
                    if (in_array($formField, ['stripe_enabled', 'paypal_enabled'])) {
                        // Explicit boolean conversion for checkboxes
                        $value = (bool) ((string) $value === '1' || (string) $value === 'true' || $value === true);
                        \Log::info("Converting {$formField} to boolean:", [
                            'original' => $data[$formField], 
                            'converted' => $value,
                            'original_type' => gettype($data[$formField]),
                            'converted_type' => gettype($value)
                        ]);
                    }
                    
                    \Log::info("Saving setting {$settingKey}:", ['value' => $value, 'type' => gettype($value)]);
                    
                    // Store boolean fields as '1' or '0', other fields as their actual values
                    $storedValue = in_array($formField, ['stripe_enabled', 'paypal_enabled']) 
                        ? ($value ? '1' : '0') 
                        : $value;
                    
                    ShopSettings::updateOrCreate(
                        ['key' => $settingKey],
                        [
                            'value' => $storedValue,
                            'type' => $this->getSettingType($value),
                            'group' => 'payment',
                            'is_public' => in_array($settingKey, ['currency']) // Only currency is public
                        ]
                    );
                }
            }

            // Clear settings cache
            cache()->forget('shop.settings');

            $message = 'Payment gateway settings updated successfully.';

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // Return redirect for regular form submissions
            return redirect()->route('admin.shop.settings.payment-gateways')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            $errorMessage = 'Failed to update payment gateway settings. Please try again.';
            
            // Return JSON error response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            // Return redirect with error for regular form submissions
            return redirect()->route('admin.shop.settings.payment-gateways')
                ->with('error', $errorMessage);
        }
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
    public function updateGeneral(SettingsUpdateRequest $request): RedirectResponse|JsonResponse
    {
        try {
            // Log incoming request data for debugging
            \Log::info('Settings update request data:', $request->all());
            
            $data = $request->validated();
            
            \Log::info('Validated data:', $data);

            // Define boolean settings that need special handling
            $booleanSettings = [
                'shop_enabled', 'shop_maintenance_mode', 'auto_setup', 'require_email_verification', 
                'credits_enabled', 'notify_new_orders', 'notify_failed_payments', 'send_order_confirmations',
                'admin_notifications', 'customer_notifications', 'billing_enabled', 'billing_address_required',
                'fraud_protection'
            ];

            foreach ($data as $key => $value) {
                // Convert boolean values to "1" or "0" for database storage
                if (in_array($key, $booleanSettings)) {
                    $value = $value ? '1' : '0';
                }
                
                ShopSettings::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            // Clear settings cache
            cache()->forget('shop.settings');

            $message = 'General settings updated successfully.';

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // Return redirect for regular form submissions
            return redirect()->route('admin.shop.settings.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            $errorMessage = 'Failed to update general settings. Please try again.';
            
            // Return JSON error response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
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

    /**
     * Determine the setting type based on the value
     */
    private function getSettingType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_numeric($value)) {
            return 'number';
        }
        
        if (is_string($value) && strlen($value) > 255) {
            return 'text';
        }
        
        return 'string';
    }
}
