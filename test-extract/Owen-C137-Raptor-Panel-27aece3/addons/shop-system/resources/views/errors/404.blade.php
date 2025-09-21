@extends('shop::layout')

@section('shop-title', 'Page Not Found')

@section('shop-content')
<div class="error-container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card error-card not-found">
                <div class="card-body text-center py-5">
                    {{-- Error Icon --}}
                    <div class="error-icon mb-4">
                        <i class="fas fa-search fa-4x text-warning"></i>
                        <div class="error-number">404</div>
                    </div>
                    
                    {{-- Error Message --}}
                    <h2 class="error-title text-primary mb-3">Oops! Page Not Found</h2>
                    <p class="lead text-muted mb-4">
                        The page you're looking for doesn't exist or has been moved. 
                        Let's get you back to shopping!
                    </p>
                    
                    {{-- Search Box --}}
                    <div class="search-section mb-5">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-search"></i>
                                    Search our products:
                                </h6>
                                <form action="{{ route('shop.search') }}" method="GET" class="search-form">
                                    <div class="input-group">
                                        <input type="text" 
                                               name="q" 
                                               class="form-control form-control-lg" 
                                               placeholder="Search for products, servers, or services..."
                                               value="{{ request('q') }}"
                                               autocomplete="off">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-search"></i>
                                            Search
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Popular Categories --}}
                    <div class="categories-section mb-5">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-star"></i>
                            Popular Categories
                        </h6>
                        <div class="row">
                            @foreach(['Game Servers', 'VPS Hosting', 'Database Services', 'Storage Solutions'] as $category)
                            <div class="col-md-6 col-lg-3 mb-3">
                                <a href="{{ route('shop.category', strtolower(str_replace(' ', '-', $category))) }}" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-folder"></i>
                                    {{ $category }}
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="error-actions">
                        <div class="d-flex flex-column align-items-center gap-3">
                            <a href="{{ route('shop.index') }}" class="btn btn-success btn-lg">
                                <i class="fas fa-home"></i>
                                Go to Shop Home
                            </a>
                            
                            <div class="alternative-actions">
                                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left"></i>
                                    Go Back
                                </a>
                                
                                <a href="{{ route('shop.products') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-box"></i>
                                    All Products
                                </a>
                                
                                @auth
                                <a href="{{ route('shop.dashboard') }}" class="btn btn-outline-info">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                @else
                                <a href="{{ route('auth.login') }}" class="btn btn-outline-info">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Login
                                </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                    
                    {{-- Recent Products --}}
                    <div class="recent-products mt-5">
                        <hr>
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-clock"></i>
                            Recently Added Products
                        </h6>
                        <div class="row">
                            @for($i = 1; $i <= 3; $i++)
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 recent-product">
                                    <div class="card-body text-center">
                                        <i class="fas fa-server fa-2x text-primary mb-2"></i>
                                        <h6 class="card-title">Sample Product {{ $i }}</h6>
                                        <p class="card-text small text-muted">Starting at $9.99/month</p>
                                        <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                            @endfor
                        </div>
                    </div>
                    
                    {{-- Support Information --}}
                    <div class="support-section mt-4">
                        <hr>
                        <h6 class="text-muted mb-3">Still can't find what you're looking for?</h6>
                        <div class="support-buttons">
                            <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-comments"></i>
                                Contact Support
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-question-circle"></i>
                                Help Center
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.error-card.not-found {
    border: 2px solid #ffc107;
    border-radius: 12px;
}

.error-icon {
    position: relative;
    display: inline-block;
}

.error-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2rem;
    font-weight: 900;
    color: #6c757d;
    opacity: 0.3;
}

.error-title {
    font-weight: 700;
}

.search-form {
    margin: 0;
}

.recent-product {
    transition: transform 0.2s;
    border: 1px solid #e9ecef;
}

.recent-product:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.categories-section .btn {
    transition: all 0.2s;
}

.categories-section .btn:hover {
    transform: translateY(-1px);
}

.error-actions .btn-lg {
    padding: 12px 30px;
    font-weight: 600;
}

.alternative-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.support-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
}

@media (max-width: 768px) {
    .error-icon i {
        font-size: 3em !important;
    }
    
    .error-number {
        font-size: 1.5rem;
    }
    
    .categories-section .col-lg-3 {
        margin-bottom: 10px;
    }
    
    .alternative-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .alternative-actions .btn {
        width: 100%;
        margin: 0 0 10px 0 !important;
    }
    
    .support-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .support-buttons .btn {
        width: 100%;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Auto-focus search input when page loads
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.focus();
    }
});
</script>
@endpush
