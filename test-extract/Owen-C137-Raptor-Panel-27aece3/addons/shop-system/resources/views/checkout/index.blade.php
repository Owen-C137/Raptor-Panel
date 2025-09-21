@extends('shop::layout')

@section('shop-title', 'Checkout')

@section('shop-content')
<div class="content content-boxed content-full overflow-hidden">
    <!-- Header -->
    <div class="py-5 text-center">
        <h1 class="h3 fw-bold mt-3 mb-2">
            <i class="fas fa-lock text-primary me-2"></i>
            Secure Checkout
        </h1>
        <h2 class="fs-base fw-medium text-muted mb-0">
            Complete your order securely and get your server hosting ready.
        </h2>
    </div>
    <!-- END Header -->

    <form id="checkout-form" class="needs-validation" novalidate>
        <div class="row">
            {{-- Main Checkout Form --}}
            <div class="col-xl-7">
                {{-- Billing Information --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-user me-2"></i>
                            1. Billing Information
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="{{ auth()->user()->name_first ?? '' }}" placeholder="Enter your first name" required>
                                    <label for="first_name">First Name *</label>
                                    <div class="invalid-feedback">Please provide a valid first name.</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="{{ auth()->user()->name_last ?? '' }}" placeholder="Enter your last name" required>
                                    <label for="last_name">Last Name *</label>
                                    <div class="invalid-feedback">Please provide a valid last name.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="{{ auth()->user()->email ?? '' }}" placeholder="Enter your email" required>
                                <label for="email">Email Address *</label>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="company" name="company" placeholder="Enter your company">
                                <label for="company">Company (Optional)</label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="address" name="address" placeholder="Enter your address" required>
                                <label for="address">Street Address *</label>
                                <div class="invalid-feedback">Please provide a valid address.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="address2" name="address2" placeholder="Enter apartment/suite">
                                <label for="address2">Apartment/Suite (Optional)</label>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-7">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="city" name="city" placeholder="Enter your city" required>
                                    <label for="city">City *</label>
                                    <div class="invalid-feedback">Please provide a valid city.</div>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="Enter postal code" required>
                                    <label for="postal_code">ZIP/Postal Code *</label>
                                    <div class="invalid-feedback">Please provide a valid postal code.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="state" name="state" placeholder="Enter state/province" required>
                                    <label for="state">State/Province *</label>
                                    <div class="invalid-feedback">Please provide a valid state.</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating">
                                    <select class="form-select" id="country" name="country" required>
                                        <option value="">Select Country</option>
                                        <option value="US">United States</option>
                                        <option value="CA">Canada</option>
                                        <option value="GB">United Kingdom</option>
                                        <option value="DE">Germany</option>
                                        <option value="FR">France</option>
                                        <option value="AU">Australia</option>
                                        {{-- Add more countries as needed --}}
                                    </select>
                                    <label for="country">Country *</label>
                                    <div class="invalid-feedback">Please select a country.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Payment Method --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-credit-card me-2"></i>
                            2. Payment Method
                        </h3>
                    </div>
                    <div class="block-content block-content-full">
                        {{-- Payment Gateway Selection --}}
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-check form-block">
                                    <input class="form-check-input" type="radio" name="payment_gateway" 
                                           id="stripe" value="stripe" checked>
                                    <label class="form-check-label" for="stripe">
                                        <span class="d-block fw-normal p-3">
                                            <span class="d-flex align-items-center">
                                                <i class="fab fa-cc-stripe fa-2x text-primary me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-1">Credit/Debit Card</div>
                                                    <div class="fs-sm text-muted">Visa, Mastercard, American Express</div>
                                                    <span class="badge bg-{{ ($shopConfig['stripe_mode'] ?? 'test') === 'live' ? 'success' : 'warning' }} mt-1">
                                                        {{ strtoupper($shopConfig['stripe_mode'] ?? 'test') }} MODE
                                                    </span>
                                                </div>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check form-block">
                                    <input class="form-check-input" type="radio" name="payment_gateway" 
                                           id="paypal" value="paypal">
                                    <label class="form-check-label" for="paypal">
                                        <span class="d-block fw-normal p-3">
                                            <span class="d-flex align-items-center">
                                                <i class="fab fa-paypal fa-2x text-primary me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-1">PayPal</div>
                                                    <div class="fs-sm text-muted">Pay with your PayPal account</div>
                                                    <span class="badge bg-{{ ($shopConfig['paypal_mode'] ?? 'sandbox') === 'live' ? 'success' : 'warning' }} mt-1">
                                                        {{ strtoupper($shopConfig['paypal_mode'] ?? 'sandbox') }} MODE
                                                    </span>
                                                </div>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            @if(isset($paymentMethods['wallet']) && $userWallet && $userWallet->balance > 0)
                            <div class="col-12">
                                <div class="form-check form-block">
                                    <input class="form-check-input" type="radio" name="payment_gateway" 
                                           id="wallet" value="wallet">
                                    <label class="form-check-label" for="wallet">
                                        <span class="d-block fw-normal p-3">
                                            <span class="d-flex align-items-center">
                                                <i class="fas fa-wallet fa-2x text-success me-3"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-1">Account Credit</div>
                                                    <div class="fs-sm text-muted">
                                                        Available: {{ $currencySymbol }}{{ number_format($userWallet->balance, 2) }}
                                                    </div>
                                                </div>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        {{-- Stripe Payment Form --}}
                        <div id="stripe-payment-form" class="payment-form">
                            <div id="card-element" class="form-control" style="height: 40px; padding: 10px;">
                                <!-- Stripe Elements will create input fields here -->
                            </div>
                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                        </div>
                        
                        {{-- PayPal Payment Form --}}
                        <div id="paypal-payment-form" class="payment-form" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                You will be redirected to PayPal to complete your payment securely.
                            </div>
                        </div>
                        
                        {{-- Wallet Payment Form --}}
                        @if($userWallet && $userWallet->balance > 0)
                        <div id="wallet-payment-form" class="payment-form" style="display: none;">
                            <div class="alert alert-success">
                                <i class="fas fa-wallet"></i>
                                <strong>Available Credit: {{ $currencySymbol }}{{ number_format($userWallet->balance, 2) }}</strong>
                            </div>
                            
                            <div id="wallet-insufficient" class="alert alert-warning" style="display: none;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Insufficient account credit. You can use partial credit and pay the remaining amount with another method.
                                
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="use-partial-credit">
                                    <label class="form-check-label" for="use-partial-credit">
                                        Use available credit and charge remaining to card
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Order Notes --}}
                <div class="block block-rounded">
                    <div class="block-header">
                        <h3 class="block-title">
                            <i class="fas fa-sticky-note me-2"></i>
                            3. Order Notes (Optional)
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="form-floating">
                            <textarea class="form-control" id="notes" name="notes" rows="3" style="height: 100px;"
                                      placeholder="Any special instructions or notes for your order..."></textarea>
                            <label for="notes">Special Instructions</label>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Order Summary Sidebar --}}
            <div class="col-xl-5">
                <div class="block block-rounded sticky-top" style="top: 20px;">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fas fa-receipt me-2"></i>
                            Order Summary
                        </h3>
                    </div>
                    <div class="block-content">
                        <div id="checkout-summary">
                            {{-- Summary will be loaded via JavaScript --}}
                            <div class="text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i>
                                <small class="text-muted d-block">Loading order details...</small>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Terms and Conditions Section --}}
                    <div class="block-content bg-body-light">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms-accept" name="terms_accept" required>
                            <label class="form-check-label fw-medium" for="terms-accept">
                                I agree to the <a href="#" target="_blank" class="link-fx">Terms of Service</a> and 
                                <a href="#" target="_blank" class="link-fx">Privacy Policy</a> *
                            </label>
                            <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="marketing-opt-in" name="marketing_opt_in">
                            <label class="form-check-label fw-medium" for="marketing-opt-in">
                                I would like to receive marketing communications and special offers
                            </label>
                        </div>
                    </div>
                    
                    <div class="block-content block-content-full">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="place-order-btn">
                                <i class="fas fa-lock me-2"></i>
                                <span id="order-btn-text">Complete Order</span>
                            </button>
                            
                            <a href="{{ route('shop.cart') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Cart
                            </a>
                        </div>
                        
                        <div class="security-info mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success me-1"></i>
                                Your payment information is encrypted and secure
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@parent
{{-- Stripe.js --}}
<script src="https://js.stripe.com/v3/"></script>

<script>
console.log('🚀 Checkout script starting...');

// Global shop configuration
window.shopConfig = {
    currency: '{{ $shopConfig['currency'] ?? 'USD' }}',
    currencySymbol: '{{ $currencySymbol }}',
    taxRate: {{ $shopConfig['tax_rate'] ?? 0 }},
    stripeEnabled: {{ ($shopConfig['stripe_enabled'] ?? false) ? 'true' : 'false' }},
    paypalEnabled: {{ ($shopConfig['paypal_enabled'] ?? false) ? 'true' : 'false' }}
};

// Shop utility object
window.Shop = {
    showNotification: function(type, message) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        notification.innerHTML = `
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>${type.charAt(0).toUpperCase() + type.slice(1)}!</strong> ${message}
        `;
        
        // Add to page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(notification, container.firstChild);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 150);
        }, 5000);
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 DOM loaded for checkout page');
    
    // Initialize Stripe only if publishable key is available
    const stripePublishableKey = '{{ $shopConfig['stripe_publishable_key'] ?? '' }}';
    let stripe = null;
    let cardElement = null;
    
    if (stripePublishableKey && stripePublishableKey.trim() !== '') {
        stripe = Stripe(stripePublishableKey);
        const elements = stripe.elements();
        
        // Create card element
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                color: '#424770',
                '::placeholder': {
                    color: '#aab7c4',
                },
            },
        },
    });
    
        cardElement.mount('#card-element');
        
        // Handle real-time validation errors from the card Element
        cardElement.on('change', ({error}) => {
            const displayError = document.getElementById('card-errors');
            if (error) {
                displayError.textContent = error.message;
            } else {
                displayError.textContent = '';
            }
        });
    } else {
        console.warn('⚠️ Stripe publishable key not configured. Stripe payments will not work.');
        // Hide Stripe payment option if key is not configured
        const stripeOption = document.getElementById('stripe');
        if (stripeOption) {
            stripeOption.closest('.form-check').style.display = 'none';
        }
    }
    
    // Payment method switching
    document.querySelectorAll('input[name="payment_gateway"]').forEach(input => {
        input.addEventListener('change', function() {
            // Hide all payment forms
            document.querySelectorAll('.payment-form').forEach(form => {
                form.style.display = 'none';
            });
            
            // Show selected payment form
            const selectedForm = document.getElementById(this.value + '-payment-form');
            if (selectedForm) {
                selectedForm.style.display = 'block';
            }

            // Show mode notice for payment gateways
            let modeMessage = '';
            if (this.value === 'stripe') {
                const stripeMode = @json(($shopConfig['stripe_mode'] ?? 'test') === 'test' ? 'TEST' : 'LIVE');
                modeMessage = `🔧 Stripe is running in ${stripeMode} mode`;
                Shop.showNotification('info', modeMessage);
            } else if (this.value === 'paypal') {
                const paypalMode = @json(($shopConfig['paypal_mode'] ?? 'sandbox') === 'sandbox' ? 'SANDBOX' : 'LIVE');
                modeMessage = `🔧 PayPal is running in ${paypalMode} mode`;
                Shop.showNotification('info', modeMessage);
            } else if (this.value === 'wallet') {
                Shop.showNotification('info', '💰 Using wallet balance for payment');
            }
            
            updateOrderButton();
        });
    });
    
    // Load order summary
    function loadOrderSummary() {
        console.log('🔄 Loading checkout summary...');
        console.log('📍 Summary URL:', '{{ route("shop.checkout.summary") }}');
        
        fetch('{{ route("shop.checkout.summary") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => {
                console.log('📡 Summary response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ Summary data received:', data);
                if (data.success) {
                    console.log('🎯 Rendering order summary with:', data.summary);
                    renderOrderSummary(data.summary);
                    checkWalletSufficiency(data.summary.total);
                } else {
                    console.error('❌ Summary API returned failure:', data);
                    document.getElementById('checkout-summary').innerHTML = 
                        '<div class="alert alert-danger">Failed to load order summary</div>';
                }
            })
            .catch(error => {
                console.error('❌ Error loading summary:', error);
                document.getElementById('checkout-summary').innerHTML = 
                    '<div class="alert alert-danger">Error loading order details</div>';
            });
    }
    
    // Render order summary
    function renderOrderSummary(summary) {
        let html = '';
        
        // Items
        summary.items.forEach(item => {
            const plan = item.plan;
            const itemTotal = (plan.price + plan.setup_fee) * item.quantity;
            
            html += `
                <div class="order-item mb-2">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="fw-bold">${plan.name}</div>
                            <small class="text-muted">${plan.product_name}</small>
                            ${item.quantity > 1 ? `<span class="badge bg-secondary ms-1">×${item.quantity}</span>` : ''}
                        </div>
                        <div class="text-end">
                            ${window.shopConfig.currencySymbol}${itemTotal.toFixed(2)}
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '<hr>';
        
        // Totals
        html += `
            <div class="summary-line">
                <span>Subtotal:</span>
                <span>${window.shopConfig.currencySymbol}${summary.subtotal.toFixed(2)}</span>
            </div>
        `;
        
        if (summary.setup_total > 0) {
            html += `
                <div class="summary-line">
                    <span>Setup Fees:</span>
                    <span>${window.shopConfig.currencySymbol}${summary.setup_total.toFixed(2)}</span>
                </div>
            `;
        }
        
        if (summary.discount > 0) {
            html += `
                <div class="summary-line text-success">
                    <span>Discount:</span>
                    <span>-${window.shopConfig.currencySymbol}${summary.discount.toFixed(2)}</span>
                </div>
            `;
        }
        
        if (summary.tax > 0) {
            html += `
                <div class="summary-line">
                    <span>Tax:</span>
                    <span>${window.shopConfig.currencySymbol}${summary.tax.toFixed(2)}</span>
                </div>
            `;
        }
        
        html += `
            <hr>
            <div class="summary-line total">
                <strong>
                    <span>Total:</span>
                    <span>${window.shopConfig.currencySymbol}${summary.total.toFixed(2)}</span>
                </strong>
            </div>
        `;
        
        document.getElementById('checkout-summary').innerHTML = html;
    }
    
    // Check wallet sufficiency
    function checkWalletSufficiency(total) {
        @if($userWallet)
        const walletBalance = {{ $userWallet->balance }};
        const insufficient = document.getElementById('wallet-insufficient');
        
        if (walletBalance < total && insufficient) {
            insufficient.style.display = 'block';
        } else if (insufficient) {
            insufficient.style.display = 'none';
        }
        @endif
    }
    
    // Update order button text
    function updateOrderButton() {
        const selectedGateway = document.querySelector('input[name="payment_gateway"]:checked').value;
        const btnText = document.getElementById('order-btn-text');
        
        switch(selectedGateway) {
            case 'paypal':
                btnText.textContent = 'Continue with PayPal';
                break;
            case 'wallet':
                btnText.textContent = 'Complete Order';
                break;
            default:
                btnText.textContent = 'Complete Order';
        }
    }
    
    // Form submission
    document.getElementById('checkout-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        const submitButton = document.getElementById('place-order-btn');
        const originalText = submitButton.innerHTML;
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        try {
            const selectedGateway = document.querySelector('input[name="payment_gateway"]:checked').value;
            
            if (selectedGateway === 'stripe') {
                await processStripePayment();
            } else if (selectedGateway === 'paypal') {
                await processPayPalPayment();
            } else if (selectedGateway === 'wallet') {
                await processWalletPayment();
            }
        } catch (error) {
            console.error('Checkout error:', error);
            Shop.showNotification('error', 'Payment processing failed. Please try again.');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
    
    // Process Stripe payment
    async function processStripePayment() {
        // Check if Stripe is properly initialized
        if (!stripe || !cardElement) {
            console.error('❌ Stripe not properly initialized');
            showAlert('Stripe payment is not properly configured. Please contact support.', 'error');
            return;
        }
        
        const formData = new FormData(document.getElementById('checkout-form'));
        
        // Create payment method
        const {error, paymentMethod} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
            billing_details: {
                name: formData.get('first_name') + ' ' + formData.get('last_name'),
                email: formData.get('email'),
                address: {
                    line1: formData.get('address'),
                    line2: formData.get('address2'),
                    city: formData.get('city'),
                    state: formData.get('state'),
                    postal_code: formData.get('postal_code'),
                    country: formData.get('country'),
                }
            }
        });
        
        if (error) {
            throw new Error(error.message);
        }
        
        // Add payment method ID to form data
        formData.append('payment_method_id', paymentMethod.id);
        
        // Submit to server
        const response = await fetch('{{ route("shop.checkout.process") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            if (result.data.requires_payment_action && result.data.payment_intent) {
                // Handle 3D secure
                if (!stripe) {
                    throw new Error('Stripe not properly initialized for payment confirmation');
                }
                const {error: confirmError} = await stripe.confirmCardPayment(result.data.payment_intent.client_secret);
                if (confirmError) {
                    throw new Error(confirmError.message);
                }
                // Redirect to success page
                window.location.href = result.data.redirect_url || '/shop/orders';
            } else {
                window.location.href = result.data.redirect_url || '/shop/orders';
            }
        } else {
            throw new Error(result.message);
        }
    }
    
    // Process PayPal payment
    async function processPayPalPayment() {
        const formData = new FormData(document.getElementById('checkout-form'));
        
        const response = await fetch('{{ route("shop.checkout.process") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        console.log('🎯 PayPal frontend result:', result);
        console.log('🔍 data object:', result.data);
        console.log('🔍 redirect_url value:', result.data?.redirect_url);
        console.log('🔍 redirect_url type:', typeof result.data?.redirect_url);
        
        if (result.success && result.data) {
            if (result.data.redirect_url && result.data.redirect_url !== 'undefined') {
                console.log('✅ Redirecting to:', result.data.redirect_url);
                window.location.href = result.data.redirect_url;
            } else {
                console.error('❌ Invalid redirect_url:', result.data.redirect_url);
                throw new Error('Invalid redirect URL received');
            }
        } else {
            throw new Error(result.message);
        }
    }
    
    // Process wallet payment
    async function processWalletPayment() {
        const formData = new FormData(document.getElementById('checkout-form'));
        
        const response = await fetch('{{ route("shop.checkout.process") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            window.location.href = result.data.redirect_url || '/shop/orders';
        } else {
            throw new Error(result.message);
        }
    }
    
    // Initialize
    console.log('🔧 About to call loadOrderSummary...');
    loadOrderSummary();
    console.log('🔧 About to call updateOrderButton...');
    updateOrderButton();
});
</script>
@endsection

@section('styles')
@parent
<style>
.checkout-steps {
    display: flex;
    align-items: center;
    gap: 10px;
}

.step {
    padding: 8px 16px;
    border-radius: 20px;
    background: #e9ecef;
    color: #6c757d;
    font-size: 0.9em;
    transition: all 0.3s;
}

.step.active {
    background: #007bff;
    color: white;
}

.step-divider {
    color: #dee2e6;
    font-weight: bold;
}

.payment-method-option {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.payment-method-option:hover {
    border-color: #007bff;
}

.payment-method-option .form-check-input:checked ~ .form-check-label {
    color: #007bff;
}

.payment-method-option .form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.payment-icon {
    width: 60px;
    text-align: center;
}

#card-element {
    background: white;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

#card-element:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.order-item {
    padding: 8px 0;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.summary-line.total {
    margin-top: 10px;
    font-size: 1.1em;
}

.security-info {
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
}

@media (max-width: 768px) {
    .checkout-steps {
        font-size: 0.8em;
    }
    
    .step {
        padding: 6px 12px;
    }
    
    .payment-method-option {
        padding: 10px;
    }
    
    .payment-icon {
        width: 45px;
    }
}
</style>
@endsection
