<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PterodactylAddons\ShopSystem\Models\ShopOrder;

class InjectShopNavigationSimple
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only process HTML responses
        if (!$this->shouldInjectScript($request, $response)) {
            return $response;
        }
        
        $this->injectDashboardOverlays($response, $request);
        
        return $response;
    }
    
    private function shouldInjectScript(Request $request, $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'text/html') && $response->getStatusCode() === 200;
    }
    
    private function injectDashboardOverlays($response, Request $request)
    {
        // Only on dashboard/home page
        $currentPath = $request->getPathInfo();
        if ($currentPath !== '/') {
            return;
        }
        
        $user = Auth::user();
        if (!$user) {
            return;
        }
        
        // Get cancelled server UUIDs
        $cancelledServers = ShopOrder::query()
            ->where('user_id', $user->id)
            ->where('type', 'server')
            ->where('status', 'cancelled')
            ->whereNotNull('item_id')
            ->pluck('item_id')
            ->toArray();
        
        if (empty($cancelledServers)) {
            return;
        }
        
        $content = $response->getContent();
        
        // Generate CSS-only overlays (React can't remove CSS)
        $overlayStyles = '<style id="shop-cancelled-server-overlays">';
        
        foreach ($cancelledServers as $uuid) {
            $overlayStyles .= "
            /* Overlay for cancelled server $uuid */
            a[href*='/server/$uuid'] {
                position: relative !important;
                pointer-events: none !important;
            }
            
            a[href*='/server/$uuid']::before {
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
            
            a[href*='/server/$uuid']:hover::before {
                background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
                box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4) !important;
            }";
        }
        
        $overlayStyles .= '</style>';
        
        // JavaScript for click handling and modal
        $overlayScript = "
        <script>
        document.addEventListener('click', function(e) {
            const cancelledUuids = " . json_encode($cancelledServers) . ";
            const link = e.target.closest('a');
            
            if (link && link.href) {
                for (let uuid of cancelledUuids) {
                    if (link.href.includes('/server/' + uuid)) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Show purchase modal
                        const modal = document.createElement('div');
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
                        `;
                        
                        modal.innerHTML = `
                            <div style='background: white; padding: 32px; border-radius: 16px; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);'>
                                <div style='font-size: 48px; margin-bottom: 16px;'>🚫</div>
                                <h2 style='color: #dc2626; margin-bottom: 16px; font-size: 24px;'>Server Plan Cancelled</h2>
                                <p style='color: #6b7280; margin-bottom: 24px;'>This server's hosting plan has been cancelled. To restore access, please purchase a new plan.</p>
                                <div style='display: flex; gap: 12px; justify-content: center;'>
                                    <button onclick='window.location.href=\"/shop\"' style='background: #dc2626; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>Purchase Plan</button>
                                    <button onclick='this.closest(\"div[style*=\\\"position: fixed\\\"]\").remove()' style='background: #6b7280; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>Close</button>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                        
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                modal.remove();
                            }
                        });
                        
                        break;
                    }
                }
            }
        });
        
        console.log('✅ CSS overlay system loaded for servers:', " . json_encode($cancelledServers) . ");
        </script>";
        
        // Inject styles and script
        $injection = $overlayStyles . $overlayScript;
        $updatedContent = str_replace('</body>', $injection . '</body>', $content);
        $response->setContent($updatedContent);
    }
}