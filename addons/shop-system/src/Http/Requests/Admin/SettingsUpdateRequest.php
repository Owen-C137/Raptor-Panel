<?php

namespace PterodactylAddons\ShopSystem\Http\Requests\Admin;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class SettingsUpdateRequest extends AdminFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // General settings
            'shop_name' => 'sometimes|string|max:255',
            'shop_description' => 'sometimes|string|max:1000',
            'shop_enabled' => 'sometimes|boolean',
            'shop_maintenance_mode' => 'sometimes|boolean',
            'shop_maintenance_message' => 'sometimes|string|max:500',
            
            // Note: Payment gateway settings are handled in dedicated Payment Gateway page
            // Currency and tax settings moved to payment gateway page as well
            
            // Billing settings
            'billing_enabled' => 'sometimes|boolean',
            'billing_address_required' => 'sometimes|boolean',
            'invoice_prefix' => 'sometimes|string|max:10',
            'invoice_notes' => 'sometimes|string|max:1000',
            
            // Notification settings
            'admin_notifications' => 'sometimes|boolean',
            'customer_notifications' => 'sometimes|boolean',
            'notification_email' => 'sometimes|email',
            'order_notification_webhook' => 'sometimes|url',
            
            // Security settings
            'require_email_verification' => 'sometimes|boolean',
            'max_orders_per_user' => 'sometimes|integer|min:0',
            'order_timeout_minutes' => 'sometimes|integer|min:1',
            'fraud_protection' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'stripe_public_key.required_if' => 'Stripe publishable key is required when Stripe is enabled.',
            'stripe_secret_key.required_if' => 'Stripe secret key is required when Stripe is enabled.',
            'paypal_client_id.required_if' => 'PayPal client ID is required when PayPal is enabled.',
            'paypal_client_secret.required_if' => 'PayPal client secret is required when PayPal is enabled.',
            'tax_rate.max' => 'Tax rate cannot exceed 100%.',
            'currency.max' => 'Currency code must be 3 characters or less.',
        ];
    }
}
