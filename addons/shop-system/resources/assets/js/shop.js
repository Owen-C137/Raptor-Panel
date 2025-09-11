// Shop System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Only run shop JavaScript if we're on a shop page
    if (!document.body.classList.contains('shop-page') && !document.querySelector('.shop-container')) {
        return;
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            showNotification('Item added to cart!', 'success');
        } else {
            showNotification(data.message || 'Failed to add item to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding to cart:', error);
        showNotification('Error adding item to cart', 'error');
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
        .then(response => response.json())
        .then(data => {
            const cartCountElements = document.querySelectorAll('.cart-count, #cart-count');
            cartCountElements.forEach(element => {
                element.textContent = data.item_count || 0;
                element.style.display = data.item_count > 0 ? 'inline' : 'none';
            });
        })
        .catch(error => {
            console.error('Error updating cart count:', error);
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
