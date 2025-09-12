<?php

namespace PterodactylAddons\ShopSystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;
use PterodactylAddons\ShopSystem\Providers\ShopNavigationServiceProvider;

class ShopSystemServiceProvider extends ServiceProvider
{
    /**
     * Register any addon services.
     */
    public function register(): void
    {
        // Merge addon configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shop.php', 
            'shop'
        );

        // Register repositories
        $this->registerRepositories();
        
        // Register services
        $this->registerServices();
        
        // Register navigation provider
        $this->app->register(ShopNavigationServiceProvider::class);
        
        // Register middleware
        $this->registerMiddleware();
    }

    /**
     * Bootstrap any addon services.
     */
    public function boot(): void
    {
        // Load addon migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load addon views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'shop');

        // Publish addon assets
        $this->publishes([
            __DIR__ . '/../config/shop.php' => config_path('shop.php'),
        ], 'shop-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/shop'),
        ], 'shop-views');

        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/shop/css'),
            __DIR__ . '/../resources/js' => public_path('vendor/shop/js'),
        ], 'shop-assets');

        // Register routes
        $this->registerShopRoutes();
        
        // Schedule jobs
        $this->scheduleShopTasks();
        
        // Boot addon functionality
        $this->bootShopFeatures();
    }
    
    /**
     * Register repository bindings
     */
    private function registerRepositories(): void
    {
        $this->app->bind(
            'PterodactylAddons\\ShopSystem\\Contracts\\ShopPlanRepositoryInterface',
            'PterodactylAddons\\ShopSystem\\Repositories\\ShopPlanRepository'
        );
        
        $this->app->bind(
            'PterodactylAddons\\ShopSystem\\Contracts\\ShopOrderRepositoryInterface',
            'PterodactylAddons\\ShopSystem\\Repositories\\ShopOrderRepository'
        );
        
        $this->app->bind(
            'PterodactylAddons\\ShopSystem\\Contracts\\UserWalletRepositoryInterface',
            'PterodactylAddons\\ShopSystem\\Repositories\\UserWalletRepository'
        );
        
        $this->app->bind(
            'PterodactylAddons\\ShopSystem\\Contracts\\ShopPaymentRepositoryInterface',
            'PterodactylAddons\\ShopSystem\\Repositories\\ShopPaymentRepository'
        );
        
        $this->app->bind(
            'PterodactylAddons\\ShopSystem\\Contracts\\ShopCouponRepositoryInterface',
            'PterodactylAddons\\ShopSystem\\Repositories\\ShopCouponRepository'
        );
    }
    
    /**
     * Register service bindings
     */
    private function registerServices(): void
    {
        $this->app->singleton('PterodactylAddons\\ShopSystem\\Services\\ShopOrderService');
        $this->app->singleton('PterodactylAddons\\ShopSystem\\Services\\WalletService');
        $this->app->singleton('PterodactylAddons\\ShopSystem\\Services\\PaymentGatewayManager');
        $this->app->singleton('PterodactylAddons\\ShopSystem\\Services\\ShopNotificationService');
    }
    
    /**
     * Schedule shop-related tasks
     */
    private function scheduleShopTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Process order renewals every hour
            $schedule->job(\PterodactylAddons\ShopSystem\Jobs\ProcessOrderRenewalsJob::class)
                ->hourly()
                ->name('shop:process-renewals')
                ->withoutOverlapping();

            // Send renewal notifications daily at 9 AM  
            $schedule->command('shop:send-notifications')
                ->dailyAt('09:00')
                ->name('shop:renewal-notifications')
                ->withoutOverlapping();

            // Suspend overdue orders daily at 10 AM
            $schedule->command('shop:suspend-overdue')
                ->dailyAt('10:00')
                ->name('shop:suspend-overdue')
                ->withoutOverlapping();

            // Terminate long-overdue orders daily at 11 AM
            $schedule->command('shop:terminate-overdue')
                ->dailyAt('11:00')
                ->name('shop:terminate-overdue')
                ->withoutOverlapping();
        });
    }
    
    /**
     * Register shop routes
     */
    private function registerShopRoutes(): void
    {
        // Admin routes are loaded by the main RouteServiceProvider to avoid conflicts
        // This allows proper integration with Pterodactyl's existing route structure
        
        // Load web routes  
        if (file_exists(__DIR__ . '/../routes/web.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
        
        // Load API routes
        if (file_exists(__DIR__ . '/../routes/api.php')) {
            Route::prefix('api')
                ->middleware(['api', 'auth:sanctum'])
                ->group(__DIR__ . '/../routes/api.php');
        }
        
        // Test routes for debugging
        Route::middleware(['web'])
            ->prefix('shop')
            ->name('shop.')
            ->group(function () {
                Route::get('/test', function() {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Shop system routes are working!',
                        'timestamp' => now(),
                        'addon' => 'Shop System v1.0',
                        'database_tables' => \Schema::hasTable('shop_products') ? 'Connected' : 'Missing'
                    ]);
                })->name('test');
            });
    }
    
    /**
     * Boot additional shop features
     */
    private function bootShopFeatures(): void
    {
        // Register shop commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PterodactylAddons\ShopSystem\Commands\ShopInstallCommand::class,
                \PterodactylAddons\ShopSystem\Commands\ShopUninstallCommand::class,
                \PterodactylAddons\ShopSystem\Commands\ProcessShopOrdersCommand::class,
            ]);
        }
    }
    
    /**
     * Register custom middleware
     */
    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        
        // Register middleware aliases
        $router->aliasMiddleware('shop.enabled', \PterodactylAddons\ShopSystem\Http\Middleware\CheckShopEnabled::class);
        $router->aliasMiddleware('shop.credits', \PterodactylAddons\ShopSystem\Http\Middleware\CheckCreditsEnabled::class);
    }
}

/**
 * Helper function to check if shop is enabled
 */
if (!function_exists('shop_enabled')) {
    function shop_enabled(): bool
    {
        return config('shop.enabled', false);
    }
}

/**
 * Helper function to format shop currency
 */
if (!function_exists('shop_currency')) {
    function shop_currency(float $amount): string
    {
        $symbol = config('shop.currency_symbol', '$');
        $currency = config('shop.currency', 'USD');
        
        return $symbol . number_format($amount, 2);
    }
}

/**
 * Helper function to get shop cart count
 */
if (!function_exists('shop_cart_count')) {
    function shop_cart_count(): int
    {
        if (!auth()->check()) {
            return 0;
        }
        
        return session('shop_cart', collect())->sum('quantity');
    }
}
