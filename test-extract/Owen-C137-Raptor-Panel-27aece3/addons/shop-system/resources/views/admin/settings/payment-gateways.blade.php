@extends('layouts.admin')

@section('title')
    Payment Gateways
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Payment Gateway SettingsConfigure payment processing
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure payment processing
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.settings.index') }}">Settings</a></li>
          <li class="breadcrumb-item" aria-current="page">Payment Gateways</li>
        </ol>
      </nav>
    </div>
  </div>
</div>
@endsection

@section('content')
{{-- Alert Container --}}
<div id="alert-container"></div>

<div class="row">
    <div class="col-12">
        <form id="payment-settings-form" method="POST" action="{{ route('admin.shop.settings.payment-gateways.update') }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fab fa-stripe me-1"></i>Stripe Configuration
                    </h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="stripe_enabled" class="form-label">Enable Stripe</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="stripe_enabled" value="0">
                                    <input type="checkbox" id="stripe_enabled" name="stripe_enabled" value="1" 
                                           class="form-check-input" {{ ($settings['stripe_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label for="stripe_enabled" class="form-check-label fw-semibold">Enable Stripe payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="stripe_mode" class="form-label">Stripe Mode</label>
                                <select id="stripe_mode" name="stripe_mode" class="form-select">
                                    <option value="test" {{ ($settings['stripe_mode'] ?? 'test') === 'test' ? 'selected' : '' }}>Test</option>
                                    <option value="live" {{ ($settings['stripe_mode'] ?? 'test') === 'live' ? 'selected' : '' }}>Live</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="stripe_publishable_key" class="form-label">Publishable Key</label>
                                <input type="text" id="stripe_publishable_key" name="stripe_publishable_key" 
                                       class="form-control" value="{{ $settings['stripe_publishable_key'] ?? '' }}"
                                       placeholder="pk_test_...">
                                <div class="form-text">Your Stripe publishable key</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="stripe_secret_key" class="form-label">Secret Key</label>
                                <input type="password" id="stripe_secret_key" name="stripe_secret_key" 
                                       class="form-control" value="{{ $settings['stripe_secret_key'] ?? '' }}"
                                       placeholder="sk_test_...">
                                <div class="form-text">Your Stripe secret key (keep this secure)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="stripe_webhook_secret" class="form-label">Webhook Endpoint Secret</label>
                            <input type="password" id="stripe_webhook_secret" name="stripe_webhook_secret" 
                                   class="form-control" value="{{ $settings['stripe_webhook_secret'] ?? '' }}"
                                   placeholder="whsec_...">
                            <div class="form-text">Webhook endpoint secret for secure webhook processing</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fab fa-paypal me-1"></i>PayPal Configuration
                    </h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="paypal_enabled" class="form-label">Enable PayPal</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="paypal_enabled" value="0">
                                    <input type="checkbox" id="paypal_enabled" name="paypal_enabled" value="1" 
                                           class="form-check-input" {{ ($settings['paypal_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label for="paypal_enabled" class="form-check-label fw-semibold">Enable PayPal payments</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="paypal_mode" class="form-label">PayPal Mode</label>
                                <select id="paypal_mode" name="paypal_mode" class="form-select">
                                    <option value="sandbox" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                                    <option value="live" {{ ($settings['paypal_mode'] ?? 'sandbox') === 'live' ? 'selected' : '' }}>Live</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="paypal_client_id" class="form-label">Client ID</label>
                                <input type="text" id="paypal_client_id" name="paypal_client_id" 
                                       class="form-control" value="{{ $settings['paypal_client_id'] ?? '' }}"
                                       placeholder="Your PayPal Client ID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="paypal_client_secret" class="form-label">Client Secret</label>
                                <input type="password" id="paypal_client_secret" name="paypal_client_secret" 
                                       class="form-control" value="{{ $settings['paypal_client_secret'] ?? '' }}"
                                       placeholder="Your PayPal Client Secret">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-cog me-1"></i>General Payment Settings
                    </h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="currency" class="form-label">Currency</label>
                                <select id="currency" name="currency" class="form-select">
                                    <option value="USD" {{ ($settings['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="EUR" {{ ($settings['currency'] ?? 'USD') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="GBP" {{ ($settings['currency'] ?? 'USD') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                    <option value="CAD" {{ ($settings['currency'] ?? 'USD') === 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                    <option value="AUD" {{ ($settings['currency'] ?? 'USD') === 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" class="form-control" 
                                       value="{{ $settings['tax_rate'] ?? '0' }}" 
                                       min="0" max="100" step="0.01"
                                       placeholder="0.00">
                                <div class="form-text">Tax rate to apply to all purchases</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="payment_terms" class="form-label">Payment Terms</label>
                            <textarea id="payment_terms" name="payment_terms" class="form-control" rows="4"
                                      placeholder="Enter your payment terms and conditions">{{ $settings['payment_terms'] ?? '' }}</textarea>
                            <div class="form-text">Terms and conditions for payments (displayed on checkout)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success" id="save-settings-btn">
                                <i class="fa fa-save me-1"></i><span id="save-btn-text">Save Payment Settings</span>
                                <i class="fa fa-spinner fa-spin" id="save-spinner" style="display: none;"></i>
                            </button>
                            <a href="{{ route('admin.shop.settings.index') }}" class="btn btn-secondary ms-2">
                                <i class="fa fa-arrow-left me-1"></i>Back to Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
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
                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const alertHtml = `
                    <div class="alert ${alertClass} alert-dismissible" role="alert">
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                    </div>
                `;
                
                alertContainer.innerHTML = alertHtml;
                
                // Auto-hide success alerts after 5 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        const alert = alertContainer.querySelector('.alert');
                        if (alert) {
                            alert.remove();
                        }
                    }, 5000);
                }
                
                // Scroll to top to show the alert
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>
@endsection
