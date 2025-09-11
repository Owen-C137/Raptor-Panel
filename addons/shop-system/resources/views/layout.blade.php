@extends('shop::base')

@section('title', config('shop.branding.name', 'Server Shop'))

@section('assets')
    @parent
        <link rel="stylesheet" href="{{ url('shop/assets/css/shop.css?v=3') }}">
    @if(config('shop.branding.custom_css'))
        <style>{{ config('shop.branding.custom_css') }}</style>
    @endif
@endsection

@section('scripts')
    @parent
        <script src="{{ url('shop/assets/js/shop.js?v=3') }}"></script>
@endsection

@section('content-header')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    @if(config('shop.branding.logo'))
                        <img src="{{ config('shop.branding.logo') }}" alt="{{ config('shop.branding.name', 'Server Shop') }}" class="shop-logo me-2">
                    @endif
                    @yield('shop-title', config('shop.branding.name', 'Server Shop'))
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    @auth
                        <div class="shop-user-info">
                            <div class="btn-group">
                                @if(config('shop.wallet.enabled'))
                                    <a href="{{ route('shop.wallet.index') }}" class="btn btn-outline-primary">
                                        <i class="fas fa-wallet"></i>
                                        <span class="wallet-balance" data-balance="{{ auth()->user()->wallet->balance ?? 0 }}">
                                            {{ config('shop.currency.symbol', '$') }}{{ number_format(auth()->user()->wallet->balance ?? 0, 2) }}
                                        </span>
                                    </a>
                                @endif
                                <a href="{{ route('shop.orders.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-shopping-bag"></i>
                                    My Orders
                                </a>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-info" id="cart-toggle" data-count="0">
                                        <i class="fas fa-shopping-cart"></i>
                                        Cart (<span class="cart-count">0</span>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('auth.login') }}" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i>
                            Login to Shop
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="content shop-container">
    <div class="container-fluid">
        
        {{-- Shop Navigation --}}
        <div class="row mb-3">
            <div class="col-12">
                <nav class="shop-navigation">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('shop.index') ? 'active' : '' }}" href="{{ route('shop.index') }}">
                                <i class="fas fa-home"></i>
                                Game Hosting
                            </a>
                        </li>
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('shop.orders.*') ? 'active' : '' }}" href="{{ route('shop.orders.index') }}">
                                    <i class="fas fa-shopping-bag"></i>
                                    My Orders
                                </a>
                            </li>
                            @if(config('shop.wallet.enabled'))
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('shop.wallet.*') ? 'active' : '' }}" href="{{ route('shop.wallet.index') }}">
                                        <i class="fas fa-wallet"></i>
                                        Wallet
                                    </a>
                                </li>
                            @endif
                        @endauth
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('shop.cart') ? 'active' : '' }}" href="{{ route('shop.cart') }}">
                                <i class="fas fa-shopping-cart"></i>
                                Cart (<span class="cart-count">0</span>)
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i>
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
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ session('error') }}
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
                <strong>Total: <span id="cart-total">{{ config('shop.currency.symbol', '$') }}0.00</span></strong>
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
    
    // Update wallet balance periodically
    @auth
    setInterval(function() {
        Shop.updateWalletBalance();
    }, 30000); // Update every 30 seconds
    @endauth
});
</script>
@endpush
