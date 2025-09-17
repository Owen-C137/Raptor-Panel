<?php

namespace PterodactylAddons\OllamaAi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to inject AI navigation into Pterodactyl admin and client areas.
 */
class InjectAiNavigation
{
    public function handle(Request $request, Closure $next)
    {        
        $response = $next($request);
        
        // Only inject navigation if AI addon is installed and enabled
        if (!$this->isAiAddonInstalled()) {
            return $response;
        }
        
        // Only inject navigation on HTML responses
        if ($response->headers->get('Content-Type') && 
            strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
            
            $this->injectAdminNavigation($response, $request);
            $this->injectClientNavigation($response, $request);
        }
        
        return $response;
    }
    
    /**
     * Check if AI addon is installed
     */
    private function isAiAddonInstalled(): bool
    {
        return config('ai') !== null && 
               \Illuminate\Support\Facades\Schema::hasTable('ai_conversations');
    }
    
    /**
     * Inject AI navigation into admin panel
     */
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
        
        // Find the admin navigation menu and inject AI management section
        $aiNavigation = '
                        <li class="treeview ai-management-menu" data-ai-menu="true">
                            <a href="#" class="ai-toggle">
                                <i class="fa fa-robot"></i>
                                <span>AI Management</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu" style="display: none;">
                                <li class="{{ Route::currentRouteName() == \'admin.ai.index\' || Route::currentRouteName() == \'admin.ai.dashboard\' ? \'active\' : \'\' }}">
                                    <a href="/admin/ai">
                                        <i class="fa fa-dashboard"></i> <span>Dashboard</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.chat\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/chat">
                                        <i class="fa fa-comments"></i> <span>Chat</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.conversations\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/conversations">
                                        <i class="fa fa-list"></i> <span>Conversations</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.models\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/models">
                                        <i class="fa fa-brain"></i> <span>AI Models</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.analysis\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/analysis">
                                        <i class="fa fa-chart-line"></i> <span>Analysis</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.code-generation\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/code-generation">
                                        <i class="fa fa-code"></i> <span>Code Generation</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.templates\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/templates">
                                        <i class="fa fa-file-code"></i> <span>Templates</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.optimization\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/optimization">
                                        <i class="fa fa-tachometer-alt"></i> <span>Optimization</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.analytics\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/analytics">
                                        <i class="fa fa-analytics"></i> <span>Analytics</span>
                                    </a>
                                </li>
                                <li class="{{ starts_with(Route::currentRouteName(), \'admin.ai.settings\') ? \'active\' : \'\' }}">
                                    <a href="/admin/ai/settings">
                                        <i class="fa fa-cog"></i> <span>Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </li>';
        
        // JavaScript and CSS to initialize the AI management treeview
        $treeviewScript = '
        <style>
        .ai-management-menu .treeview-menu {
            padding-left: 0;
        }
        .ai-management-menu .treeview-menu li a {
            padding-left: 50px;
        }
        .ai-management-menu .ai-toggle {
            cursor: pointer;
        }
        .ai-management-menu .pull-right-container .fa {
            transition: transform 0.3s;
        }
        .ai-management-menu.active .pull-right-container .fa {
            transform: rotate(-90deg);
        }
        /* AI-specific styling */
        .ai-management-menu .fa-robot {
            color: #4ade80;
        }
        .ai-management-menu.active > a,
        .ai-management-menu:hover > a {
            background-color: rgba(74, 222, 128, 0.1);
        }
        </style>
        <script>
        $(document).ready(function() {
            // Wait a bit to ensure DOM is fully loaded
            setTimeout(function() {
                // Initialize AI management treeview specifically
                var $aiMenu = $(\'.ai-management-menu\');
                
                if ($aiMenu.length > 0) {
                    var $toggleLink = $aiMenu.find(\'.ai-toggle\');
                    var $submenu = $aiMenu.find(\'.treeview-menu\');
                    var $icon = $toggleLink.find(\'.fa-angle-left\');
                    
                    // Check if current URL contains ai or any submenu item is active
                    var currentPath = window.location.pathname;
                    var isAiSection = currentPath.includes(\'/admin/ai\');
                    var hasActiveSubmenu = $submenu.find(\'li.active\').length > 0;
                    
                    if (isAiSection || hasActiveSubmenu) {
                        $submenu.show();
                        $aiMenu.addClass(\'active\');
                        $icon.addClass(\'fa-angle-down\').removeClass(\'fa-angle-left\');
                    }
                    
                    // Toggle functionality
                    $toggleLink.off(\'click.ai-menu\').on(\'click.ai-menu\', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        $submenu.slideToggle(200, function() {
                            if ($submenu.is(\':visible\')) {
                                $aiMenu.addClass(\'active\');
                                $icon.addClass(\'fa-angle-down\').removeClass(\'fa-angle-left\');
                            } else {
                                $aiMenu.removeClass(\'active\');
                                $icon.addClass(\'fa-angle-left\').removeClass(\'fa-angle-down\');
                            }
                        });
                    });
                }
            }, 500);
        });
        </script>';

        // Pattern to find where to inject the navigation in admin sidebar
        // Look for the end of the main navigation list
        $patterns = [
            // Pattern for AdminLTE sidebar
            '/(<ul[^>]*class="[^"]*sidebar-menu[^"]*"[^>]*>.*?)(<\/ul>)/s',
            // Pattern for other admin layouts
            '/(<nav[^>]*class="[^"]*admin[^"]*"[^>]*>.*?)(<\/nav>)/s',
            // Generic pattern for navigation lists
            '/(<ul[^>]*class="[^"]*nav[^"]*"[^>]*>.*?)(<\/ul>)/s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$1' . $aiNavigation . '$2', $content);
                
                // Inject the treeview script before closing </body> tag
                $content = str_replace('</body>', $treeviewScript . '</body>', $content);
                
                $response->setContent($content);
                break;
            }
        }
    }
    
    /**
     * Inject AI navigation into client interface
     */
    private function injectClientNavigation($response, Request $request)
    {
        // Only inject on client pages (not admin, auth, or API)
        if ($request->is('admin*') || $request->is('auth*') || $request->is('api*')) {
            return;
        }
        
        // Check if user is authenticated
        $user = $request->user();
        if (!$user) {
            return;
        }
        
        $content = $response->getContent();
        
        // Look for the client navigation bar pattern
        $aiClientNav = '
            <a href="/ai" title="AI Assistant" class="ai-nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </a>';
            
        $aiClientStyle = '
        <style>
        .ai-nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            margin: 0 0.5rem;
            border-radius: 0.5rem;
            color: rgb(156, 163, 175);
            transition: all 0.2s;
            text-decoration: none;
        }
        .ai-nav-link:hover {
            color: rgb(34, 197, 94);
            background-color: rgba(34, 197, 94, 0.1);
            transform: scale(1.05);
        }
        .ai-nav-link svg {
            width: 1.5rem;
            height: 1.5rem;
        }
        </style>';
        
        // Try to inject near notifications or user menu
        $patterns = [
            // Pattern for notification bell area
            '/(<[^>]*notifications[^>]*>.*?<\/[^>]*>)/',
            // Pattern for user menu area  
            '/(<[^>]*user[^>-]*menu[^>]*>)/',
            // Generic pattern for navigation area
            '/(<nav[^>]*>.*?)(<\/nav>)/s'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$1' . $aiClientNav . '$2', $content);
                break;
            }
        }
        
        // Inject styles
        $content = str_replace('</head>', $aiClientStyle . '</head>', $content);
        
        $response->setContent($content);
    }
}