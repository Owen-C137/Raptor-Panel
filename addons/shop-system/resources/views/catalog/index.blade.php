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
                <form id="shop-filters" method="GET" class="compact-filters">
                    {{-- Compact Search --}}
                    <div class="mb-3">
                        <div class="search-wrapper position-relative">
                            <input type="text" class="form-control search-input" 
                                   id="search" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Search plans...">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>

                    {{-- Compact Game Categories --}}
                    @if($categories->count() > 0)
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-2">
                            <i class="fas fa-gamepad me-1"></i>Games
                        </label>
                        <div class="category-grid">
                            {{-- All Button --}}
                            <input class="btn-check" type="radio" name="category" id="category-all" 
                                   value="" {{ !request('category') ? 'checked' : '' }} autocomplete="off">
                            <label class="category-btn" for="category-all">
                                <i class="fas fa-th-large"></i>
                                <span>All</span>
                            </label>
                            
                            {{-- Game Categories --}}
                            @foreach($categories as $category)
                            @php
                                $categoryIcon = match(strtolower($category)) {
                                    'minecraft' => 'fas fa-cube',
                                    'ark' => 'fas fa-dragon',
                                    'rust' => 'fas fa-tools',
                                    'cs2', 'csgo', 'counter-strike' => 'fas fa-crosshairs',
                                    'gmod', 'garry\'s mod' => 'fas fa-wrench',
                                    'terraria' => 'fas fa-mountain',
                                    'valheim' => 'fas fa-hammer',
                                    'satisfactory' => 'fas fa-industry',
                                    'palworld' => 'fas fa-paw',
                                    'fivem' => 'fas fa-car',
                                    default => 'fas fa-gamepad'
                                };
                            @endphp
                            <input class="btn-check" type="radio" name="category" 
                                   id="category-{{ Str::slug($category) }}" value="{{ $category }}"
                                   {{ request('category') == $category ? 'checked' : '' }} autocomplete="off">
                            <label class="category-btn" for="category-{{ Str::slug($category) }}">
                                <i class="{{ $categoryIcon }}"></i>
                                <span>{{ $category }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Compact Service Type --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-2">
                            <i class="fas fa-server me-1"></i>Type
                        </label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="server" {{ request('type') == 'server' ? 'selected' : '' }}>Game Servers</option>
                            <option value="vps" {{ request('type') == 'vps' ? 'selected' : '' }}>VPS</option>
                            <option value="dedicated" {{ request('type') == 'dedicated' ? 'selected' : '' }}>Dedicated</option>
                            <option value="addon" {{ request('type') == 'addon' ? 'selected' : '' }}>Add-ons</option>
                        </select>
                    </div>

                    {{-- Compact Action Buttons --}}
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i>Apply
                        </button>
                        @if(request()->hasAny(['search', 'category', 'type']))
                            <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Best Value Plans Widget --}}
        @php
            // Get the best value plans across all categories
            $bestPlans = collect();
            foreach($products as $category) {
                if($category->plans && $category->plans->count() > 0) {
                    foreach($category->plans as $plan) {
                        if($plan->status === 'active') {
                            // Calculate value score (higher memory + disk, lower price = better value)
                            $memoryGB = (float) str_replace(['MB', 'GB'], ['', ''], $plan->memory ?? '0') / (str_contains($plan->memory ?? '', 'MB') ? 1024 : 1);
                            $diskGB = (float) str_replace(['MB', 'GB'], ['', ''], $plan->disk ?? '0') / (str_contains($plan->disk ?? '', 'MB') ? 1024 : 1);
                            $price = (float) $plan->price;
                            
                            $valueScore = ($memoryGB + ($diskGB / 10)) / max($price, 1); // Prevent division by zero
                            
                            $bestPlans->push([
                                'plan' => $plan,
                                'category' => $category,
                                'value_score' => $valueScore,
                                'memory_gb' => $memoryGB,
                                'disk_gb' => $diskGB
                            ]);
                        }
                    }
                }
            }
            $topPlans = $bestPlans->sortByDesc('value_score')->take(4);
        @endphp
        
        @if($topPlans->count() > 0)
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-crown text-warning"></i>
                    Best Value Plans
                </h5>
            </div>
            <div class="card-body p-3">
                @foreach($topPlans as $item)
                @php 
                    $plan = $item['plan'];
                    $category = $item['category'];
                @endphp
                <div class="featured-plan mb-3 p-3 rounded-3 bg-gradient-light border position-relative overflow-hidden">
                    {{-- Value Badge --}}
                    <div class="position-absolute top-0 end-0 m-2">
                        <span class="badge bg-success">
                            <i class="fas fa-gem me-1"></i>Best Value
                        </span>
                    </div>
                    
                    {{-- Game-specific background gradient --}}
                    @php
                        $gameClass = match(strtolower($category->name)) {
                            'minecraft' => 'minecraft-featured',
                            'ark' => 'ark-featured', 
                            'rust' => 'rust-featured',
                            default => 'default-featured'
                        };
                    @endphp
                    <div class="featured-bg {{ $gameClass }} position-absolute top-0 start-0 w-100 h-100 opacity-5"></div>
                    
                    <div class="row align-items-center position-relative">
                        {{-- Game Icon --}}
                        <div class="col-2">
                            @php
                                $gameIcon = match(strtolower($category->name)) {
                                    'minecraft' => 'fas fa-cube',
                                    'ark' => 'fas fa-dragon',
                                    'rust' => 'fas fa-tools',
                                    'cs2', 'csgo', 'counter-strike' => 'fas fa-crosshairs',
                                    'gmod', 'garry\'s mod' => 'fas fa-wrench',
                                    'terraria' => 'fas fa-mountain',
                                    'valheim' => 'fas fa-hammer',
                                    'satisfactory' => 'fas fa-industry',
                                    'palworld' => 'fas fa-paw',
                                    'fivem' => 'fas fa-car',
                                    default => 'fas fa-gamepad'
                                };
                            @endphp
                            <div class="featured-icon d-flex align-items-center justify-content-center bg-primary bg-opacity-15 rounded-2 text-primary" style="height: 40px; width: 40px;">
                                <i class="{{ $gameIcon }} fa-lg"></i>
                            </div>
                        </div>
                        
                        {{-- Plan Info --}}
                        <div class="col-10">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">{{ $plan->name }}</h6>
                                    <small class="text-muted">{{ $category->name }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="h6 text-primary fw-bold mb-0">{{ $currencySymbol }}{{ number_format($plan->price, 2) }}</div>
                                    <small class="text-muted">{{ $plan->billing_cycle ?? 'monthly' }}</small>
                                </div>
                            </div>
                            
                            {{-- Quick Specs --}}
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <div class="spec-item text-center">
                                        <div class="small fw-semibold text-primary">{{ $plan->memory ?? 'N/A' }}</div>
                                        <div class="tiny text-muted">RAM</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="spec-item text-center">
                                        <div class="small fw-semibold text-success">{{ $plan->disk ?? 'N/A' }}</div>
                                        <div class="tiny text-muted">Storage</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="spec-item text-center">
                                        <div class="small fw-semibold text-info">{{ $plan->cpu ?? 'N/A' }}%</div>
                                        <div class="tiny text-muted">CPU</div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success flex-fill quick-add-btn" 
                                        data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                    <i class="fas fa-cart-plus me-1"></i>
                                    Add to Cart
                                </button>
                                <a href="{{ route('shop.category', $category->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="border-bottom mb-3"></div>
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
                    <div class="card product-card h-100 shadow-sm border-0 position-relative overflow-hidden">
                        {{-- Product Image with Overlay --}}
                        <div class="product-image-wrapper position-relative">
                            @if($product->image_url)
                                <img src="{{ $product->image_url }}" class="card-img-top product-image" alt="{{ $product->name }}" style="height: 200px; object-fit: cover;">
                            @else
                                {{-- Default game-specific background --}}
                                @php
                                    $gameClass = match(strtolower($product->name)) {
                                        'minecraft' => 'minecraft-bg',
                                        'ark' => 'ark-bg', 
                                        'rust' => 'rust-bg',
                                        default => 'default-game-bg'
                                    };
                                @endphp
                                <div class="card-img-top product-image-placeholder {{ $gameClass }} d-flex align-items-center justify-content-center" style="height: 200px;">
                                    @php
                                        $gameIcon = match(strtolower($product->name)) {
                                            'minecraft' => 'fas fa-cube',
                                            'ark' => 'fas fa-dragon',
                                            'rust' => 'fas fa-tools',
                                            default => 'fas fa-gamepad'
                                        };
                                    @endphp
                                    <i class="{{ $gameIcon }} fa-4x text-white opacity-75"></i>
                                </div>
                            @endif
                            
                            {{-- Badges Overlay --}}
                            <div class="position-absolute top-0 end-0 p-2">
                                @if($product->featured)
                                    <span class="badge bg-warning text-dark badge-glow">
                                        <i class="fas fa-star me-1"></i>Featured
                                    </span>
                                @endif
                                @if($product->category)
                                    <span class="badge bg-dark bg-opacity-75 mt-1 d-block">{{ $product->category }}</span>
                                @endif
                            </div>
                            
                            {{-- Availability Indicator --}}
                            <div class="position-absolute top-0 start-0 p-2">
                                @if($product->plans->count() > 0 && $product->plans->where('status', 'active')->count() > 0)
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle me-1"></i>Available
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i>Unavailable
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Hover Overlay --}}
                            <div class="product-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center opacity-0 transition-all">
                                <a href="{{ route('shop.category', $product->id) }}" class="btn btn-light btn-lg rounded-pill shadow-lg">
                                    <i class="fas fa-eye me-2"></i>View Plans
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-body d-flex flex-column p-4">
                            {{-- Product Header --}}
                            <div class="product-header mb-3">
                                <h5 class="card-title fw-bold mb-2 text-dark">{{ $product->name }}</h5>
                                <div class="product-meta d-flex align-items-center text-muted small">
                                    <i class="fas fa-server me-2"></i>
                                    <span class="me-3">{{ $product->plans->count() }} plan{{ $product->plans->count() !== 1 ? 's' : '' }}</span>
                                    @if($product->plans->count() > 0)
                                        <i class="fas fa-users me-2"></i>
                                        <span>Up to {{ $product->plans->max('slots') ?? 'Unlimited' }} players</span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Pricing Section --}}
                            @if($product->plans->count() > 0)
                                <div class="pricing-section mb-4">
                                    @php
                                        $minPrice = $product->plans->min('price');
                                        $maxPrice = $product->plans->max('price');
                                    @endphp
                                    
                                    <div class="price-display text-center p-3 bg-light rounded-3">
                                        @if($minPrice == $maxPrice)
                                            <div class="h4 text-primary fw-bold mb-0">{{ $currencySymbol }}{{ number_format($minPrice, 2) }}</div>
                                        @else
                                            <div class="h5 text-primary fw-bold mb-0">
                                                {{ $currencySymbol }}{{ number_format($minPrice, 2) }} - {{ $currencySymbol }}{{ number_format($maxPrice, 2) }}
                                            </div>
                                        @endif
                                        <small class="text-muted">per {{ $product->plans->first()->billing_cycle ?? 'month' }}</small>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Action Buttons --}}
                            <div class="card-actions mt-auto">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('shop.category', $product->id) }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-rocket me-2"></i>
                                        View All Plans
                                    </a>
                                    
                                    {{-- Quick Add for Single Plan --}}
                                    @if($product->plans->count() == 1)
                                        @php $plan = $product->plans->first(); @endphp
                                        @if($plan->status === 'active')
                                            <button type="button" class="btn btn-outline-success quick-add-btn" 
                                                    data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }}">
                                                <i class="fas fa-cart-plus me-2"></i>
                                                Quick Add - {{ $currencySymbol }}{{ number_format($plan->price, 2) }}
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary" disabled>
                                                <i class="fas fa-ban me-2"></i>
                                                Currently Unavailable
                                            </button>
                                        @endif
                                    @else
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-info btn-sm w-100" 
                                                        data-bs-toggle="tooltip" title="Compare all available plans">
                                                    <i class="fas fa-balance-scale me-1"></i>
                                                    Compare
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button type="button" class="btn btn-outline-warning btn-sm w-100"
                                                        data-bs-toggle="tooltip" title="Add to wishlist">
                                                    <i class="fas fa-heart me-1"></i>
                                                    Wishlist
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- Improved Footer --}}
                        <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>
                                    <i class="fas fa-clock me-1"></i>
                                    Instant Setup
                                </span>
                                <span>
                                    <i class="fas fa-headset me-1"></i>
                                    24/7 Support
                                </span>
                            </div>
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

@push('styles')
<style>
/* Enhanced Category Filter Styling */
.category-filters {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.category-filters .form-check-inline-block {
    display: block;
    width: 100%;
}

.category-filters .btn {
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 12px 16px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    border: 2px solid #e3e6f0;
    background: #ffffff;
    color: #5a6c7d;
    position: relative;
    overflow: hidden;
    text-align: left;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.category-filters .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s;
}

.category-filters .btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
    border-color: #0d6efd;
    color: #0d6efd;
}

.category-filters .btn:hover::before {
    left: 100%;
}

.category-filters .btn-check:checked + .btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.category-filters .btn-check:checked + .btn::after {
    content: '✓';
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 0.8rem;
    font-weight: bold;
    opacity: 0.9;
}

.category-filters .btn i {
    transition: transform 0.25s ease;
    font-size: 1rem;
    min-width: 20px;
}

.category-filters .btn:hover i {
    transform: scale(1.15) rotate(5deg);
}

.category-filters .btn-check:checked + .btn i {
    transform: scale(1.1);
    filter: drop-shadow(0 0 3px rgba(255, 255, 255, 0.3));
}

/* Game-specific enhanced themes */
.category-filters .btn[for*="minecraft"] {
    border-color: #2ecc71;
}

.category-filters .btn[for*="minecraft"]:hover {
    border-color: #27ae60;
    color: #27ae60;
    box-shadow: 0 8px 25px rgba(46, 204, 113, 0.2);
}

.category-filters .btn-check:checked + .btn[for*="minecraft"] {
    background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
}

.category-filters .btn[for*="ark"] {
    border-color: #e74c3c;
}

.category-filters .btn[for*="ark"]:hover {
    border-color: #c0392b;
    color: #c0392b;
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.2);
}

.category-filters .btn-check:checked + .btn[for*="ark"] {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.category-filters .btn[for*="rust"] {
    border-color: #f39c12;
}

.category-filters .btn[for*="rust"]:hover {
    border-color: #e67e22;
    color: #e67e22;
    box-shadow: 0 8px 25px rgba(243, 156, 18, 0.2);
}

.category-filters .btn-check:checked + .btn[for*="rust"] {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);
}

.category-filters .btn[for*="cs"],
.category-filters .btn[for*="counter"] {
    border-color: #34495e;
}

.category-filters .btn[for*="cs"]:hover,
.category-filters .btn[for*="counter"]:hover {
    border-color: #2c3e50;
    color: #2c3e50;
    box-shadow: 0 8px 25px rgba(52, 73, 94, 0.2);
}

.category-filters .btn-check:checked + .btn[for*="cs"],
.category-filters .btn-check:checked + .btn[for*="counter"] {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
    box-shadow: 0 6px 20px rgba(52, 73, 94, 0.4);
}

.category-filters .btn[for*="gmod"] {
    border-color: #9b59b6;
}

.category-filters .btn[for*="gmod"]:hover {
    border-color: #8e44ad;
    color: #8e44ad;
    box-shadow: 0 8px 25px rgba(155, 89, 182, 0.2);
}

.category-filters .btn-check:checked + .btn[for*="gmod"] {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    box-shadow: 0 6px 20px rgba(155, 89, 182, 0.4);
}

/* Enhanced filter container */
.col-lg-3 .card {
    border-radius: 16px;
    border: none;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    background: #ffffff;
    overflow: hidden;
}

.col-lg-3 .card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.col-lg-3 .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0;
    border: none;
    padding: 1.25rem 1.5rem;
    position: relative;
}

.col-lg-3 .card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
    pointer-events: none;
}

.col-lg-3 .card-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 1.1rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.col-lg-3 .card-body {
    padding: 2rem 1.5rem;
    background: #fafbfc;
}

/* Form enhancements */
.form-label.fw-semibold {
    color: #495057;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    display: flex;
    align-items: center;
}

.form-label.fw-semibold i {
    font-size: 1rem;
}

/* Search input styling */
.form-control {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    padding: 12px 16px;
    background: #ffffff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15), 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
    background: #ffffff;
}

.form-control::placeholder {
    color: #adb5bd;
    font-style: italic;
}

/* Animation for category changes */
@keyframes categorySelect {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1.01); }
}

.category-filters .btn-check:checked + .btn {
    animation: categorySelect 0.3s ease;
}

/* Mobile responsive improvements */
@media (max-width: 768px) {
    .category-filters .btn {
        padding: 10px 14px;
        font-size: 0.85rem;
    }
    
    .col-lg-3 .card-body {
        padding: 1.5rem 1.25rem;
    }
}
</style>
@endpush
