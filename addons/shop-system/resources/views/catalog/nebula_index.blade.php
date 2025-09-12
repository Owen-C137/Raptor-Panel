@extends('shop::layout')

@section('shop-title', 'Cosmic Gaming Hub')
@section('shop-subtitle', 'Explore our galaxy of gaming server plans')

@section('shop-content')
<div class="nebula-main-container">
    <!-- Cosmic Background Effects -->
    <div class="cosmic-background">
        <div class="stellar-particle stellar-particle-1"></div>
        <div class="stellar-particle stellar-particle-2"></div>
        <div class="stellar-particle stellar-particle-3"></div>
        <div class="stellar-particle stellar-particle-4"></div>
        <div class="stellar-particle stellar-particle-5"></div>
        <div class="nebula-glow nebula-glow-primary"></div>
        <div class="nebula-glow nebula-glow-secondary"></div>
        <div class="stardust-trail stardust-trail-1"></div>
        <div class="stardust-trail stardust-trail-2"></div>
    </div>

    <!-- Main Content Grid -->
    <div class="nebula-content-grid">
        <!-- Sidebar Filters -->
        <div class="nebula-sidebar">
            <div class="cosmic-filter-panel">
                <div class="cosmic-panel-header">
                    <div class="cosmic-icon-wrapper">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                    <h3 class="cosmic-panel-title">Mission Control</h3>
                    <div class="cosmic-header-accent"></div>
                </div>
                
                <div class="cosmic-panel-body">
                    <form id="nebula-filters" method="GET" class="cosmic-filter-form">
                        <!-- Search Module -->
                        <div class="cosmic-filter-group">
                            <label for="search" class="cosmic-label">
                                <i class="fas fa-search cosmic-label-icon"></i>
                                Scan Frequencies
                            </label>
                            <div class="cosmic-input-wrapper">
                                <input type="text" 
                                       class="cosmic-input cosmic-search-input" 
                                       id="search" 
                                       name="search" 
                                       value="{{ request('search') }}" 
                                       placeholder="Search the galaxy...">
                                <div class="cosmic-input-glow"></div>
                            </div>
                        </div>

                        <!-- Category Selection -->
                        @if($categories->count() > 0)
                        <div class="cosmic-filter-group">
                            <label class="cosmic-label">
                                <i class="fas fa-rocket cosmic-label-icon"></i>
                                Mission Type
                            </label>
                            <div class="cosmic-radio-grid">
                                <div class="cosmic-radio-item">
                                    <input class="cosmic-radio-input" 
                                           type="radio" 
                                           name="category" 
                                           id="category-all" 
                                           value="" 
                                           {{ !request('category') ? 'checked' : '' }}>
                                    <label class="cosmic-radio-label" for="category-all">
                                        <div class="cosmic-radio-indicator"></div>
                                        <span class="cosmic-radio-text">All Galaxies</span>
                                    </label>
                                </div>
                                @foreach($categories as $category)
                                <div class="cosmic-radio-item">
                                    <input class="cosmic-radio-input" 
                                           type="radio" 
                                           name="category" 
                                           id="category-{{ Str::slug($category) }}" 
                                           value="{{ $category }}"
                                           {{ request('category') == $category ? 'checked' : '' }}>
                                    <label class="cosmic-radio-label" for="category-{{ Str::slug($category) }}">
                                        <div class="cosmic-radio-indicator"></div>
                                        <span class="cosmic-radio-text">{{ $category }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- Price Range -->
                        <div class="cosmic-filter-group">
                            <label class="cosmic-label">
                                <i class="fas fa-coins cosmic-label-icon"></i>
                                Resource Budget
                            </label>
                            <div class="cosmic-price-range">
                                <div class="cosmic-price-inputs">
                                    <input type="number" 
                                           class="cosmic-input cosmic-price-input" 
                                           name="min_price" 
                                           id="min_price" 
                                           value="{{ request('min_price') }}" 
                                           placeholder="Min" 
                                           step="0.01">
                                    <div class="cosmic-price-separator">
                                        <i class="fas fa-arrows-alt-h"></i>
                                    </div>
                                    <input type="number" 
                                           class="cosmic-input cosmic-price-input" 
                                           name="max_price" 
                                           id="max_price" 
                                           value="{{ request('max_price') }}" 
                                           placeholder="Max" 
                                           step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- Sort Options -->
                        <div class="cosmic-filter-group">
                            <label for="sort" class="cosmic-label">
                                <i class="fas fa-sort cosmic-label-icon"></i>
                                Temporal Order
                            </label>
                            <div class="cosmic-select-wrapper">
                                <select class="cosmic-select" name="sort" id="sort">
                                    <option value="" {{ !request('sort') ? 'selected' : '' }}>Default Sequence</option>
                                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Energy: Low to High</option>
                                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Energy: High to Low</option>
                                    <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z to A</option>
                                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Most Popular</option>
                                </select>
                                <div class="cosmic-select-arrow">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="cosmic-filter-actions">
                            <button type="submit" class="cosmic-btn cosmic-btn-primary cosmic-btn-block">
                                <i class="fas fa-rocket"></i>
                                <span>Launch Scan</span>
                                <div class="cosmic-btn-glow"></div>
                            </button>
                            <a href="{{ route('shop.index') }}" class="cosmic-btn cosmic-btn-secondary cosmic-btn-block">
                                <i class="fas fa-undo"></i>
                                <span>Reset Coordinates</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="nebula-main-content">
            <!-- Results Header -->
            <div class="cosmic-results-header">
                <div class="cosmic-results-info">
                    <h2 class="cosmic-results-title">
                        @if(request('search'))
                            <i class="fas fa-satellite"></i>
                            Scan Results for "{{ request('search') }}"
                        @elseif(request('category'))
                            <i class="fas fa-planet-ringed"></i>
                            {{ request('category') }} Sector
                        @else
                            <i class="fas fa-galaxy"></i>
                            All Gaming Sectors
                        @endif
                    </h2>
                    <p class="cosmic-results-count">
                        <span class="cosmic-count-number">{{ $products->total() }}</span>
                        <span class="cosmic-count-text">{{ Str::plural('sector', $products->total()) }} discovered</span>
                    </p>
                </div>
                
                <div class="cosmic-view-controls">
                    <div class="cosmic-view-toggles">
                        <button class="cosmic-view-toggle cosmic-view-toggle-active" data-view="grid" title="Grid View">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="cosmic-view-toggle" data-view="list" title="List View">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            @if($products->count() > 0)
                <div class="cosmic-categories-grid" id="cosmic-grid">
                    @foreach($products as $category)
                        <div class="cosmic-sector-card" data-category-id="{{ $category->id }}">
                            <!-- Sector Background Effects -->
                            <div class="cosmic-card-bg">
                                <div class="cosmic-card-particles">
                                    <div class="cosmic-particle cosmic-particle-1"></div>
                                    <div class="cosmic-particle cosmic-particle-2"></div>
                                    <div class="cosmic-particle cosmic-particle-3"></div>
                                </div>
                                <div class="cosmic-card-glow"></div>
                                <div class="cosmic-card-border"></div>
                            </div>

                            <!-- Sector Header -->
                            <div class="cosmic-sector-header">
                                <div class="cosmic-sector-icon">
                                    @if($category->image_url)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="cosmic-sector-image">
                                    @else
                                        <i class="fas fa-rocket cosmic-default-icon"></i>
                                    @endif
                                </div>
                                <div class="cosmic-sector-status">
                                    <div class="cosmic-status-indicator cosmic-status-active"></div>
                                    <span class="cosmic-status-text">Active</span>
                                </div>
                            </div>

                            <!-- Sector Info -->
                            <div class="cosmic-sector-content">
                                <h3 class="cosmic-sector-name">{{ $category->name }}</h3>
                                <p class="cosmic-sector-description">{{ Str::limit($category->description, 120) }}</p>
                                
                                <!-- Sector Stats -->
                                <div class="cosmic-sector-stats">
                                    @if(isset($category->plans_count))
                                        <div class="cosmic-stat">
                                            <i class="fas fa-server cosmic-stat-icon"></i>
                                            <span class="cosmic-stat-value">{{ $category->plans_count }}</span>
                                            <span class="cosmic-stat-label">{{ Str::plural('Plan', $category->plans_count) }}</span>
                                        </div>
                                    @endif
                                    @if(isset($category->min_price))
                                        <div class="cosmic-stat">
                                            <i class="fas fa-coins cosmic-stat-icon"></i>
                                            <span class="cosmic-stat-value">{{ $paymentConfig['currency_symbol'] ?? '$' }}{{ number_format($category->min_price, 2) }}</span>
                                            <span class="cosmic-stat-label">Starting</span>
                                        </div>
                                    @endif
                                    <div class="cosmic-stat">
                                        <i class="fas fa-shield-alt cosmic-stat-icon"></i>
                                        <span class="cosmic-stat-value">99.9%</span>
                                        <span class="cosmic-stat-label">Uptime</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Sector Actions -->
                            <div class="cosmic-sector-actions">
                                <a href="{{ route('shop.category', $category) }}" class="cosmic-btn cosmic-btn-primary cosmic-btn-sector">
                                    <i class="fas fa-rocket"></i>
                                    <span>Explore Sector</span>
                                    <div class="cosmic-btn-trail"></div>
                                </a>
                                <a href="{{ route('shop.category', $category) }}" class="cosmic-btn cosmic-btn-outline cosmic-btn-sector">
                                    <i class="fas fa-info-circle"></i>
                                    <span>View Plans</span>
                                </a>
                            </div>

                            <!-- Hover Effect Overlay -->
                            <div class="cosmic-card-hover-overlay"></div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($products->hasPages())
                    <div class="cosmic-pagination-wrapper">
                        <div class="cosmic-pagination">
                            {{ $products->links('shop::components.pagination') }}
                        </div>
                    </div>
                @endif
            @else
                <!-- Empty State -->
                <div class="cosmic-empty-state">
                    <div class="cosmic-empty-icon">
                        <i class="fas fa-satellite-dish"></i>
                    </div>
                    <h3 class="cosmic-empty-title">No Sectors Found</h3>
                    <p class="cosmic-empty-text">
                        @if(request('search') || request('category') || request('min_price') || request('max_price'))
                            Our deep space scanners couldn't locate any sectors matching your criteria. Try adjusting your search parameters.
                        @else
                            Our galaxy is currently being mapped. Please check back soon for new sectors!
                        @endif
                    </p>
                    <div class="cosmic-empty-actions">
                        @if(request()->hasAny(['search', 'category', 'min_price', 'max_price', 'sort']))
                            <a href="{{ route('shop.index') }}" class="cosmic-btn cosmic-btn-primary">
                                <i class="fas fa-rocket"></i>
                                <span>Reset Scan</span>
                            </a>
                        @endif
                        <a href="{{ route('index') }}" class="cosmic-btn cosmic-btn-outline">
                            <i class="fas fa-home"></i>
                            <span>Return to Base</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter form auto-submit with cosmic delay
    const filterForm = document.getElementById('nebula-filters');
    const filterInputs = filterForm.querySelectorAll('input, select');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Add cosmic scanning delay
            const scanningDelay = this.type === 'radio' ? 600 : 1000;
            setTimeout(() => {
                filterForm.submit();
            }, scanningDelay);
        });
    });

    // View toggle functionality
    const viewToggles = document.querySelectorAll('.cosmic-view-toggle');
    const cosmicGrid = document.getElementById('cosmic-grid');
    
    viewToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active state
            viewToggles.forEach(t => t.classList.remove('cosmic-view-toggle-active'));
            this.classList.add('cosmic-view-toggle-active');
            
            // Update grid layout
            if (view === 'list') {
                cosmicGrid.classList.add('cosmic-categories-list');
            } else {
                cosmicGrid.classList.remove('cosmic-categories-list');
            }
            
            // Store preference in cosmic database (localStorage)
            localStorage.setItem('cosmic_view_preference', view);
        });
    });
    
    // Load saved view preference
    const savedView = localStorage.getItem('cosmic_view_preference');
    if (savedView) {
        const toggle = document.querySelector(`[data-view="${savedView}"]`);
        if (toggle) {
            toggle.click();
        }
    }

    // Cosmic sector card hover effects
    const sectorCards = document.querySelectorAll('.cosmic-sector-card');
    
    sectorCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('cosmic-card-hover');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('cosmic-card-hover');
        });
    });

    // Enhanced search with cosmic scanning
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let cosmicScanTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(cosmicScanTimeout);
            
            // Add cosmic scanning effect
            this.classList.add('cosmic-scanning');
            
            cosmicScanTimeout = setTimeout(() => {
                this.classList.remove('cosmic-scanning');
                if (this.value.length >= 3 || this.value.length === 0) {
                    filterForm.submit();
                }
            }, 800);
        });
    }

    // Cosmic particle animation enhancer
    const cosmicParticles = document.querySelectorAll('.cosmic-particle, .stellar-particle');
    cosmicParticles.forEach(particle => {
        // Add random delay to create more organic movement
        const randomDelay = Math.random() * 10;
        particle.style.animationDelay = `${randomDelay}s`;
    });

    // Nebula glow intensity based on scroll
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const nebulaBg = document.querySelector('.cosmic-background');
        
        if (nebulaBg) {
            const intensity = Math.min(scrolled / 500, 1);
            nebulaBg.style.opacity = 1 - (intensity * 0.3);
        }
    });
});
</script>
@endpush
