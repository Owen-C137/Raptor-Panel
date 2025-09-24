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
            const currencySymbol = (window.shopConfig && window.shopConfig.currencySymbol) || '$';
            depositAmountDisplay.textContent = selectedAmount > 0 ? `${currencySymbol}${selectedAmount.toFixed(2)}` : `${currencySymbol}0.00`;
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

function addToCart(planIdOrFormData, billingCycleOrCallback = 'monthly') {
    console.log('🛒 Adding to cart - Raw params:', arguments);
    console.log('🛒 First param type:', typeof planIdOrFormData, 'value:', planIdOrFormData);
    console.log('🛒 Second param type:', typeof billingCycleOrCallback, 'value:', billingCycleOrCallback);
    
    // Handle old API: addToCart(formData, callback)
    if (planIdOrFormData instanceof FormData) {
        console.log('🔄 Using old API signature: addToCart(formData, callback)');
        const formData = planIdOrFormData;
        const callback = billingCycleOrCallback;
        
        // Check if user is authenticated
        if (!window.PterodactylUser) {
            console.log('❌ User not authenticated, redirecting to login');
            showNotification('Please login to add items to cart', 'info');
            setTimeout(() => {
                window.location.href = '/auth/login';
            }, 1500);
            return Promise.resolve();
        }
        
        console.log('📦 FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        return fetch('/shop/cart/add', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: formData
        })
        .then(response => {
            console.log('📦 Add to cart response:', response.status, response.statusText);
            
            if (response.status === 401 || response.status === 403) {
                showNotification('Please login to add items to cart', 'info');
                setTimeout(() => {
                    window.location.href = '/auth/login';
                }, 1500);
                return;
            }
            
            const contentType = response.headers.get('content-type');
            console.log('📦 Content type:', contentType);
            
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('🚨 Expected JSON but got:', text.substring(0, 200) + '...');
                    throw new Error(`Server returned ${response.status}: Expected JSON but got ${contentType}`);
                });
            }
            
            if (!response.ok) {
                if (response.status === 422) {
                    return response.json().then(errorData => {
                        console.error('🚨 Validation errors:', errorData);
                        throw new Error(`Validation failed: ${JSON.stringify(errorData.errors || errorData.message)}`);
                    });
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            if (data && data.success) {
                updateCartCount();
                showNotification('Item added to cart!', 'success');
                if (typeof callback === 'function') {
                    callback(data);
                }
            } else if (data) {
                showNotification(data.message || 'Failed to add item to cart', 'error');
                if (typeof callback === 'function') {
                    callback(data);
                }
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            
            if (error.message && error.message.includes('401')) {
                showNotification('Please login to add items to cart', 'info');
                setTimeout(() => {
                    window.location.href = '/auth/login';
                }, 1500);
            } else if (error.message && error.message.includes('Expected JSON')) {
                showNotification('Server error - please try again later', 'error');
                console.error('Server returned HTML instead of JSON - possible route/controller issue');
            } else if (error.message && error.message.includes('Validation failed')) {
                showNotification('Please check your input and try again', 'error');
            } else {
                showNotification('Error adding item to cart', 'error');
            }
            
            if (typeof callback === 'function') {
                callback({ success: false, message: error.message });
            }
        });
    }
    
    // Handle new API: addToCart(planId, billingCycle)
    const planId = planIdOrFormData;
    const billingCycle = billingCycleOrCallback;
    
    // Validate parameters
    if (!planId || typeof planId !== 'string' && typeof planId !== 'number') {
        console.error('❌ Invalid planId:', planId);
        showNotification('Invalid product ID', 'error');
        return Promise.resolve();
    }
    
    if (typeof billingCycle !== 'string') {
        console.error('❌ Invalid billingCycle:', billingCycle);
        billingCycle = 'monthly'; // fallback
    }
    
    console.log('🛒 Adding to cart - Validated:', { planId, billingCycle });
    
    // Check if user is authenticated
    if (!window.PterodactylUser) {
        console.log('❌ User not authenticated, redirecting to login');
        showNotification('Please login to add items to cart', 'info');
        setTimeout(() => {
            window.location.href = '/auth/login';
        }, 1500);
        return Promise.resolve();
    }
    
    // Prepare form data instead of JSON
    const formData = new FormData();
    formData.append('plan_id', planId);
    formData.append('billing_cycle', billingCycle);
    formData.append('quantity', 1);
    
    console.log('📦 FormData contents:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    return fetch('/shop/cart/add', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest', // This tells Laravel this is an AJAX request
            // Remove Content-Type header to let browser set it for FormData
        },
        body: formData
    })
    .then(response => {
        console.log('📦 Add to cart response:', response.status, response.statusText);
        
        if (response.status === 401 || response.status === 403) {
            // User not authenticated, redirect to login
            showNotification('Please login to add items to cart', 'info');
            setTimeout(() => {
                window.location.href = '/auth/login';
            }, 1500);
            return;
        }
        
        // Check if response is HTML (error page) instead of JSON
        const contentType = response.headers.get('content-type');
        console.log('📦 Content type:', contentType);
        
        if (!contentType || !contentType.includes('application/json')) {
            // Log the actual response for debugging
            return response.text().then(text => {
                console.error('🚨 Expected JSON but got:', text.substring(0, 200) + '...');
                console.error('🚨 Full response headers:', [...response.headers.entries()]);
                throw new Error(`Server returned ${response.status}: Expected JSON but got ${contentType}`);
            });
        }
        
        if (!response.ok) {
            // For validation errors (422), try to get the error details
            if (response.status === 422) {
                return response.json().then(errorData => {
                    console.error('🚨 Validation errors:', errorData);
                    throw new Error(`Validation failed: ${JSON.stringify(errorData.errors || errorData.message)}`);
                });
            }
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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
        } else if (error.message && error.message.includes('Expected JSON')) {
            showNotification('Server error - please try again later', 'error');
            console.error('Server returned HTML instead of JSON - possible route/controller issue');
        } else if (error.name === 'SyntaxError' && error.message.includes('Unexpected token')) {
            showNotification('Server configuration error - please contact support', 'error');
            console.error('JSON parsing failed - server likely returned HTML error page');
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
                const cartBadgeElements = document.querySelectorAll('.cart-count-badge');
                
                cartCountElements.forEach(element => {
                    element.textContent = data.cart_count || 0;
                });
                
                if (data.cart_count && data.cart_count > 0) {
                    // Show cart count and badge
                    cartCountElements.forEach(element => {
                        element.style.display = 'inline';
                    });
                    cartBadgeElements.forEach(element => {
                        element.style.display = 'block';
                        // Add animation class if available
                        element.classList.add('animate__animated', 'animate__pulse');
                        setTimeout(() => {
                            element.classList.remove('animate__animated', 'animate__pulse');
                        }, 1000);
                    });
                } else {
                    // Hide cart count and badge
                    cartCountElements.forEach(element => {
                        element.style.display = 'none';
                    });
                    cartBadgeElements.forEach(element => {
                        element.style.display = 'none';
                    });
                }
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
    // Core functions
    init: function() {
        console.log('🛒 Shop system initialized');
        this.initWalletModal();
        this.initEventListeners();
        this.initTooltips();
        this.loadCart();
    },
    
    initEventListeners: function() {
        // Initialize cart button listeners
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const planId = this.dataset.planId || this.dataset.productId;
                const billingCycle = this.dataset.billingCycle || 'monthly';
                Shop.addToCart(planId, billingCycle);
            });
        });
        
        // Initialize quantity update listeners
        document.querySelectorAll('.update-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.dataset.itemId;
                const action = this.dataset.action;
                Shop.updateCartQuantity(itemId, action);
            });
        });
        
        // Initialize filter form listeners
        const filterForm = document.getElementById('shop-filters');
        if (filterForm) {
            filterForm.addEventListener('change', function() {
                this.submit();
            });
        }
        
        // Initialize cart dropdown refresh on show
        const cartDropdown = document.getElementById('page-header-cart-dropdown');
        if (cartDropdown) {
            cartDropdown.addEventListener('click', function() {
                console.log('🛒 Cart dropdown clicked, refreshing cart...');
                Shop.loadCart();
            });
        }
        
        // Also refresh cart when dropdown is shown via Bootstrap
        const cartDropdownElement = document.querySelector('[data-bs-toggle="dropdown"]#page-header-cart-dropdown');
        if (cartDropdownElement) {
            cartDropdownElement.addEventListener('shown.bs.dropdown', function() {
                console.log('🛒 Cart dropdown shown, refreshing cart...');
                Shop.loadCart();
            });
        }
    },
    
    initTooltips: function() {
        // Initialize Bootstrap tooltips for product cards
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
            console.log('🔧 Initialized', tooltipList.length, 'tooltips');
        } else {
            console.log('⚠️ Bootstrap tooltips not available');
        }
    },
    
    loadCart: function() {
        console.log('📦 Loading cart data...');
        
        // Show loading state for dropdown
        $('#cart-loading-dropdown').show();
        $('#cart-empty-dropdown, #cart-items-list, #cart-footer-dropdown').hide();
        
        // First try to load from server
        fetch('/shop/cart/items', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Cart data received from server:', data);
            $('#cart-loading-dropdown').hide();
            
            if (data.success && data.cart && data.cart.items && data.cart.items.length > 0) {
                this.updateCartDisplay(data.cart);
                this.updateCartCount(data.cart.total_items || data.cart.items.length);
            } else {
                this.showCartEmpty();
                this.updateCartCount(0);
            }
        })
        .catch(error => {
            console.log('⚠️ Server cart failed, trying localStorage:', error);
            $('#cart-loading-dropdown').hide();
            
            // Fallback to localStorage
            const cart = localStorage.getItem('shop_cart');
            if (cart) {
                try {
                    const cartData = JSON.parse(cart);
                    console.log('📦 Cart loaded from localStorage:', cartData);
                    
                    if (cartData && cartData.length > 0) {
                        this.updateCartDisplayFromLocalStorage(cartData);
                        this.updateCartCount(cartData.length);
                    } else {
                        this.showCartEmpty();
                        this.updateCartCount(0);
                    }
                } catch (e) {
                    console.error('❌ Error parsing localStorage cart:', e);
                    localStorage.removeItem('shop_cart');
                    this.showCartEmpty();
                    this.updateCartCount(0);
                }
            } else {
                this.showCartEmpty();
                this.updateCartCount(0);
            }
        });
    },
    
    updateWalletBalance: function() {
        console.log('🔄 Updating wallet balance via AJAX');
        
        // Store original balance as fallback
        const balanceElements = document.querySelectorAll('.wallet-balance');
        const originalBalances = [];
        balanceElements.forEach((element, index) => {
            originalBalances[index] = {
                text: element.textContent,
                balance: element.dataset.balance
            };
        });
        
        // Fetch updated wallet balance - using the correct API route
        fetch('/shop/wallet/balance')
            .then(response => {
                console.log('💰 Wallet balance response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error(`Expected JSON but got ${contentType}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('💰 Wallet balance response data:', data);
                if (data.success && data.formatted_balance) {
                    balanceElements.forEach(element => {
                        element.textContent = data.formatted_balance;
                        element.dataset.balance = data.balance;
                    });
                    console.log('💰 Wallet balance updated successfully to:', data.formatted_balance);
                } else {
                    console.warn('💰 Wallet balance update failed, keeping original balance');
                }
            })
            .catch(error => {
                console.error('💰 Error updating wallet balance, restoring original:', error);
                // Restore original balance if update fails
                balanceElements.forEach((element, index) => {
                    if (originalBalances[index]) {
                        element.textContent = originalBalances[index].text;
                        element.dataset.balance = originalBalances[index].balance;
                    }
                });
            });
    },
    
    updateCartDisplay: function(cart) {
        console.log('🔄 Updating cart display with:', cart);
        
        const cartItemsList = document.getElementById('cart-items-list');
        const cartEmpty = document.getElementById('cart-empty-dropdown');
        const cartFooter = document.getElementById('cart-footer-dropdown');
        const cartTotalAmount = document.getElementById('cart-total-amount');
        
        if (!cart || !cart.items || cart.items.length === 0) {
            this.showCartEmpty();
            return;
        }
        
        // Hide empty state, show items
        cartEmpty.style.display = 'none';
        cartItemsList.style.display = 'block';
        cartFooter.style.display = 'block';
        
        // Build cart items HTML
        let itemsHtml = '';
        cart.items.forEach(item => {
            const currencySymbol = cart.currency_symbol || '$';
            itemsHtml += `
                <div class="cart-item border-bottom p-3" data-item-id="${item.id}">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 rounded p-2">
                                <i class="fas fa-server text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-1 fw-semibold">${item.name || item.plan_name}</h6>
                                <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="Shop.removeCartItem('${item.id}')" title="Remove item">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="text-muted small mb-2">
                                ${item.description || ''}
                                ${item.billing_cycle ? `<span class="badge bg-secondary ms-1">${item.billing_cycle}</span>` : ''}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-secondary" onclick="Shop.updateCartQuantity('${item.id}', 'decrease')" ${item.quantity <= 1 ? 'disabled' : ''}>
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="btn btn-outline-secondary disabled">${item.quantity}</span>
                                    <button class="btn btn-outline-secondary" onclick="Shop.updateCartQuantity('${item.id}', 'increase')">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <span class="fw-semibold text-primary">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItemsList.innerHTML = itemsHtml;
        
        // Update total
        if (cartTotalAmount) {
            const currencySymbol = cart.currency_symbol || '$';
            cartTotalAmount.textContent = `${currencySymbol}${cart.total_amount || '0.00'}`;
        }
    },
    
    updateCartDisplayFromLocalStorage: function(cartItems) {
        console.log('🔄 Updating cart display from localStorage:', cartItems);
        
        const cartItemsList = document.getElementById('cart-items-list');
        const cartEmpty = document.getElementById('cart-empty-dropdown');
        const cartFooter = document.getElementById('cart-footer-dropdown');
        const cartTotalAmount = document.getElementById('cart-total-amount');
        
        if (!cartItems || cartItems.length === 0) {
            this.showCartEmpty();
            return;
        }
        
        // Hide empty state, show items
        cartEmpty.style.display = 'none';
        cartItemsList.style.display = 'block';
        cartFooter.style.display = 'block';
        
        // Build cart items HTML (simplified for localStorage)
        let itemsHtml = '';
        let totalAmount = 0;
        
        cartItems.forEach(item => {
            const price = parseFloat(item.price || 0);
            const quantity = parseInt(item.quantity || 1);
            const itemTotal = price * quantity;
            totalAmount += itemTotal;
            
            itemsHtml += `
                <div class="cart-item border-bottom p-3" data-item-id="${item.id}">
                    <div class="d-flex">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 rounded p-2">
                                <i class="fas fa-server text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-1 fw-semibold">${item.name || 'Hosting Plan'}</h6>
                                <button class="btn btn-sm btn-link text-danger p-0 ms-2" onclick="Shop.removeCartItem('${item.id}')" title="Remove item">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="text-muted small mb-2">
                                ${item.billing_cycle ? `<span class="badge bg-secondary">${item.billing_cycle}</span>` : ''}
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Qty: ${quantity}</span>
                                <span class="fw-semibold text-primary">${(window.shopConfig && window.shopConfig.currencySymbol) || '$'}${itemTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        cartItemsList.innerHTML = itemsHtml;
        
        // Update total
        if (cartTotalAmount) {
            const currencySymbol = (window.shopConfig && window.shopConfig.currencySymbol) || '$';
            cartTotalAmount.textContent = `${currencySymbol}${totalAmount.toFixed(2)}`;
        }
    },
    
    showCartEmpty: function() {
        console.log('📭 Showing empty cart state');
        const cartItemsList = document.getElementById('cart-items-list');
        const cartEmpty = document.getElementById('cart-empty-dropdown');
        const cartFooter = document.getElementById('cart-footer-dropdown');
        
        if (cartItemsList) cartItemsList.style.display = 'none';
        if (cartEmpty) cartEmpty.style.display = 'block';
        if (cartFooter) cartFooter.style.display = 'none';
    },
    
    updateCartCount: function(count) {
        console.log('🔢 Updating cart count to:', count);
        
        const cartCountElements = document.querySelectorAll('.cart-count, #cart-count, .cart-count-nav');
        const cartBadgeElements = document.querySelectorAll('.cart-count-badge');
        
        cartCountElements.forEach(element => {
            element.textContent = count || 0;
        });
        
        if (count && count > 0) {
            // Show cart count and badge
            cartCountElements.forEach(element => {
                element.style.display = 'inline';
            });
            cartBadgeElements.forEach(element => {
                element.style.display = 'block';
                // Add animation class if available
                element.classList.add('animate__animated', 'animate__pulse');
                setTimeout(() => {
                    element.classList.remove('animate__animated', 'animate__pulse');
                }, 1000);
            });
        } else {
            // Hide cart count and badge
            cartCountElements.forEach(element => {
                element.style.display = 'none';
            });
            cartBadgeElements.forEach(element => {
                element.style.display = 'none';
            });
        }
    },
    
    loadLocations: function(callback) {
        fetch('/shop/api/locations')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && callback) {
                    callback(data.locations || []);
                }
            })
            .catch(error => {
                console.error('Error loading locations:', error);
                if (callback) callback([]);
            });
    },
    
    loadPlanDetails: function(planId, callback) {
        fetch(`/shop/api/plans/${planId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && callback) {
                    callback(data.plan);
                }
            })
            .catch(error => {
                console.error('Error loading plan details:', error);
                if (callback) callback(null);
            });
    },
    
    loadLocations: function(callback) {
        console.log('📍 Loading server locations...');
        fetch('/shop/api/locations')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Locations loaded:', data);
                if (callback && typeof callback === 'function') {
                    callback(data.locations || data);
                }
            })
            .catch(error => {
                console.error('❌ Error loading locations:', error);
                // Fallback with empty array
                if (callback && typeof callback === 'function') {
                    callback([]);
                }
            });
    },
    
    loadPlanDetails: function(planId, callback) {
        console.log(`📋 Loading plan details for ID: ${planId}`);
        fetch(`/shop/api/plans/${planId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Plan details loaded:', data);
                if (callback && typeof callback === 'function') {
                    callback(data.plan || data);
                }
            })
            .catch(error => {
                console.error('❌ Error loading plan details:', error);
                if (callback && typeof callback === 'function') {
                    callback(null);
                }
            });
    },
    
    // Existing functions
    showNotification: showNotification,
    addToCart: addToCart,
    updateCartCount: updateCartCount,
    updateCartQuantity: updateCartQuantity,
    clearCart: clearCart,
    applyCoupon: applyCoupon,
    applyPromoCode: applyPromoCode,
    updateCartItem: updateCartItem,
    removeCartItem: removeCartItem,
    updatePlanPricing: updatePlanPricing,
    initWalletModal: initWalletModal
};
