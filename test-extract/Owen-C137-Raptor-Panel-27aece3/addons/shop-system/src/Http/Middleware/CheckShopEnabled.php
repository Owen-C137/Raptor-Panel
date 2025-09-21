<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // First check if shop is installed (config file exists and tables exist)
        if (!config('shop') || !Schema::hasTable('shop_settings')) {
            return $this->shopNotAvailable($request);
        }

        // Get shop enabled setting from database safely
        try {
            $shopEnabled = DB::table('shop_settings')
                ->where('key', 'shop_enabled')
                ->value('value');
            
            $shopEnabled = in_array($shopEnabled, ['true', '1', 1, true], true);
        } catch (\Exception $e) {
            // If database query fails, consider shop disabled
            return $this->shopNotAvailable($request);
        }
        
        // If shop is disabled, show appropriate response
        if (!$shopEnabled) {
            $maintenanceMessage = 'The shop is temporarily closed for maintenance.';
            
            try {
                $maintenanceMessage = DB::table('shop_settings')
                    ->where('key', 'shop_maintenance_message')
                    ->value('value') ?: $maintenanceMessage;
            } catch (\Exception $e) {
                // Use default message if database query fails
            }
            
            return $this->shopDisabled($request, $maintenanceMessage);
        }
        
        return $next($request);
    }

    /**
     * Handle shop not available (not installed)
     */
    private function shopNotAvailable(Request $request)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Shop system is not available',
                'error' => 'Shop not installed',
            ], 503);
        }
        
        return response()->view('shop::errors.503', [
            'message' => 'Shop system is not available',
        ], 503);
    }

    /**
     * Handle shop disabled (installed but disabled)
     */
    private function shopDisabled(Request $request, string $maintenanceMessage)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $maintenanceMessage,
                'error' => 'Shop is currently disabled',
            ], 503);
        }
        
        return response()->view('shop::errors.503', [
            'message' => $maintenanceMessage,
        ], 503);
    }
}
