<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Services\SimpleUpdateService;

/**
 * Update Service Provider (Fixed)
 * 
 * Registers only services that actually exist.
 * The SimpleUpdateService is the main working update service.
 * 
 * Note: Previously tried to register non-existent services which caused
 * fatal errors. Now only registers what exists in the codebase.
 */
class UpdateServiceProviderSimple extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the main SimpleUpdateService - this is the one that actually exists and works
        $this->app->singleton(SimpleUpdateService::class, function ($app) {
            return new SimpleUpdateService();
        });

        /* 
         * COMMENTED OUT: These services don't exist in the codebase and were causing fatal errors
         * 
         * - GitHubReleaseService::class (app/Services/Updates/GitHub/ doesn't exist)
         * - VersionService::class (app/Services/Updates/Database/ doesn't exist) 
         * - SessionService::class (app/Services/Updates/Database/ doesn't exist)
         * - BackupService::class (app/Services/Updates/Files/ doesn't exist)
         * - ValidationService::class (app/Services/Updates/ doesn't exist)
         * - HealthService::class (app/Services/Updates/ doesn't exist)
         * 
         * If these services are needed, they must be created first before being registered here.
         */
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}