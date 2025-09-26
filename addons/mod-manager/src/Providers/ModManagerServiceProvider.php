<?php

namespace PterodactylAddons\ModManager\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use PterodactylAddons\ModManager\Commands\ModManagerInstallCommand;
use PterodactylAddons\ModManager\Commands\ModManagerUninstallCommand;
use PterodactylAddons\ModManager\Commands\ModManagerStatusCommand;
use PterodactylAddons\ModManager\Commands\ModManagerVerifyCommand;
use PterodactylAddons\ModManager\Commands\ModManagerHarvestCategoriesCommand;
use PterodactylAddons\ModManager\Commands\ModManagerHarvestStatusCommand;
use PterodactylAddons\ModManager\Commands\ModManagerHarvestStopCommand;
use PterodactylAddons\ModManager\Commands\ModManagerPerformanceCommand;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class ModManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(
            base_path('addons/mod-manager/config/mod-manager.php'), 
            'mod-manager'
        );

        // Register CurseForge API service
        $this->app->singleton(CurseForgeApiService::class, function ($app) {
            return new CurseForgeApiService(
                config('mod-manager.curseforge.api_key'),
                config('mod-manager.curseforge.base_url'),
                config('mod-manager.curseforge.rate_limit')
            );
        });

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModManagerInstallCommand::class,
                ModManagerUninstallCommand::class,
                ModManagerPerformanceCommand::class,
                \PterodactylAddons\ModManager\Commands\RepairPermissionsCommand::class,
                ModManagerStatusCommand::class,
                ModManagerVerifyCommand::class,
                \PterodactylAddons\ModManager\Commands\TestApiStructureCommand::class,
                \PterodactylAddons\ModManager\Commands\ModManagerPublishAssetsCommand::class,
                \PterodactylAddons\ModManager\Commands\PrepareCacheDirectoriesCommand::class,
                \PterodactylAddons\ModManager\Commands\BenchmarkFileProcessingCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations from addon directory
        $this->loadMigrationsFrom(base_path('addons/mod-manager/database/migrations'));
        
        // Load views from addon directory  
        $this->loadViewsFrom(base_path('addons/mod-manager/resources/views'), 'mod-manager');
        
        // Load routes
        $this->loadRoutes();
        
        // Load configuration for publishing
        $this->publishes([
            base_path('addons/mod-manager/config/mod-manager.php') => config_path('mod-manager.php'),
        ], 'mod-manager-config');

        // Register event listeners and other boot logic here
        $this->registerEventListeners();
    }

    /**
     * Load routes for the mod manager
     */
    private function loadRoutes(): void
    {
        // Load web routes with admin middleware
        Route::middleware(['web', 'admin'])
            ->prefix('admin/mod-manager')
            ->name('admin.mod-manager.')
            ->group(base_path('addons/mod-manager/routes/web.php'));

        // Load API routes with admin middleware
        Route::middleware(['web', 'admin'])
            ->prefix('admin/mod-manager/api')
            ->name('admin.mod-manager.api.')
            ->group(base_path('addons/mod-manager/routes/api.php'));

        // Load client API routes - NO middleware here, let them be loaded by the main routes
        // This will be loaded by the main api-client.php route file
    }

    /**
     * Register event listeners for mod manager.
     */
    private function registerEventListeners(): void
    {
        // Register any event listeners here
        // For example: job completion events, progress updates, etc.
    }
}