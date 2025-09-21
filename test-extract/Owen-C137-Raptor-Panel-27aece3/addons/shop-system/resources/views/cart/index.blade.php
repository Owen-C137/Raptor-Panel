@extends('shop::layout')

@section('shop-title', 'Shopping Cart')

@section('shop-content')
{{-- Debug Info Panel --}}
@if(config('app.debug'))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle"></i> Debug Information</h6>
            <ul class="mb-0">
                <li><strong>Authentication Status:</strong> {{ auth()->check() ? 'Logged In' : 'Guest' }}</li>
                @auth
                    <li><strong>User ID:</strong> {{ auth()->id() }}</li>
                    <li><strong>User Name:</strong> {{ auth()->user()->name_first }} {{ auth()->user()->name_last }}</li>
                    <li><strong>User Email:</strong> {{ auth()->user()->email }}</li>
                @endauth
                <li><strong>Route Name:</strong> {{ request()->route()->getName() }}</li>
                <li><strong>Request URL:</strong> {{ request()->fullUrl() }}</li>
                <li><strong>Current Time:</strong> {{ now() }}</li>
            </ul>
        </div>
    </div>
</div>
@endif

{{-- Initial Loading State --}}
<div class="row" id="initial-loading">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x mb-3 text-primary"></i>
                <h4>Loading Your Cart</h4>
                <p class="text-muted">Please wait while we load your cart items...</p>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1>
                <i class="fas fa-shopping-cart"></i>
                Shopping Cart
            </h1>
            
            <div class="cart-actions">
                <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Browse Plans
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row d-none" id="cart-container">
    {{-- Cart Items --}}
    <div class="col-lg-8">
        <div class="cart-items" id="cart-items">
            {{-- Items will be loaded via JavaScript --}}
            <div class="text-center py-5" id="cart-loading">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Loading cart items...</p>
            </div>
        </div>
    </div>
    
    {{-- Cart Summary --}}
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-calculator"></i>
                    Order Summary
                </h5>
            </div>
            <div class="card-body">
                <div id="cart-summary-content">
                    {{-- Summary will be loaded via JavaScript --}}
                    <div class="text-center py-3" id="summary-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <small class="text-muted">Calculating...</small>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Promo Code --}}
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-ticket-alt"></i>
                    Promo Code
                </h6>
            </div>
            <div class="card-body">
                <form id="promo-code-form">
                    <div class="input-group">
                        <input type="text" class="form-control" id="promo-code-input" 
                               placeholder="Enter promo code">
                        <button type="submit" class="btn btn-outline-primary" id="apply-promo-btn">
                            Apply
                        </button>
                    </div>
                </form>
                <div id="applied-promo" class="mt-2" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge bg-success" id="promo-code-display"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="remove-promo-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Empty Cart State --}}
<div class="row" id="empty-cart" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3>Your Cart is Empty</h3>
                <p class="text-muted">Browse our products and add items to your cart.</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">
                    <i class="fas fa-store"></i>
                    Start Shopping
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script>
    console.log('🛒 Cart script initializing...');
    
    // Set currency symbol globally for JavaScript use
    window.currencySymbol = '{{ $currencySymbol }}';
    
    // Create Shop object if it doesn't exist to bridge with global functions
    if (typeof Shop === 'undefined') {
        window.Shop = {
            showNotification: function(type, message) {
                if (typeof showNotification === 'function') {
                    showNotification(message, type);
                } else {
                    console.log('Notification:', type, message);
                }
            },
            updateCartCount: function(count) {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                } else {
                    console.log('Update cart count:', count);
                }
            }
        };
    }
    
    function loadCart() {
        console.log('🛒 Starting loadCart()');
        console.log('🔗 Auth status:', '{{ auth()->check() ? "authenticated" : "guest" }}');
        console.log('👤 User ID:', '{{ auth()->id() ?? "none" }}');
        console.log('📍 Current URL:', window.location.href);
        console.log('🎯 Cart summary URL:', '{{ route("shop.cart.summary") }}');
        
        // Show initial loading state
        const initialLoading = document.getElementById('initial-loading');
        const cartContainer = document.getElementById('cart-container');
        const emptyCart = document.getElementById('empty-cart');
        
        console.log('🔍 Elements found:', {
            initialLoading: !!initialLoading,
            cartContainer: !!cartContainer,
            emptyCart: !!emptyCart
        });
        
        if (initialLoading) initialLoading.style.display = 'block';
        if (cartContainer) cartContainer.classList.add('d-none');
        if (emptyCart) emptyCart.style.display = 'none';

        // Set a timeout to prevent infinite loading
        const loadingTimeout = setTimeout(() => {
            console.log('⏰ Loading timeout reached, showing empty cart');
            if (initialLoading) initialLoading.style.display = 'none';
            showEmptyCart();
        }, 10000); // 10 second timeout

        console.log('📤 Making AJAX request to cart summary...');
        $.ajax({
            url: '{{ route("shop.cart.summary") }}',
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            beforeSend: function(xhr) {
                console.log('� jQuery AJAX request starting...');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('Content-Type', 'application/json');
            },
            success: function(data) {
                clearTimeout(loadingTimeout); // Clear timeout on success
                console.log('🎉 Cart data received via jQuery:', data);
                console.log('🔢 Data type:', typeof data);
                console.log('✅ Data success:', data.success);
                console.log('📦 Data items:', data.items);
                console.log('🔢 Items count:', data.items ? data.items.length : 'undefined');
                
                // Hide initial loading
                if (initialLoading) {
                    console.log('🚫 Hiding initial loading');
                    initialLoading.style.display = 'none';
                }
                
                if (data && data.success) {
                    console.log('✅ Data success is true, calling renderCart');
                    renderCart(data);
                } else {
                    console.log('⚠️ Data success is false or missing, showing empty cart');
                    console.log('📊 Full data object:', JSON.stringify(data, null, 2));
                    showEmptyCart();
                }
            },
            error: function(xhr, status, error) {
                clearTimeout(loadingTimeout); // Clear timeout on error
                console.error('❌ jQuery AJAX Error:', error);
                console.error('📝 Status:', status);
                console.error('📚 XHR:', xhr);
                console.error('📊 Response Text:', xhr.responseText);
                
                // Hide initial loading
                if (initialLoading) {
                    console.log('🚫 Hiding initial loading due to error');
                    initialLoading.style.display = 'none';
                }
                
                if (typeof Shop !== 'undefined' && Shop.showNotification) {
                    if (xhr.status === 401) {
                        Shop.showNotification('warning', 'Please log in to view your cart.');
                    } else if (xhr.status === 419) {
                        Shop.showNotification('warning', 'Session expired. Please refresh the page.');
                    } else {
                        Shop.showNotification('error', 'Failed to load cart items.');
                    }
                } else {
                    console.log('🚫 Shop object not available for notification');
                }
                showEmptyCart();
            }
        });
    }
    
    function renderCart(data) {
        console.log('renderCart called with data:', data);
        console.log('Data items:', data.items);
        console.log('Items length:', data.items ? data.items.length : 'undefined');
        
        if (!data.items || data.items.length === 0) {
            console.log('No items found, calling showEmptyCart');
            showEmptyCart();
            return;
        }
        
        console.log('Rendering cart with items...');
        
        // Show cart content and hide empty cart
        const cartContainer = document.getElementById('cart-container');
        const emptyCart = document.getElementById('empty-cart');
        
        if (cartContainer) {
            cartContainer.style.display = '';
            cartContainer.classList.remove('d-none');
        }
        if (emptyCart) {
            emptyCart.style.display = 'none';
        }
        
        // Build cart items HTML
        let cartItemsHtml = '';
        data.items.forEach((item, index) => {
            cartItemsHtml += `
                <div class="cart-item mb-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="cart-item-info">
                                        <h6 class="mb-1">${item.plan.name}</h6>
                                        <p class="text-muted mb-1">${item.plan.description || ''}</p>
                                        <small class="text-primary">
                                            <strong>Billing:</strong> ${item.billing_cycle}
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="quantity-controls">
                                        <div class="input-group">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="updateQuantity(${item.plan_id}, ${item.quantity - 1})">-</button>
                                            <input type="text" class="form-control form-control-sm text-center" value="${item.quantity}" readonly>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="updateQuantity(${item.plan_id}, ${item.quantity + 1})">+</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="item-pricing">
                                        <div class="item-price">${window.currencySymbol}${parseFloat(item.plan.price).toFixed(2)}</div>
                                        ${parseFloat(item.plan.setup_fee) > 0 ? `<small class="text-muted">+${window.currencySymbol}${parseFloat(item.plan.setup_fee).toFixed(2)} setup</small>` : ''}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <strong>${window.currencySymbol}${parseFloat(item.subtotal).toFixed(2)}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12 text-end">
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.plan_id})">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        document.getElementById('cart-items').innerHTML = cartItemsHtml;
        
        // Update cart summary
        let summaryHtml = `
            <div class="summary-line">
                <span>Subtotal (${data.cart_count} items):</span>
                <span>${data.formatted_total}</span>
            </div>`;
        
        // Add coupon information if present
        if (data.coupon) {
            summaryHtml += `
            <div class="summary-line coupon-discount">
                <span>Discount (${data.coupon.code}):</span>
                <span class="text-success">-${data.coupon.formatted_discount}</span>
            </div>`;
        }
        
        summaryHtml += `
            <hr>
            <div class="summary-line total">
                <strong>Total: ${data.coupon ? data.coupon.formatted_new_total : data.formatted_total}</strong>
            </div>
            <div class="d-grid gap-2 mt-3">
                <a href="{{ route('shop.checkout.index') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-credit-card"></i>
                    Proceed to Checkout
                </a>
            </div>
        `;
        
        document.getElementById('cart-summary-content').innerHTML = summaryHtml;
        
        // Update cart count if function exists
        if (typeof updateCartCount === 'function') {
            updateCartCount(data.cart_count);
        }
    }
    
    function showEmptyCart() {
        console.log('showEmptyCart called');
        console.log('cart-container element:', document.getElementById('cart-container'));
        console.log('empty-cart element:', document.getElementById('empty-cart'));
        
        // Clear any loading content
        const cartItems = document.getElementById('cart-items');
        const cartSummary = document.getElementById('cart-summary-content');
        
        if (cartItems) {
            cartItems.innerHTML = '';
            console.log('Cleared cart-items content');
        }
        
        if (cartSummary) {
            cartSummary.innerHTML = '';
            console.log('Cleared cart-summary-content');
        }
        
        const cartContainer = document.getElementById('cart-container');
        const emptyCart = document.getElementById('empty-cart');
        
        if (cartContainer) {
            cartContainer.classList.add('d-none');
            console.log('Hidden cart-container');
        } else {
            console.error('cart-container element not found!');
        }
        
        if (emptyCart) {
            emptyCart.style.display = 'block';
            console.log('Shown empty-cart');
        } else {
            console.error('empty-cart element not found!');
        }
        
        // Update cart count to 0
        if (typeof updateCartCount === 'function') {
            updateCartCount(0);
            console.log('Updated cart count to 0');
        } else {
            console.log('updateCartCount function not available');
        }
    }
    
    function updateQuantity(planId, newQuantity) {
        if (newQuantity < 1) {
            removeFromCart(planId);
            return;
        }
        
        fetch('{{ route("shop.cart.update") }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                plan_id: planId,
                quantity: newQuantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCart(); // Reload cart
                Shop.showNotification('success', 'Cart updated successfully.');
            } else {
                Shop.showNotification('error', data.message || 'Failed to update cart.');
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            Shop.showNotification('error', 'Failed to update cart.');
        });
    }
    
    function removeFromCart(planId) {
        fetch('{{ route("shop.cart.remove") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                plan_id: planId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCart(); // Reload cart
                Shop.showNotification('success', 'Item removed from cart.');
            } else {
                Shop.showNotification('error', data.message || 'Failed to remove item.');
            }
        })
        .catch(error => {
            console.error('Error removing from cart:', error);
            Shop.showNotification('error', 'Failed to remove item.');
        });
    }
    
    // Load cart when page is ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, starting cart load...');
        console.log('Current URL:', window.location.href);
        console.log('User agent:', navigator.userAgent);
        
        // Check if required elements exist
        const cartItems = document.getElementById('cart-items');
        const cartSummary = document.getElementById('cart-summary-content');
        const cartContainer = document.getElementById('cart-container');
        const emptyCart = document.getElementById('empty-cart');
        
        console.log('Required elements check:');
        console.log('- cart-items:', cartItems ? 'found' : 'MISSING');
        console.log('- cart-summary-content:', cartSummary ? 'found' : 'MISSING');
        console.log('- cart-container:', cartContainer ? 'found' : 'MISSING');
        console.log('- empty-cart:', emptyCart ? 'found' : 'MISSING');
        
        if (!cartItems || !cartSummary) {
            console.error('Required cart elements missing! Cannot load cart.');
            return;
        }
        
        loadCart();
    });
</script>
@endsection

@section('styles')
@parent
<style>
.cart-item {
    transition: all 0.3s ease;
}

.cart-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quantity-controls .input-group {
    width: 120px;
}

.item-pricing {
    text-align: center;
}

.item-price {
    font-weight: 600;
    color: #28a745;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.summary-line.total {
    font-size: 1.1em;
    margin-top: 10px;
}

.cart-item-info h6 {
    color: #495057;
}

.cart-item-info .text-primary {
    font-weight: 500;
}

#empty-cart .card-body {
    padding: 3rem 1.5rem;
}

/* Ensure Bootstrap grid layout integrity */
#cart-container .row {
    margin: 0;
}

#cart-container .col-lg-8,
#cart-container .col-lg-4 {
    padding-left: 15px;
    padding-right: 15px;
}

/* Prevent Order Summary from floating */
.sticky-top {
    position: sticky !important;
    top: 20px !important;
}

@media (max-width: 991px) {
    .sticky-top {
        position: relative !important;
        top: 0 !important;
    }
}

@media (max-width: 768px) {
    .cart-item .row > div {
        margin-bottom: 1rem;
        text-align: center;
    }
    
    .quantity-controls .input-group {
        width: 100px;
        margin: 0 auto;
    }
}
</style>
@endsection
