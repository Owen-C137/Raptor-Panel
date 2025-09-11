<?php

namespace PterodactylAddons\ShopSystem\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation;
use PterodactylAddons\ShopSystem\Providers\ShopNavigationServiceProvider;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Policies\ShopOrderPolicy;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Load config from addon directory
        $this->mergeConfigFrom(
            base_path('addons/shop-system/config/shop.php'), 'shop'
        );

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PterodactylAddons\ShopSystem\Commands\ShopInstallCommand::class,
                \PterodactylAddons\ShopSystem\Commands\ShopUninstallCommand::class,
                \PterodactylAddons\ShopSystem\Commands\ProcessShopOrdersCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Register the navigation service provider first
        $this->app->register(ShopNavigationServiceProvider::class);
        
        // Load migrations from the addon directory
        $this->loadMigrationsFrom(base_path('addons/shop-system/database/migrations'));
        
        // Load views from the addon directory
        $this->loadViewsFrom(base_path('addons/shop-system/resources/views'), 'shop');
        
        // Publish addon config
        $this->publishes([
            base_path('addons/shop-system/config/shop.php') => config_path('shop.php'),
        ], 'shop-config');
        
        // Publish CSS and JS assets
        $this->publishes([
            base_path('addons/shop-system/resources/assets/css') => public_path('vendor/shop/css'),
            base_path('addons/shop-system/resources/assets/js') => public_path('vendor/shop/js'),
        ], 'shop-assets');
        
        // Register route model bindings
        $this->registerRouteModelBindings();
        
        // Register shop routes before Pterodactyl's routes to avoid conflicts
        $this->registerShopRoutes();
        
        // Register navigation injection middleware
        $this->registerShopMiddleware();
        
        // Share shop navigation data with views
        $this->shareNavigationData();
        
        // Register shop configuration view composer
        $this->registerShopConfigComposer();
        
        // Register shop policies
        $this->registerShopPolicies();
    }
    
    /**
     * Register shop configuration view composer
     */
    protected function registerShopConfigComposer()
    {
        View::composer([
            'shop::*',  // All shop views
            'catalog.*',  // Legacy catalog views 
            'checkout.*',  // Checkout views
            'wallet.*',  // Wallet views
            'client.shop.*'  // Client shop views
        ], \PterodactylAddons\ShopSystem\Http\View\Composers\ShopConfigComposer::class);
    }

    /**
     * Register shop policies for authorization
     */
    protected function registerShopPolicies()
    {
        Gate::policy(ShopOrder::class, ShopOrderPolicy::class);
    }

    /**
     * Register route model bindings for shop models
     */
    protected function registerRouteModelBindings()
    {
        Route::model('category', \PterodactylAddons\ShopSystem\Models\ShopCategory::class);
        Route::model('product', \PterodactylAddons\ShopSystem\Models\ShopCategory::class);
        Route::model('order', \PterodactylAddons\ShopSystem\Models\ShopOrder::class);
        Route::model('coupon', \PterodactylAddons\ShopSystem\Models\ShopCoupon::class);
        Route::model('plan', \PterodactylAddons\ShopSystem\Models\ShopPlan::class);
    }

    /**
     * Register shop routes from separate route files
     */
    protected function registerShopRoutes()
    {
        // Load client web routes (shop frontend with navigation)
        Route::middleware(['web', 'inject-shop-nav'])
            ->group(base_path('addons/shop-system/routes/web.php'));
            
        // Load admin routes (admin interface) with proper prefix
        Route::middleware(['web'])
            ->prefix('admin/shop')
            ->group(base_path('addons/shop-system/routes/admin.php'));
            
        // Load API routes
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix('api/shop')
            ->group(base_path('addons/shop-system/routes/api.php'));
    }

    /**
     * Register shop-specific middleware
     */
    protected function registerShopMiddleware()
    {
        // Register the navigation injection middleware
        $this->app['router']->aliasMiddleware('inject-shop-nav', InjectShopNavigation::class);
    }

    /**
     * Share navigation data with all views
     */
    protected function shareNavigationData()
    {
        View::composer('*', function ($view) {
            $shopNavigation = [
                'enabled' => true,
                'client_menu' => [
                    [
                        'name' => 'Shop',
                        'route' => 'shop.index',
                        'icon' => 'fas fa-shopping-cart',
                        'children' => [
                            ['name' => 'Browse Products', 'route' => 'shop.index'],
                            ['name' => 'Shopping Cart', 'route' => 'shop.cart'],
                            ['name' => 'My Orders', 'route' => 'shop.orders'],
                        ]
                    ]
                ],
                'admin_menu' => [
                    [
                        'name' => 'Shop Management',
                        'route' => 'admin.shop.dashboard',
                        'icon' => 'fas fa-store',
                        'children' => [
                            ['name' => 'Dashboard', 'route' => 'admin.shop.dashboard'],
                            ['name' => 'Categories', 'route' => 'admin.shop.categories'],
                            ['name' => 'Plans', 'route' => 'admin.shop.plans'],
                            ['name' => 'Orders', 'route' => 'admin.shop.orders'],
                            ['name' => 'Analytics', 'route' => 'admin.shop.analytics'],
                            ['name' => 'Settings', 'route' => 'admin.shop.settings'],
                        ]
                    ]
                ]
            ];

            $view->with('shopNavigation', $shopNavigation);
        });
    }
}
