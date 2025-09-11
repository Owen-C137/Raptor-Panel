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
document.addEventListener('DOMContentLoaded', function() {
    let cart = [];
    
    // Load cart items
    function loadCart() {
        fetch('{{ route("shop.cart.summary") }}')
            .then(response => response.json())
            .then(data => {
                cart = data.items || [];
                renderCart();
                renderSummary();
            })
            .catch(error => {
                console.error('Error loading cart:', error);
                Shop.showNotification('error', 'Failed to load cart items.');
                showEmptyCart();
            });
    }
    
    // Render cart items
    function renderCart() {
        const cartContainer = document.getElementById('cart-items');
        const loadingElement = document.getElementById('cart-loading');
        
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        if (cart.length === 0) {
            showEmptyCart();
            return;
        }
        
        document.getElementById('cart-container').style.display = 'block';
        document.getElementById('empty-cart').style.display = 'none';
        
        let html = '';
        
        cart.forEach((item, index) => {
            const plan = item.plan;
            const subtotal = (plan.price + plan.setup_fee) * item.quantity;
            
            html += `
                <div class="card mb-3 cart-item" data-index="${index}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="cart-item-info">
                                    <h6 class="mb-1">${plan.product_name}</h6>
                                    <p class="mb-1 text-primary">${plan.name}</p>
                                    <small class="text-muted">${plan.billing_cycle} billing</small>
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-center">
                                <div class="quantity-controls">
                                    <div class="input-group input-group-sm">
                                        <button type="button" class="btn btn-outline-secondary quantity-btn" 
                                                data-action="decrease" data-index="${index}">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="form-control text-center quantity-input" 
                                               value="${item.quantity}" min="1" max="10" 
                                               data-index="${index}">
                                        <button type="button" class="btn btn-outline-secondary quantity-btn" 
                                                data-action="increase" data-index="${index}">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-center">
                                <div class="item-pricing">
                                    <div class="item-price">
                                        {{ config('shop.currency.symbol', '$') }}${(plan.price).toFixed(2)}
                                    </div>
                                    ${plan.setup_fee > 0 ? `
                                    <small class="text-muted">
                                        +{{ config('shop.currency.symbol', '$') }}${(plan.setup_fee).toFixed(2)} setup
                                    </small>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-end">
                                <div class="item-total">
                                    <strong>{{ config('shop.currency.symbol', '$') }}${subtotal.toFixed(2)}</strong>
                                    <div class="item-actions mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" 
                                                data-index="${index}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${plan.description ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">${plan.description}</small>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        cartContainer.innerHTML = html;
        attachCartEvents();
    }
    
    // Render order summary
    function renderSummary() {
        const summaryContainer = document.getElementById('cart-summary-content');
        const loadingElement = document.getElementById('summary-loading');
        
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
        
        if (cart.length === 0) {
            summaryContainer.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-shopping-cart"></i>
                    <p class="mb-0">No items in cart</p>
                </div>
            `;
            return;
        }
        
        let subtotal = 0;
        let setupTotal = 0;
        let itemCount = 0;
        
        cart.forEach(item => {
            const planCost = item.plan.price * item.quantity;
            const setupCost = item.plan.setup_fee * item.quantity;
            subtotal += planCost;
            setupTotal += setupCost;
            itemCount += item.quantity;
        });
        
        const total = subtotal + setupTotal;
        
        let html = `
            <div class="summary-line">
                <span>Items (${itemCount}):</span>
                <span>{{ config('shop.currency.symbol', '$') }}${subtotal.toFixed(2)}</span>
            </div>
        `;
        
        if (setupTotal > 0) {
            html += `
                <div class="summary-line">
                    <span>Setup Fees:</span>
                    <span>{{ config('shop.currency.symbol', '$') }}${setupTotal.toFixed(2)}</span>
                </div>
            `;
        }
        
        html += `
            <hr>
            <div class="summary-line total">
                <strong>
                    <span>Total:</span>
                    <span>{{ config('shop.currency.symbol', '$') }}${total.toFixed(2)}</span>
                </strong>
            </div>
            
            <div class="d-grid gap-2 mt-3">
                <a href="{{ route('shop.checkout.index') }}" class="btn btn-success btn-lg">
                    <i class="fas fa-lock"></i>
                    Proceed to Checkout
                </a>
                <button type="button" class="btn btn-outline-danger" id="clear-cart-btn">
                    <i class="fas fa-trash"></i>
                    Clear Cart
                </button>
            </div>
        `;
        
        summaryContainer.innerHTML = html;
        
        // Attach clear cart event
        document.getElementById('clear-cart-btn').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                clearCart();
            }
        });
    }
    
    // Attach cart event listeners
    function attachCartEvents() {
        // Quantity buttons
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                const action = this.dataset.action;
                const input = document.querySelector(`input[data-index="${index}"]`);
                let quantity = parseInt(input.value);
                
                if (action === 'increase' && quantity < 10) {
                    quantity++;
                } else if (action === 'decrease' && quantity > 1) {
                    quantity--;
                }
                
                input.value = quantity;
                updateCartItem(index, quantity);
            });
        });
        
        // Quantity inputs
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                const quantity = Math.max(1, Math.min(10, parseInt(this.value) || 1));
                this.value = quantity;
                updateCartItem(index, quantity);
            });
        });
        
        // Remove item buttons
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                if (confirm('Remove this item from your cart?')) {
                    removeCartItem(index);
                }
            });
        });
    }
    
    // Update cart item quantity
    function updateCartItem(index, quantity) {
        const formData = new FormData();
        formData.append('index', index);
        formData.append('quantity', quantity);
        
        fetch('{{ route("shop.cart.update") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cart = data.cart.items || [];
                renderSummary();
                Shop.updateCartCount(data.cart.count);
            } else {
                Shop.showNotification('error', data.message);
                loadCart(); // Reload cart on error
            }
        })
        .catch(error => {
            console.error('Error updating cart:', error);
            Shop.showNotification('error', 'Failed to update cart.');
            loadCart(); // Reload cart on error
        });
    }
    
    // Remove cart item
    function removeCartItem(index) {
        const formData = new FormData();
        formData.append('index', index);
        
        fetch('{{ route("shop.cart.remove") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Item removed from cart.');
                loadCart();
                Shop.updateCartCount(data.cart.count);
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error removing item:', error);
            Shop.showNotification('error', 'Failed to remove item.');
        });
    }
    
    // Clear entire cart
    function clearCart() {
        fetch('{{ route("shop.cart.clear") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Cart cleared.');
                cart = [];
                showEmptyCart();
                Shop.updateCartCount(0);
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error clearing cart:', error);
            Shop.showNotification('error', 'Failed to clear cart.');
        });
    }
    
    // Show empty cart state
    function showEmptyCart() {
        document.getElementById('cart-container').style.display = 'none';
        document.getElementById('empty-cart').style.display = 'block';
    }
    
    // Promo code handling
    document.getElementById('promo-code-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const code = document.getElementById('promo-code-input').value.trim();
        if (!code) return;
        
        const formData = new FormData();
        formData.append('code', code);
        
        const btn = document.getElementById('apply-promo-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('{{ route("shop.cart.promo.apply") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Promo code applied!');
                document.getElementById('promo-code-display').textContent = code;
                document.getElementById('applied-promo').style.display = 'block';
                document.getElementById('promo-code-input').value = '';
                renderSummary(); // Refresh summary with discount
            } else {
                Shop.showNotification('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error applying promo code:', error);
            Shop.showNotification('error', 'Failed to apply promo code.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Apply';
        });
    });
    
    // Remove promo code
    document.getElementById('remove-promo-btn').addEventListener('click', function() {
        fetch('{{ route("shop.cart.promo.remove") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Shop.showNotification('success', 'Promo code removed.');
                document.getElementById('applied-promo').style.display = 'none';
                renderSummary(); // Refresh summary without discount
            } else {
                Shop.showNotification('error', data.message);
            }
        });
    });
    
    // Initialize
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
