<?php

namespace PterodactylAddons\ShopSystem\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Models\ShopSettings;

class CheckCreditsEnabled
{
    /**
     * Handle an incoming request to ensure credits/wallet functionality is enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get credits enabled setting from database
        $creditsEnabled = ShopSettings::getValue('credits_enabled', true);
        
        // If credits are disabled, redirect to shop with error message
        if (!$creditsEnabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Wallet functionality is currently disabled.'
                ], 403);
            }
            
            return redirect()->route('shop.index')
                ->with('error', 'Wallet functionality is currently disabled.');
        }
        
        return $next($request);
    }
}
