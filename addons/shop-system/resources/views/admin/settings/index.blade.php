@extends('layouts.admin')

@section('title')
    Shop Settings
@endsection

@section('content-header')
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
      <div class="flex-grow-1">
        <h1 class="h3 fw-bold mb-1">
          Shop Settings Configure shop system
        </h1>
        <h2 class="fs-base lh-base fw-medium text-muted mb-0">
          Configure shop system
        </h2>
      </div>
      <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-alt">
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.index') }}">Admin</a></li>
          <li class="breadcrumb-item"><a class="link-fx" href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
          <li class="breadcrumb-item" aria-current="page">Settings</li>
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
    <div class="col-lg-8">
        <form id="general-settings-form" method="POST" action="{{ route('admin.shop.settings.general.update') }}">
            @csrf
            @method('PUT')
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-cog me-1"></i>General Settings
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="shop_enabled" class="form-label">Shop Status</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="shop_enabled" value="0">
                                <input type="checkbox" name="shop_enabled" id="shop_enabled" value="1" 
                                       class="form-check-input" {{ old('shop_enabled', $settings['shop_enabled'] ?? true) ? 'checked' : '' }}>
                                <label for="shop_enabled" class="form-check-label fw-semibold">Enable Shop System</label>
                            </div>
                            <div class="form-text">
                                Disable to put the shop in maintenance mode.
                            </div>
                            @error('shop_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="shop_name" class="form-label">Shop Name</label>
                            <input type="text" name="shop_name" id="shop_name" class="form-control" 
                                   value="{{ old('shop_name', $settings['shop_name'] ?? 'Game Server Shop') }}">
                            @error('shop_name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="shop_description" class="form-label">Shop Description</label>
                            <textarea name="shop_description" id="shop_description" class="form-control" rows="3">{{ old('shop_description', $settings['shop_description'] ?? '') }}</textarea>
                            @error('shop_description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-shopping-cart me-1"></i>Order Settings
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="auto_setup" class="form-label">Automatic Server Setup</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="auto_setup" value="0">
                                <input type="checkbox" name="auto_setup" id="auto_setup" value="1" 
                                       class="form-check-input" {{ old('auto_setup', $settings['auto_setup'] ?? true) ? 'checked' : '' }}>
                                <label for="auto_setup" class="form-check-label fw-semibold">Automatically create servers after payment</label>
                            </div>
                            <div class="form-text">
                                If disabled, servers must be created manually for each order.
                            </div>
                            @error('auto_setup')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="require_email_verification" class="form-label">Email Verification</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="require_email_verification" value="0">
                                <input type="checkbox" name="require_email_verification" id="require_email_verification" value="1" 
                                       class="form-check-input" {{ old('require_email_verification', $settings['require_email_verification'] ?? false) ? 'checked' : '' }}>
                                <label for="require_email_verification" class="form-check-label fw-semibold">Require email verification before purchase</label>
                            </div>
                            @error('require_email_verification')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="order_prefix" class="form-label">Order ID Prefix</label>
                            <input type="text" name="order_prefix" id="order_prefix" class="form-control" 
                                   value="{{ old('order_prefix', $settings['order_prefix'] ?? 'ORD') }}" maxlength="10">
                            <div class="form-text">
                                Prefix for order IDs (e.g., "ORD" creates order IDs like ORD-12345).
                            </div>
                            @error('order_prefix')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-coins me-1"></i>Credit System
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="credits_enabled" class="form-label">Enable Credits</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="credits_enabled" value="0">
                                <input type="checkbox" name="credits_enabled" id="credits_enabled" value="1" 
                                       class="form-check-input" {{ old('credits_enabled', $settings['credits_enabled'] ?? true) ? 'checked' : '' }}>
                                <label for="credits_enabled" class="form-check-label fw-semibold">Allow users to purchase and use credits</label>
                            </div>
                            @error('credits_enabled')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="credit_bonus" class="form-label">Credit Purchase Bonus (%)</label>
                            <input type="number" name="credit_bonus" id="credit_bonus" class="form-control" 
                                   value="{{ old('credit_bonus', $settings['credit_bonus'] ?? 0) }}" step="0.1" min="0" max="100">
                            <div class="form-text">
                                Percentage bonus when users purchase credits (e.g., 5% = $105 credits for $100).
                            </div>
                            @error('credit_bonus')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="minimum_credit_purchase" class="form-label">Minimum Credit Purchase</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="minimum_credit_purchase" id="minimum_credit_purchase" class="form-control" 
                                       value="{{ old('minimum_credit_purchase', $settings['minimum_credit_purchase'] ?? 5) }}" step="0.01" min="1">
                            </div>
                            @error('minimum_credit_purchase')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Gateways section removed - use dedicated Payment Gateway page instead -->

            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">
                        <i class="fa fa-envelope me-1"></i>Email Notifications
                    </h3>
                </div>
                
                <div class="block-content">
                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="admin_email" class="form-label">Admin Email</label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control" 
                                   value="{{ old('admin_email', $settings['admin_email'] ?? '') }}">
                            <div class="form-text">
                                Email address to receive admin notifications.
                            </div>
                            @error('admin_email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="notify_new_orders" id="notify_new_orders" value="1" 
                                       class="form-check-input" {{ old('notify_new_orders', $settings['notify_new_orders'] ?? true) ? 'checked' : '' }}>
                                <label for="notify_new_orders" class="form-check-label fw-semibold">Notify admin of new orders</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="notify_failed_payments" id="notify_failed_payments" value="1" 
                                       class="form-check-input" {{ old('notify_failed_payments', $settings['notify_failed_payments'] ?? true) ? 'checked' : '' }}>
                                <label for="notify_failed_payments" class="form-check-label fw-semibold">Notify admin of failed payments</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="send_order_confirmations" id="send_order_confirmations" value="1" 
                                       class="form-check-input" {{ old('send_order_confirmations', $settings['send_order_confirmations'] ?? true) ? 'checked' : '' }}>
                                <label for="send_order_confirmations" class="form-check-label fw-semibold">Send order confirmations to customers</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="block block-rounded">
                <div class="block-content">
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-success" id="save-general-settings-btn">
                                <i class="fa fa-save me-1"></i><span id="save-general-btn-text">Save Settings</span>
                                <i class="fa fa-spinner fa-spin" id="save-general-spinner" style="display: none;"></i>
                            </button>
                            <button type="button" class="btn btn-warning ms-2" onclick="resetToDefaults()">
                                <i class="fa fa-undo me-1"></i>Reset to Defaults
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-lg-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-info-circle me-1"></i>System Status
                </h3>
            </div>
            
            <div class="block-content">
                <div class="row mb-3">
                    <div class="col-5 fw-semibold">Shop Version:</div>
                    <div class="col-7">{{ $version ?? '1.0.0' }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-5 fw-semibold">Database:</div>
                    <div class="col-7">
                        @if($dbStatus ?? true)
                            <span class="badge bg-success">Connected</span>
                        @else
                            <span class="badge bg-danger">Error</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-5 fw-semibold">Cache:</div>
                    <div class="col-7">
                        @if($cacheStatus ?? true)
                            <span class="badge bg-success">Working</span>
                        @else
                            <span class="badge bg-warning">Issues</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-5 fw-semibold">Queue:</div>
                    <div class="col-7">
                        @if($queueStatus ?? true)
                            <span class="badge bg-success">Running</span>
                        @else
                            <span class="badge bg-danger">Stopped</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bolt me-1"></i>Quick Actions
                </h3>
            </div>
            
            <div class="block-content">
                <div class="d-grid gap-2">
                    <button class="btn btn-info" onclick="clearCache()">
                        <i class="fa fa-undo me-1"></i>Clear Cache
                    </button>
                    
                    <button class="btn btn-warning" onclick="runMigrations()">
                        <i class="fa fa-database me-1"></i>Run Migrations
                    </button>
                    
                    <button class="btn btn-primary" onclick="testNotifications()">
                        <i class="fa fa-envelope me-1"></i>Test Notifications
                    </button>
                    
                    <hr>
                    
                    <a href="{{ route('admin.shop.settings.payment-gateways') }}" class="btn btn-secondary">
                        <i class="fa fa-cog me-1"></i>Payment Gateway Settings
                    </a>
                </div>
            </div>
        </div>
        
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-book me-1"></i>Documentation
                </h3>
            </div>
            
            <div class="block-content">
                <p class="mb-3">Need help configuring the shop system?</p>
                <div class="list-group list-group-flush">
                    <a href="#" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fa fa-book me-2"></i>Setup Guide
                    </a>
                    <a href="#" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fa fa-credit-card me-2"></i>Payment Gateway Setup
                    </a>
                    <a href="#" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fa fa-question-circle me-2"></i>FAQ
                    </a>
                    <a href="#" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fa fa-bug me-2"></i>Report Issues
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function clearCache() {
            if (confirm('Clear all shop system cache?')) {
                $.ajax({
                    url: '{{ route('admin.shop.settings.cache.clear') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Cache cleared successfully!');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while clearing cache.');
                    }
                });
            }
        }
        
        // Initialize checkbox functionality  
        $(document).ready(function() {
            // Add visual feedback for checkbox interactions (now works with proper AdminLTE structure)
            $('input[type="checkbox"]').on('change', function() {
                var checkbox = $(this);
                var label = checkbox.next('label'); // Label is now next sibling
                
                if (checkbox.is(':checked')) {
                    label.addClass('text-success');
                } else {
                    label.removeClass('text-success');
                }
                
                // Auto-save indication
                var formGroup = checkbox.closest('.form-group');
                var saveIndicator = formGroup.find('.save-indicator');
                if (saveIndicator.length === 0) {
                    formGroup.append('<small class="save-indicator text-muted"><i class="fa fa-clock-o"></i> Click Save to apply changes</small>');
                }
            });
            
            // Initialize checkbox states on page load
            $('input[type="checkbox"]').each(function() {
                var checkbox = $(this);
                var label = checkbox.next('label');
                
                if (checkbox.is(':checked')) {
                    label.addClass('text-success');
                }
            });
        });
        
        function runMigrations() {
            if (confirm('Run database migrations? This may take some time.')) {
                $.ajax({
                    url: '{{ route('admin.shop.settings.jobs.process') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Migrations completed successfully!');
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while running migrations.');
                    }
                });
            }
        }
        
        function testNotifications() {
            $.ajax({
                url: '{{ route('admin.shop.settings.notifications') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test notification sent successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while sending test notification.');
                }
            });
        }
        
        function resetToDefaults() {
            if (confirm('Reset all settings to default values? This will overwrite your current configuration.')) {
                window.location.href = '{{ route('admin.shop.settings.index') }}';
            }
        }

        // AJAX form handling for general settings
        const generalForm = document.getElementById('general-settings-form');
        const generalSaveBtn = document.getElementById('save-general-settings-btn');
        const generalSaveBtnText = document.getElementById('save-general-btn-text');
        const generalSaveSpinner = document.getElementById('save-general-spinner');
        const generalAlertContainer = document.getElementById('alert-container');

        generalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            generalSaveBtn.disabled = true;
            generalSaveBtnText.textContent = 'Saving...';
            generalSaveSpinner.style.display = 'inline-block';
            
            // Clear any existing alerts
            generalAlertContainer.innerHTML = '';
            
            // Prepare form data
            const formData = new FormData(generalForm);
            
            // Add CSRF token
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'PUT');
            
            // Make AJAX request
            fetch(generalForm.action, {
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
                showGeneralAlert('success', data.message || 'Settings saved successfully!');
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
                    showGeneralAlert('danger', errorMessage);
                } else {
                    showGeneralAlert('danger', 'Failed to save settings. Please try again.');
                }
            })
            .finally(() => {
                // Reset button state
                generalSaveBtn.disabled = false;
                generalSaveBtnText.textContent = 'Save Settings';
                generalSaveSpinner.style.display = 'none';
            });
        });
        
        function showGeneralAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <p class="mb-0">
                        <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                    </p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            generalAlertContainer.innerHTML = alertHtml;
            
            // Auto-hide success alerts after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    const alert = generalAlertContainer.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => alert.remove(), 150);
                    }
                }, 5000);
            }
            
            // Scroll to top to show the alert
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Custom ValidationError class
        class ValidationError extends Error {
            constructor(data) {
                super('Validation failed');
                this.data = data;
            }
        }
    </script>
@endsection

@push('styles')
<style>
    /* Enhanced checkbox styling - works with AdminLTE checkbox-primary */
    .checkbox label.text-success {
        color: #28a745 !important;
        font-weight: 600 !important;
    }
    
    .save-indicator {
        display: block;
        margin-top: 5px;
        font-style: italic;
    }
    
    /* Additional spacing for form groups with checkboxes */
    .form-group .checkbox {
        margin-bottom: 10px;
    }
</style>
@endpush
