<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Database\VersionService;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\Files\BackupService;
use Pterodactyl\Services\Updates\ValidationService;
use Pterodactyl\Services\Updates\HealthService;

/**
 * Update Service Provider (Simplified)
 * 
 * Registers only the basic update services needed for the dashboard to work.
 * Complex services are disabled until constructor issues are resolved.
 */
class UpdateServiceProviderSimple extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register GitHub Services
        $this->app->singleton(GitHubReleaseService::class, function ($app) {
            return new GitHubReleaseService();
        });

        // Register Basic Database Services  
        $this->app->singleton(VersionService::class, function ($app) {
            return new VersionService();
        });

        $this->app->singleton(SessionService::class, function ($app) {
            return new SessionService();
        });

        // Register File Services
        $this->app->singleton(BackupService::class, function ($app) {
            return new BackupService();
        });

        // Register ValidationService for UpdateController
        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService();
        });

        // Register Health Service
        $this->app->singleton(HealthService::class, function ($app) {
            return new HealthService(
                app(\Pterodactyl\Contracts\Repository\SettingsRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}