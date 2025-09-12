<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Pterodactyl\Models\User;

class InjectShopNavigation
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only inject navigation on HTML responses
        if ($response->headers->get('Content-Type') && 
            strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            
            $this->injectAdminNavigation($response, $request);
            $this->injectClientNavigation($response, $request);
        }
        
        return $response;
    }
    
    private function injectAdminNavigation($response, Request $request)
    {
        // Only inject on admin pages
        if (!$request->is('admin*')) {
            return;
        }
        
        // Check if user has admin permissions
        $user = $request->user();
        if (!$user || !$user->root_admin) {
            return;
        }
        
        $content = $response->getContent();
        
        // Find the admin navigation menu (match Pterodactyl structure with collapsible dropdown)
        $shopNavigation = '
                        <li class="treeview shop-management-menu" data-shop-menu="true">
                            <a href="#" class="shop-toggle">
                                <i class="fa fa-shopping-bag"></i>
                                <span>Shop Management</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu" style="display: none;">
                                <li class="{{ Route::currentRouteName() == \'admin.shop.index\' || Route::currentRouteName() == \'admin.shop.dashboard\' ? \'active\' : \'\' }}">
                                    <a href="/admin/shop">
                                        <i class="fa fa-dashboard"></i> <span>Dashboard</span>
                                    </a>
                                </li>

                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.plans\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/plans">
                                        <i class="fa fa-list"></i> <span>Plans</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.categories\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/categories">
                                        <i class="fa fa-folder"></i> <span>Categories</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.orders\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/orders">
                                        <i class="fa fa-shopping-cart"></i> <span>Orders</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.payments\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/payments">
                                        <i class="fa fa-credit-card"></i> <span>Payments</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.coupons\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/coupons">
                                        <i class="fa fa-tag"></i> <span>Coupons</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.analytics\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/analytics">
                                        <i class="fa fa-bar-chart"></i> <span>Analytics</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.reports\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/reports">
                                        <i class="fa fa-line-chart"></i> <span>Reports</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.shop.settings\') ? \'active\' : \'\' }}">
                                    <a href="/admin/shop/settings">
                                        <i class="fa fa-gear"></i> <span>Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>';
        
        // JavaScript to initialize the shop management treeview
        $treeviewScript = '
        <style>
        .shop-management-menu .treeview-menu {
            padding-left: 0;
        }
        .shop-management-menu .treeview-menu li a {
            padding-left: 50px;
        }
        .shop-management-menu .shop-toggle {
            cursor: pointer;
        }
        .shop-management-menu .pull-right-container .fa {
            transition: transform 0.3s;
        }
        </style>
        <script>
        $(document).ready(function() {
            // Wait a bit to ensure DOM is fully loaded
            setTimeout(function() {
                // Initialize shop management treeview specifically
                var $shopMenu = $(\'.shop-management-menu\');
                
                if ($shopMenu.length > 0) {
                    var $toggleLink = $shopMenu.find(\'.shop-toggle\');
                    var $submenu = $shopMenu.find(\'.treeview-menu\');
                    var $icon = $toggleLink.find(\'.fa-angle-left\');
                    
                    // Check if current URL contains shop or any submenu item is active
                    var currentUrl = window.location.pathname;
                    var isOnShopPage = currentUrl.indexOf(\'/admin/shop\') !== -1;
                    var hasActiveChild = $submenu.find(\'li.active\').length > 0;
                    
                    if (isOnShopPage || hasActiveChild) {
                        // Open the menu if on shop page or has active child
                        $shopMenu.addClass(\'active\');
                        $submenu.show();
                        $icon.removeClass(\'fa-angle-left\').addClass(\'fa-angle-down\');
                        
                        // Store state to keep it open
                        sessionStorage.setItem(\'shopMenuOpen\', \'true\');
                    } else {
                        // Check if menu should stay open from previous navigation
                        var shouldStayOpen = sessionStorage.getItem(\'shopMenuOpen\');
                        if (shouldStayOpen === \'true\') {
                            $shopMenu.addClass(\'active\');
                            $submenu.show();
                            $icon.removeClass(\'fa-angle-left\').addClass(\'fa-angle-down\');
                        }
                    }
                    
                    // Handle toggle click
                    $toggleLink.on(\'click\', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if ($shopMenu.hasClass(\'active\')) {
                            // Close menu
                            $shopMenu.removeClass(\'active\');
                            $submenu.slideUp(300);
                            $icon.removeClass(\'fa-angle-down\').addClass(\'fa-angle-left\');
                            sessionStorage.setItem(\'shopMenuOpen\', \'false\');
                        } else {
                            // Open menu
                            $shopMenu.addClass(\'active\');
                            $submenu.slideDown(300);
                            $icon.removeClass(\'fa-angle-left\').addClass(\'fa-angle-down\');
                            sessionStorage.setItem(\'shopMenuOpen\', \'true\');
                        }
                    });
                    
                    // Prevent submenu clicks from closing the menu
                    $submenu.on(\'click\', function(e) {
                        e.stopPropagation();
                    });
                }
            }, 500);
        });
        </script>';
        
        // Try multiple patterns to inject shop navigation (match Pterodactyl structure)
        $patterns = [
            // Pattern 1: After SERVICE MANAGEMENT section
            '/(<li class="header">SERVICE MANAGEMENT<\/li>.*?<\/li>)(\s*<li class="header">)/s',
            // Pattern 2: After MANAGEMENT section  
            '/(<li class="header">MANAGEMENT<\/li>.*?<\/li>)(\s*<li class="header">)/s',
            // Pattern 3: After any management section with users
            '/(<a href="[^"]*\/admin\/users[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 4: After settings menu item
            '/(<a href="[^"]*\/admin\/settings[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 5: Fallback - before closing ul in sidebar
            '/(<\/ul>\s*<\/section>)/s'
        ];
        
        $injected = false;
        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $content)) {
                if ($i <= 1) {
                    // Patterns 0-1: Inject between sections
                    $content = preg_replace($pattern, '$1' . $shopNavigation . '$2', $content);
                } elseif ($i <= 3) {
                    // Patterns 2-3: Inject after specific menu items
                    $content = preg_replace($pattern, '$1' . $shopNavigation, $content);
                } else {
                    // Pattern 4: Fallback - inject before closing element
                    $content = preg_replace($pattern, $shopNavigation . '$1', $content);
                }
                $injected = true;
                \Log::info('Shop navigation injection successful using pattern ' . ($i + 1));
                break;
            }
        }
        
        // Inject the JavaScript for treeview functionality if navigation was injected
        if ($injected) {
            // Try to inject script before closing </body> tag
            if (preg_match('/<\/body>/i', $content)) {
                $content = preg_replace('/<\/body>/i', $treeviewScript . '</body>', $content);
            } else {
                // Fallback: inject before closing </html> tag
                $content = preg_replace('/<\/html>/i', $treeviewScript . '</html>', $content);
            }
        }
        
        // Debug: log injection attempt (remove this in production)
        if (!$injected && $user && $user->root_admin) {
            \Log::info('Shop navigation injection failed - no matching patterns found');
        }
        
        $response->setContent($content);
    }
    
    private function injectClientNavigation($response, Request $request)
    {
        // Only inject on client pages (not admin, auth, API, or shop pages)
        if ($request->is('admin*') || 
            $request->is('auth*') || 
            $request->is('api*') || 
            $request->is('shop*')) {
            return;
        }
        
        $content = $response->getContent();
        
        // Get credits enabled setting
        $creditsEnabled = \PterodactylAddons\ShopSystem\Models\ShopSettings::getValue('credits_enabled', true);
        $walletNavItem = $creditsEnabled ? '<li><a class="dropdown-item" href="/shop/wallet"><i class="fa fa-wallet me-2"></i>Wallet</a></li>' : '';
        
        // Inject shop navigation into client area
        $shopClientNav = '
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="shopDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fa fa-shopping-bag me-1"></i> Shop
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/shop"><i class="fa fa-home me-2"></i>Browse Products</a></li>
                    <li><a class="dropdown-item" href="/shop/cart"><i class="fa fa-shopping-cart me-2"></i>Shopping Cart <span class="badge bg-primary" id="cart-count">0</span></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/shop/orders"><i class="fa fa-list me-2"></i>My Orders</a></li>
                    ' . $walletNavItem . '
                    <li><a class="dropdown-item" href="/shop/dashboard"><i class="fa fa-dashboard me-2"></i>Shop Dashboard</a></li>
                </ul>
            </li>';
        
        // Try to inject into client navigation
        $patterns = [
            // Bootstrap navbar pattern
            '/<ul class="navbar-nav[^"]*"[^>]*>/',
            // Alternative pattern
            '/<div class="collapse navbar-collapse[^"]*"[^>]*>/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$0' . $shopClientNav, $content);
                break;
            }
        }
        
        $response->setContent($content);
    }
}
