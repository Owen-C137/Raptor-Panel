@extends('shop::base')

@section('title', $shopConfig['shop_name'] ?? 'Server Shop')

@section('assets')
    @parent
        <link rel="stylesheet" href="{{ url('shop/assets/css/oneui.css?v=3') }}">
    @if(!empty($shopConfig['custom_css']))
        <style>{{ $shopConfig['custom_css'] }}</style>
    @endif
@endsection

@section('scripts')
    @parent
        <script src="{{ url('shop/assets/js/shop.js?v=3') }}"></script>
@endsection

@section('content')
<!-- Page Container -->
<div id="page-container" class="page-header-dark main-content-narrow">

    <!-- Header -->
    <header id="page-header">
        <!-- Header Content -->
        <div class="content-header">
            <!-- Right Section -->
            <div class="d-flex align-items-center">
                <!-- Logo -->
                <a class="fw-semibold fs-5 tracking-wider text-dual ms-3" href="{{ route('shop.index') }}">
                    @if(!empty($shopConfig['logo_url']))
                        <img src="{{ $shopConfig['logo_url'] }}" alt="{{ $shopConfig['shop_name'] ?? 'Server Shop' }}" class="shop-logo me-2" style="height: 32px;">
                    @else
                        <i class="fas fa-store me-2"></i>
                    @endif
                    {{ $shopConfig['shop_name'] ?? 'Server Shop' }}
                </a>
                <!-- END Logo -->
            </div>
            <!-- END Right Section -->

            <!-- Left Section -->
            <div class="d-flex align-items-center">
                <!-- Search Form -->
                <form class="d-none d-sm-inline-block" method="GET" action="{{ route('shop.index') }}">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-alt" placeholder="Search products.." name="search" value="{{ request('search') }}">
                        <button type="submit" class="btn btn-alt-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <!-- END Search Form -->

                {{-- Shopping Cart --}}
                @auth
                    <div class="dropdown d-inline-block me-2 ms-2">
                        <button type="button" class="btn btn-sm btn-alt-secondary position-relative" id="page-header-cart-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count-badge" style="display: none;">
                                <span class="cart-count-nav">0</span>
                            </span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 border-0 fs-sm" aria-labelledby="page-header-cart-dropdown" style="min-width: 350px; max-width: 400px;">
                            <div class="p-3 bg-body-light border-bottom text-center rounded-top">
                                <h5 class="dropdown-header text-uppercase mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>Shopping Cart
                                </h5>
                            </div>
                            
                            <!-- Cart Loading State -->
                            <div class="cart-loading text-center py-4" id="cart-loading-dropdown" style="display: none;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading cart...</p>
                            </div>
                            
                            <!-- Cart Items Container -->
                            <div class="cart-items-container" id="cart-items-dropdown" style="max-height: 400px; overflow-y: auto;">
                                <!-- Empty Cart State -->
                                <div class="cart-empty text-center py-4" id="cart-empty-dropdown">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3 opacity-50"></i>
                                    <h6 class="text-muted mb-2">Your cart is empty</h6>
                                    <p class="text-muted small mb-3">Add some awesome hosting plans!</p>
                                    <a href="{{ route('shop.index') }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-gamepad me-1"></i>Browse Plans
                                    </a>
                                </div>
                                
                                <!-- Cart Items Will Be Populated Here -->
                                <div class="cart-items-list" id="cart-items-list" style="display: none;"></div>
                            </div>
                            
                            <!-- Cart Footer -->
                            <div class="cart-footer border-top bg-white" id="cart-footer-dropdown" style="display: none;">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="fw-semibold">Total:</span>
                                        <span class="h5 mb-0 text-primary fw-bold" id="cart-total-amount">
                                            {{ $paymentConfig['currency_symbol'] ?? '$' }}0.00
                                        </span>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-shopping-cart me-1"></i>View Full Cart
                                        </a>
                                        <a href="{{ route('shop.checkout.index') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-credit-card me-1"></i>Checkout Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endauth

                {{-- Wallet Balance (if enabled) --}}
                @auth
                    @if($shopConfig['wallet_enabled'] ?? true)
                        <div class="me-2">
                            <span class="badge bg-success">
                                <i class="fas fa-wallet me-1"></i>
                                <span class="wallet-balance" data-balance="{{ $userWallet->balance ?? 0 }}">
                                    {{ $paymentConfig['currency_symbol'] ?? '$' }}{{ number_format($userWallet->balance ?? 0, 2) }}
                                </span>
                            </span>
                        </div>
                    @endif
                @endauth

                <!-- User Dropdown -->
                @auth
                    <div class="dropdown d-inline-block ms-2">
                        <button type="button" class="btn btn-sm btn-alt-secondary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <span class="d-none d-sm-inline-block">{{ auth()->user()->name_first }}</span>
                            <i class="fa fa-fw fa-angle-down d-none d-sm-inline-block ms-1"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0 border-0" aria-labelledby="page-header-user-dropdown">
                            <div class="p-3 text-center bg-body-light border-bottom rounded-top">
                                <i class="fas fa-user-circle fa-3x text-muted mb-2"></i>
                                <p class="mt-2 mb-0 fw-medium">{{ auth()->user()->name_first }} {{ auth()->user()->name_last }}</p>
                                <p class="mb-0 text-muted fs-sm">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-2">
                                <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('index') }}">
                                    <span class="fs-sm fw-medium">
                                        <i class="fas fa-tachometer-alt me-2"></i>Panel Dashboard
                                    </span>
                                </a>
                                <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('shop.orders.index') }}">
                                    <span class="fs-sm fw-medium">
                                        <i class="fas fa-shopping-bag me-2"></i>My Orders
                                    </span>
                                </a>
                                @if($shopConfig['wallet_enabled'] ?? true)
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('shop.wallet.index') }}">
                                        <span class="fs-sm fw-medium">
                                            <i class="fas fa-wallet me-2"></i>Wallet
                                        </span>
                                    </a>
                                @endif
                                <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('account') }}">
                                    <span class="fs-sm fw-medium">
                                        <i class="fas fa-cog me-2"></i>Account Settings
                                    </span>
                                </a>
                            </div>
                            <div role="separator" class="dropdown-divider m-0"></div>
                            <div class="p-2">
                                <form method="POST" action="{{ route('auth.logout') }}" class="d-inline w-100">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center justify-content-between text-danger">
                                        <span class="fs-sm fw-medium">
                                            <i class="fas fa-sign-out-alt me-2"></i>Log Out
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Guest User Actions -->
                    <div class="d-flex gap-2 ms-2">
                        <a href="{{ route('auth.login') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <a href="{{ route('auth.register') }}" class="btn btn-sm btn-alt-secondary d-none d-md-inline-block">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </div>
                @endauth
                <!-- END User Dropdown -->
            </div>
            <!-- END Left Section -->
        </div>
        <!-- END Header Content -->
    </header>
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container">
        <!-- Navigation -->
        <div class="bg-primary-darker">
            <div class="content py-3">
                <!-- Toggle Main Navigation -->
                <div class="d-lg-none">
                    <button type="button" class="btn w-100 btn-alt-secondary d-flex justify-content-between align-items-center" data-toggle="class-toggle" data-target="#main-navigation" data-class="d-none">
                        Menu
                        <i class="fa fa-bars"></i>
                    </button>
                </div>
                <!-- END Toggle Main Navigation -->

                <!-- Main Navigation -->
                <div id="main-navigation" class="d-none d-lg-block mt-2 mt-lg-0">
                    <ul class="nav-main nav-main-dark nav-main-horizontal nav-main-hover">
                        <li class="nav-main-item">
                            <a class="nav-main-link {{ request()->routeIs('shop.index') ? 'active' : '' }}" href="{{ route('shop.index') }}">
                                <i class="nav-main-link-icon fas fa-gamepad"></i>
                                <span class="nav-main-link-name">Game Hosting</span>
                            </a>
                        </li>
                        @auth
                            <li class="nav-main-item">
                                <a class="nav-main-link {{ request()->routeIs('shop.orders.*') ? 'active' : '' }}" href="{{ route('shop.orders.index') }}">
                                    <i class="nav-main-link-icon fas fa-shopping-bag"></i>
                                    <span class="nav-main-link-name">My Orders</span>
                                </a>
                            </li>
                            @if($shopConfig['wallet_enabled'] ?? true)
                                <li class="nav-main-item">
                                    <a class="nav-main-link {{ request()->routeIs('shop.wallet.*') ? 'active' : '' }}" href="{{ route('shop.wallet.index') }}">
                                        <i class="nav-main-link-icon fas fa-wallet"></i>
                                        <span class="nav-main-link-name">Wallet</span>
                                    </a>
                                </li>
                            @endif
                            <li class="nav-main-item">
                                <a class="nav-main-link {{ request()->routeIs('shop.cart') ? 'active' : '' }}" href="{{ route('shop.cart') }}">
                                    <i class="nav-main-link-icon fas fa-shopping-cart"></i>
                                    <span class="nav-main-link-name">Shopping Cart</span>
                                    <span class="nav-main-link-badge badge rounded-pill bg-primary cart-count-nav" style="display: none;">0</span>
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>
                <!-- END Main Navigation -->
            </div>
        </div>
        <!-- END Navigation -->

        <!-- Page Content -->
        <div class="content">
            {{-- Page Title Section --}}
            @hasSection('shop-title')
                <div class="block block-rounded">
                    <div class="block-content block-content-full">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h2 class="content-heading mb-1">@yield('shop-title')</h2>
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
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Main Shop Content --}}
            @yield('shop-content')
        </div>
        <!-- END Page Content -->
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer" class="bg-body-extra-light">
        <div class="content py-3">
            <div class="row fs-sm">
                <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-start">
                    Powered by <i class="fa fa-heart text-danger"></i> {{ $shopConfig['shop_name'] ?? 'Server Shop' }}
                </div>
                <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-end">
                    &copy; <span data-toggle="year-copy">{{ date('Y') }}</span>
                </div>
            </div>
        </div>
    </footer>
    <!-- END Footer -->
</div>
<!-- END Page Container -->
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if Shop object exists before initializing
    if (typeof Shop !== 'undefined') {
        // Initialize shop functionality
        Shop.init();
        
        console.log('🛒 Shop system loaded and initialized');
        
        // Update wallet balance periodically for authenticated users
        @auth
        setInterval(function() {
            if (Shop.updateWalletBalance) {
                Shop.updateWalletBalance();
            }
        }, 30000); // Update every 30 seconds
        @endauth
    } else {
        console.error('❌ Shop object not found - shop.js may not be loaded properly');
        
        // Fallback: Basic cart count update function
        window.updateCartCount = function(count) {
            const cartElements = document.querySelectorAll('.cart-count, .cart-count-nav');
            cartElements.forEach(element => {
                element.textContent = count || 0;
            });
            
            const cartBadges = document.querySelectorAll('.cart-count-badge');
            cartBadges.forEach(badge => {
                if (count > 0) {
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            });
        };
    }
});
</script>
@endpush
