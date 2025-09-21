<?php

namespace Pterodactyl\Providers;

use Illuminate\Support\ServiceProvider;
use Pterodactyl\Services\Updates\Database\MigrationService;
use Pterodactyl\Services\Updates\Database\EnhancedMigrationService;
use Pterodactyl\Services\Updates\Database\MigrationDetectionService;
use Pterodactyl\Services\Updates\Database\MigrationDependencyService;
use Pterodactyl\Services\Updates\Database\MigrationValidationService;
use Pterodactyl\Services\Updates\Database\MigrationExecutionService;
use Pterodactyl\Services\Updates\Database\MigrationConflictService;
use Pterodactyl\Services\Updates\Database\MigrationRollbackService;
use Pterodactyl\Services\Updates\Database\MigrationTestingService;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\Database\VersionService;
use Pterodactyl\Services\Updates\Files\ArchiveService;
use Pterodactyl\Services\Updates\Files\BackupService;
use Pterodactyl\Services\Updates\Files\FileUpdateService;
use Pterodactyl\Services\Updates\GitHub\GitHubFileService;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Progress\ProgressTracker;
use Pterodactyl\Services\Updates\SystemHealthService;
use Pterodactyl\Services\Updates\UpdateOrchestrator;
use Pterodactyl\Services\Updates\UpdateServiceInterface;
use Pterodactyl\Services\Updates\Validation\ValidationService;

/**
 * Update Service Provider
 * 
 * Registers all update-related services with Laravel's service container
 * and configures dependency injection for the update system.
 */
class UpdateServiceProvider extends ServiceProvider
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

        $this->app->singleton(GitHubFileService::class, function ($app) {
            return new GitHubFileService();
        });

        /*
        // DISABLED: Complex migration services until constructors are fixed
        // Register Migration Services (Advanced Phase 4 Components)
        $this->app->singleton(MigrationDetectionService::class, function ($app) {
            return new MigrationDetectionService();
        });
        */

        /*
        // DISABLED: Complex migration services until constructors are fixed
        
        $this->app->singleton(MigrationDependencyService::class, function ($app) {
            return new MigrationDependencyService();
        });

        $this->app->singleton(MigrationValidationService::class, function ($app) {
            return new MigrationValidationService();
        });

        $this->app->singleton(MigrationExecutionService::class, function ($app) {
            return new MigrationExecutionService();
        });

        $this->app->singleton(MigrationConflictService::class, function ($app) {
            return new MigrationConflictService();
        });

        $this->app->singleton(MigrationRollbackService::class, function ($app) {
            return new MigrationRollbackService();
        });

        $this->app->singleton(MigrationTestingService::class, function ($app) {
            return new MigrationTestingService(
                $app->make(MigrationExecutionService::class),
                $app->make(MigrationValidationService::class),
                $app->make(MigrationRollbackService::class)
            );
        });

        // Register Enhanced Migration Service (orchestrates all migration components)
        $this->app->singleton(EnhancedMigrationService::class, function ($app) {
            return new EnhancedMigrationService(
                $app->make(MigrationDetectionService::class),
                $app->make(MigrationDependencyService::class),
                $app->make(MigrationValidationService::class),
                $app->make(MigrationExecutionService::class),
                $app->make(MigrationConflictService::class),
                $app->make(MigrationRollbackService::class),
                $app->make(MigrationTestingService::class)
            );
        });
        */

        // Register Basic Database Services
        $this->app->singleton(VersionService::class, function ($app) {
            return new VersionService();
        });

        $this->app->singleton(SessionService::class, function ($app) {
            return new SessionService();
        });

        // Register Legacy Migration Service for backward compatibility
        $this->app->singleton(MigrationService::class, function ($app) {
            return new MigrationService();
        });

        // Register File Services
        $this->app->singleton(BackupService::class, function ($app) {
            return new BackupService();
        });

        $this->app->singleton(FileUpdateService::class, function ($app) {
            return new FileUpdateService();
        });

        $this->app->singleton(ArchiveService::class, function ($app) {
            return new ArchiveService();
        });

        // Register Progress and Validation Services
        $this->app->singleton(ProgressTracker::class, function ($app) {
            return new ProgressTracker();
        });

        $this->app->singleton(\Pterodactyl\Services\Updates\ValidationService::class, function ($app) {
            return new \Pterodactyl\Services\Updates\ValidationService();
        });

        // Also register the Validation namespace ValidationService for enhanced migration system
        $this->app->singleton(ValidationService::class, function ($app) {
            return new ValidationService();
        });

        // Register Health Service
        $this->app->singleton(\Pterodactyl\Services\Updates\HealthService::class, function ($app) {
            return new \Pterodactyl\Services\Updates\HealthService();
        });

        // Register System Health Service for real-time monitoring
        $this->app->singleton(SystemHealthService::class, function ($app) {
            return new SystemHealthService();
        });

        // Register Main Orchestrator with Enhanced Migration Service
        $this->app->singleton(UpdateOrchestrator::class, function ($app) {
            return new UpdateOrchestrator(
                $app->make(GitHubReleaseService::class),
                $app->make(GitHubFileService::class),
                $app->make(VersionService::class),
                $app->make(SessionService::class),
                $app->make(EnhancedMigrationService::class), // Use enhanced migration service
                $app->make(BackupService::class),
                $app->make(FileUpdateService::class),
                $app->make(ArchiveService::class),
                $app->make(ProgressTracker::class),
                $app->make(ValidationService::class)
            );
        });

        // Register UpdateOrchestrationService (legacy compatibility)
        $this->app->singleton(\Pterodactyl\Services\Updates\UpdateOrchestrationService::class, function ($app) {
            return new \Pterodactyl\Services\Updates\UpdateOrchestrationService(
                $app->make(SessionService::class),
                $app->make(VersionService::class),
                $app->make(BackupService::class),
                $app->make(FileUpdateService::class),
                $app->make(ArchiveService::class),
                $app->make(GitHubFileService::class),
                $app->make(GitHubReleaseService::class),
                $app->make(EnhancedMigrationService::class),
                $app->make(\Pterodactyl\Services\Updates\ValidationService::class)
            );
        });

        // Bind interface to orchestrator for easy access
        $this->app->bind(UpdateServiceInterface::class, UpdateOrchestrator::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../../config/updates.php' => config_path('updates.php'),
        ], 'raptor-updates-config');

        // Load configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/updates.php',
            'updates'
        );

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->registerConsoleCommands();
        }
    }

    /**
     * Register console commands for update management.
     */
    private function registerConsoleCommands(): void
    {
        $this->commands([
            \Pterodactyl\Console\Commands\Updates\CheckUpdatesCommand::class,
            \Pterodactyl\Console\Commands\Updates\PerformUpdateCommand::class,
            \Pterodactyl\Console\Commands\Updates\RollbackUpdateCommand::class,
            \Pterodactyl\Console\Commands\Updates\ValidateSystemCommand::class,
            \Pterodactyl\Console\Commands\Updates\CleanupUpdatesCommand::class,
        ]);
    }
}