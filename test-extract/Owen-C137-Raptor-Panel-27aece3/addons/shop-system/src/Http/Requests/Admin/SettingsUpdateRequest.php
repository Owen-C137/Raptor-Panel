<?php

namespace PterodactylAddons\ShopSystem\Http\Requests\Admin;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

class SettingsUpdateRequest extends AdminFormRequest
{
    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        Log::error('Settings validation failed:', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all()
        ]);
        
        parent::failedValidation($validator);
    }
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
            'auto_setup' => 'sometimes|boolean',
            'order_prefix' => 'sometimes|string|max:10',
            
            // Credit system settings
            'credits_enabled' => 'sometimes|boolean',
            'credit_bonus' => 'sometimes|numeric|min:0|max:100',
            'minimum_credit_purchase' => 'sometimes|numeric|min:0',
            
            // Notification settings
            'admin_email' => 'sometimes|nullable|email',
            'notify_new_orders' => 'sometimes|boolean',
            'notify_failed_payments' => 'sometimes|boolean',
            'send_order_confirmations' => 'sometimes|boolean',
            'admin_notifications' => 'sometimes|boolean',
            'customer_notifications' => 'sometimes|boolean',
            'notification_email' => 'sometimes|nullable|email',
            'order_notification_webhook' => 'sometimes|nullable|url',
            
            // Billing settings
            'billing_enabled' => 'sometimes|boolean',
            'billing_address_required' => 'sometimes|boolean',
            'invoice_prefix' => 'sometimes|string|max:10',
            'invoice_notes' => 'sometimes|string|max:1000',
            
            // Security settings
            'require_email_verification' => 'sometimes|boolean',
            'max_orders_per_user' => 'sometimes|integer|min:0',
            'order_timeout_minutes' => 'sometimes|integer|min:1',
            'fraud_protection' => 'sometimes|boolean',
            
            // Payment Gateway settings
            'stripe_enabled' => 'sometimes|boolean',
            'stripe_mode' => 'sometimes|string|in:test,live',
            'stripe_publishable_key' => 'sometimes|nullable|string',
            'stripe_secret_key' => 'sometimes|nullable|string',
            'stripe_webhook_secret' => 'sometimes|nullable|string',
            'paypal_enabled' => 'sometimes|boolean',
            'paypal_mode' => 'sometimes|string|in:sandbox,live',
            'paypal_client_id' => 'sometimes|nullable|string',
            'paypal_client_secret' => 'sometimes|nullable|string',
            'currency' => 'sometimes|string|max:3|in:USD,EUR,GBP,CAD,AUD',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'payment_terms' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'stripe_publishable_key.required_if' => 'Stripe publishable key is required when Stripe is enabled.',
            'stripe_secret_key.required_if' => 'Stripe secret key is required when Stripe is enabled.',
            'paypal_client_id.required_if' => 'PayPal client ID is required when PayPal is enabled.',
            'paypal_client_secret.required_if' => 'PayPal client secret is required when PayPal is enabled.',
            'tax_rate.max' => 'Tax rate cannot exceed 100%.',
            'tax_rate.min' => 'Tax rate cannot be negative.',
            'currency.max' => 'Currency code must be 3 characters or less.',
            'currency.in' => 'Please select a valid currency.',
            'stripe_mode.in' => 'Stripe mode must be either test or live.',
            'paypal_mode.in' => 'PayPal mode must be either sandbox or live.',
            'credit_bonus.max' => 'Credit bonus cannot exceed 100%.',
            'credit_bonus.min' => 'Credit bonus cannot be negative.',
            'minimum_credit_purchase.min' => 'Minimum credit purchase cannot be negative.',
            'admin_email.email' => 'Please enter a valid email address for admin notifications.',
            'order_prefix.max' => 'Order prefix cannot be longer than 10 characters.',
        ];
    }
}
