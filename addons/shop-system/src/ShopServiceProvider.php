<?php

namespace PterodactylAddons\ShopSystem;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Console\Scheduling\Schedule;
use PterodactylAddons\ShopSystem\Services\BillingService;

class ShopServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'shop');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'shop');

        // Register policies
        $this->registerPolicies();

        // Register console commands
        $this->registerConsoleCommands();

        // Schedule tasks
        $this->scheduleShopTasks();

        // Publish assets for customization
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shop.php' => config_path('shop.php'),
            ], 'shop-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/shop'),
            ], 'shop-views');

            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/shop/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/shop/css'),
            ], 'shop-assets');
        }

        // Add shop navigation to admin panel
        $this->extendAdminNavigation();
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
            $this->commands([
                \PterodactylAddons\ShopSystem\Console\Commands\ProcessShopOrdersCommand::class,
            ]);
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
     * Extend admin navigation (hook into Pterodactyl's admin nav).
     */
    private function extendAdminNavigation(): void
    {
        // This will be implemented based on Pterodactyl's navigation system
        // For now, we'll use a view composer or middleware to inject nav items
    }
}
