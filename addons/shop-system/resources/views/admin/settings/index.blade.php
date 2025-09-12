@extends('layouts.admin')

@section('title')
    Shop Settings
@endsection

@section('content-header')
    <h1>Shop Settings <small>Configure shop system</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.shop.dashboard') }}">Shop</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
{{-- Alert Container --}}
<div id="alert-container"></div>

<div class="row">
    <div class="col-md-8">
        <form id="general-settings-form" method="POST" action="{{ route('admin.shop.settings.general.update') }}">
            @csrf
            @method('PUT')
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">General Settings</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="shop_enabled">Shop Status</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="shop_enabled" value="0">
                            <input type="checkbox" name="shop_enabled" id="shop_enabled" value="1" 
                                   {{ old('shop_enabled', $settings['shop_enabled'] ?? true) ? 'checked' : '' }}>
                            <label for="shop_enabled" class="strong">Enable Shop System</label>
                        </div>
                        <small class="form-text text-muted">
                            Disable to put the shop in maintenance mode.
                        </small>
                        @error('shop_enabled')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="shop_name">Shop Name</label>
                        <input type="text" name="shop_name" id="shop_name" class="form-control" 
                               value="{{ old('shop_name', $settings['shop_name'] ?? 'Game Server Shop') }}">
                        @error('shop_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="shop_description">Shop Description</label>
                        <textarea name="shop_description" id="shop_description" class="form-control" rows="3">{{ old('shop_description', $settings['shop_description'] ?? '') }}</textarea>
                        @error('shop_description')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Order Settings</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="auto_setup">Automatic Server Setup</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="auto_setup" value="0">
                            <input type="checkbox" name="auto_setup" id="auto_setup" value="1" 
                                   {{ old('auto_setup', $settings['auto_setup'] ?? true) ? 'checked' : '' }}>
                            <label for="auto_setup" class="strong">Automatically create servers after payment</label>
                        </div>
                        <small class="form-text text-muted">
                            If disabled, servers must be created manually for each order.
                        </small>
                        @error('auto_setup')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="require_email_verification">Email Verification</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="require_email_verification" value="0">
                            <input type="checkbox" name="require_email_verification" id="require_email_verification" value="1" 
                                   {{ old('require_email_verification', $settings['require_email_verification'] ?? false) ? 'checked' : '' }}>
                            <label for="require_email_verification" class="strong">Require email verification before purchase</label>
                        </div>
                        @error('require_email_verification')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="order_prefix">Order ID Prefix</label>
                        <input type="text" name="order_prefix" id="order_prefix" class="form-control" 
                               value="{{ old('order_prefix', $settings['order_prefix'] ?? 'ORD') }}" maxlength="10">
                        <small class="form-text text-muted">
                            Prefix for order IDs (e.g., "ORD" creates order IDs like ORD-12345).
                        </small>
                        @error('order_prefix')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Credit System</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="credits_enabled">Enable Credits</label>
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="hidden" name="credits_enabled" value="0">
                            <input type="checkbox" name="credits_enabled" id="credits_enabled" value="1" 
                                   {{ old('credits_enabled', $settings['credits_enabled'] ?? true) ? 'checked' : '' }}>
                            <label for="credits_enabled" class="strong">Allow users to purchase and use credits</label>
                        </div>
                        @error('credits_enabled')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="credit_bonus">Credit Purchase Bonus (%)</label>
                        <input type="number" name="credit_bonus" id="credit_bonus" class="form-control" 
                               value="{{ old('credit_bonus', $settings['credit_bonus'] ?? 0) }}" step="0.1" min="0" max="100">
                        <small class="form-text text-muted">
                            Percentage bonus when users purchase credits (e.g., 5% = $105 credits for $100).
                        </small>
                        @error('credit_bonus')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="minimum_credit_purchase">Minimum Credit Purchase</label>
                        <div class="input-group">
                            <span class="input-group-addon">$</span>
                            <input type="number" name="minimum_credit_purchase" id="minimum_credit_purchase" class="form-control" 
                                   value="{{ old('minimum_credit_purchase', $settings['minimum_credit_purchase'] ?? 5) }}" step="0.01" min="1">
                        </div>
                        @error('minimum_credit_purchase')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Payment Gateways section removed - use dedicated Payment Gateway page instead -->

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Email Notifications</h3>
                </div>
                
                <div class="box-body">
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" name="admin_email" id="admin_email" class="form-control" 
                               value="{{ old('admin_email', $settings['admin_email'] ?? '') }}">
                        <small class="form-text text-muted">
                            Email address to receive admin notifications.
                        </small>
                        @error('admin_email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="notify_new_orders" id="notify_new_orders" value="1" 
                                   {{ old('notify_new_orders', $settings['notify_new_orders'] ?? true) ? 'checked' : '' }}>
                            <label for="notify_new_orders" class="strong">Notify admin of new orders</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="notify_failed_payments" id="notify_failed_payments" value="1" 
                                   {{ old('notify_failed_payments', $settings['notify_failed_payments'] ?? true) ? 'checked' : '' }}>
                            <label for="notify_failed_payments" class="strong">Notify admin of failed payments</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" name="send_order_confirmations" id="send_order_confirmations" value="1" 
                                   {{ old('send_order_confirmations', $settings['send_order_confirmations'] ?? true) ? 'checked' : '' }}>
                            <label for="send_order_confirmations" class="strong">Send order confirmations to customers</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success" id="save-general-settings-btn">
                        <i class="fa fa-save"></i> <span id="save-general-btn-text">Save Settings</span>
                        <i class="fa fa-spinner fa-spin" id="save-general-spinner" style="display: none;"></i>
                    </button>
                    <button type="button" class="btn btn-warning" onclick="resetToDefaults()">
                        <i class="fa fa-refresh"></i> Reset to Defaults
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">System Status</h3>
            </div>
            
            <div class="box-body">
                <dl class="dl-horizontal">
                    <dt>Shop Version:</dt>
                    <dd>{{ $version ?? '1.0.0' }}</dd>
                    
                    <dt>Database:</dt>
                    <dd>
                        @if($dbStatus ?? true)
                            <span class="label label-success">Connected</span>
                        @else
                            <span class="label label-danger">Error</span>
                        @endif
                    </dd>
                    
                    <dt>Cache:</dt>
                    <dd>
                        @if($cacheStatus ?? true)
                            <span class="label label-success">Working</span>
                        @else
                            <span class="label label-warning">Issues</span>
                        @endif
                    </dd>
                    
                    <dt>Queue:</dt>
                    <dd>
                        @if($queueStatus ?? true)
                            <span class="label label-success">Running</span>
                        @else
                            <span class="label label-danger">Stopped</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Quick Actions</h3>
            </div>
            
            <div class="box-body">
                <button class="btn btn-info btn-block" onclick="clearCache()">
                    <i class="fa fa-refresh"></i> Clear Cache
                </button>
                
                <button class="btn btn-warning btn-block" onclick="runMigrations()">
                    <i class="fa fa-database"></i> Run Migrations
                </button>
                
                <button class="btn btn-primary btn-block" onclick="testNotifications()">
                    <i class="fa fa-envelope"></i> Test Notifications
                </button>
                
                <hr>
                
                <a href="{{ route('admin.shop.settings.payment-gateways') }}" class="btn btn-default btn-block">
                    <i class="fa fa-cog"></i> Payment Gateway Settings
                </a>
            </div>
        </div>
        
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Documentation</h3>
            </div>
            
            <div class="box-body">
                <p>Need help configuring the shop system?</p>
                <ul class="list-unstyled">
                    <li><a href="#" target="_blank"><i class="fa fa-book"></i> Setup Guide</a></li>
                    <li><a href="#" target="_blank"><i class="fa fa-credit-card"></i> Payment Gateway Setup</a></li>
                    <li><a href="#" target="_blank"><i class="fa fa-question-circle"></i> FAQ</a></li>
                    <li><a href="#" target="_blank"><i class="fa fa-bug"></i> Report Issues</a></li>
                </ul>
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
                <div class="alert alert-${type} alert-dismissible fade in" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                </div>
            `;
            
            generalAlertContainer.innerHTML = alertHtml;
            
            // Auto-hide success alerts after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    const alert = generalAlertContainer.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('in');
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
