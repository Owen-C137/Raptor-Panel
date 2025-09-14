<?php

namespace PterodactylAddons\ShopSystem\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use PterodactylAddons\ShopSystem\Providers\ShopNavigationServiceProvider;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Policies\ShopOrderPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Always register middleware to prevent binding resolution errors
        $this->registerShopMiddleware();
        
        // Always register commands for install/uninstall functionality
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PterodactylAddons\ShopSystem\Console\Commands\ShopInstallCommand::class,
                \PterodactylAddons\ShopSystem\Console\Commands\ShopUninstallCommand::class,
            ]);
        }

        // Only register other shop services if shop is installed
        if (!file_exists(config_path('shop.php'))) {
            return;
        }

        // Load config from addon directory
        $this->mergeConfigFrom(
            base_path('addons/shop-system/config/shop.php'), 'shop'
        );

        // Register additional commands if running in console and shop is installed
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PterodactylAddons\ShopSystem\Console\Commands\ProcessShopOrdersCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Only fully boot the shop system if it's installed
        if (!$this->isShopInstalled()) {
            // Only register commands for install/uninstall
            return;
        }
        
        // Register the navigation service provider first
        $this->app->register(ShopNavigationServiceProvider::class);
        
        // Register morph map for addon models to fix activity logging
        $this->registerMorphMap();
        
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
        
        // Bind order by UUID or ID for flexibility in admin interface
        Route::bind('order', function ($value) {
            // Check if it's a UUID (contains hyphens) or an ID (numeric)
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                // It's a UUID
                return \PterodactylAddons\ShopSystem\Models\ShopOrder::where('uuid', $value)->firstOrFail();
            } else {
                // It's an ID
                return \PterodactylAddons\ShopSystem\Models\ShopOrder::findOrFail($value);
            }
        });
        
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
            
        // Load admin routes (admin interface) with proper authentication
        Route::middleware(['web', 'auth.session', RequireTwoFactorAuthentication::class, AdminAuthenticate::class])
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
        $router = $this->app['router'];
        
        // Register the navigation injection middleware
        $router->aliasMiddleware('inject-shop-nav', InjectShopNavigation::class);
        $router->aliasMiddleware('shop.inject.navigation', \PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation::class);
        
        // Register shop functionality middleware
        $router->aliasMiddleware('shop.enabled', \PterodactylAddons\ShopSystem\Http\Middleware\CheckShopEnabled::class);
        $router->aliasMiddleware('shop.credits', \PterodactylAddons\ShopSystem\Http\Middleware\CheckCreditsEnabled::class);
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

    /**
     * Register morph map for addon models to fix activity logging.
     */
    protected function registerMorphMap()
    {
        Relation::morphMap([
            'user_wallet' => \PterodactylAddons\ShopSystem\Models\UserWallet::class,
            'wallet_transaction' => \PterodactylAddons\ShopSystem\Models\WalletTransaction::class,
            'shop_order' => \PterodactylAddons\ShopSystem\Models\ShopOrder::class,
            'shop_payment' => \PterodactylAddons\ShopSystem\Models\ShopPayment::class,
            'shop_plan' => \PterodactylAddons\ShopSystem\Models\ShopPlan::class,
            'shop_coupon' => \PterodactylAddons\ShopSystem\Models\ShopCoupon::class,
            'shop_coupon_usage' => \PterodactylAddons\ShopSystem\Models\ShopCouponUsage::class,
            'shop_product' => \PterodactylAddons\ShopSystem\Models\ShopProduct::class,
        ]);
    }
    
    /**
     * Check if the shop system is installed
     */
    protected function isShopInstalled(): bool
    {
        try {
            // Simple file-based check that doesn't require database access
            return file_exists(config_path('shop.php'));
        } catch (\Exception $e) {
            return false;
        }
    }
}
