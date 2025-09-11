@extends('shop::layout')

@section('shop-title', 'Checkout')

@section('shop-content')
<div class="checkout-container">
    <div class="row">
        <div class="col-12">
            <div class="checkout-header mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <h1>
                        <i class="fas fa-lock"></i>
                        Secure Checkout
                    </h1>
                    
                    <div class="checkout-steps">
                        <span class="step active" data-step="1">
                            <i class="fas fa-shopping-cart"></i>
                            Cart
                        </span>
                        <span class="step-divider">></span>
                        <span class="step active" data-step="2">
                            <i class="fas fa-credit-card"></i>
                            Payment
                        </span>
                        <span class="step-divider">></span>
                        <span class="step" data-step="3">
                            <i class="fas fa-check"></i>
                            Complete
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <form id="checkout-form" class="needs-validation" novalidate>
        <div class="row">
            {{-- Main Checkout Form --}}
            <div class="col-lg-8">
                {{-- Billing Information --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user"></i>
                            Billing Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="{{ auth()->user()->name_first ?? '' }}" required>
                                <div class="invalid-feedback">Please provide a valid first name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="{{ auth()->user()->name_last ?? '' }}" required>
                                <div class="invalid-feedback">Please provide a valid last name.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="{{ auth()->user()->email ?? '' }}" required>
                            <div class="invalid-feedback">Please provide a valid email address.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="company" class="form-label">Company (Optional)</label>
                            <input type="text" class="form-control" id="company" name="company">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                                <div class="invalid-feedback">Please provide a valid address.</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="address2" class="form-label">Apt/Suite</label>
                                <input type="text" class="form-control" id="address2" name="address2">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                                <div class="invalid-feedback">Please provide a valid city.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="state" class="form-label">State/Province *</label>
                                <input type="text" class="form-control" id="state" name="state" required>
                                <div class="invalid-feedback">Please provide a valid state.</div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="postal_code" class="form-label">ZIP/Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                <div class="invalid-feedback">Please provide a valid postal code.</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="country" class="form-label">Country *</label>
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
                            <div class="invalid-feedback">Please select a country.</div>
                        </div>
                    </div>
                </div>
                
                {{-- Payment Method --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Payment Gateway Selection --}}
                        <div class="payment-methods mb-4">
                            <div class="form-check payment-method-option">
                                <input class="form-check-input" type="radio" name="payment_gateway" 
                                       id="stripe" value="stripe" checked>
                                <label class="form-check-label d-flex align-items-center" for="stripe">
                                    <div class="payment-icon me-3">
                                        <i class="fab fa-cc-stripe fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Credit/Debit Card</div>
                                        <small class="text-muted">Visa, Mastercard, American Express</small>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="form-check payment-method-option">
                                <input class="form-check-input" type="radio" name="payment_gateway" 
                                       id="paypal" value="paypal">
                                <label class="form-check-label d-flex align-items-center" for="paypal">
                                    <div class="payment-icon me-3">
                                        <i class="fab fa-paypal fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">PayPal</div>
                                        <small class="text-muted">Pay with your PayPal account</small>
                                    </div>
                                </label>
                            </div>
                            
                            @if(auth()->user()->shopWallet && auth()->user()->shopWallet->balance > 0)
                            <div class="form-check payment-method-option">
                                <input class="form-check-input" type="radio" name="payment_gateway" 
                                       id="wallet" value="wallet">
                                <label class="form-check-label d-flex align-items-center" for="wallet">
                                    <div class="payment-icon me-3">
                                        <i class="fas fa-wallet fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold">Account Credit</div>
                                        <small class="text-muted">
                                            Available: {{ config('shop.currency.symbol', '$') }}{{ number_format(auth()->user()->shopWallet->balance, 2) }}
                                        </small>
                                    </div>
                                </label>
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
                        @if(auth()->user()->shopWallet && auth()->user()->shopWallet->balance > 0)
                        <div id="wallet-payment-form" class="payment-form" style="display: none;">
                            <div class="alert alert-success">
                                <i class="fas fa-wallet"></i>
                                <strong>Available Credit: {{ config('shop.currency.symbol', '$') }}{{ number_format(auth()->user()->shopWallet->balance, 2) }}</strong>
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
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note"></i>
                            Order Notes (Optional)
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Any special instructions or notes for your order..."></textarea>
                    </div>
                </div>
                
                {{-- Terms and Conditions --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms-accept" name="terms_accept" required>
                            <label class="form-check-label" for="terms-accept">
                                I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                                <a href="#" target="_blank">Privacy Policy</a> *
                            </label>
                            <div class="invalid-feedback">You must agree to the terms and conditions.</div>
                        </div>
                        
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="marketing-opt-in" name="marketing_opt_in">
                            <label class="form-check-label" for="marketing-opt-in">
                                I would like to receive marketing communications and special offers
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Order Summary Sidebar --}}
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt"></i>
                            Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="checkout-summary">
                            {{-- Summary will be loaded via JavaScript --}}
                            <div class="text-center py-3">
                                <i class="fas fa-spinner fa-spin"></i>
                                <small class="text-muted d-block">Loading order details...</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" id="place-order-btn">
                                <i class="fas fa-lock"></i>
                                <span id="order-btn-text">Complete Order</span>
                            </button>
                            
                            <a href="{{ route('shop.cart') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Back to Cart
                            </a>
                        </div>
                        
                        <div class="security-info mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt text-success"></i>
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 DOM loaded for checkout page');
    
    // Initialize Stripe
    const stripe = Stripe('{{ config("shop.payment.stripe.public_key") }}');
    const elements = stripe.elements();
    
    // Create card element
    const cardElement = elements.create('card', {
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
                            {{ config('shop.currency.symbol', '$') }}${itemTotal.toFixed(2)}
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
                <span>{{ config('shop.currency.symbol', '$') }}${summary.subtotal.toFixed(2)}</span>
            </div>
        `;
        
        if (summary.setup_total > 0) {
            html += `
                <div class="summary-line">
                    <span>Setup Fees:</span>
                    <span>{{ config('shop.currency.symbol', '$') }}${summary.setup_total.toFixed(2)}</span>
                </div>
            `;
        }
        
        if (summary.discount > 0) {
            html += `
                <div class="summary-line text-success">
                    <span>Discount:</span>
                    <span>-{{ config('shop.currency.symbol', '$') }}${summary.discount.toFixed(2)}</span>
                </div>
            `;
        }
        
        if (summary.tax > 0) {
            html += `
                <div class="summary-line">
                    <span>Tax:</span>
                    <span>{{ config('shop.currency.symbol', '$') }}${summary.tax.toFixed(2)}</span>
                </div>
            `;
        }
        
        html += `
            <hr>
            <div class="summary-line total">
                <strong>
                    <span>Total:</span>
                    <span>{{ config('shop.currency.symbol', '$') }}${summary.total.toFixed(2)}</span>
                </strong>
            </div>
        `;
        
        document.getElementById('checkout-summary').innerHTML = html;
    }
    
    // Check wallet sufficiency
    function checkWalletSufficiency(total) {
        @if(auth()->user()->shopWallet)
        const walletBalance = {{ auth()->user()->shopWallet->balance }};
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
        
        if (result.success) {
            if (result.requires_action) {
                // Handle 3D secure
                const {error: confirmError} = await stripe.confirmCardPayment(result.payment_intent.client_secret);
                if (confirmError) {
                    throw new Error(confirmError.message);
                }
                // Redirect to success page
                window.location.href = result.redirect_url;
            } else {
                window.location.href = result.redirect_url;
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
        
        if (result.success) {
            window.location.href = result.redirect_url;
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
        
        if (result.success) {
            window.location.href = result.redirect_url;
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
