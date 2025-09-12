@extends('layouts.admin')

@section('title')
    Payment Gateways
@endsection

@section('content-header')
    <h1>Payment Gateway Settings<small>Configure payment processing</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li><a href="{{ route('admin.shop.settings.index') }}">Settings</a></li>
        <li class="active">Payment Gateways</li>
    </ol>
@endsection

@section('content')
{{-- Alert Container --}}
<div id="alert-container"></div>

<div class="row">
    <div class="col-xs-12">
        <form id="payment-settings-form" method="POST" action="{{ route('admin.shop.settings.payment-gateways.update') }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Stripe Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stripe_enabled" class="control-label">Enable Stripe</label>
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input type="hidden" name="stripe_enabled" value="0">
                                    <input type="checkbox" id="stripe_enabled" name="stripe_enabled" value="1" 
                                           {{ ($settings['stripe_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label for="stripe_enabled" class="strong">Enable Stripe payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stripe_mode" class="control-label">Stripe Mode</label>
                                <select id="stripe_mode" name="stripe_mode" class="form-control">
                                    <option value="test" {{ ($settings['stripe_mode'] ?? 'test') === 'test' ? 'selected' : '' }}>Test</option>
                                    <option value="live" {{ ($settings['stripe_mode'] ?? 'test') === 'live' ? 'selected' : '' }}>Live</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stripe_publishable_key" class="control-label">Publishable Key</label>
                                <input type="text" id="stripe_publishable_key" name="stripe_publishable_key" 
                                       class="form-control" value="{{ $settings['stripe_publishable_key'] ?? '' }}"
                                       placeholder="pk_test_...">
                                <p class="text-muted small">Your Stripe publishable key</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="stripe_secret_key" class="control-label">Secret Key</label>
                                <input type="password" id="stripe_secret_key" name="stripe_secret_key" 
                                       class="form-control" value="{{ $settings['stripe_secret_key'] ?? '' }}"
                                       placeholder="sk_test_...">
                                <p class="text-muted small">Your Stripe secret key (keep this secure)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_webhook_secret" class="control-label">Webhook Endpoint Secret</label>
                        <input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                               class="form-control" value="{{ $settings['stripe_webhook_secret'] ?? '' }}"
                               placeholder="whsec_...">
                        <p class="text-muted small">Webhook endpoint secret for secure webhook processing</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">PayPal Configuration</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paypal_enabled" class="control-label">Enable PayPal</label>
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input type="hidden" name="paypal_enabled" value="0">
                                    <input type="checkbox" id="paypal_enabled" name="paypal_enabled" value="1" 
                                           {{ ($settings['paypal_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label for="paypal_enabled" class="strong">Enable PayPal payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paypal_mode" class="control-label">PayPal Mode</label>
                                <select id="paypal_mode" name="paypal_mode" class="form-control">
                                    <option value="sandbox" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                    <option value="live" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'live' ? 'selected' : '' }}>Live</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paypal_client_id" class="control-label">Client ID</label>
                                <input type="text" id="paypal_client_id" name="paypal_client_id" 
                                       class="form-control" value="{{ $settings['paypal_client_id'] ?? '' }}"
                                       placeholder="Your PayPal Client ID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="paypal_client_secret" class="control-label">Client Secret</label>
                                <input type="password" id="paypal_client_secret" name="paypal_client_secret" 
                                       class="form-control" value="{{ $settings['paypal_client_secret'] ?? '' }}"
                                       placeholder="Your PayPal Client Secret">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">General Payment Settings</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency" class="control-label">Currency</label>
                                <select id="currency" name="currency" class="form-control">
                                    <option value="USD" {{ ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? 'USD') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="GBP" {{ ($settings['currency'] ?? 'USD') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                    <option value="CAD" {{ ($settings['currency'] ?? 'USD') === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                    <option value="AUD" {{ ($settings['currency'] ?? 'USD') === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tax_rate" class="control-label">Tax Rate (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" class="form-control" 
                                       value="{{ $settings['tax_rate'] ?? '0' }}" 
                                       min="0" max="100" step="0.01"
                                       placeholder="0.00">
                                <p class="text-muted small">Tax rate to apply to all purchases</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_terms" class="control-label">Payment Terms</label>
                        <textarea id="payment_terms" name="payment_terms" class="form-control" rows="4"
                                  placeholder="Enter your payment terms and conditions">{{ $settings['payment_terms'] ?? '' }}</textarea>
                        <p class="text-muted small">Terms and conditions for payments (displayed on checkout)</p>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success btn-sm" id="save-settings-btn">
                        <i class="fa fa-save"></i> <span id="save-btn-text">Save Payment Settings</span>
                        <i class="fa fa-spinner fa-spin" id="save-spinner" style="display: none;"></i>
                    </button>
                    <a href="{{ route('admin.shop.settings.index') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Settings
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- AJAX JavaScript --}}
<script>
// Custom error class for validation errors
class ValidationError extends Error {
    constructor(data) {
        super('Validation Error');
        this.data = data;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('payment-settings-form');
    const saveBtn = document.getElementById('save-settings-btn');
    const saveBtnText = document.getElementById('save-btn-text');
    const saveSpinner = document.getElementById('save-spinner');
    const alertContainer = document.getElementById('alert-container');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        saveBtn.disabled = true;
        saveBtnText.textContent = 'Saving...';
        saveSpinner.style.display = 'inline-block';
        
        // Clear any existing alerts
        alertContainer.innerHTML = '';
        
        // Prepare form data
        const formData = new FormData(form);
        
        // Add CSRF token
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        
        // Make AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            // Handle validation errors
            if (response.status === 422) {
                return response.json().then(data => {
                    throw new ValidationError(data);
                });
            }
            throw new Error('Network response was not ok');
        })
        .then(data => {
            // Success
            showAlert('success', data.message || 'Payment settings saved successfully!');
        })
        .catch(error => {
            console.error('Error:', error);
            
            if (error instanceof ValidationError) {
                // Handle validation errors
                const validationErrors = error.data.errors || {};
                const errorMessages = Object.values(validationErrors).flat();
                const errorMessage = errorMessages.length > 0 
                    ? errorMessages.join('<br>') 
                    : 'Please check your input and try again.';
                showAlert('danger', errorMessage);
            } else {
                showAlert('danger', 'Failed to save settings. Please try again.');
            }
        })
        .finally(() => {
            // Reset button state
            saveBtn.disabled = false;
            saveBtnText.textContent = 'Save Payment Settings';
            saveSpinner.style.display = 'none';
        });
    });
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade in" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
            </div>
        `;
        
        alertContainer.innerHTML = alertHtml;
        
        // Auto-hide success alerts after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('in');
                    setTimeout(() => alert.remove(), 150);
                }
            }, 5000);
        }
        
        // Scroll to top to show the alert
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>

<style>
.checkbox-label {
    font-weight: normal;
    margin-left: 8px;
}
</style>
@endsection
