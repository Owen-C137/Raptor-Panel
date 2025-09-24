<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Cosmic Shop') | {{ config('app.name', 'Pterodactyl') }}</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('description', 'Explore the cosmic marketplace - Premium hosting services and digital products in the nebula.')">
    <meta name="keywords" content="hosting, minecraft, discord, servers, premium, cosmic, nebula">
    <meta name="author" content="{{ config('app.name', 'Pterodactyl') }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="@yield('title', 'Cosmic Shop') | {{ config('app.name', 'Pterodactyl') }}">
    <meta property="og:description" content="@yield('description', 'Explore the cosmic marketplace - Premium hosting services and digital products in the nebula.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name', 'Pterodactyl') }}">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Cosmic Shop') | {{ config('app.name', 'Pterodactyl') }}">
    <meta name="twitter:description" content="@yield('description', 'Explore the cosmic marketplace - Premium hosting services and digital products in the nebula.')">
    
    <!-- Preconnect for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Nebula Theme CSS Framework -->
    <link href="{{ route('shop.assets.css', 'nebula-theme.css') }}" rel="stylesheet">
    
    <!-- Inter Font for Cosmic Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- JetBrains Mono for Code Elements -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Additional Page Specific Styles -->
    @stack('styles')
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/favicons/apple-touch-icon.png">
    
    <!-- Theme Color for Mobile Browsers -->
    <meta name="theme-color" content="#0a0a0f">
    <meta name="msapplication-TileColor" content="#6366f1">
</head>

<body class="h-full">
    <!-- Cosmic Background Particles -->
    <div class="nebula-stardust"></div>
    
    <!-- Main Layout Container -->
    <div id="nebula-app" class="min-h-screen relative">
        
        <!-- 🌌 COSMIC HEADER -->
        <header class="nebula-header">
            <!-- Cosmic Particles in Header -->
            <div class="nebula-particles">
                <div class="nebula-particle"></div>
                <div class="nebula-particle"></div>
                <div class="nebula-particle"></div>
                <div class="nebula-particle"></div>
            </div>
            
            <!-- Main Navigation -->
            <nav class="nebula-nav">
                <!-- Nebula Brand -->
                <a href="{{ route('shop.catalog') }}" class="nebula-brand">
                    <div class="nebula-logo nebula-glow-pulse">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L13.09 8.26L19 7.27L15.18 12L19 16.73L13.09 15.74L12 22L10.91 15.74L5 16.73L8.82 12L5 7.27L10.91 8.26L12 2Z" fill="currentColor"/>
                            <circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.6"/>
                        </svg>
                    </div>
                    <span class="nebula-brand-text">Cosmic Shop</span>
                </a>
                
                <!-- Desktop Navigation Links -->
                <ul class="nebula-nav-links hidden lg:flex">
                    <li>
                        <a href="{{ route('shop.catalog') }}" 
                           class="nebula-nav-link {{ Route::is('shop.catalog') ? 'nebula-nav-link-active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7H3Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M3 7H21L20 19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19L3 7Z" stroke="currentColor" stroke-width="2"/>
                                <path d="M8 11V14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M16 11V14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span>Catalog</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="{{ route('shop.cart') }}" 
                           class="nebula-nav-link {{ Route::is('shop.cart') ? 'nebula-nav-link-active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V16.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="9" cy="20" r="1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="20" cy="20" r="1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Cart</span>
                            @if(session('cart') && count(session('cart')) > 0)
                                <span class="nebula-badge">{{ count(session('cart')) }}</span>
                            @endif
                        </a>
                    </li>
                    
                    @auth
                        <li>
                            <a href="{{ route('shop.orders') }}" 
                               class="nebula-nav-link {{ Route::is('shop.orders') ? 'nebula-nav-link-active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <polyline points="10,9 9,9 8,9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Orders</span>
                            </a>
                        </li>
                        
                        <li>
                            <a href="{{ route('shop.wallet') }}" 
                               class="nebula-nav-link {{ Route::is('shop.wallet') ? 'nebula-nav-link-active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>Wallet</span>
                                @if(isset($walletBalance))
                                    <span class="nebula-balance">${{ number_format($walletBalance, 2) }}</span>
                                @endif
                            </a>
                        </li>
                    @endauth
                </ul>
                
                <!-- User Account Section -->
                <div class="hidden lg:flex items-center gap-4">
                    @auth
                        <!-- User Avatar & Dropdown -->
                        <div class="relative">
                            <button class="nebula-btn nebula-btn-ghost nebula-flex nebula-items-center nebula-gap-sm" 
                                    onclick="toggleUserDropdown()">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        {{ substr(auth()->user()->name_first, 0, 1) }}{{ substr(auth()->user()->name_last, 0, 1) }}
                                    </span>
                                </div>
                                <span class="hidden md:inline">{{ auth()->user()->name_first }}</span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <polyline points="6,9 12,15 18,9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            
                            <!-- User Dropdown Menu -->
                            <div id="user-dropdown" class="absolute right-0 top-full mt-2 w-56 hidden">
                                <div class="nebula-card nebula-card-glass">
                                    <div class="nebula-card-body">
                                        <div class="flex items-center gap-3 mb-4">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                                                <span class="text-white font-bold">
                                                    {{ substr(auth()->user()->name_first, 0, 1) }}{{ substr(auth()->user()->name_last, 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-sm">{{ auth()->user()->name_first }} {{ auth()->user()->name_last }}</div>
                                                <div class="text-xs nebula-text-muted">{{ auth()->user()->email }}</div>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-1">
                                            <a href="{{ route('index') }}" class="nebula-dropdown-link">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <polyline points="9,22 9,12 15,12 15,22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span>Dashboard</span>
                                            </a>
                                            
                                            <a href="{{ route('account') }}" class="nebula-dropdown-link">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                                <span>Account Settings</span>
                                            </a>
                                            
                                            <div class="border-t border-gray-600 my-2"></div>
                                            
                                            <form method="POST" action="{{ route('auth.logout') }}">
                                                @csrf
                                                <button type="submit" class="nebula-dropdown-link w-full text-left">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <span>Sign Out</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Login/Register Buttons -->
                        <div class="flex items-center gap-3">
                            <a href="{{ route('auth.login') }}" class="nebula-btn nebula-btn-ghost">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M15 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <polyline points="10,17 15,12 10,7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Sign In</span>
                            </a>
                            
                            <a href="{{ route('auth.register') }}" class="nebula-btn nebula-btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="20" y1="8" x2="20" y2="14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <line x1="23" y1="11" x2="17" y2="11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span>Join Now</span>
                            </a>
                        </div>
                    @endauth
                </div>
                
                <!-- Mobile Menu Toggle -->
                <button class="nebula-nav-toggle lg:hidden" onclick="toggleMobileMenu()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="3" y1="12" x2="21" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <line x1="3" y1="18" x2="21" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </nav>
            
            <!-- Mobile Navigation Menu -->
            <div id="mobile-menu" class="lg:hidden hidden">
                <div class="nebula-card nebula-card-glass mx-4 mb-4">
                    <div class="nebula-card-body">
                        <!-- Mobile Navigation Links -->
                        <nav class="space-y-2">
                            <a href="{{ route('shop.catalog') }}" 
                               class="nebula-mobile-nav-link {{ Route::is('shop.catalog') ? 'active' : '' }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 7V5C3 3.89543 3.89543 3 5 3H19C20.1046 3 21 3.89543 21 5V7H3Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M3 7H21L20 19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19L3 7Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>Catalog</span>
                            </a>
                            
                            <a href="{{ route('shop.cart') }}" 
                               class="nebula-mobile-nav-link {{ Route::is('shop.cart') ? 'active' : '' }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V16.5" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="9" cy="20" r="1" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="20" cy="20" r="1" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <span>Cart</span>
                                @if(session('cart') && count(session('cart')) > 0)
                                    <span class="nebula-badge">{{ count(session('cart')) }}</span>
                                @endif
                            </a>
                            
                            @auth
                                <a href="{{ route('shop.orders') }}" 
                                   class="nebula-mobile-nav-link {{ Route::is('shop.orders') ? 'active' : '' }}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M14 2H6C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2Z" stroke="currentColor" stroke-width="2"/>
                                        <polyline points="14,2 14,8 20,8" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>Orders</span>
                                </a>
                                
                                <a href="{{ route('shop.wallet') }}" 
                                   class="nebula-mobile-nav-link {{ Route::is('shop.wallet') ? 'active' : '' }}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                                        <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>Wallet</span>
                                </a>
                                
                                <div class="border-t border-gray-600 my-3"></div>
                                
                                <a href="{{ route('index') }}" class="nebula-mobile-nav-link">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 9L12 2L21 9V20C21 20.5304 20.7893 21.0391 20.4142 21.4142C20.0391 21.7893 19.5304 22 19 22H5C4.46957 22 3.96086 21.7893 3.58579 21.4142C3.21071 21.0391 3 20.5304 3 20V9Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>Dashboard</span>
                                </a>
                                
                                <form method="POST" action="{{ route('auth.logout') }}">
                                    @csrf
                                    <button type="submit" class="nebula-mobile-nav-link w-full text-left">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H9" stroke="currentColor" stroke-width="2"/>
                                            <polyline points="16,17 21,12 16,7" stroke="currentColor" stroke-width="2"/>
                                            <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                        <span>Sign Out</span>
                                    </button>
                                </form>
                            @else
                                <div class="border-t border-gray-600 my-3"></div>
                                
                                <a href="{{ route('auth.login') }}" class="nebula-mobile-nav-link">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M15 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H15" stroke="currentColor" stroke-width="2"/>
                                        <polyline points="10,17 15,12 10,7" stroke="currentColor" stroke-width="2"/>
                                        <line x1="15" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>Sign In</span>
                                </a>
                                
                                <a href="{{ route('auth.register') }}" class="nebula-mobile-nav-link">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16 21V19C16 17.9391 15.5786 16.9217 14.8284 16.1716C14.0783 15.4214 13.0609 15 12 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="currentColor" stroke-width="2"/>
                                        <circle cx="8.5" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
                                        <line x1="20" y1="8" x2="20" y2="14" stroke="currentColor" stroke-width="2"/>
                                        <line x1="23" y1="11" x2="17" y2="11" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span>Join Now</span>
                                </a>
                            @endauth
                        </nav>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- 🌌 MAIN CONTENT AREA -->
        <main class="relative min-h-screen">
            <!-- Alert Messages -->
            @if(session('success'))
                <div class="nebula-alert nebula-alert-success nebula-appear">
                    <div class="max-w-7xl mx-auto px-4 py-3">
                        <div class="flex items-center gap-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="nebula-alert nebula-alert-error nebula-appear">
                    <div class="max-w-7xl mx-auto px-4 py-3">
                        <div class="flex items-center gap-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2"/>
                                <line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="nebula-alert nebula-alert-warning nebula-appear">
                    <div class="max-w-7xl mx-auto px-4 py-3">
                        <div class="flex items-center gap-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.29 3.86L1.82 18A2 2 0 0 0 3.68 21H20.32A2 2 0 0 0 22.18 18L13.71 3.86A2 2 0 0 0 10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="12" y1="9" x2="12" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <line x1="12" y1="17" x2="12.01" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>{{ session('warning') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="nebula-alert nebula-alert-error nebula-appear">
                    <div class="max-w-7xl mx-auto px-4 py-3">
                        <div class="flex items-start gap-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mt-0.5 flex-shrink-0">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2"/>
                                <line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <div>
                                <div class="font-semibold mb-1">Please fix the following errors:</div>
                                <ul class="space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li class="text-sm">• {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Page Content -->
            <div class="nebula-content">
                @yield('content')
            </div>
        </main>
        
        <!-- 🌌 COSMIC FOOTER -->
        <footer class="nebula-footer">
            <div class="max-w-7xl mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand Section -->
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="nebula-logo nebula-glow-sm">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2L13.09 8.26L19 7.27L15.18 12L19 16.73L13.09 15.74L12 22L10.91 15.74L5 16.73L8.82 12L5 7.27L10.91 8.26L12 2Z" fill="currentColor"/>
                                </svg>
                            </div>
                            <span class="nebula-brand-text text-xl">Cosmic Shop</span>
                        </div>
                        <p class="nebula-text-secondary max-w-md leading-relaxed">
                            Explore the infinite possibilities of our cosmic marketplace. Premium hosting services, 
                            digital products, and stellar experiences await in the nebula.
                        </p>
                        <div class="flex items-center gap-4 mt-6">
                            <a href="#" class="nebula-social-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                </svg>
                            </a>
                            <a href="#" class="nebula-social-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.317 4.492c-1.53-.69-3.17-1.2-4.885-1.49a.075.075 0 0 0-.079.036c-.21.369-.444.85-.608 1.23a18.566 18.566 0 0 0-5.487 0 12.36 12.36 0 0 0-.617-1.23A.077.077 0 0 0 8.562 3c-1.714.29-3.354.8-4.885 1.491a.07.07 0 0 0-.032.027C.533 9.093-.32 13.555.099 17.961a.08.08 0 0 0 .031.055 20.03 20.03 0 0 0 5.993 2.98.078.078 0 0 0 .084-.026 13.83 13.83 0 0 0 1.226-1.963.074.074 0 0 0-.041-.104 13.201 13.201 0 0 1-1.872-.878.075.075 0 0 1-.008-.125c.126-.093.252-.19.372-.287a.075.075 0 0 1 .078-.01c3.927 1.764 8.18 1.764 12.061 0a.075.075 0 0 1 .079.009c.12.098.246.195.372.288a.075.075 0 0 1-.006.125c-.598.344-1.22.635-1.873.877a.075.075 0 0 0-.041.105c.36.687.772 1.341 1.225 1.962a.077.077 0 0 0 .084.028 19.963 19.963 0 0 0 6.002-2.981.076.076 0 0 0 .032-.054c.5-5.094-.838-9.52-3.549-13.442a.06.06 0 0 0-.031-.028zM8.02 15.278c-1.182 0-2.157-1.069-2.157-2.38 0-1.312.956-2.38 2.157-2.38 1.21 0 2.176 1.077 2.157 2.38 0 1.312-.956 2.38-2.157 2.38zm7.975 0c-1.183 0-2.157-1.069-2.157-2.38 0-1.312.955-2.38 2.157-2.38 1.21 0 2.176 1.077 2.157 2.38 0 1.312-.946 2.38-2.157 2.38z"/>
                                </svg>
                            </a>
                            <a href="#" class="nebula-social-link">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.347-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.748-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div>
                        <h3 class="font-semibold text-lg mb-4 nebula-text-gradient">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('shop.catalog') }}" class="nebula-footer-link">Catalog</a></li>
                            <li><a href="{{ route('shop.cart') }}" class="nebula-footer-link">Shopping Cart</a></li>
                            @auth
                                <li><a href="{{ route('shop.orders') }}" class="nebula-footer-link">My Orders</a></li>
                                <li><a href="{{ route('shop.wallet') }}" class="nebula-footer-link">Wallet</a></li>
                            @endauth
                        </ul>
                    </div>
                    
                    <!-- Support -->
                    <div>
                        <h3 class="font-semibold text-lg mb-4 nebula-text-gradient">Support</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="nebula-footer-link">Help Center</a></li>
                            <li><a href="#" class="nebula-footer-link">Contact Us</a></li>
                            <li><a href="#" class="nebula-footer-link">Terms of Service</a></li>
                            <li><a href="#" class="nebula-footer-link">Privacy Policy</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Footer Bottom -->
                <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="nebula-text-muted text-sm">
                        © {{ date('Y') }} {{ config('app.name', 'Pterodactyl') }}. All rights reserved.
                    </div>
                    <div class="nebula-text-muted text-sm">
                        Powered by <span class="nebula-text-cosmic">Nebula Theme</span> • Made with ❤️ in the cosmos
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Additional JavaScript -->
    @stack('scripts')
    
    <!-- Nebula Theme JavaScript -->
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
        
        // User Dropdown Toggle
        function toggleUserDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('hidden');
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userDropdown = document.getElementById('user-dropdown');
            const mobileMenu = document.getElementById('mobile-menu');
            
            // Close user dropdown if clicking outside
            if (userDropdown && !userDropdown.closest('.relative').contains(event.target)) {
                userDropdown.classList.add('hidden');
            }
            
            // Close mobile menu if clicking outside
            if (mobileMenu && !mobileMenu.closest('header').contains(event.target)) {
                mobileMenu.classList.add('hidden');
            }
        });
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.nebula-alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Initialize cosmic effects on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add appear animation to cards
            const cards = document.querySelectorAll('.nebula-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('nebula-appear');
            });
            
            // Add cosmic cursor effect (optional - can be disabled for performance)
            if (window.matchMedia('(min-width: 1024px)').matches) {
                document.addEventListener('mousemove', function(e) {
                    const cursor = document.createElement('div');
                    cursor.className = 'cosmic-cursor';
                    cursor.style.cssText = `
                        position: fixed;
                        left: ${e.clientX}px;
                        top: ${e.clientY}px;
                        width: 6px;
                        height: 6px;
                        background: radial-gradient(circle, rgba(99, 102, 241, 0.6) 0%, transparent 70%);
                        border-radius: 50%;
                        pointer-events: none;
                        z-index: 9999;
                        animation: cosmic-fade 1s ease-out forwards;
                    `;
                    document.body.appendChild(cursor);
                    
                    setTimeout(() => cursor.remove(), 1000);
                });
                
                // Add cosmic cursor fade animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes cosmic-fade {
                        0% { opacity: 1; transform: scale(1); }
                        100% { opacity: 0; transform: scale(0.5); }
                    }
                `;
                document.head.appendChild(style);
            }
        });
    </script>
</body>
</html>
