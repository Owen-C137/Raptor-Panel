// Shop System JavaScript

// Wallet Add Funds Modal Functionality
function initWalletModal() {
    console.log('Initializing wallet modal...');
    
    const modal = document.getElementById('addFundsModal');
    const amountButtons = document.querySelectorAll('.amount-btn');
    const customAmountInput = document.getElementById('customAmount');
    const depositAmountDisplay = document.getElementById('depositAmount');
    const addFundsBtn = document.getElementById('addFundsBtn');
    
    if (!modal) {
        console.log('Wallet modal not found on this page');
        return;
    }
    
    console.log('Found wallet modal elements:', {
        modal: !!modal,
        amountButtons: amountButtons.length,
        customAmountInput: !!customAmountInput,
        depositAmountDisplay: !!depositAmountDisplay,
        addFundsBtn: !!addFundsBtn
    });
    
    let selectedAmount = 0;
    
    // Amount button handlers
    amountButtons.forEach(button => {
        console.log('Setting up amount button:', button.textContent);
        button.addEventListener('click', function() {
            console.log('Amount button clicked:', this.textContent);
            
            // Remove active class from all buttons
            amountButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get amount from data attribute or text
            selectedAmount = parseFloat(this.dataset.amount || this.textContent.replace(/[^0-9.]/g, ''));
            console.log('Selected amount:', selectedAmount);
            
            // Clear custom amount input
            if (customAmountInput) {
                customAmountInput.value = '';
            }
            
            // Update display
            updateDepositDisplay();
        });
    });
    
    // Custom amount input handler
    if (customAmountInput) {
        customAmountInput.addEventListener('input', function() {
            console.log('Custom amount input changed:', this.value);
            
            // Remove active class from amount buttons
            amountButtons.forEach(btn => btn.classList.remove('active'));
            
            // Set selected amount
            selectedAmount = parseFloat(this.value) || 0;
            console.log('Custom selected amount:', selectedAmount);
            
            // Update display
            updateDepositDisplay();
        });
    }
    
    // Update deposit display function
    function updateDepositDisplay() {
        console.log('Updating deposit display with amount:', selectedAmount);
        
        if (depositAmountDisplay) {
            depositAmountDisplay.textContent = selectedAmount > 0 ? `$${selectedAmount.toFixed(2)}` : '$0.00';
            console.log('Updated deposit display to:', depositAmountDisplay.textContent);
        } else {
            console.error('Deposit amount display element not found');
        }
        
        // Update add funds button state
        if (addFundsBtn) {
            addFundsBtn.disabled = selectedAmount <= 0;
            console.log('Add funds button disabled:', addFundsBtn.disabled);
        }
    }
    
    // Add funds button handler
    if (addFundsBtn) {
        addFundsBtn.addEventListener('click', function() {
            console.log('Add funds button clicked, amount:', selectedAmount);
            
            if (selectedAmount <= 0) {
                console.error('No amount selected');
                if (typeof showNotification === 'function') {
                    showNotification('Please select an amount to add', 'error');
                } else {
                    alert('Please select an amount to add');
                }
                return;
            }
            
            // Get selected payment method
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                console.error('No payment method selected');
                if (typeof showNotification === 'function') {
                    showNotification('Please select a payment method', 'error');
                } else {
                    alert('Please select a payment method');
                }
                return;
            }
            
            console.log('Processing payment with method:', paymentMethod.value);
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Create form data
            const formData = new FormData();
            formData.append('amount', selectedAmount);
            formData.append('payment_method', paymentMethod.value);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            // Submit payment request
            fetch('/shop/wallet/add-funds', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Payment response:', data);
                
                if (data.success) {
                    // Handle Stripe payment
                    if (data.client_secret && paymentMethod.value === 'stripe') {
                        console.log('Processing Stripe payment...');
                        // For now, we'll show a success message since Stripe integration requires more frontend setup
                        if (typeof showNotification === 'function') {
                            showNotification('Payment initiated successfully! Please complete the payment process.', 'success');
                        } else {
                            alert('Payment initiated successfully! Please complete the payment process.');
                        }
                        // In a full implementation, you'd use Stripe.js here to handle the payment intent
                        setTimeout(() => window.location.reload(), 2000);
                    }
                    // Handle PayPal payment
                    else if (data.approval_url && paymentMethod.value === 'paypal') {
                        console.log('Redirecting to PayPal:', data.approval_url);
                        window.location.href = data.approval_url;
                    }
                    // Handle legacy redirect_url format
                    else if (data.redirect_url) {
                        console.log('Redirecting to payment gateway:', data.redirect_url);
                        window.location.href = data.redirect_url;
                    }
                    else {
                        console.log('Payment processed successfully');
                        if (typeof showNotification === 'function') {
                            showNotification('Payment processed successfully!', 'success');
                        } else {
                            alert('Payment processed successfully!');
                        }
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } else {
                    console.error('Payment failed:', data.message);
                    if (typeof showNotification === 'function') {
                        showNotification(data.message || 'Payment processing failed', 'error');
                    } else {
                        alert(data.message || 'Payment processing failed');
                    }
                    
                    // Reset button
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-plus"></i> Add Funds';
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                if (typeof showNotification === 'function') {
                    showNotification('Payment processing failed', 'error');
                } else {
                    alert('Payment processing failed');
                }
                
                // Reset button
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-plus"></i> Add Funds';
            });
        });
    }
    
    console.log('Wallet modal initialization complete');
}

document.addEventListener('DOMContentLoaded', function() {
    // Only run shop JavaScript if we're on a shop page or wallet page
    if (!document.body.classList.contains('shop-page') && 
        !document.querySelector('.shop-container') && 
        !document.querySelector('#addFundsModal')) {
        return;
    }
    
    // Initialize wallet modal if present
    if (document.querySelector('#addFundsModal')) {
        console.log('Wallet page detected, initializing wallet modal...');
        initWalletModal();
    }
    
    // Update cart count on page load
    updateCartCount();
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const billingCycle = this.dataset.billingCycle || 'monthly';
            
            addToCart(planId, billingCycle);
        });
    });
    
    // Cart quantity controls
    document.querySelectorAll('.quantity-control').forEach(control => {
        control.addEventListener('click', function() {
            const action = this.dataset.action;
            const itemId = this.dataset.itemId;
            
            updateCartQuantity(itemId, action);
        });
    });
    
    // Remove item buttons
    document.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.dataset.index;
            removeCartItem(index);
        });
    });
    
    // Quantity update inputs
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const index = this.dataset.index;
            const quantity = parseInt(this.value);
            if (quantity > 0) {
                updateCartItem(index, quantity);
            }
        });
    });
    
    // Promo code form
    const promoForm = document.getElementById('promo-code-form');
    if (promoForm) {
        promoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const code = document.getElementById('promo-code-input').value.trim();
            if (!code) return;
            
            applyPromoCode(code);
        });
    }
    
    // Cart sidebar toggle functionality
    const cartToggle = document.getElementById('cart-toggle');
    const cartSidebar = document.getElementById('cart-sidebar');
    const cartClose = document.getElementById('cart-close');
    const cartBackdrop = document.getElementById('cart-backdrop');
    
    function openCart() {
        if (cartSidebar) {
            cartSidebar.classList.add('active');
        }
        if (cartBackdrop) {
            cartBackdrop.classList.add('active');
        }
        document.body.style.overflow = 'hidden'; // Prevent body scroll
    }
    
    function closeCart() {
        if (cartSidebar) {
            cartSidebar.classList.remove('active');
        }
        if (cartBackdrop) {
            cartBackdrop.classList.remove('active');
        }
        document.body.style.overflow = ''; // Restore body scroll
    }
    
    if (cartToggle) {
        cartToggle.addEventListener('click', openCart);
    }
    
    if (cartClose) {
        cartClose.addEventListener('click', closeCart);
    }
    
    if (cartBackdrop) {
        cartBackdrop.addEventListener('click', closeCart);
    }
    
    // Close cart when pressing Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && cartSidebar && cartSidebar.classList.contains('active')) {
            closeCart();
        }
    });
    
    // Billing cycle selector
    document.querySelectorAll('.billing-cycle-selector').forEach(selector => {
        selector.addEventListener('change', function() {
            const planId = this.dataset.planId;
            const cycle = this.value;
            
            updatePlanPricing(planId, cycle);
        });
    });
});

function addToCart(planId, billingCycle = 'monthly') {
    fetch('/shop/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            plan_id: planId,
            billing_cycle: billingCycle,
            quantity: 1
        })
    })
    .then(response => {
        if (response.status === 401 || response.status === 403) {
            // User not authenticated, redirect to login
            showNotification('Please login to add items to cart', 'info');
            setTimeout(() => {
                window.location.href = '/auth/login';
            }, 1500);
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            updateCartCount();
            showNotification('Item added to cart!', 'success');
        } else if (data) {
            showNotification(data.message || 'Failed to add item to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        if (error.message && error.message.includes('401')) {
            showNotification('Please login to add items to cart', 'info');
            setTimeout(() => {
                window.location.href = '/auth/login';
            }, 1500);
        } else {
            showNotification('Error adding item to cart', 'error');
        }
    });
}

function updateCartQuantity(itemId, action) {
    const endpoint = action === 'remove' ? '/shop/cart/remove' : '/shop/cart/update';
    
    fetch(endpoint, {
        method: action === 'remove' ? 'DELETE' : 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to update cart display
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating cart:', error);
        showNotification('Error updating cart', 'error');
    });
}

function updateCartCount() {
    fetch('/shop/cart/summary')
        .then(response => {
            if (response.status === 401 || response.status === 403) {
                // User not authenticated, hide cart count
                const cartCountElements = document.querySelectorAll('.cart-count, #cart-count, .cart-count-nav');
                cartCountElements.forEach(element => {
                    element.textContent = 0;
                    element.style.display = 'none';
                });
                return;
            }
            return response.json();
        })
        .then(data => {
            if (data) {
                const cartCountElements = document.querySelectorAll('.cart-count, #cart-count, .cart-count-nav');
                cartCountElements.forEach(element => {
                    element.textContent = data.cart_count || 0;
                    if (data.cart_count && data.cart_count > 0) {
                        element.style.display = 'inline';
                        // Add animation class if available
                        element.classList.add('animate__animated', 'animate__pulse');
                        setTimeout(() => {
                            element.classList.remove('animate__animated', 'animate__pulse');
                        }, 1000);
                    } else {
                        element.style.display = 'none';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
            // Hide cart count on error
            const cartCountElements = document.querySelectorAll('.cart-count, #cart-count, .cart-count-nav');
            cartCountElements.forEach(element => {
                element.textContent = 0;
                element.style.display = 'none';
            });
        });
}

function updatePlanPricing(planId, billingCycle) {
    // Update the pricing display based on selected billing cycle
    const priceElement = document.querySelector(`#plan-${planId}-price`);
    const setupFeeElement = document.querySelector(`#plan-${planId}-setup-fee`);
    const addToCartBtn = document.querySelector(`#plan-${planId}-add-btn`);
    
    if (priceElement && addToCartBtn) {
        // Update the button's billing cycle data
        addToCartBtn.dataset.billingCycle = billingCycle;
        
        // You can add more sophisticated pricing updates here
        // For now, we'll let the server handle the pricing logic
    }
}

function showNotification(message, type = 'info') {
    // Create a simple notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    
    notification.innerHTML = `
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

// Cart management functions
function clearCart() {
    if (confirm('Are you sure you want to clear your cart?')) {
        fetch('/shop/cart/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification('Failed to clear cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            showNotification('Error clearing cart', 'error');
        });
    }
}

// Coupon management
function applyCoupon() {
    const couponCode = document.querySelector('#coupon-code').value;
    
    if (!couponCode) {
        showNotification('Please enter a coupon code', 'warning');
        return;
    }
    
    fetch('/shop/checkout/coupon/apply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            coupon_code: couponCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Coupon applied successfully!', 'success');
            location.reload();
        } else {
            showNotification(data.message || 'Invalid coupon code', 'error');
        }
    })
    .catch(error => {
        console.error('Error applying coupon:', error);
        showNotification('Error applying coupon', 'error');
    });
}

// Cart item management functions
function updateCartItem(index, quantity) {
    // Create a form and submit to refresh the page
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/shop/cart/update';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    
    // Add method spoofing for PUT request
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PUT';
    
    const indexInput = document.createElement('input');
    indexInput.type = 'hidden';
    indexInput.name = 'index';
    indexInput.value = index;
    
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity';
    quantityInput.value = quantity;
    
    form.appendChild(csrfInput);
    form.appendChild(methodInput);
    form.appendChild(indexInput);
    form.appendChild(quantityInput);
    document.body.appendChild(form);
    form.submit();
}

function removeCartItem(index) {
    // Create a form and submit to refresh the page
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/shop/cart/remove';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
    
    // Add method spoofing for DELETE request
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    
    const indexInput = document.createElement('input');
    indexInput.type = 'hidden';
    indexInput.name = 'index';
    indexInput.value = index;
    
    form.appendChild(csrfInput);
    form.appendChild(methodInput);
    form.appendChild(indexInput);
    document.body.appendChild(form);
    form.submit();
}

function applyPromoCode(code) {
    const formData = new FormData();
    formData.append('code', code);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    fetch('/shop/checkout/coupon/apply', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Invalid promo code', 'error');
        }
    })
    .catch(error => {
        console.error('Error applying promo code:', error);
        showNotification('Error applying promo code', 'error');
    });
}

// Global Shop object for external access
window.Shop = {
    showNotification: showNotification,
    addToCart: addToCart,
    updateCartCount: updateCartCount,
    clearCart: clearCart,
    applyCoupon: applyCoupon,
    initWalletModal: initWalletModal
};
