@extends('shop::base')

@section('title', $shopConfig['shop_name'] ?? 'Server Shop')

@section('assets')
    @parent
        <link rel="stylesheet" href="{{ url('shop/assets/css/shop.css?v=3') }}">
    @if(!empty($shopConfig['custom_css']))
        <style>{{ $shopConfig['custom_css'] }}</style>
    @endif
@endsection

@section('scripts')
    @parent
        <script src="{{ url('shop/assets/js/shop.js?v=3') }}"></script>
@endsection

@section('content')
{{-- Modern Shop Header --}}
<div class="shop-header">
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                {{-- Logo/Brand Section --}}
                <div class="navbar-brand d-flex align-items-center">
                    @if(!empty($shopConfig['logo_url']))
                        <img src="{{ $shopConfig['logo_url'] }}" alt="{{ $shopConfig['shop_name'] ?? 'Server Shop' }}" class="shop-logo me-2" style="height: 40px;">
                    @else
                        <i class="fas fa-store me-2 text-primary" style="font-size: 1.8rem;"></i>
                    @endif
                    <span class="fw-bold text-dark fs-4">{{ $shopConfig['shop_name'] ?? 'Server Shop' }}</span>
                </div>

                {{-- Mobile Toggle Button --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shopNavbar" aria-controls="shopNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                {{-- Navigation Links (Center) --}}
                <div class="collapse navbar-collapse" id="shopNavbar">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('shop.index') ? 'active fw-bold' : '' }}" href="{{ route('shop.index') }}">
                                <i class="fas fa-gamepad me-1"></i>
                                Game Hosting
                            </a>
                        </li>
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('shop.orders.*') ? 'active fw-bold' : '' }}" href="{{ route('shop.orders.index') }}">
                                    <i class="fas fa-shopping-bag me-1"></i>
                                    My Orders
                                </a>
                            </li>
                            @if($shopConfig['wallet_enabled'] ?? true)
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('shop.wallet.*') ? 'active fw-bold' : '' }}" href="{{ route('shop.wallet.index') }}">
                                        <i class="fas fa-wallet me-1"></i>
                                        Wallet
                                    </a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    {{-- User Section (Right) --}}
                    <div class="d-flex align-items-center">
                        {{-- Shopping Cart --}}
                        @auth
                            <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary me-3 position-relative {{ request()->routeIs('shop.cart') ? 'active' : '' }}">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="d-none d-md-inline ms-1">Cart</span>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count-badge" style="display: none;">
                                    <span class="cart-count-nav">0</span>
                                </span>
                            </a>

                            {{-- Wallet Balance (if enabled) --}}
                            @if($shopConfig['wallet_enabled'] ?? true)
                                <div class="me-3">
                                    <span class="badge bg-success fs-6">
                                        <i class="fas fa-wallet me-1"></i>
                                        <span class="wallet-balance" data-balance="{{ $userWallet->balance ?? 0 }}">
                                            {{ $paymentConfig['currency_symbol'] ?? '$' }}{{ number_format($userWallet->balance ?? 0, 2) }}
                                        </span>
                                    </span>
                                </div>
                            @endif

                            {{-- User Dropdown --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-dark dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <span class="d-none d-md-inline">{{ auth()->user()->name_first }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <h6 class="dropdown-header">
                                            <i class="fas fa-user"></i>
                                            {{ auth()->user()->name_first }} {{ auth()->user()->name_last }}
                                        </h6>
                                    </li>
                                    <li>
                                        <span class="dropdown-item-text text-muted small">
                                            <i class="fas fa-envelope"></i>
                                            {{ auth()->user()->email }}
                                        </span>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('index') }}">
                                            <i class="fas fa-tachometer-alt me-2"></i>
                                            Panel Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('shop.orders.index') }}">
                                            <i class="fas fa-shopping-bag me-2"></i>
                                            My Orders
                                        </a>
                                    </li>
                                    @if($shopConfig['wallet_enabled'] ?? true)
                                        <li>
                                            <a class="dropdown-item" href="{{ route('shop.wallet.index') }}">
                                                <i class="fas fa-wallet me-2"></i>
                                                Wallet
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item" href="{{ route('account') }}">
                                            <i class="fas fa-cog me-2"></i>
                                            Account Settings
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('auth.logout') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-sign-out-alt me-2"></i>
                                                Logout
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            {{-- Guest User Actions --}}
                            <div class="d-flex gap-2">
                                <a href="{{ route('auth.login') }}" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Login
                                </a>
                                <a href="{{ route('auth.register') }}" class="btn btn-outline-primary d-none d-md-inline-block">
                                    <i class="fas fa-user-plus me-1"></i>
                                    Register
                                </a>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>

{{-- Main Shop Content Container --}}
<div class="content shop-container">
    <div class="container-fluid">
        {{-- Page Title Section --}}
        @hasSection('shop-title')
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-1 text-dark">@yield('shop-title')</h2>
                            @hasSection('shop-subtitle')
                                <p class="text-muted mb-0">@yield('shop-subtitle')</p>
                            @endif
                        </div>
                        @hasSection('shop-actions')
                            <div class="d-flex gap-2">
                                @yield('shop-actions')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Shop Content --}}
        @yield('shop-content')
    </div>
</div>

{{-- Shopping Cart Sidebar --}}
<div class="shop-cart-sidebar" id="cart-sidebar">
    <div class="cart-header">
        <h5>Shopping Cart</h5>
        <button type="button" class="btn-close" id="cart-close"></button>
    </div>
    <div class="cart-content">
        <div class="cart-items" id="cart-items">
            <div class="cart-empty text-center py-4" id="cart-empty">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <p class="text-muted">Your cart is empty</p>
                <a href="{{ route('shop.index') }}" class="btn btn-primary">Browse Products</a>
            </div>
        </div>
        <div class="cart-footer" id="cart-footer" style="display: none;">
            <div class="cart-total">
                <strong>Total: <span id="cart-total">{{ $paymentConfig['currency_symbol'] ?? '$' }}0.00</span></strong>
            </div>
            <div class="cart-actions mt-3">
                <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary btn-block">View Cart</a>
                @auth
                    <a href="{{ route('shop.checkout.index') }}" class="btn btn-success btn-block mt-2">Checkout</a>
                @else
                    <a href="{{ route('auth.login') }}" class="btn btn-success btn-block mt-2">Login to Checkout</a>
                @endauth
            </div>
        </div>
    </div>
</div>

{{-- Shopping Cart Backdrop --}}
<div class="shop-cart-backdrop" id="cart-backdrop"></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize shop functionality
    Shop.init();
    
    // Load cart from localStorage
    Shop.loadCart();
    
    // Update cart count in all locations (header badge and navigation)
    function updateCartCount(count) {
        // Update text elements (.cart-count, .cart-count-nav)
        const cartElements = document.querySelectorAll('.cart-count, .cart-count-nav');
        cartElements.forEach(element => {
            element.textContent = count || 0;
        });
        
        // Update header cart badge visibility
        const cartBadge = document.querySelector('.cart-count-badge');
        if (cartBadge) {
            if (count > 0) {
                cartBadge.style.display = 'inline-block';
                cartBadge.classList.add('animate__animated', 'animate__pulse');
            } else {
                cartBadge.style.display = 'none';
                cartBadge.classList.remove('animate__animated', 'animate__pulse');
            }
        }
        
        // Update navigation badge visibility
        const navBadge = document.querySelector('.cart-count-nav');
        const navBadgeParent = navBadge?.parentElement;
        if (navBadgeParent) {
            if (count > 0) {
                navBadgeParent.style.display = 'inline';
                navBadge.classList.add('animate__animated', 'animate__pulse');
            } else {
                navBadgeParent.style.display = 'none';
                navBadge.classList.remove('animate__animated', 'animate__pulse');
            }
        }
        
        console.log('🔄 Cart count updated to:', count);
    }
    
    // Update wallet balance periodically
    @auth
    setInterval(function() {
        Shop.updateWalletBalance();
    }, 30000); // Update every 30 seconds
    @endauth
});
</script>
@endpush

@push('styles')
<style>
/* Shop Navigation Improvements */
.shop-navigation .nav-pills .nav-link {
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    border: 2px solid transparent;
}

.shop-navigation .nav-pills .nav-link:hover {
    background-color: rgba(0, 123, 255, 0.1);
    border-color: rgba(0, 123, 255, 0.3);
    transform: translateY(-2px);
}

.shop-navigation .nav-pills .nav-link.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

/* User Dropdown Improvements */
.shop-user-info .btn-group .btn {
    border-radius: 6px;
    margin-left: 4px;
    transition: all 0.3s ease;
}

.shop-user-info .btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.shop-user-info .dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    border: none;
    margin-top: 8px;
}

.shop-user-info .dropdown-item {
    padding: 8px 16px;
    transition: all 0.2s ease;
}

.shop-user-info .dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    padding-left: 20px;
}

.shop-user-info .dropdown-header {
    color: var(--bs-primary);
    font-weight: 600;
    font-size: 0.85rem;
}

/* Cart Count Badge Animation */
.cart-count, .cart-count-nav {
    transition: all 0.3s ease;
}

.cart-count-nav {
    font-size: 0.7rem;
}

/* Guest Info Styling */
.shop-guest-info .btn {
    margin-left: 4px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.shop-guest-info .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* Mobile Responsive Improvements */
@media (max-width: 768px) {
    .shop-user-info .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .shop-user-info .btn {
        font-size: 0.8rem;
        padding: 6px 8px;
    }
    
    .shop-navigation .nav-pills {
        flex-wrap: wrap;
    }
    
    .shop-navigation .nav-item {
        flex: 1;
        min-width: 0;
    }
    
    .shop-navigation .nav-link {
        text-align: center;
        padding: 8px 4px;
        font-size: 0.85rem;
    }
}

/* Authentication Status Indicator */
.shop-user-info::before {
    content: '';
    position: absolute;
    top: -2px;
    right: -2px;
    width: 8px;
    height: 8px;
    background-color: #28a745;
    border-radius: 50%;
    z-index: 10;
}

/* Cart Button Special Styling */
#cart-btn {
    position: relative;
}

#cart-btn .cart-count {
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7rem;
    margin-left: 4px;
}

/* Modern Header Styling */
.shop-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.shop-header .navbar {
    padding: 1rem 0;
    background: white !important;
    border-radius: 10px;
    margin: 15px 15px 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.shop-header .navbar-brand {
    font-size: 1.5rem;
    color: #333 !important;
    text-decoration: none;
}

.shop-header .navbar-brand:hover {
    color: #007bff !important;
}

.shop-header .navbar-nav .nav-link {
    color: #495057 !important;
    font-weight: 500;
    padding: 0.7rem 1rem;
    border-radius: 6px;
    transition: all 0.3s ease;
    margin: 0 0.2rem;
}

.shop-header .navbar-nav .nav-link:hover {
    color: #007bff !important;
    background-color: rgba(0, 123, 255, 0.08);
    transform: translateY(-1px);
}

.shop-header .navbar-nav .nav-link.active {
    color: #007bff !important;
    background-color: rgba(0, 123, 255, 0.15);
    font-weight: 600;
}

.shop-header .btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.shop-header .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.shop-header .dropdown-menu {
    border-radius: 8px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    margin-top: 8px;
    min-width: 220px;
}

.shop-header .dropdown-item {
    padding: 10px 20px;
    transition: all 0.2s ease;
}

.shop-header .dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.08);
    padding-left: 24px;
}

.shop-header .dropdown-header {
    color: #007bff;
    font-weight: 600;
    padding: 10px 20px 5px;
}

.shop-header .dropdown-item-text {
    padding: 5px 20px;
}

.cart-count-badge {
    font-size: 0.6rem;
    min-width: 18px;
    height: 18px;
    line-height: 1;
    padding: 3px;
}

.wallet-balance {
    font-weight: 600;
}

/* Mobile Responsive Header */
@media (max-width: 991.98px) {
    .shop-header .navbar {
        margin: 10px;
    }
    
    .shop-header .navbar-brand {
        font-size: 1.3rem;
    }
    
    .shop-header .navbar-nav {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
    }
    
    .shop-header .d-flex {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
        justify-content: center;
        gap: 0.5rem;
    }
}

@media (max-width: 576px) {
    .shop-header .navbar-brand span {
        font-size: 1.1rem;
    }
    
    .shop-header .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>
@endpush
