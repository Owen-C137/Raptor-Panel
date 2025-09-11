@extends('shop::layout')

@section('shop-title', 'Shopping Cart')

@section('shop-content')
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

<div class="row" id="cart-container">
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

@push('scripts')
<script>
    console.log('Cart script starting...');
    
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
        console.log('Loading cart...');
        document.getElementById('cart-items').innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading cart items...</span></div></div>';
        document.getElementById('cart-summary-content').innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Calculating...</span></div></div>';

        fetch('{{ route("shop.cart.summary") }}')
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Cart data received:', data);
                if (data.success) {
                    renderCart(data);
                } else {
                    showEmptyCart();
                }
            })
            .catch(error => {
                console.error('Error loading cart:', error);
                Shop.showNotification('error', 'Failed to load cart items.');
                showEmptyCart();
            });
    }
    
    function renderCart(data) {
        console.log('Rendering cart with data:', data);
        
        if (!data.items || data.items.length === 0) {
            showEmptyCart();
            return;
        }
        
        // Show cart content and hide empty cart
        document.getElementById('cart-container').style.display = 'block';
        document.getElementById('empty-cart').style.display = 'none';
        
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
                                        <div class="item-price">$${item.plan.price}</div>
                                        ${item.plan.setup_fee > 0 ? `<small class="text-muted">+$${item.plan.setup_fee} setup</small>` : ''}
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="text-center">
                                        <strong>$${item.subtotal.toFixed(2)}</strong>
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
            </div>
            <hr>
            <div class="summary-line total">
                <strong>Total: ${data.formatted_total}</strong>
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
        console.log('Showing empty cart');
        document.getElementById('cart-container').style.display = 'none';
        document.getElementById('empty-cart').style.display = 'block';
        
        // Update cart count to 0
        if (typeof updateCartCount === 'function') {
            updateCartCount(0);
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
        loadCart();
    });
</script>
@endpush

@push('styles')
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
@endpush
