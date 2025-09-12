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
        
        // If shop is disabled, show the shop closed page
        if (!$shopEnabled) {
            return response()->view('shop::shop.disabled', [
                'maintenanceMessage' => ShopSettings::getValue('shop_maintenance_message', 'The shop is temporarily closed for maintenance.'),
                'dashboardUrl' => route('index')
            ], 503);
        }
        
        return $next($request);
    }
}
