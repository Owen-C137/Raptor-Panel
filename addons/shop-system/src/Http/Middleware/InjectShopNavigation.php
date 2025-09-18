<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
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
        
        // ALWAYS check for server page blocking, regardless of path
        $this->injectServerPageBlocking($response, $request);
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
        
        // Find the admin navigation menu (One UI nav-main structure)
        $shopNavigation = '
                            <li class="nav-main-heading">SHOP MANAGEMENT</li>
                            <li class="nav-main-item {{ Route::currentRouteName() == \'admin.shop.index\' || Route::currentRouteName() == \'admin.shop.dashboard\' ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ Route::currentRouteName() == \'admin.shop.index\' || Route::currentRouteName() == \'admin.shop.dashboard\' ? \'active\' : \'\' }}" href="/admin/shop">
                                    <i class="nav-main-link-icon fa fa-dashboard"></i>
                                    <span class="nav-main-link-name">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.plans\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.plans\') ? \'active\' : \'\' }}" href="/admin/shop/plans">
                                    <i class="nav-main-link-icon fa fa-list"></i>
                                    <span class="nav-main-link-name">Plans</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.categories\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.categories\') ? \'active\' : \'\' }}" href="/admin/shop/categories">
                                    <i class="nav-main-link-icon fa fa-folder"></i>
                                    <span class="nav-main-link-name">Categories</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.orders\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.orders\') ? \'active\' : \'\' }}" href="/admin/shop/orders">
                                    <i class="nav-main-link-icon fa fa-shopping-cart"></i>
                                    <span class="nav-main-link-name">Orders</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.payments\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.payments\') ? \'active\' : \'\' }}" href="/admin/shop/payments">
                                    <i class="nav-main-link-icon fa fa-credit-card"></i>
                                    <span class="nav-main-link-name">Payments</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.coupons\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.coupons\') ? \'active\' : \'\' }}" href="/admin/shop/coupons">
                                    <i class="nav-main-link-icon fa fa-tag"></i>
                                    <span class="nav-main-link-name">Coupons</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.analytics\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.analytics\') ? \'active\' : \'\' }}" href="/admin/shop/analytics">
                                    <i class="nav-main-link-icon fa fa-bar-chart"></i>
                                    <span class="nav-main-link-name">Analytics</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.reports\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.reports\') ? \'active\' : \'\' }}" href="/admin/shop/reports">
                                    <i class="nav-main-link-icon fa fa-line-chart"></i>
                                    <span class="nav-main-link-name">Reports</span>
                                </a>
                            </li>
                            <li class="nav-main-item {{ starts_with(Route::currentRouteName(), \'admin.shop.settings\') ? \'open\' : \'\' }}">
                                <a class="nav-main-link {{ starts_with(Route::currentRouteName(), \'admin.shop.settings\') ? \'active\' : \'\' }}" href="/admin/shop/settings">
                                    <i class="nav-main-link-icon fa fa-gear"></i>
                                    <span class="nav-main-link-name">Settings</span>
                                </a>
                            </li>';
        
        // Simple JavaScript for One UI shop navigation (no submenus needed)
        $treeviewScript = '
        <script>
        $(document).ready(function() {
            // Simple navigation handling - no complex submenu logic needed
            // One UI will handle the basic navigation functionality
            console.log("Shop navigation loaded successfully");
        });
        </script>';
        
        // Try multiple patterns to inject shop navigation (match One UI nav-main structure)
        $patterns = [
            // Pattern 1: After the last item in SERVICE MANAGEMENT section (Nests)
            '/(<li class="nav-main-item[^"]*"[^>]*>\s*<a class="nav-main-link[^"]*" href="[^"]*\/admin\/nests[^"]*">[^<]*<i class="nav-main-link-icon fa fa-th-large"><\/i>[^<]*<span class="nav-main-link-name">Nests<\/span>[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 2: After SERVICE MANAGEMENT heading
            '/(<li class="nav-main-heading">SERVICE MANAGEMENT<\/li>.*?<\/li>)(\s*<\/ul>)/s',
            // Pattern 3: After MANAGEMENT heading  
            '/(<li class="nav-main-heading">MANAGEMENT<\/li>.*?<\/li>)(\s*<li class="nav-main-heading">)/s',
            // Pattern 4: After any management section with users
            '/(<li class="nav-main-item[^"]*"[^>]*>\s*<a class="nav-main-link[^"]*" href="[^"]*\/admin\/users[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 5: After settings menu item
            '/(<li class="nav-main-item[^"]*"[^>]*>\s*<a class="nav-main-link[^"]*" href="[^"]*\/admin\/settings[^"]*">[^<]*<\/a>\s*<\/li>)/s',
            // Pattern 6: Fallback - before closing ul in nav-main
            '/(<\/ul>\s*<\/div>\s*<\/div>\s*<\/nav>)/s'
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
            ->map(function($order) {
                if (!$order->server) {
                    return null;
                }
                return [
                    'id' => $order->server->id,
                    'uuid' => $order->server->uuid,
                    'uuidShort' => $order->server->uuidShort,
                    'auto_delete_at' => $order->auto_delete_at ? $order->auto_delete_at->toISOString() : null,
                    'order_id' => $order->id,
                ];
            })
            ->filter()
            ->values()
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
        
        /* CSS-only overlays for cancelled servers */";
        
        foreach ($cancelledServers as $server) {
            $uuid = $server['uuid'];
            $shortUuid = $server['uuidShort'];
            
            $cancelledServersScript .= "
            a[href*='/server/$uuid'],
            a[href*='/server/$shortUuid'] {
                position: relative !important;
                pointer-events: none !important;
                cursor: default !important;
            }
            
            a[href*='/server/$uuid']::before,
            a[href*='/server/$shortUuid']::before {
                content: '🚫 Plan Cancelled - Click to Purchase' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                width: 100% !important;
                height: 100% !important;
                min-height: 60px !important;
                background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
                color: white !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                font-weight: bold !important;
                font-size: 14px !important;
                z-index: 999999 !important;
                border-radius: 8px !important;
                cursor: pointer !important;
                pointer-events: auto !important;
                border: 3px solid #fca5a5 !important;
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3) !important;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5) !important;
            }
            
            /* Make sure all child elements of cancelled servers can't be clicked */
            a[href*='/server/$uuid'] *,
            a[href*='/server/$shortUuid'] * {
                pointer-events: none !important;
            }";
        }
        
        $cancelledServersScript .= "
        </style>
        <script>
        // Use capture phase to intercept clicks before they reach the link
        document.addEventListener('click', function(e) {
            const cancelledServers = " . json_encode($cancelledServers) . ";
            
            // Check if we clicked on or inside a cancelled server link
            const link = e.target.closest('a[href*=\"/server/\"]');
            
            if (link && link.href) {
                for (let server of cancelledServers) {
                    if (link.href.includes('/server/' + server.uuid) || link.href.includes('/server/' + server.uuidShort)) {
                        // Immediately prevent any navigation
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        
                        console.log('Cancelled server clicked:', server.uuidShort);
                        
                        // Show modal immediately
                        showCancellationModal(server);
                        
                        return false;
                    }
                }
            }
        }, true); // Capture phase
        
        // Function to show the modal
        function showCancellationModal(server) {
            // Remove existing modal first
            const existingModal = document.getElementById('cancelled-server-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Show purchase modal
            const modal = document.createElement('div');
            modal.id = 'cancelled-server-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.75);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999999;
                backdrop-filter: blur(4px);
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: rgb(31, 41, 55);
                color: rgb(243, 244, 246);
                padding: 32px;
                border-radius: 16px;
                max-width: 500px;
                width: 90%;
                text-align: center;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
                border: 1px solid rgb(55, 65, 81);
            `;
            
            modalContent.innerHTML = `
                <div style='font-size: 48px; margin-bottom: 16px;'>⚠️</div>
                <h2 style='color: rgb(239, 68, 68); margin-bottom: 16px; font-size: 24px; font-weight: 700;'>Server Plan Cancelled</h2>
                <p style='color: rgb(156, 163, 175); margin-bottom: 8px;'>Server: <strong style='color: rgb(243, 244, 246);'>` + server.uuidShort + `</strong></p>
                <div style='background: rgb(153, 27, 27); border: 1px solid rgb(239, 68, 68); border-radius: 8px; padding: 16px; margin: 16px 0; text-align: left;'>
                    <div style='display: flex; align-items: center; margin-bottom: 8px;'>
                        <span style='font-size: 20px; margin-right: 8px;'>⏰</span>
                        <strong style='color: rgb(254, 202, 202);'>Automatic Deletion Warning</strong>
                    </div>
                    <p style='color: rgb(254, 202, 202); font-size: 14px; margin: 0; line-height: 1.4;'>
                        This server will be <strong>permanently deleted</strong> in <strong id='countdown-timer'>calculating...</strong> if not renewed.
                        <br>All data will be lost and cannot be recovered.
                    </p>
                </div>
                <p style='color: rgb(156, 163, 175); margin-bottom: 24px; line-height: 1.5;'>Choose an option to restore access to your server:</p>
                <div style='display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;'>
                    <button id='renew-btn' style='
                        background: rgb(34, 197, 94);
                        color: white;
                        padding: 14px 24px;
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 16px;
                        transition: all 0.2s;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                    '>
                        <span style='font-size: 18px;'>🔄</span>
                        Renew Current Plan
                    </button>
                    <button id='purchase-btn' style='
                        background: rgb(59, 130, 246);
                        color: white;
                        padding: 12px 24px;
                        border: none;
                        border-radius: 8px;
                        cursor: pointer;
                        font-weight: 600;
                        font-size: 15px;
                        transition: all 0.2s;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                    '>
                        <span style='font-size: 16px;'>🛒</span>
                        Buy New Plan
                    </button>
                </div>
                <button id='close-btn' style='
                    background: rgb(55, 65, 81);
                    color: rgb(243, 244, 246);
                    padding: 10px 20px;
                    border: 1px solid rgb(75, 85, 99);
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 500;
                    font-size: 14px;
                    transition: all 0.2s;
                '>Close</button>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Add proper event handlers
            modal.querySelector('#renew-btn').addEventListener('click', function() {
                // Redirect directly to checkout with renewal parameter
                window.location.href = '/shop/checkout?renew=' + server.uuidShort;
            });
            
            modal.querySelector('#purchase-btn').addEventListener('click', function() {
                window.location.href = '/shop';
            });
            
            modal.querySelector('#close-btn').addEventListener('click', function() {
                modal.remove();
            });
            
            // Update countdown timer
            function updateCountdown() {
                const countdownElement = modal.querySelector('#countdown-timer');
                if (!countdownElement || !server.auto_delete_at) {
                    countdownElement.textContent = '7 days (estimated)';
                    return;
                }
                
                const now = new Date();
                const deleteAt = new Date(server.auto_delete_at);
                const timeDiff = deleteAt.getTime() - now.getTime();
                
                if (timeDiff <= 0) {
                    countdownElement.textContent = 'EXPIRED - Server will be deleted soon';
                    countdownElement.style.color = 'rgb(254, 226, 226)';
                    return;
                }
                
                const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
                
                if (days > 0) {
                    countdownElement.textContent = days + ' days, ' + hours + ' hours';
                } else if (hours > 0) {
                    countdownElement.textContent = hours + ' hours, ' + minutes + ' minutes';
                } else {
                    countdownElement.textContent = minutes + ' minutes';
                    countdownElement.style.color = 'rgb(254, 226, 226)';
                }
            }
            
            // Update countdown immediately and then every minute
            updateCountdown();
            const countdownInterval = setInterval(updateCountdown, 60000);
            
            // Clean up interval when modal is closed
            const originalRemove = modal.remove;
            modal.remove = function() {
                clearInterval(countdownInterval);
                originalRemove.call(this);
            };
            
            // Close on backdrop click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            // Close on escape key
            const escapeHandler = function(e) {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        }
        
        // Additional aggressive event blocking for cancelled server links
        document.addEventListener('click', function(e) {
            const cancelledServers = " . json_encode($cancelledServers) . ";
            
            const link = e.target.closest('a[href*=\"/server/\"]');
            if (link) {
                for (let server of cancelledServers) {
                    if (link.href.includes('/server/' + server.uuid) || link.href.includes('/server/' + server.uuidShort)) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        return false;
                    }
                }
            }
        }, true); // Capture phase
        
        console.log('✅ CSS overlay system loaded');
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
    
    private function injectServerPageBlocking($response, Request $request)
    {
        // Check if we're on a server page
        $currentPath = $request->getPathInfo();
        \Illuminate\Support\Facades\Log::info('Server blocking check', [
            'path' => $currentPath,
            'matches_pattern' => preg_match('/^\/server\/([a-f0-9-]{8,36})/', $currentPath)
        ]);
        
        if (!preg_match('/^\/server\/([a-f0-9-]{8,36})/', $currentPath, $matches)) {
            return; // Not a server page
        }
        
        $serverIdentifier = $matches[1];
        $user = Auth::user();
        if (!$user) {
            \Illuminate\Support\Facades\Log::info('Server blocking: No user found');
            return;
        }
        
        // Check if this server has a cancelled plan
        // Get the server by its UUID/uuidShort first
        $server = \Pterodactyl\Models\Server::where('uuid', 'LIKE', $serverIdentifier . '%')
            ->orWhere('uuidShort', $serverIdentifier)
            ->first();
            
        if (!$server) {
            \Illuminate\Support\Facades\Log::info('Server not found', ['identifier' => $serverIdentifier]);
            return;
        }
        
        $cancelledServer = \PterodactylAddons\ShopSystem\Models\ShopOrder::query()
            ->where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->where('server_id', $server->id)
            ->first();
        
        \Illuminate\Support\Facades\Log::info('Server blocking query result', [
            'server_identifier' => $serverIdentifier,
            'server_found' => $server ? 'yes' : 'no',
            'server_id' => $server ? $server->id : null,
            'user_id' => $user->id,
            'cancelled_server_found' => $cancelledServer ? 'yes' : 'no'
        ]);
        
        if (!$cancelledServer) {
            return; // Server plan is not cancelled
        }
        
        \Illuminate\Support\Facades\Log::info('Blocking server access', ['server' => $serverIdentifier]);
        
        // Block access to this server page completely
        $blockingContent = "<!DOCTYPE html>
<html lang='en'>
<head>
    <title>Access Denied - Server Plan Cancelled</title>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, rgb(17, 24, 39) 0%, rgb(31, 41, 55) 100%);
            color: rgb(243, 244, 246);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .container {
            max-width: 600px;
            padding: 48px;
            text-align: center;
            background: rgb(31, 41, 55);
            border-radius: 16px;
            border: 1px solid rgb(55, 65, 81);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .icon {
            font-size: 72px;
            margin-bottom: 24px;
            animation: pulse 2s infinite;
            filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.3));
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        h1 {
            font-size: 32px;
            margin-bottom: 16px;
            font-weight: 700;
            color: rgb(243, 244, 246);
        }
        
        h2 {
            font-size: 24px;
            margin-bottom: 24px;
            color: rgb(239, 68, 68);
            font-weight: 600;
        }
        
        p {
            font-size: 16px;
            margin-bottom: 32px;
            line-height: 1.6;
            color: rgb(156, 163, 175);
        }
        
        .server-info {
            background: rgb(17, 24, 39);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 32px;
            border: 1px solid rgb(55, 65, 81);
        }
        
        .server-info strong {
            color: rgb(243, 244, 246);
            font-family: 'Monaco', 'Menlo', monospace;
        }
        
        .buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        button {
            padding: 14px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: rgb(239, 68, 68);
            color: white;
        }
        
        .btn-primary:hover {
            background: rgb(220, 38, 38);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
        }
        
        .btn-secondary {
            background: rgb(55, 65, 81);
            color: rgb(243, 244, 246);
            border: 1px solid rgb(75, 85, 99);
        }
        
        .btn-secondary:hover {
            background: rgb(75, 85, 99);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 640px) {
            .container {
                margin: 20px;
                padding: 32px 24px;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='icon'>🚫</div>
        <h1>Access Denied</h1>
        <h2>Server Plan Cancelled</h2>
        <div class='server-info'>
            <p style='margin-bottom: 0; font-size: 14px;'>
                Server ID: <strong>" . htmlspecialchars($serverIdentifier) . "</strong>
            </p>
        </div>
        <p>
            This server's hosting plan has been cancelled and access has been suspended. 
            To restore access to your server, please purchase a new hosting plan from our shop.
        </p>
        <div class='buttons'>
            <button class='btn-primary' onclick='window.location.href=\"/shop\"'>
                🛒 Purchase New Plan
            </button>
            <button class='btn-secondary' onclick='window.location.href=\"/\"'>
                🏠 Back to Dashboard
            </button>
        </div>
    </div>
    
    <script>
    // Block any attempts to bypass
    document.addEventListener('keydown', function(e) {
        // Block F12, Ctrl+Shift+I, Ctrl+U, etc.
        if (e.key === 'F12' || 
            (e.ctrlKey && e.shiftKey && e.key === 'I') ||
            (e.ctrlKey && e.key === 'u') ||
            (e.ctrlKey && e.shiftKey && e.key === 'C')) {
            e.preventDefault();
            return false;
        }
    });
    
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Additional security - prevent iframe embedding
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }
    
    console.log('%cAccess Denied', 'color: #ef4444; font-size: 24px; font-weight: bold;');
    console.log('%cThis server plan has been cancelled.', 'color: #6b7280; font-size: 16px;');
    </script>
</body>
</html>";
        
        // Replace the entire response with our blocking page
        $response->setContent($blockingContent);
    }

    /**
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
