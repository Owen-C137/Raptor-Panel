<?php

namespace PterodactylAddons\ShopSystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;
use Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication;
use Pterodactyl\Http\Middleware\AdminAuthenticate;
use PterodactylAddons\ShopSystem\Services\BillingService;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Always register middleware to prevent binding resolution errors
        $this->registerShopMiddleware();
        
        // Only register shop services if shop is installed
        if (!file_exists(config_path('shop.php'))) {
            // Always register commands for install/uninstall functionality
            if ($this->app->runningInConsole()) {
                $this->commands([
                    \PterodactylAddons\ShopSystem\Console\Commands\ShopInstallCommand::class,
                    \PterodactylAddons\ShopSystem\Console\Commands\ShopUninstallCommand::class,
                ]);
            }
            return;
        }

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shop.php',
            'shop'
        );

        // Register repository bindings
        $this->registerRepositories();
        
        // Register service bindings
        $this->registerServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Always register console commands (install/uninstall need to be available)
        $this->registerConsoleCommands();

        // Only fully boot the shop system if it's installed
        if (!$this->isShopInstalled()) {
            return;
        }

        // Apply navigation injection middleware to web routes
        $this->app['router']->pushMiddlewareToGroup('web', \PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation::class);

        // Load shop routes
        $this->loadShopRoutes();

        // Register shop views
        $this->loadViewsFrom(base_path('addons/shop-system/resources/views'), 'shop');

        // Load migrations from addon directory
        $this->loadMigrationsFrom(base_path('addons/shop-system/database/migrations'));

        // Register shop policies  
        $this->registerPolicies();
        
        // Schedule shop background tasks
        $this->scheduleShopTasks();

        // Add shop navigation to admin panel
        $this->extendAdminNavigation();
    }
    
    /**
     * Check if the shop system is installed
     */
    private function isShopInstalled(): bool
    {
        return file_exists(config_path('shop.php'));
    }
    
    /**
     * Load shop routes
     */
    private function loadShopRoutes(): void
    {
        // Load asset routes without shop middleware (always accessible)
        Route::middleware(['web'])
            ->group(function () {
                // Serve shop CSS
                Route::get('shop/assets/css/shop.css', function () {
                    $path = base_path('addons/shop-system/resources/assets/css/shop.css');
                    if (!file_exists($path)) {
                        abort(404);
                    }
                    return response(file_get_contents($path))
                        ->header('Content-Type', 'text/css');
                })->name('shop.assets.css');

                // oneui theme CSS
                Route::get('shop/assets/css/oneui.css', function () {
                    $path = base_path('addons/shop-system/resources/assets/css/oneui.css');
                    if (!file_exists($path)) {
                        abort(404);
                    }
                    return response(file_get_contents($path))
                        ->header('Content-Type', 'text/css');
                })->name('shop.assets.oneui.css');

                // Serve shop JS
                Route::get('shop/assets/js/shop.js', function () {
                    $path = base_path('addons/shop-system/resources/assets/js/shop.js');
                    if (!file_exists($path)) {
                        abort(404);
                    }
                    return response(file_get_contents($path))
                        ->header('Content-Type', 'application/javascript');
                })->name('shop.assets.js');
            });

        // Load shop functional routes with middleware from web.php (excluding assets)
        Route::middleware(['web'])
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
     * Register repository bindings.
     */
    private function registerRepositories(): void
    {
        $repositories = [
            \PterodactylAddons\ShopSystem\Repositories\ShopCategoryRepository::class,
            \PterodactylAddons\ShopSystem\Repositories\ShopPlanRepository::class,
            \PterodactylAddons\ShopSystem\Repositories\ShopOrderRepository::class,
            \PterodactylAddons\ShopSystem\Repositories\UserWalletRepository::class,
            \PterodactylAddons\ShopSystem\Repositories\ShopPaymentRepository::class,
            \PterodactylAddons\ShopSystem\Repositories\ShopCouponRepository::class,
        ];

        foreach ($repositories as $repository) {
            $this->app->singleton($repository);
        }
    }

    /**
     * Register service bindings.
     */
    private function registerServices(): void
    {
        // Core services
        $this->app->singleton(\PterodactylAddons\ShopSystem\Services\ShopOrderService::class);
        $this->app->singleton(\PterodactylAddons\ShopSystem\Services\WalletService::class);
        $this->app->singleton(\PterodactylAddons\ShopSystem\Services\PaymentGatewayManager::class);
        
        // Payment gateways
        $this->app->singleton(\PterodactylAddons\ShopSystem\PaymentGateways\StripePaymentGateway::class);
        $this->app->singleton(\PterodactylAddons\ShopSystem\PaymentGateways\PayPalPaymentGateway::class);
        
        $this->app->singleton(
            \PterodactylAddons\ShopSystem\Services\BillingService::class
        );
    }

    /**
     * Register authorization policies.
     */
    private function registerPolicies(): void
    {
        // No policies needed for category-based system yet
    }

    /**
     * Register console commands.
     */
    private function registerConsoleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            // Always register install/uninstall commands
            $commands = [
                \PterodactylAddons\ShopSystem\Console\Commands\ShopInstallCommand::class,
                \PterodactylAddons\ShopSystem\Console\Commands\ShopUninstallCommand::class,
            ];
            
            // Only register operational commands if shop is installed
            if (file_exists(config_path('shop.php'))) {
                $commands[] = \PterodactylAddons\ShopSystem\Console\Commands\ProcessShopOrdersCommand::class;
            }
            
            $this->commands($commands);
        }
    }

    /**
     * Schedule shop-related tasks.
     */
    private function scheduleShopTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Process all shop orders daily at 3 AM
            $schedule->command('shop:process-orders')
                ->dailyAt('03:00')
                ->name('shop:daily-processing')
                ->withoutOverlapping()
                ->runInBackground();

            // Process urgent renewals every hour (for time-sensitive payments)
            $schedule->command('shop:process-orders')
                ->hourly()
                ->name('shop:hourly-processing')
                ->withoutOverlapping()
                ->runInBackground()
                ->when(function () {
                    // Only run during business hours for urgent processing
                    return now()->hour >= 8 && now()->hour <= 22;
                });
        });
    }

    /**
     * Register shop middleware. 
     * Always register middleware to prevent binding resolution errors.
     */
    protected function registerShopMiddleware()
    {
        // Register shop middleware (required for routes that reference them)
        $router = $this->app['router'];
        
        $router->aliasMiddleware('shop.enabled', \PterodactylAddons\ShopSystem\Http\Middleware\CheckShopEnabled::class);
        $router->aliasMiddleware('shop.credits', \PterodactylAddons\ShopSystem\Http\Middleware\CheckCreditsEnabled::class);
        $router->aliasMiddleware('shop.inject.navigation', \PterodactylAddons\ShopSystem\Http\Middleware\InjectShopNavigation::class);
    }

    /**
     * Extend admin navigation (hook into Pterodactyl's admin nav).
     */
    private function extendAdminNavigation(): void
    {
        // Use view composer to inject shop navigation into admin layout
        View::composer('layouts.admin', function ($view) {
            // Only inject for admin users on admin pages
            $user = auth()->user();
            if (!$user || !$user->root_admin) {
                return;
            }
            
            // Add shop navigation data to the view
            $view->with('shopNavigation', [
                'enabled' => true,
                'admin_menu' => [
                    [
                        'name' => 'Shop Management',
                        'icon' => 'fa-shopping-bag',
                        'children' => [
                            ['name' => 'Dashboard', 'route' => 'admin.shop.index', 'icon' => 'fa-dashboard'],
                            ['name' => 'Plans', 'route' => 'admin.shop.plans.index', 'icon' => 'fa-list'],
                            ['name' => 'Categories', 'route' => 'admin.shop.categories.index', 'icon' => 'fa-folder'],
                            ['name' => 'Orders', 'route' => 'admin.shop.orders.index', 'icon' => 'fa-shopping-cart'],
                            ['name' => 'Coupons', 'route' => 'admin.shop.coupons.index', 'icon' => 'fa-tags'],
                            ['name' => 'Analytics', 'route' => 'admin.shop.analytics.index', 'icon' => 'fa-bar-chart'],
                            ['name' => 'Settings', 'route' => 'admin.shop.settings.index', 'icon' => 'fa-gear'],
                        ]
                    ]
                ]
            ]);
        });
    }
}
