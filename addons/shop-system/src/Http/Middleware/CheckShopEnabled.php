<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Models\ShopSettings;

class CheckShopEnabled
{
    /**
     * Handle an incoming request to ensure the shop is enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get shop enabled setting from database
        $shopEnabled = ShopSettings::getValue('shop_enabled', true);
        
        // If shop is disabled, show appropriate response
        if (!$shopEnabled) {
            $maintenanceMessage = ShopSettings::getValue('shop_maintenance_message', 'The shop is temporarily closed for maintenance.');
            
            // If this is an AJAX request, return JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $maintenanceMessage,
                    'error' => 'Shop is currently disabled',
                ], 503);
            }
            
            // Otherwise return the maintenance view
            return response()->view('shop::shop.disabled', [
                'maintenanceMessage' => $maintenanceMessage,
                'dashboardUrl' => route('index')
            ], 503);
        }
        
        return $next($request);
    }
}
