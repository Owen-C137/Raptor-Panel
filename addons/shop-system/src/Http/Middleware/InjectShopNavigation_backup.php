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
        
        // Only inject navigation if shop is installed and enabled
        if (!$this->isShopInstalled()) {
            return $response;
        }
        
        // Only inject navigation on HTML responses
        if ($response->headers->get('Content-Type') && 
            strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            
            $this->injectAdminNavigation($response, $request);
            $this->injectClientNavigation($response, $request);
            $this->injectServerNavigation($response, $request);
            $this->injectDashboardServerOverlays($response, $request);
        }
        
        return $response;
    }
    
    /**
     * Check if shop is installed
     */
    private function isShopInstalled(): bool
    {
        return config('shop') !== null && 
               \Illuminate\Support\Facades\Schema::hasTable('shop_settings');
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
            // Pattern 1: After the last item in SERVICE MANAGEMENT section (Nests)
            '/(<a href="[^"]*\/admin\/nests[^"]*">[^<]*<i class="fa fa-th-large"><\/i>[^<]*<span>Nests<\/span>[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 2: After SERVICE MANAGEMENT section  
            '/(<li class="header">SERVICE MANAGEMENT<\/li>.*?<\/li>)(\s*<\/ul>)/s',
            // Pattern 3: After MANAGEMENT section
            '/(<li class="header">MANAGEMENT<\/li>.*?<\/li>)(\s*<li class="header">)/s',
            // Pattern 4: After any management section with users
            '/(<a href="[^"]*\/admin\/users[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 5: After settings menu item
            '/(<a href="[^"]*\/admin\/settings[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 6: Fallback - before closing ul in sidebar
            '/(<\/ul>\s*<\/section>)/s'
        ];
        
        $injected = false;
        foreach ($patterns as $i => $pattern) {
            if (preg_match($pattern, $content)) {
                if ($i == 0) {
                    // Pattern 0: Inject after Nests item
                    $content = preg_replace($pattern, '$1' . $shopNavigation, $content);
                } elseif ($i == 1) {
                    // Pattern 1: Inject before closing ul after SERVICE MANAGEMENT
                    $content = preg_replace($pattern, '$1' . $shopNavigation . '$2', $content);
                } elseif ($i <= 4) {
                    // Patterns 2-4: Inject after specific menu items
                    $content = preg_replace($pattern, '$1' . $shopNavigation, $content);
                } else {
                    // Pattern 5: Fallback - inject before closing element
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
        
        // Check if shop is enabled
        if (!config('shop.enabled', true)) {
            return;
        }
        
        // Inject shop navigation into React NavigationBar
        $shopClientNavScript = '
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Wait for React to render the navigation
            setTimeout(function() {
                injectShopNavigation();
            }, 1000);
            
            // Also try to inject after React router changes
            var observer = new MutationObserver(function(mutations) {
                injectShopNavigation();
            });
            
            if (document.querySelector(".NavigationBar__RightNavigation-sc-tupl2x-0, [class*=RightNavigation]")) {
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
            
            function injectShopNavigation() {
                // Find the right navigation container
                var rightNav = document.querySelector(".NavigationBar__RightNavigation-sc-tupl2x-0, [class*=RightNavigation]");
                if (!rightNav) {
                    return;
                }
                
                // Check if shop link already exists
                if (document.querySelector("#shop-nav-link")) {
                    return;
                }
                
                // Find the admin link (last item before account) to insert before it
                var adminLink = rightNav.querySelector("a[href=\"/admin\"]");
                var accountLink = rightNav.querySelector("a[href=\"/account\"]");
                
                if (!accountLink) {
                    return;
                }
                
                // Create shop navigation link
                var shopLink = document.createElement("div");
                shopLink.innerHTML = `
                    <a href="/shop" id="shop-nav-link" style="
                        display: flex;
                        align-items: center;
                        height: 100%;
                        text-decoration: none;
                        color: rgb(212, 212, 216);
                        padding-left: 1.5rem;
                        padding-right: 1.5rem;
                        cursor: pointer;
                        transition: all 150ms;
                    " onmouseover="this.style.color=\'rgb(245, 245, 245)\'; this.style.backgroundColor=\'rgb(0, 0, 0)\';" 
                       onmouseout="this.style.color=\'rgb(212, 212, 216)\'; this.style.backgroundColor=\'transparent\';"
                       title="Shop">
                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="shopping-bag" 
                             class="svg-inline--fa fa-shopping-bag fa-w-14" role="img" xmlns="http://www.w3.org/2000/svg" 
                             viewBox="0 0 448 512" style="width: 1em; height: 1em;">
                            <path fill="currentColor" d="M352 160v-32C352 57.42 294.579 0 224 0 153.42 0 96 57.42 96 128v32H0v272c0 44.183 35.817 80 80 80h288c44.183 0 80-35.817 80-80V160h-96zM224 48c44.112 0 80 35.888 80 80v32H144v-32c0-44.112 35.888-80 80-80zm176 384c0 17.645-14.355 32-32 32H80c-17.645 0-32-14.355-32-32V208h48v40c0 13.255 10.745 24 24 24s24-10.745 24-24v-40h160v40c0 13.255 10.745 24 24 24s24-10.745 24-24v-40h48v224z"></path>
                        </svg>
                    </a>
                `;
                
                // Insert before account link
                accountLink.parentNode.insertBefore(shopLink.firstElementChild, accountLink);
                
                console.log("Shop navigation injected successfully");
            }
        });
        </script>';
        
        // Try to inject the script before closing </body> tag
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $shopClientNavScript . '</body>', $content);
            $response->setContent($content);
            \Illuminate\Support\Facades\Log::info('Shop navigation injection successful using pattern 1');
        } else if (strpos($content, '</html>') !== false) {
            // Fallback: inject before closing </html> tag
            $content = str_replace('</html>', $shopClientNavScript . '</html>', $content);
            $response->setContent($content);
            \Illuminate\Support\Facades\Log::info('Shop navigation injection successful using pattern 2');
        } else {
            \Illuminate\Support\Facades\Log::info('Shop navigation injection failed - no matching patterns found');
        }
    }

    /**
     * Inject server plan management navigation
     */
    private function injectServerNavigation($response, Request $request)
    {
        // Only inject on server pages
        if (!preg_match('/^\/server\/[a-zA-Z0-9-]+/', $request->getPathInfo())) {
            return;
        }

        // Extract server identifier from URL
        preg_match('/^\/server\/([a-zA-Z0-9-]+)/', $request->getPathInfo(), $matches);
        if (!isset($matches[1])) {
            return;
        }
        $serverId = $matches[1];

        $content = $response->getContent();

        // Check if shop is enabled
        if (!config('shop.enabled', true)) {
            return;
        }

        // Inject manage plan navigation into server sub-navigation
        $managePlanNavScript = "
        <script>
        (function() {
            var injected = false;
            var retryCount = 0;
            var maxRetries = 100; // 10 seconds max for initial load
            var isInitialLoad = true;
            
            function injectManagePlanNav() {
                if (injected) return;
                
                // Find the server sub-navigation container and ensure it's fully populated
                var subNav = document.querySelector('.SubNavigation-sc-lfuaoi-0 > div, [class*=SubNavigation] > div, [class*=\"SubNavigation\"] > div');
                if (!subNav) {
                    if (retryCount < maxRetries) {
                        retryCount++;
                        setTimeout(injectManagePlanNav, isInitialLoad ? 200 : 100);
                    }
                    return;
                }
                
                // On initial load, make sure the navigation has actual links (not just loading)
                var navLinks = subNav.querySelectorAll('a');
                if (isInitialLoad && navLinks.length < 2) {
                    // Navigation hasn't fully loaded yet, wait longer
                    if (retryCount < maxRetries) {
                        retryCount++;
                        setTimeout(injectManagePlanNav, 300);
                    }
                    return;
                }
                
                // Check if manage plan link already exists
                if (document.querySelector('#manage-plan-nav')) {
                    injected = true;
                    return;
                }
                
                // Find the Settings link to insert after it
                var settingsLink = null;
                var links = subNav.querySelectorAll('a');
                links.forEach(function(link) {
                    if (link.textContent.trim() === 'Settings') {
                        settingsLink = link;
                    }
                });
                
                if (!settingsLink) {
                    if (retryCount < maxRetries) {
                        retryCount++;
                        setTimeout(injectManagePlanNav, isInitialLoad ? 300 : 100);
                    }
                    return;
                }
                
                // Create manage plan navigation link with exact React structure
                var managePlanLink = settingsLink.cloneNode(false); // Clone the structure
                managePlanLink.href = '/server/{$serverId}/manage-plan';
                managePlanLink.id = 'manage-plan-nav';
                managePlanLink.textContent = 'Manage Plan';
                
                // Remove any active state classes
                managePlanLink.className = managePlanLink.className.replace(/active/gi, '');
                
                // Insert after settings link
                if (settingsLink.nextSibling) {
                    subNav.insertBefore(managePlanLink, settingsLink.nextSibling);
                } else {
                    subNav.appendChild(managePlanLink);
                }
                
                // Mark as no longer initial load after first successful injection
                isInitialLoad = false;
                
                // Immediate visibility check and fix
                requestAnimationFrame(function() {
                    // Ensure visibility
                    if (managePlanLink.offsetParent === null) {
                        managePlanLink.style.display = window.getComputedStyle(settingsLink).display;
                        managePlanLink.style.visibility = 'visible';
                    }
                    
                    // Log with debugging info
                    console.log('Manage Plan navigation injected successfully', {
                        visible: managePlanLink.offsetParent !== null,
                        display: window.getComputedStyle(managePlanLink).display,
                        visibility: window.getComputedStyle(managePlanLink).visibility,
                        isInitialLoad: isInitialLoad
                    });
                });
                
                injected = true;
            }
            
            // Start trying immediately but with longer intervals on initial load
            setTimeout(injectManagePlanNav, 100);
            
            // Watch for DOM changes (React router navigation)
            var observer = new MutationObserver(function(mutations) {
                if (!injected) {
                    injectManagePlanNav();
                }
            });
            
            // Start observing the document for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: false
            });
            
            // Also listen for React router history changes
            if (window.history && window.history.pushState) {
                var originalPushState = window.history.pushState;
                window.history.pushState = function() {
                    originalPushState.apply(history, arguments);
                    injected = false;
                    retryCount = 0;
                    isInitialLoad = false; // Subsequent navigations are not initial load
                    setTimeout(injectManagePlanNav, 50);
                };
                
                var originalReplaceState = window.history.replaceState;
                window.history.replaceState = function() {
                    originalReplaceState.apply(history, arguments);
                    injected = false;
                    retryCount = 0;
                    isInitialLoad = false;
                    setTimeout(injectManagePlanNav, 50);
                };
            }
            
            // Listen for popstate events (back/forward button)
            window.addEventListener('popstate', function() {
                injected = false;
                retryCount = 0;
                isInitialLoad = false;
                setTimeout(injectManagePlanNav, 50);
            });
        })();
        </script>";

        // Try to inject the script before closing </body> tag
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $managePlanNavScript . '</body>', $content);
            $response->setContent($content);
            \Illuminate\Support\Facades\Log::info('Server plan navigation injection successful');
        } else if (strpos($content, '</html>') !== false) {
            // Fallback: inject before closing </html> tag
            $content = str_replace('</html>', $managePlanNavScript . '</html>', $content);
            $response->setContent($content);
            \Illuminate\Support\Facades\Log::info('Server plan navigation injection successful (fallback)');
        }
    }

    private function injectDashboardServerOverlays($response, Request $request)
    {
        // Check if we're on the main dashboard page
        $currentPath = $request->getPathInfo();
        $isRootPath = $currentPath === '/';
        $isHomePage = $request->route() && $request->route()->getName() === 'index';
        
        \Illuminate\Support\Facades\Log::info('Dashboard overlay check', [
            'path' => $currentPath, 
            'is_root' => $isRootPath,
            'is_home' => $isHomePage,
            'route_name' => $request->route() ? $request->route()->getName() : 'no_route'
        ]);
        
        // Only inject on dashboard/home page (root path or named index route)
        if (!$isRootPath && !$isHomePage) {
            return;
        }

        $content = $response->getContent();
        
        // Skip content check - servers are loaded dynamically in React
        \Illuminate\Support\Facades\Log::info('Dashboard content check', [
            'content_length' => strlen($content),
            'note' => 'Skipping server content detection - React loads dynamically'
        ]);

        $user = $request->user();
        if (!$user) {
            return;
        }

        // Get servers with cancelled plans for this user
        $cancelledServers = \PterodactylAddons\ShopSystem\Models\ShopOrder::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->with('server:id,uuid,uuidShort')
            ->get()
            ->pluck('server')
            ->filter()
            ->map(function($server) {
                return [
                    'id' => $server->id,
                    'uuid' => $server->uuid,
                    'uuidShort' => $server->uuidShort,
                ];
            })
            ->toArray();

        if (empty($cancelledServers)) {
            return;
        }

        // Create JavaScript to overlay cancelled servers
        $cancelledServersScript = "
        <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        @keyframes modalSlideIn {
            from { 
                transform: scale(0.9) translateY(-20px); 
                opacity: 0; 
            }
            to { 
                transform: scale(1) translateY(0); 
                opacity: 1; 
            }
        }
        
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutToRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        /* Pre-hide cancelled servers instantly */
        " . $this->generateServerHidingCSS($cancelledServers) . "
        </style>
        <script>
        (function() {
            const cancelledServers = " . json_encode($cancelledServers) . ";
            let overlayCheckInterval;
            let overlayApplied = false;
            
            console.log('Dashboard overlay script loaded. Cancelled servers:', cancelledServers);
            
            function applyServerOverlays(attempts = 0) {
                if (cancelledServers.length === 0) {
                    console.log('No cancelled servers to overlay');
                    return;
                }
                
                console.log('Checking for server elements...');
                
                // Debug: Log what's actually on the page
                console.log('Page title:', document.title);
                console.log('All links on page:', document.querySelectorAll('a').length);
                console.log('Links with /server/ in href:', document.querySelectorAll('a[href*=\"/server/\"]').length);
                
                // Sample some links to see what's available
                const allLinks = Array.from(document.querySelectorAll('a')).slice(0, 10);
                console.log('First 10 links on page:', allLinks.map(a => ({ href: a.href, text: a.textContent.trim().substring(0, 50) })));
                
                // Try multiple selectors for different React components and rendered states
                const serverSelectors = [
                    // Direct server links
                    'a[href^=\"/server/\"]',
                    'a[href*=\"/server/\"]',
                    // Data attributes
                    '[data-server-uuid]',
                    '[data-server-id]',
                    // Common class patterns in React components
                    '.server-row a',
                    '.ServerRow a', 
                    '.server-card a',
                    '.server-item a',
                    '.server-list a',
                    // Generic patterns that might contain server links
                    'div[class*=\"server\"] a',
                    'div[class*=\"Server\"] a',
                    // Look for any links in containers that might be servers
                    'div[class*=\"grid\"] a[href*=\"/\"]',
                    'div[class*=\"list\"] a[href*=\"/\"]'
                ];
                
                let foundLinks = [];
                let totalChecked = 0;
                
                serverSelectors.forEach(selector => {
                    try {
                        const links = document.querySelectorAll(selector);
                        totalChecked += links.length;
                        
                        if (links.length > 0) {
                            console.log('Found', links.length, 'links with selector:', selector);
                            
                            // Filter to only server-like links
                            Array.from(links).forEach(link => {
                                const href = link.getAttribute('href');
                                if (href && (href.includes('/server/') || link.hasAttribute('data-server-uuid'))) {
                                    foundLinks.push(link);
                                    console.log('Added server link:', href);
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Error with selector', selector, ':', e);
                    }
                });
                
                console.log('Total elements checked:', totalChecked, 'Server links found:', foundLinks.length);
                
                // Remove duplicates
                const uniqueLinks = [...new Set(foundLinks)];
                console.log('Unique server links:', uniqueLinks.length);
                
                if (uniqueLinks.length === 0) {
                    console.log('No server links found yet, will retry...');
                    
                    // If we've tried many times and still no links, create a fallback notice
                    if (attempts > 15) {
                        console.log('Creating fallback notification for cancelled servers...');
                        createFallbackNotification();
                        return true; // Stop retrying
                    }
                    
                    return false; // Indicate we should keep trying
                }
                
                let overlaysApplied = 0;
                
                uniqueLinks.forEach(link => {
                    let serverUuid = null;
                    
                    // Try to extract UUID from href
                    const href = link.getAttribute('href');
                    if (href && href.includes('/server/')) {
                        const parts = href.split('/server/');
                        if (parts.length > 1) {
                            serverUuid = parts[1].split('/')[0];
                        }
                    }
                    
                    // Try to get UUID from data attributes
                    if (!serverUuid) {
                        serverUuid = link.getAttribute('data-server-uuid') || 
                                    link.getAttribute('data-server-id');
                    }
                    
                    // Try parent elements for data attributes
                    if (!serverUuid) {
                        const parent = link.closest('[data-server-uuid], [data-server-id]');
                        if (parent) {
                            serverUuid = parent.getAttribute('data-server-uuid') || 
                                        parent.getAttribute('data-server-id');
                        }
                    }
                    
                    console.log('Processing server link:', href, 'UUID:', serverUuid);
                    
                    if (serverUuid) {
                        // Check if this server has a cancelled plan (more flexible matching)
                        const cancelledServer = cancelledServers.find(server => {
                            // Try various matching strategies
                            return server.uuid === serverUuid || 
                                   server.uuidShort === serverUuid ||
                                   server.uuid.startsWith(serverUuid) ||
                                   serverUuid.startsWith(server.uuidShort) ||
                                   (serverUuid.length >= 8 && server.uuidShort.startsWith(serverUuid.substring(0, 8)));
                        });
                        
                        if (cancelledServer) {
                            console.log('Found cancelled server, applying overlay:', cancelledServer);
                            if (applyServerOverlay(link, serverUuid, cancelledServer)) {
                                overlaysApplied++;
                            }
                        }
                    }
                });
                
                if (overlaysApplied > 0) {
                    console.log('Applied', overlaysApplied, 'server overlays');
                    overlayApplied = true;
                    return true; // Success
                }
                
                return false; // Keep trying
            }
            
            function applyServerOverlay(serverLink, serverUuid, cancelledServer) {
                // Don't apply overlay twice
                if (serverLink.classList.contains('shop-cancelled-server')) {
                    console.log('Overlay already applied to server:', serverUuid);
                    
                    // Debug: Check if the overlay actually exists and is visible
                    const existingOverlay = document.getElementById('overlay-' + serverUuid.substring(0, 8));
                    if (existingOverlay) {
                        console.log('Existing overlay found:', existingOverlay.id, 'Display:', existingOverlay.style.display, 'Visibility:', existingOverlay.style.visibility);
                        console.log('Overlay parent:', existingOverlay.parentElement ? existingOverlay.parentElement.tagName : 'none');
                        console.log('Overlay computed styles:', window.getComputedStyle(existingOverlay).display, window.getComputedStyle(existingOverlay).visibility, window.getComputedStyle(existingOverlay).opacity);
                    } else {
                        console.log('Overlay marked as applied but element not found - re-applying');
                        serverLink.classList.remove('shop-cancelled-server');
                        // Continue with applying overlay
                    }
                    
                    if (existingOverlay) {
                        return false;
                    }
                }
                
                console.log('Applying overlay to server:', serverUuid, cancelledServer);
                
                serverLink.classList.add('shop-cancelled-server');
                
                // Find the server row container (look for the actual server row)
                let serverElement = serverLink;
                
                // Try to find the server row container by traversing up the DOM
                let parent = serverLink.parentElement;
                while (parent && !parent.classList.contains('GreyRowBox-sc-1xo9c6v-0') && parent !== document.body) {
                    if (parent.className && (
                        parent.className.includes('server') ||
                        parent.className.includes('Server') ||
                        parent.className.includes('Row') ||
                        parent.className.includes('card') ||
                        parent.className.includes('GreyRowBox')
                    )) {
                        serverElement = parent;
                        break;
                    }
                    parent = parent.parentElement;
                }
                
                console.log('Selected server container:', serverElement.tagName, serverElement.className);
                
                // Force container properties
                serverElement.style.position = 'relative !important';
                serverElement.style.minHeight = '80px !important';
                
                // Disable all interactions on the container
                serverElement.style.pointerEvents = 'none !important';
                
                // Create red overlay with maximum strength CSS
                const overlay = document.createElement('div');
                overlay.className = 'shop-server-cancelled-overlay';
                overlay.id = 'overlay-' + serverUuid.substring(0, 8);
                
                // Use setAttribute to force styles that can't be overridden
                overlay.setAttribute('style', `
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    min-height: 80px !important;
                    background: rgba(239, 68, 68, 0.9) !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    z-index: 999999 !important;
                    border-radius: 8px !important;
                    cursor: pointer !important;
                    pointer-events: auto !important;
                    opacity: 0.9 !important;
                    visibility: visible !important;
                    border: 3px solid red !important;
                `);
                
                // Alternative: Also set via style object as backup
                Object.assign(overlay.style, {
                    position: 'absolute',
                    top: '0',
                    left: '0',
                    right: '0', 
                    bottom: '0',
                    width: '100%',
                    height: '100%',
                    minHeight: '80px',
                    background: 'rgba(239, 68, 68, 0.9)',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    zIndex: '999999',
                    borderRadius: '8px',
                    cursor: 'pointer',
                    pointerEvents: 'auto',
                    opacity: '0.9',
                    visibility: 'visible',
                    border: '3px solid red'
                });
                
                // Create cancelled message
                const message = document.createElement('div');
                message.style.cssText = `
                    color: white !important;
                    font-weight: bold !important;
                    text-align: center !important;
                    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.7) !important;
                    padding: 1rem !important;
                    border-radius: 6px !important;
                    background: rgba(0, 0, 0, 0.4) !important;
                    border: 2px solid rgba(255, 255, 255, 0.3) !important;
                    max-width: 200px !important;
                `;
                message.innerHTML = `
                    <div style=\"margin-bottom: 0.5rem; font-size: 24px;\">🚫</div>
                    <div style=\"font-size: 16px; line-height: 1.3; margin-bottom: 0.25rem;\">
                        Plan Cancelled
                    </div>
                    <div style=\"font-size: 12px; opacity: 0.9; line-height: 1.2;\">
                        Click to purchase new plan
                    </div>
                `;
                
                overlay.appendChild(message);
                serverElement.appendChild(overlay);
                
                console.log('Overlay added to server element:', serverElement.tagName, serverElement.className, 'Overlay ID:', overlay.id);
                console.log('Server element dimensions:', serverElement.getBoundingClientRect());
                console.log('Overlay dimensions:', overlay.getBoundingClientRect());
                console.log('Overlay styles - display:', overlay.style.display, 'opacity:', overlay.style.opacity, 'visibility:', overlay.style.visibility);
                console.log('Server element styles - position:', serverElement.style.position, 'opacity:', serverElement.style.opacity);
                
                // Force overlay to be extremely visible
                setTimeout(() => {
                    overlay.style.setProperty('background-color', 'red', 'important');
                    overlay.style.setProperty('border', '5px solid yellow', 'important');
                    overlay.style.setProperty('z-index', '999999', 'important');
                    console.log('Applied extreme visibility styles after timeout');
                }, 100);
                
                // Add click handler to show shop redirect
                overlay.addEventListener('click', function(e) {
                    console.log('Cancelled server overlay clicked for server:', serverUuid);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    showCancellationModal(serverUuid);
                });
                
                // Add hover effect
                overlay.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                overlay.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
                
                return true; // Successfully applied
            }
            
            // Styled modal for cancellation confirmation
            function showCancellationModal(serverUuid) {
                // Create modal backdrop
                const backdrop = document.createElement('div');
                backdrop.id = 'shop-cancellation-modal-backdrop';
                backdrop.style.cssText = `
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100vw !important;
                    height: 100vh !important;
                    background: rgba(0, 0, 0, 0.7) !important;
                    backdrop-filter: blur(4px) !important;
                    z-index: 99999 !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    animation: fadeIn 0.3s ease !important;
                `;
                
                // Create modal container
                const modal = document.createElement('div');
                modal.style.cssText = `
                    background: linear-gradient(135deg, #1f2937, #374151) !important;
                    border-radius: 16px !important;
                    padding: 2rem !important;
                    max-width: 400px !important;
                    width: 90% !important;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
                    border: 2px solid rgba(239, 68, 68, 0.3) !important;
                    transform: scale(0.9) !important;
                    animation: modalSlideIn 0.3s ease forwards !important;
                    color: white !important;
                    font-family: system-ui, -apple-system, sans-serif !important;
                `;
                
                // Modal content
                modal.innerHTML = `
                    <div style=\"text-align: center; margin-bottom: 1.5rem;\">
                        <div style=\"
                            width: 80px; 
                            height: 80px; 
                            margin: 0 auto 1rem; 
                            background: linear-gradient(135deg, #ef4444, #dc2626); 
                            border-radius: 50%; 
                            display: flex; 
                            align-items: center; 
                            justify-content: center;
                            font-size: 40px;
                        \">🚫</div>
                        <h2 style=\"margin: 0 0 0.5rem; font-size: 24px; font-weight: bold; color: #ef4444;\">
                            Plan Cancelled
                        </h2>
                        <p style=\"margin: 0; font-size: 16px; color: #d1d5db; line-height: 1.5;\">
                            The plan for server <strong style=\"color: white;\">\${serverUuid}</strong> has been cancelled.
                        </p>
                    </div>
                    
                    <div style=\"margin-bottom: 1.5rem;\">
                        <p style=\"margin: 0; font-size: 14px; color: #9ca3af; text-align: center; line-height: 1.4;\">
                            You need an active plan to access this server. Would you like to purchase a new plan from our shop?
                        </p>
                    </div>
                    
                    <div style=\"display: flex; gap: 1rem; justify-content: center;\">
                        <button id=\"shop-modal-cancel\" style=\"
                            background: transparent;
                            border: 2px solid #6b7280;
                            color: #d1d5db;
                            padding: 0.75rem 1.5rem;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: 500;
                            transition: all 0.2s ease;
                        \">Cancel</button>
                        
                        <button id=\"shop-modal-confirm\" style=\"
                            background: linear-gradient(135deg, #059669, #047857);
                            border: none;
                            color: white;
                            padding: 0.75rem 1.5rem;
                            border-radius: 8px;
                            cursor: pointer;
                            font-size: 14px;
                            font-weight: 600;
                            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
                            transition: all 0.2s ease;
                        \">Go to Shop</button>
                    </div>
                `;
                
                backdrop.appendChild(modal);
                document.body.appendChild(backdrop);
                
                // Add hover effects
                const cancelBtn = modal.querySelector('#shop-modal-cancel');
                const confirmBtn = modal.querySelector('#shop-modal-confirm');
                
                cancelBtn.addEventListener('mouseenter', function() {
                    this.style.borderColor = '#9ca3af';
                    this.style.color = 'white';
                });
                
                cancelBtn.addEventListener('mouseleave', function() {
                    this.style.borderColor = '#6b7280';
                    this.style.color = '#d1d5db';
                });
                
                confirmBtn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px rgba(5, 150, 105, 0.4)';
                });
                
                confirmBtn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 12px rgba(5, 150, 105, 0.3)';
                });
                
                // Event handlers
                function closeModal() {
                    backdrop.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        if (backdrop.parentNode) {
                            backdrop.parentNode.removeChild(backdrop);
                        }
                    }, 300);
                }
                
                cancelBtn.addEventListener('click', closeModal);
                backdrop.addEventListener('click', function(e) {
                    if (e.target === backdrop) closeModal();
                });
                
                confirmBtn.addEventListener('click', function() {
                    closeModal();
                    setTimeout(() => {
                        window.location.href = '/shop';
                    }, 100);
                });
                
                // Close on Escape
                function handleEscape(e) {
                    if (e.key === 'Escape') {
                        closeModal();
                        document.removeEventListener('keydown', handleEscape);
                    }
                }
                document.addEventListener('keydown', handleEscape);
            }
            
            // Fallback notification when server elements can't be found
            function createFallbackNotification() {
                console.log('Creating fallback notification for cancelled servers');
                
                // Create a floating notification
                const notification = document.createElement('div');
                notification.id = 'shop-cancelled-servers-notification';
                notification.style.cssText = `
                    position: fixed !important;
                    top: 20px !important;
                    right: 20px !important;
                    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
                    color: white !important;
                    padding: 1rem 1.5rem !important;
                    border-radius: 8px !important;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
                    z-index: 99999 !important;
                    font-family: system-ui, -apple-system, sans-serif !important;
                    max-width: 350px !important;
                    cursor: pointer !important;
                    animation: slideInFromRight 0.3s ease !important;
                `;
                
                notification.innerHTML = `
                    <div style=\"display: flex; align-items: center; gap: 0.75rem;\">
                        <div style=\"font-size: 24px;\">🚫</div>
                        <div>
                            <div style=\"font-weight: bold; font-size: 14px; margin-bottom: 0.25rem;\">
                                Cancelled Server Plans
                            </div>
                            <div style=\"font-size: 12px; opacity: 0.9;\">
                                You have \" + cancelledServers.length + \" server\" + (cancelledServers.length > 1 ? 's' : '') + \" with cancelled plans. Click to visit shop.
                            </div>
                        </div>
                        <div style=\"font-size: 18px; opacity: 0.7;\">×</div>
                    </div>
                `;
                
                // Add click handler
                notification.addEventListener('click', function() {
                    showCancellationModal('multiple servers');
                });
                
                document.body.appendChild(notification);
                
                // Auto-hide after 10 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.style.animation = 'slideOutToRight 0.3s ease';
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.parentNode.removeChild(notification);
                            }
                        }, 300);
                    }
                }, 10000);
            }
            
            // Smart retry logic for dynamic content
            function startOverlaySystem() {
                let attempts = 0;
                const maxAttempts = 20; // Reduced from 30
                const retryInterval = 100; // Much faster - check every 100ms
                
                console.log('Starting instant overlay system...');
                
                function tryApplyOverlays() {
                    attempts++;
                    console.log('Overlay attempt ' + attempts + '/' + maxAttempts);
                    
                    const success = applyServerOverlays(attempts);
                    
                    if (success || attempts >= maxAttempts) {
                        if (success) {
                            console.log('✅ Overlay system successful on attempt', attempts);
                        } else {
                            console.log('❌ Overlay system gave up after', attempts, 'attempts');
                        }
                        
                        if (overlayCheckInterval) {
                            clearInterval(overlayCheckInterval);
                        }
                    }
                }
                
                // Try immediately, even before DOM is ready
                tryApplyOverlays();
                
                // Then retry very quickly if needed
                if (!overlayApplied) {
                    overlayCheckInterval = setInterval(tryApplyOverlays, retryInterval);
                }
            }
            
            // Start immediately - don't wait for DOM ready
            startOverlaySystem();
            
            // Watch for dynamic content changes with MutationObserver
            const observer = new MutationObserver(function(mutations) {
                let shouldCheck = false;
                
                mutations.forEach(mutation => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        for (let node of mutation.addedNodes) {
                            if (node.nodeType === 1) { // Element node
                                // Check if new content might contain servers
                                if (node.querySelector && (
                                    node.querySelector('a[href*=\"/server/\"]') ||
                                    node.querySelector('[data-server-uuid]') ||
                                    node.tagName === 'A' && node.href && node.href.includes('/server/')
                                )) {
                                    shouldCheck = true;
                                    break;
                                }
                            }
                        }
                    }
                });
                
                if (shouldCheck && !overlayApplied) {
                    console.log('New server content detected, applying overlays immediately...');
                    // Apply immediately without delay for dynamic content
                    applyServerOverlays(0);
                }
            });
            
            observer.observe(document.body, { 
                childList: true, 
                subtree: true 
            });
            
            console.log('Server cancellation overlay system initialized for', cancelledServers.length, 'servers');
        })();
        </script>";

        // Inject the script before closing body tag
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $cancelledServersScript . '</body>', $content);
            $response->setContent($content);
            \Illuminate\Support\Facades\Log::info('Dashboard server overlays injected successfully', [
                'cancelled_servers' => count($cancelledServers),
                'script_length' => strlen($cancelledServersScript)
            ]);
        }
    }
    
    /**
     * Generate CSS to instantly hide cancelled servers
     */
    private function generateServerHidingCSS($cancelledServers)
    {
        if (empty($cancelledServers)) {
            return '';
        }
        
        $css = '';
        foreach ($cancelledServers as $server) {
            $uuid = $server['uuid'];
            $shortUuid = $server['uuidShort'];
            
            // Hide servers by multiple possible selectors
            $css .= "
                a[href*=\"/server/{$shortUuid}\"],
                a[href*=\"/server/{$uuid}\"],
                [data-server-uuid=\"{$uuid}\"],
                [data-server-uuid=\"{$shortUuid}\"],
                [data-server-id=\"{$server['id']}\"] {
                    opacity: 0 !important;
                    transition: opacity 0.1s ease !important;
                }
            ";
        }
        
        return $css;
    }
}
