@extends('shop::layout')

@section('shop-title', $category->name . ' Hosting Plans')

@section('shop-content')
<div class="row">
    <div class="col-12">
        {{-- Breadcrumb --}}
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('shop.index') }}">
                        <i class="fas fa-home"></i>
                        Shop
                    </a>
                </li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    {{-- Product Information --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    {{-- Category Image --}}
                    @if($category->image_url)
                    <div class="col-md-5">
                        <div class="category-image-container">
                            <img src="{{ $category->image_url }}" alt="{{ $category->name }}" 
                                 class="img-fluid rounded category-main-image">
                        </div>
                    </div>
                    <div class="col-md-7">
                    @else
                    <div class="col-12">
                    @endif
                        {{-- Category Details --}}
                        <div class="category-info">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div>
                                    <h2 class="product-title">{{ $category->name }}</h2>
                                    <div class="product-badges">
                                        @if($category->parent)
                                            <span class="badge bg-secondary me-2">{{ $category->parent->name }}</span>
                                        @endif
                                        @if(isset($category->metadata['featured']) && $category->metadata['featured'])
                                            <span class="badge bg-warning text-dark me-2">
                                                <i class="fas fa-star"></i>
                                                Featured
                                            </span>
                                        @endif
                                        @if(isset($category->metadata['type']))
                                            <span class="badge bg-info">{{ ucfirst($category->metadata['type']) }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Price Range --}}
                                @if($plans->count() > 0)
                                <div class="price-display text-end">
                                    @php
                                        $allPrices = $plans->flatMap(function ($plan) {
                                            return collect($plan->billing_cycles ?? [])->pluck('price');
                                        })->filter();
                                        
                                        $minPrice = $allPrices->min();
                                        $maxPrice = $allPrices->max();
                                    @endphp
                                    
                                    @if($minPrice && $maxPrice && $minPrice == $maxPrice)
                                        <div class="price-single">
                                            <span class="price-amount">{{ config('shop.currency.symbol', '$') }}{{ number_format($minPrice, 2) }}</span>
                                            <span class="price-period">/ month</span>
                                        </div>
                                    @elseif($minPrice)
                                        <div class="price-range">
                                            <span class="price-from">from</span>
                                            <span class="price-amount">{{ config('shop.currency.symbol', '$') }}{{ number_format($minPrice, 2) }}</span>
                                            <span class="price-period">/ month</span>
                                        </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                            
                            <div class="product-description mb-4">
                                {!! nl2br(e($category->description)) !!}
                            </div>
                            
                            {{-- Product Features --}}
                            @if($category->metadata && isset($category->metadata['features']))
                            <div class="product-features mb-4">
                                <h6>Features:</h6>
                                <ul class="list-unstyled">
                                    @foreach($category->metadata['features'] as $feature)
                                    <li class="mb-1">
                                        <i class="fas fa-check text-success me-2"></i>
                                        {{ $feature }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Plans Section --}}
        @if($plans->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-server"></i>
                    Available Plans
                </h5>
            </div>
            <div class="card-body">
                {{-- Plan Selection Filters --}}
                <div class="plan-filters mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="node-filter" class="form-label">Preferred Location</label>
                            <select id="node-filter" class="form-select">
                                <option value="">Any Location</option>
                                {{-- Locations will be populated via JavaScript --}}
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="billing-filter" class="form-label">Billing Cycle</label>
                            <select id="billing-filter" class="form-select">
                                <option value="">All Cycles</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semi-annually">Semi-Annually</option>
                                <option value="annually">Annually</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                {{-- Plans Grid --}}
                <div id="plans-container" class="plans-grid">
                    <div class="row" id="plans-list">
                        @foreach($plans as $plan)
                        <div class="col-lg-6 col-xl-4 mb-4 plan-item" 
                             data-billing-cycle="{{ $plan->billing_cycle }}"
                             data-location-ids="{{ json_encode($plan->location_ids ?: []) }}"
                             data-node-ids="{{ json_encode($plan->node_ids ?: []) }}">
                            <div class="card plan-card h-100 {{ !$plan->isAvailable() ? 'unavailable' : '' }}">
                                <div class="card-header plan-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="plan-name mb-0">{{ $plan->name }}</h6>
                                        @if(!$plan->isAvailable())
                                            <span class="badge bg-danger">Unavailable</span>
                                        @elseif($plan->stock && $plan->stock <= 5)
                                            <span class="badge bg-warning text-dark">{{ $plan->stock }} left</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="card-body d-flex flex-column">
                                    {{-- Plan Description --}}
                                    @if($plan->description)
                                    <p class="plan-description text-muted">{{ $plan->description }}</p>
                                    @endif
                                    
                                    {{-- Pricing --}}
                                    <div class="plan-pricing mb-3">
                                        <div class="price-main">
                                            <span class="price-currency">{{ config('shop.currency.symbol', '$') }}</span>
                                            <span class="price-amount">{{ number_format($plan->price, 2) }}</span>
                                            <span class="price-period">/ {{ $plan->billing_cycle }}</span>
                                        </div>
                                        
                                        @if($plan->setup_fee > 0)
                                        <div class="setup-fee">
                                            <small class="text-muted">
                                                + {{ config('shop.currency.symbol', '$') }}{{ number_format($plan->setup_fee, 2) }} setup fee
                                            </small>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Resources --}}
                                    <div class="plan-resources flex-grow-1">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-microchip text-primary"></i>
                                                    <span class="resource-label">CPU</span>
                                                    <span class="resource-value">{{ $plan->cpu }}%</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-memory text-info"></i>
                                                    <span class="resource-label">RAM</span>
                                                    <span class="resource-value">{{ number_format($plan->memory) }}MB</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-hdd text-success"></i>
                                                    <span class="resource-label">Storage</span>
                                                    <span class="resource-value">{{ number_format($plan->storage) }}MB</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-database text-warning"></i>
                                                    <span class="resource-label">Databases</span>
                                                    <span class="resource-value">{{ $plan->databases }}</span>
                                                </div>
                                            </div>
                                            @if($plan->backups > 0)
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-save text-secondary"></i>
                                                    <span class="resource-label">Backups</span>
                                                    <span class="resource-value">{{ $plan->backups }}</span>
                                                </div>
                                            </div>
                                            @endif
                                            @if($plan->allocations > 1)
                                            <div class="col-6">
                                                <div class="resource-item">
                                                    <i class="fas fa-network-wired text-info"></i>
                                                    <span class="resource-label">Ports</span>
                                                    <span class="resource-value">{{ $plan->allocations }}</span>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    @if($plan->isAvailable())
                                        @auth
                                        <div class="d-flex align-items-center mb-2">
                                            <label for="quantity-{{ $plan->id }}" class="form-label me-2 mb-0">Qty:</label>
                                            <input type="number" class="form-control form-control-sm" 
                                                   id="quantity-{{ $plan->id }}" value="1" min="1" max="10" style="width: 70px;">
                                        </div>
                                        <button type="button" class="btn btn-success btn-block add-to-cart-btn" 
                                                data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                            <i class="fas fa-plus"></i>
                                            Add to Cart
                                        </button>
                                        @else
                                        <a href="{{ route('auth.login') }}" class="btn btn-primary btn-block">
                                            <i class="fas fa-sign-in-alt"></i>
                                            Login to Order
                                        </a>
                                        @endauth
                                    @else
                                        <button type="button" class="btn btn-secondary btn-block" disabled>
                                            <i class="fas fa-times"></i>
                                            Not Available
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Quick Order Summary --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shopping-cart"></i>
                    Quick Order
                </h5>
            </div>
            <div class="card-body">
                @if($plans->count() > 0)
                <form id="quick-order-form">
                    <div class="mb-3">
                        <label for="quick-plan-select" class="form-label">Select Plan</label>
                        <select id="quick-plan-select" class="form-select">
                            <option value="">Choose a plan...</option>
                            @foreach($plans as $plan)
                                @if($plan->isAvailable())
                                <option value="{{ $plan->id }}" 
                                        data-price="{{ $plan->price }}" 
                                        data-setup="{{ $plan->setup_fee }}"
                                        data-billing="{{ $plan->billing_cycle }}">
                                    {{ $plan->name }} - {{ config('shop.currency.symbol', '$') }}{{ number_format($plan->price, 2) }}
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quick-quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quick-quantity" 
                               value="1" min="1" max="10">
                    </div>
                    
                    <div class="order-summary" id="quick-order-summary" style="display: none;">
                        <hr>
                        <div class="summary-row">
                            <span>Plan Cost:</span>
                            <span id="summary-plan-cost">{{ config('shop.currency.symbol', '$') }}0.00</span>
                        </div>
                        <div class="summary-row" id="summary-setup-row" style="display: none;">
                            <span>Setup Fee:</span>
                            <span id="summary-setup-cost">{{ config('shop.currency.symbol', '$') }}0.00</span>
                        </div>
                        <div class="summary-row total">
                            <strong>
                                <span>Total:</span>
                                <span id="summary-total">{{ config('shop.currency.symbol', '$') }}0.00</span>
                            </strong>
                        </div>
                        <hr>
                    </div>
                    
                    @auth
                    <button type="button" class="btn btn-success btn-block" id="quick-add-to-cart">
                        <i class="fas fa-plus"></i>
                        Add to Cart
                    </button>
                    @else
                    <a href="{{ route('auth.login') }}" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i>
                        Login to Order
                    </a>
                    @endauth
                </form>
                @else
                <div class="text-center text-muted">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <p>No plans are currently available for this product.</p>
                </div>
                @endif
            </div>
        </div>
        
        {{-- Support Information --}}
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-question-circle"></i>
                    Need Help?
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Have questions about this product? Our support team is here to help!</p>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-comments"></i>
                        Live Chat
                    </a>
                    <a href="#" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-ticket-alt"></i>
                        Support Ticket
                    </a>
                    <a href="#" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-book"></i>
                        Documentation
                    </a>
                </div>
            </div>
        </div>
        
        {{-- Related Products --}}
        @if(isset($relatedCategories) && $relatedCategories->count() > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cube"></i>
                    Related Categories
                </h5>
            </div>
            <div class="card-body">
                @foreach($relatedCategories as $related)
                <div class="related-product mb-3">
                    <h6>{{ $related->name }}</h6>
                    <p class="text-muted small">{{ Str::limit($related->description, 80) }}</p>
                    <a href="{{ route('shop.category', $related) }}" class="btn btn-sm btn-outline-primary">
                        View Category
                    </a>
                    @if(!$loop->last)<hr>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quick order form functionality
    const planSelect = document.getElementById('quick-plan-select');
    const quantityInput = document.getElementById('quick-quantity');
    const orderSummary = document.getElementById('quick-order-summary');
    
    function updateQuickOrderSummary() {
        const selectedOption = planSelect.selectedOptions[0];
        if (!selectedOption || !selectedOption.value) {
            orderSummary.style.display = 'none';
            return;
        }
        
        const price = parseFloat(selectedOption.dataset.price);
        const setupFee = parseFloat(selectedOption.dataset.setup);
        const quantity = parseInt(quantityInput.value);
        
        const planCost = price * quantity;
        const setupCost = setupFee * quantity;
        const total = planCost + setupCost;
        
        document.getElementById('summary-plan-cost').textContent = 
            '{{ config("shop.currency.symbol", "$") }}' + planCost.toFixed(2);
        
        if (setupFee > 0) {
            document.getElementById('summary-setup-cost').textContent = 
                '{{ config("shop.currency.symbol", "$") }}' + setupCost.toFixed(2);
            document.getElementById('summary-setup-row').style.display = 'flex';
        } else {
            document.getElementById('summary-setup-row').style.display = 'none';
        }
        
        document.getElementById('summary-total').textContent = 
            '{{ config("shop.currency.symbol", "$") }}' + total.toFixed(2);
        
        orderSummary.style.display = 'block';
    }
    
    planSelect.addEventListener('change', updateQuickOrderSummary);
    quantityInput.addEventListener('input', updateQuickOrderSummary);
    
    // Quick add to cart
    document.getElementById('quick-add-to-cart').addEventListener('click', function() {
        const planId = planSelect.value;
        const quantity = quantityInput.value;
        
        if (!planId) {
            Shop.showNotification('error', 'Please select a plan first.');
            return;
        }
        
        const formData = new FormData();
        formData.append('plan_id', planId);
        formData.append('quantity', quantity);
        
        Shop.addToCart(formData, function(response) {
            if (response.success) {
                Shop.showNotification('success', response.message);
                Shop.updateCartCount(response.cart_count);
                planSelect.value = '';
                quantityInput.value = '1';
                updateQuickOrderSummary();
            } else {
                Shop.showNotification('error', response.message);
            }
        });
    });
    
    // Plan card add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const quantityInput = document.getElementById('quantity-' + planId);
            const quantity = quantityInput ? quantityInput.value : 1;
            
            const formData = new FormData();
            formData.append('plan_id', planId);
            formData.append('quantity', quantity);
            
            Shop.addToCart(formData, function(response) {
                if (response.success) {
                    Shop.showNotification('success', response.message);
                    Shop.updateCartCount(response.cart_count);
                } else {
                    Shop.showNotification('error', response.message);
                }
            });
        });
    });
    
    // Plan filtering
    const nodeFilter = document.getElementById('node-filter');
    const billingFilter = document.getElementById('billing-filter');
    
    function filterPlans() {
        const selectedBilling = billingFilter.value;
        const planItems = document.querySelectorAll('.plan-item');
        
        planItems.forEach(function(item) {
            let show = true;
            
            if (selectedBilling && item.dataset.billingCycle !== selectedBilling) {
                show = false;
            }
            
            item.style.display = show ? 'block' : 'none';
        });
    }
    
    billingFilter.addEventListener('change', filterPlans);
    
    // Load locations for node filter
    Shop.loadLocations(function(locations) {
        locations.forEach(function(location) {
            const option = document.createElement('option');
            option.value = location.id;
            option.textContent = location.name;
            nodeFilter.appendChild(option);
        });
    });
});
</script>
@endpush
