@extends('shop::layout')

@section('shop-title', 'Renew Server Plan')

@section('shop-content')
<div class="content content-boxed content-full overflow-hidden">
    <!-- Header -->
    <div class="py-5 text-center">
        <h1 class="h3 fw-bold mt-3 mb-2">
            <i class="fas fa-redo text-success me-2"></i>
            Renew Server Plan
        </h1>
        <h2 class="fs-base fw-medium text-muted mb-0">
            Renew your hosting plan and restore server access.
        </h2>
    </div>
    <!-- END Header -->

    <form id="renewal-form" action="{{ route('shop.checkout.process') }}" method="POST" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="renewal_order_id" value="{{ $cancelledOrder->id }}">
        
        <div class="row">
            {{-- Plan Details --}}
            <div class="col-xl-7">
                {{-- Server Information --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-server me-2"></i>
                            Server Information
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Server ID:</strong> {{ $cancelledOrder->server->uuidShort }}
                            </div>
                            <div class="col-md-6">
                                <strong>Plan:</strong> {{ $cancelledOrder->plan->name }}
                            </div>
                        </div>
                        @if($timeRemaining !== null)
                            @if($timeRemaining <= 0)
                                <div class="alert alert-danger mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Critical:</strong> This server will be deleted soon. Renew immediately to prevent data loss.
                                </div>
                            @else
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Warning:</strong> This server will be automatically deleted in {{ $timeRemaining }} day(s) if not renewed.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Billing Cycle Selection --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-calendar me-2"></i>
                            Billing Cycle
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="billing_cycle" id="monthly" value="monthly" checked>
                            <label class="form-check-label d-flex justify-content-between" for="monthly">
                                <span>
                                    <strong>Monthly</strong><br>
                                    <small class="text-muted">Billed every month</small>
                                </span>
                                <span class="fw-bold">${{ number_format($renewalPrice, 2) }}</span>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="billing_cycle" id="quarterly" value="quarterly">
                            <label class="form-check-label d-flex justify-content-between" for="quarterly">
                                <span>
                                    <strong>Quarterly (3 months)</strong><br>
                                    <small class="text-muted">Save with longer commitment</small>
                                </span>
                                <span class="fw-bold">${{ number_format($renewalPrice * 3, 2) }}</span>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="billing_cycle" id="annually" value="annually">
                            <label class="form-check-label d-flex justify-content-between" for="annually">
                                <span>
                                    <strong>Annually (12 months)</strong><br>
                                    <small class="text-muted">Best value - maximum savings</small>
                                </span>
                                <span class="fw-bold">${{ number_format($renewalPrice * 12, 2) }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Payment Method --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-credit-card me-2"></i>
                            Payment Method
                        </h3>
                    </div>
                    <div class="block-content">
                        @if(isset($paymentMethods['wallet']) && $userWallet && $userWallet->balance > 0)
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="wallet" value="wallet"
                                    {{ $userWallet->balance >= $renewalPrice ? '' : 'disabled' }}>
                                <label class="form-check-label" for="wallet">
                                    <i class="fas fa-wallet text-success me-2"></i>
                                    Wallet Balance
                                    <span class="badge bg-{{ $userWallet->balance >= $renewalPrice ? 'success' : 'danger' }} ms-2">
                                        ${{ number_format($userWallet->balance, 2) }}
                                    </span>
                                    @if($userWallet->balance < $renewalPrice)
                                        <br><small class="text-muted">Insufficient balance</small>
                                    @endif
                                </label>
                            </div>
                        @endif

                        @foreach($paymentMethods as $method => $details)
                            @if($method !== 'wallet')
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="{{ $method }}" value="{{ $method }}" 
                                       {{ (!isset($paymentMethods['wallet']) || !$userWallet || $userWallet->balance < $renewalPrice) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $method }}">
                                    <i class="{{ $details['icon'] ?? 'fas fa-credit-card' }} me-2"></i>
                                    {{ $details['name'] }}
                                </label>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Order Summary --}}
            <div class="col-xl-5">
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-receipt me-2"></i>
                            Renewal Summary
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <tbody>
                                    <tr>
                                        <td class="fw-medium">Plan:</td>
                                        <td class="text-end">{{ $cancelledOrder->plan->name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">Server:</td>
                                        <td class="text-end">{{ $cancelledOrder->server->uuidShort }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">Billing Cycle:</td>
                                        <td class="text-end" id="selected-cycle">Monthly</td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Amount:</th>
                                        <th class="text-end" id="total-amount">${{ number_format($renewalPrice, 2) }}</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg" id="submit-renewal">
                                <i class="fas fa-redo me-2"></i>
                                Complete Renewal
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-lock me-1"></i>
                                Your payment information is encrypted and secure
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Plan Details --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Plan Features
                        </h3>
                    </div>
                    <div class="block-content">
                        @if($cancelledOrder->plan->description)
                            <p>{{ $cancelledOrder->plan->description }}</p>
                        @endif
                        
                        @php
                            $planConfig = $cancelledOrder->plan->server_config ?? [];
                        @endphp
                        
                        @if(!empty($planConfig))
                            <ul class="list-unstyled">
                                @if(isset($planConfig['cpu']))
                                    <li><i class="fas fa-microchip me-2 text-primary"></i> {{ $planConfig['cpu'] }}% CPU</li>
                                @endif
                                @if(isset($planConfig['memory']))
                                    <li><i class="fas fa-memory me-2 text-primary"></i> {{ $planConfig['memory'] }}MB RAM</li>
                                @endif
                                @if(isset($planConfig['disk']))
                                    <li><i class="fas fa-hdd me-2 text-primary"></i> {{ $planConfig['disk'] }}MB Storage</li>
                                @endif
                                @if(isset($planConfig['swap']))
                                    <li><i class="fas fa-exchange-alt me-2 text-primary"></i> {{ $planConfig['swap'] }}MB Swap</li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const billingInputs = document.querySelectorAll('input[name="billing_cycle"]');
    const totalAmount = document.getElementById('total-amount');
    const selectedCycle = document.getElementById('selected-cycle');
    const basePrice = {{ $renewalPrice }};
    
    const priceMultipliers = {
        'monthly': 1,
        'quarterly': 3,
        'annually': 12
    };
    
    const cycleLabels = {
        'monthly': 'Monthly',
        'quarterly': 'Quarterly (3 months)',
        'annually': 'Annually (12 months)'
    };
    
    billingInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.checked) {
                const multiplier = priceMultipliers[this.value] || 1;
                const newTotal = basePrice * multiplier;
                totalAmount.textContent = '$' + newTotal.toFixed(2);
                selectedCycle.textContent = cycleLabels[this.value] || 'Monthly';
            }
        });
    });
    
    // Handle form submission with AJAX
    document.getElementById('renewal-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = document.getElementById('submit-renewal');
        const originalText = submitBtn.innerHTML;
        
        // Disable submit button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        // Get form data
        const formData = new FormData(form);
        
        // Make AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification('success', data.message || 'Renewal processed successfully!');
                
                // Redirect if specified
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            } else {
                // Show error message
                showNotification('error', data.message || 'Payment processing failed. Please try again.');
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Renewal error:', error);
            showNotification('error', 'An error occurred while processing your renewal. Please try again.');
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    // Helper function to show notifications
    function showNotification(type, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
});
</script>
@endsection