<?php

namespace PterodactylAddons\ShopSystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class SimpleShopServiceProvider extends ServiceProvider
{
    /**
     * Register any addon services.
     */
    public function register(): void
    {
        // Simple registration without complex dependencies
    }

    /**
     * Bootstrap any addon services.
     */
    public function boot(): void
    {
        // Register simple test routes to verify integration
        Route::group(['middleware' => 'web'], function () {
            Route::get('/shop-test', function () {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Shop system is successfully integrated!',
                    'timestamp' => now(),
                    'database_check' => \Schema::hasTable('shop_products') ? 'Tables exist' : 'Tables missing',
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ]);
            });
            
            Route::get('/admin/shop-test', function () {
                $user = auth()->check() ? auth()->user()->email : 'Not authenticated';
                return response()->json([
                    'status' => 'admin_success', 
                    'message' => 'Admin shop routes working!',
                    'user' => $user,
                    'timestamp' => now()
                ]);
            })->middleware('auth');
        });
    }
}
