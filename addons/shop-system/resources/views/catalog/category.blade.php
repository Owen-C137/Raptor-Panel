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
                            <div class="d-flex align-items-start justify-content-between mb-4">
                                <div class="flex-grow-1">
                                    <h1 class="h2 fw-bold text-primary mb-2">{{ $category->name }}</h1>
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
                                <div class="flex-shrink-0 ms-4">
                                    <div class="text-end">
                                        @php
                                            $allPrices = $plans->flatMap(function ($plan) {
                                                return collect($plan->billing_cycles ?? [])->pluck('price');
                                            })->filter();
                                            
                                            $minPrice = $allPrices->min();
                                            $maxPrice = $allPrices->max();
                                        @endphp
                                        
                                        @if($minPrice && $maxPrice && $minPrice == $maxPrice)
                                            <div class="bg-success-light rounded p-3">
                                                <div class="text-muted small mb-1">Starting from</div>
                                                <div class="h4 fw-bold text-success mb-0">
                                                    <span class="price-amount">{{ $currencySymbol }}{{ number_format($minPrice, 2) }}</span>
                                                    <span class="text-muted fs-6">/ month</span>
                                                </div>
                                            </div>
                                        @elseif($minPrice)
                                            <div class="bg-success-light rounded p-3">
                                                <div class="text-muted small mb-1">Starting from</div>
                                                <div class="h4 fw-bold text-success mb-0">
                                                    <span class="price-amount">{{ $currencySymbol }}{{ number_format($minPrice, 2) }}</span>
                                                    <span class="text-muted fs-6">/ month</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <div class="product-description mb-4">
                                <div class="bg-body-extra-light rounded p-3">
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        {!! nl2br(e($category->description)) !!}
                                    </p>
                                </div>
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
                            <div class="block block-rounded h-100 {{ !$plan->isAvailable() ? 'opacity-75' : '' }}">
                                
                                {{-- Status Badge --}}
                                @if(!$plan->isAvailable())
                                    <div class="position-absolute top-0 end-0 p-3">
                                        <span class="badge bg-danger">Unavailable</span>
                                    </div>
                                @elseif($plan->stock && $plan->stock <= 5)
                                    <div class="position-absolute top-0 end-0 p-3">
                                        <span class="badge bg-warning text-dark">{{ $plan->stock }} left</span>
                                    </div>
                                @endif
                                
                                <div class="block-header block-header-default text-center">
                                    <h3 class="block-title">
                                        {{ $plan->name }} <small class="text-success">{{ $currencySymbol }}{{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}</small>
                                    </h3>
                                </div>
                                
                                <div class="block-content bg-body-light d-flex flex-column text-center p-4">
                                    
                                    {{-- Pricing Display --}}
                                    <div class="mb-4">
                                        <div class="h2 fw-bold text-success mb-1">
                                            {{ $currencySymbol }}{{ number_format($plan->price, 2) }}
                                        </div>
                                        <small class="text-muted">per {{ $plan->billing_cycle }}</small>
                                        @if($plan->setup_fee > 0)
                                            <div class="mt-1">
                                                <small class="text-warning">
                                                    + {{ $currencySymbol }}{{ number_format($plan->setup_fee, 2) }} setup
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Resource Specifications --}}
                                    <div class="mb-4">
                                        <div class="row text-center g-2">
                                            <div class="col-4">
                                                <div class="p-2 bg-body-light rounded">
                                                    <div class="text-muted small mb-1">
                                                        <i class="fas fa-memory text-info"></i>
                                                        RAM
                                                    </div>
                                                    <div class="fw-semibold">{{ number_format($plan->memory/1024, 1) }}GB</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-body-light rounded">
                                                    <div class="text-muted small mb-1">
                                                        <i class="fas fa-hdd text-success"></i>
                                                        Storage
                                                    </div>
                                                    <div class="fw-semibold">{{ number_format($plan->storage/1024, 1) }}GB</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 bg-body-light rounded">
                                                    <div class="text-muted small mb-1">
                                                        <i class="fas fa-microchip text-primary"></i>
                                                        CPU
                                                    </div>
                                                    <div class="fw-semibold">{{ $plan->cpu }}%</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Description --}}
                                    @if($plan->description && strlen($plan->description) < 100)
                                        <p class="text-muted small mb-4">{{ $plan->description }}</p>
                                    @endif
                                    
                                    {{-- Action Buttons --}}
                                    <div class="mt-auto">
                                        @if($plan->isAvailable())
                                            @auth
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-success btn-lg add-to-cart-btn" 
                                                        data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                                    <i class="fas fa-cart-plus me-2"></i>
                                                    Add to Cart
                                                </button>
                                                
                                                <a href="{{ route('shop.plan', $plan) }}" class="btn btn-outline-primary">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    View Details
                                                </a>
                                            </div>
                                            @else
                                            <div class="d-grid">
                                                <a href="{{ route('auth.login') }}" class="btn btn-primary btn-lg">
                                                    <i class="fas fa-sign-in-alt me-2"></i>
                                                    Login to Order
                                                </a>
                                            </div>
                                            @endauth
                                        @else
                                            <div class="d-grid">
                                                <button type="button" class="btn btn-secondary btn-lg" disabled>
                                                    <i class="fas fa-times me-2"></i>
                                                    Not Available
                                                </button>
                                            </div>
                                        @endif
                                    </div>
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
                                    {{ $plan->name }} - {{ $currencySymbol }}{{ number_format($plan->price, 2) }}
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
                            <span id="summary-plan-cost">{{ $currencySymbol }}0.00</span>
                        </div>
                        <div class="summary-row" id="summary-setup-row" style="display: none;">
                            <span>Setup Fee:</span>
                            <span id="summary-setup-cost">{{ $currencySymbol }}0.00</span>
                        </div>
                        <div class="summary-row total">
                            <strong>
                                <span>Total:</span>
                                <span id="summary-total">{{ $currencySymbol }}0.00</span>
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
            <div class="block-content">
                <div class="d-flex flex-column gap-3">
                    @foreach($relatedCategories as $related)
                    <div class="related-product">
                        <div class="d-flex align-items-center justify-content-between p-3 bg-body-light rounded">
                            <div class="flex-grow-1">
                                <h5 class="fw-bold text-primary mb-1">{{ $related->name }}</h5>
                                <p class="text-muted small mb-0">{{ Str::limit($related->description, 80) }}</p>
                            </div>
                            <div class="flex-shrink-0 ms-3">
                                <a href="{{ route('shop.category', $related) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-right me-1"></i>
                                    View Category
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
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
            '{{ $currencySymbol }}' + planCost.toFixed(2);
        
        if (setupFee > 0) {
            document.getElementById('summary-setup-cost').textContent = 
                '{{ $currencySymbol }}' + setupCost.toFixed(2);
            document.getElementById('summary-setup-row').style.display = 'flex';
        } else {
            document.getElementById('summary-setup-row').style.display = 'none';
        }
        
        document.getElementById('summary-total').textContent = 
            '{{ $currencySymbol }}' + total.toFixed(2);
        
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
    
    // Load locations for node filter - with proper error checking
    function loadLocationOptions() {
        if (typeof Shop === 'undefined' || typeof Shop.loadLocations !== 'function') {
            console.warn('⚠️ Shop.loadLocations not available, retrying in 100ms...');
            setTimeout(loadLocationOptions, 100);
            return;
        }
        
        Shop.loadLocations(function(locations) {
            if (locations && locations.length > 0) {
                locations.forEach(function(location) {
                    const option = document.createElement('option');
                    option.value = location.id;
                    option.textContent = location.name;
                    nodeFilter.appendChild(option);
                });
                console.log('📍 Loaded', locations.length, 'locations for node filter');
            } else {
                console.log('📍 No locations available for filtering');
            }
        });
    }
    
    // Start loading locations
    loadLocationOptions();
});
</script>
@endpush
