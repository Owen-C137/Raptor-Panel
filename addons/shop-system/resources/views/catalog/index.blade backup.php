@extends('shop::layout')

@section('shop-title', 'Game Hosting Categories')

@section('shop-content')
<div class="row">
    {{-- Sidebar Filters --}}
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter"></i>
                    Filters
                </h5>
            </div>
            <div class="card-body">
                <form id="shop-filters" method="GET">
                    {{-- Search --}}
                    <div class="mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Search hosting plans...">
                    </div>

                    {{-- Categories --}}
                    @if($categories->count() > 0)
                    <div class="mb-3">
                        <label class="form-label">Game Type</label>
                        <div class="category-filters">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" id="category-all" 
                                       value="" {{ !request('category') ? 'checked' : '' }}>
                                <label class="form-check-label" for="category-all">
                                    All Game Types
                                </label>
                            </div>
                            @foreach($categories as $category)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" 
                                       id="category-{{ Str::slug($category) }}" value="{{ $category }}"
                                       {{ request('category') == $category ? 'checked' : '' }}>
                                <label class="form-check-label" for="category-{{ Str::slug($category) }}">
                                    {{ $category }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Service Type --}}
                    <div class="mb-3">
                        <label class="form-label">Service Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="server" {{ request('type') == 'server' ? 'selected' : '' }}>Game Servers</option>
                            <option value="vps" {{ request('type') == 'vps' ? 'selected' : '' }}>VPS</option>
                            <option value="dedicated" {{ request('type') == 'dedicated' ? 'selected' : '' }}>Dedicated</option>
                            <option value="addon" {{ request('type') == 'addon' ? 'selected' : '' }}>Add-ons</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary btn-block mt-2">Clear Filters</a>
                </form>
            </div>
        </div>

        {{-- Featured Products Widget --}}
        @if($featured->count() > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star text-warning"></i>
                    Featured Games
                </h5>
            </div>
            <div class="card-body">
                @foreach($featured as $product)
                <div class="featured-product mb-3">
                    <div class="row">
                        @if($product->image_url)
                        <div class="col-4">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" 
                                 class="img-fluid rounded">
                        </div>
                        <div class="col-8">
                        @else
                        <div class="col-12">
                        @endif
                            <h6 class="mb-1">{{ $product->name }}</h6>
                            <p class="text-muted small mb-2">{{ Str::limit($product->description, 60) }}</p>
                            <a href="{{ route('shop.category', $product) }}" class="btn btn-sm btn-outline-primary">
                                View Plans
                            </a>
                        </div>
                    </div>
                </div>
                @if(!$loop->last)
                    <hr>
                @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Main Product Grid --}}
    <div class="col-lg-9">
        {{-- Results Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">
                    @if(request('search'))
                        Search Results for "{{ request('search') }}"
                    @elseif(request('category'))
                        {{ request('category') }} Hosting Plans
                    @else
                        All Hosting Plans
                    @endif
                </h4>
                <small class="text-muted">{{ $products->total() }} hosting plan(s) found</small>
            </div>
            
            <div class="view-options">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="grid-view">
                        <i class="fas fa-th"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="list-view">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Product Grid --}}
        @if($products->count() > 0)
        <div id="products-container" class="products-grid">
            <div class="row" id="products-grid">
                @foreach($products as $product)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        @if($product->image_url)
                        <img src="{{ $product->image_url }}" class="card-img-top product-image" alt="{{ $product->name }}">
                        @endif
                        
                        <div class="card-body d-flex flex-column">
                            <div class="product-header">
                                <h5 class="card-title">{{ $product->name }}</h5>
                                @if($product->category)
                                    <span class="badge bg-secondary mb-2">{{ $product->category }}</span>
                                @endif
                                @if($product->featured)
                                    <span class="badge bg-warning text-dark mb-2">
                                        <i class="fas fa-star"></i>
                                        Featured
                                    </span>
                                @endif
                            </div>
                            
                            <p class="card-text flex-grow-1">{{ Str::limit($product->description, 120) }}</p>
                            
                            {{-- Price Range --}}
                            @if($product->plans->count() > 0)
                            <div class="price-range mb-3">
                                @php
                                    $minPrice = $product->plans->min('price');
                                    $maxPrice = $product->plans->max('price');
                                @endphp
                                
                                @if($minPrice == $maxPrice)
                                    <span class="price">{{ config('shop.currency.symbol', '$') }}{{ number_format($minPrice, 2) }}</span>
                                @else
                                    <span class="price">{{ config('shop.currency.symbol', '$') }}{{ number_format($minPrice, 2) }} - {{ config('shop.currency.symbol', '$') }}{{ number_format($maxPrice, 2) }}</span>
                                @endif
                                <small class="text-muted">/ {{ $product->plans->first()->billing_cycle }}</small>
                            </div>
                            @endif
                            
                            <div class="card-actions mt-auto">
                                <a href="{{ route('shop.category', $product->id) }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-eye"></i>
                                    View Plans
                                </a>
                                
                                {{-- Quick Add Button (if only one plan) --}}
                                @if($product->plans->count() == 1)
                                    @php $plan = $product->plans->first(); @endphp
                                    @if($plan->isAvailable())
                                        <button type="button" class="btn btn-success btn-sm mt-2 btn-block quick-add-btn" 
                                                data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                            <i class="fas fa-plus"></i>
                                            Quick Add - {{ config('shop.currency.symbol', '$') }}{{ number_format($plan->price, 2) }}
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-secondary btn-sm mt-2 btn-block" disabled>
                                            <i class="fas fa-times"></i>
                                            Unavailable
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        {{-- Product Footer --}}
                        <div class="card-footer text-muted">
                            <small>
                                <i class="fas fa-server"></i>
                                {{ $product->plans->count() }} plan{{ $product->plans->count() !== 1 ? 's' : '' }} available
                            </small>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center">
            {{ $products->appends(request()->query())->links() }}
        </div>
        
        @else
        {{-- No Products Found --}}
        <div class="text-center py-5">
            <div class="no-products">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4>No hosting plans found</h4>
                <p class="text-muted">
                    @if(request('search') || request('category') || request('type'))
                        No hosting plans match your current filters. Try adjusting your search criteria.
                    @else
                        There are no hosting plans available in the shop at this time.
                    @endif
                </p>
                @if(request('search') || request('category') || request('type'))
                    <a href="{{ route('shop.index') }}" class="btn btn-primary">View All Plans</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Quick Add Modal --}}
<div class="modal fade" id="quick-add-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add to Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quick-add-form">
                    <input type="hidden" id="quick-plan-id" name="plan_id">
                    <div class="mb-3">
                        <label for="quick-quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quick-quantity" name="quantity" 
                               value="1" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Plan Details</label>
                        <div id="quick-plan-details" class="p-3 bg-light rounded">
                            <!-- Plan details will be loaded here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirm-quick-add">
                    <i class="fas fa-plus"></i>
                    Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit filters on change
    document.querySelectorAll('#shop-filters input, #shop-filters select').forEach(function(element) {
        element.addEventListener('change', function() {
            document.getElementById('shop-filters').submit();
        });
    });

    // View toggle functionality
    const gridView = document.getElementById('grid-view');
    const listView = document.getElementById('list-view');
    const productsGrid = document.getElementById('products-grid');

    if (gridView && listView) {
        gridView.addEventListener('click', function() {
            gridView.classList.add('active');
            listView.classList.remove('active');
            productsGrid.className = 'row';
        });

        listView.addEventListener('click', function() {
            listView.classList.add('active');
            gridView.classList.remove('active');
            productsGrid.className = 'row products-list';
        });
    }

    // Quick add functionality
    document.querySelectorAll('.quick-add-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const planId = this.dataset.planId;
            const planName = this.dataset.planName;
            
            document.getElementById('quick-plan-id').value = planId;
            document.getElementById('quick-plan-details').innerHTML = `
                <strong>${planName}</strong><br>
                <span class="text-muted">Loading plan details...</span>
            `;
            
            // Load plan details via AJAX
            Shop.loadPlanDetails(planId, function(plan) {
                document.getElementById('quick-plan-details').innerHTML = `
                    <strong>${plan.name}</strong><br>
                    <span class="text-muted">${plan.description}</span><br>
                    <div class="mt-2">
                        <span class="badge bg-primary">CPU: ${plan.cpu}%</span>
                        <span class="badge bg-info">RAM: ${plan.memory}MB</span>
                        <span class="badge bg-success">Storage: ${plan.storage}MB</span>
                    </div>
                `;
            });
            
            const modal = new bootstrap.Modal(document.getElementById('quick-add-modal'));
            modal.show();
        });
    });

    // Confirm quick add
    document.getElementById('confirm-quick-add').addEventListener('click', function() {
        const form = document.getElementById('quick-add-form');
        const formData = new FormData(form);
        
        Shop.addToCart(formData, function(response) {
            if (response.success) {
                bootstrap.Modal.getInstance(document.getElementById('quick-add-modal')).hide();
                Shop.showNotification('success', response.message);
                Shop.updateCartCount(response.cart_count);
            } else {
                Shop.showNotification('error', response.message);
            }
        });
    });
});
</script>
@endpush
