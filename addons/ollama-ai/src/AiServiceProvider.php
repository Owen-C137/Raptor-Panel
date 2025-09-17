<?php

namespace PterodactylAddons\OllamaAi;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use PterodactylAddons\OllamaAi\Commands\InstallAiCommand;
use PterodactylAddons\OllamaAi\Commands\UninstallAiCommand;
use PterodactylAddons\OllamaAi\Services\OllamaService;
use PterodactylAddons\OllamaAi\Services\AiPerformanceOptimizationService;
use PterodactylAddons\OllamaAi\Services\AiUiUxOptimizationService;
use PterodactylAddons\OllamaAi\Services\AiTestingQualityAssuranceService;

/**
 * Main service provider for the Ollama AI Addon.
 * 
 * This provider handles:
 * - Service registration and dependency injection
 * - Configuration loading from addon directory
 * - Route registration for admin, client, and API endpoints
 * - View namespace registration
 * - Database migration loading
 * - Command registration for installation and management
 */
class AiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the core Ollama service
        $this->app->singleton(OllamaService::class, function ($app) {
            return new OllamaService();
        });

        // Register Phase 5 optimization services
        $this->app->singleton(AiPerformanceOptimizationService::class, function ($app) {
            return new AiPerformanceOptimizationService();
        });

        $this->app->singleton(AiUiUxOptimizationService::class, function ($app) {
            return new AiUiUxOptimizationService();
        });

        $this->app->singleton(AiTestingQualityAssuranceService::class, function ($app) {
            return new AiTestingQualityAssuranceService();
        });

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TestScrapingCommand::class,
                Commands\TestLibraryCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register navigation injection middleware
        $this->app['router']->pushMiddlewareToGroup('web', \PterodactylAddons\OllamaAi\Http\Middleware\InjectAiNavigation::class);

        // Load configuration from addon directory
        $this->mergeConfigFrom(
            base_path('addons/ollama-ai/config/ai.php'), 
            'ai'
        );

        // Load views from addon directory with 'ollama-ai' namespace
        $this->loadViewsFrom(
            base_path('addons/ollama-ai/resources/views'), 
            'ollama-ai'
        );

        // Load migrations from addon directory
        $this->loadMigrationsFrom(
            base_path('addons/ollama-ai/database/migrations')
        );

        // Load routes from addon directory with proper middleware
        $this->loadAiRoutes();
        
        // Load client and API routes normally
        $this->loadRoutesFrom(base_path('addons/ollama-ai/routes/client.php'));
        $this->loadRoutesFrom(base_path('addons/ollama-ai/routes/api.php'));

        // Publish configuration (optional for users to customize)
        $this->publishes([
            base_path('addons/ollama-ai/config/ai.php') => config_path('ai.php'),
        ], 'ai-config');

        // Register event listeners for UI integration
        $this->registerEventListeners();

        // Register middleware if needed
        $this->registerMiddleware();
    }

    /**
     * Load AI routes with proper middleware configuration.
     */
    protected function loadAiRoutes(): void
    {
        // Load admin routes with proper authentication middleware
        Route::middleware(['web', 'auth.session', 
            \Pterodactyl\Http\Middleware\RequireTwoFactorAuthentication::class, 
            \Pterodactyl\Http\Middleware\AdminAuthenticate::class
        ])
        ->prefix('admin/ai')
        ->group(base_path('addons/ollama-ai/routes/admin.php'));
    }

    /**
     * Register event listeners for seamless UI integration.
     */
    protected function registerEventListeners(): void
    {
        // Only register if AI is enabled
        if (!config('ai.enabled', true)) {
            return;
        }

        // Admin dashboard widget integration
        if ($this->app->bound('events')) {
            $this->app['events']->listen('admin.dashboard.widgets', function ($widgets) {
                // Add AI dashboard widgets here in future phases
                // $widgets[] = new AiInsightsWidget();
                // $widgets[] = new AiAlertsWidget();
            });

            // Client panel integration
            $this->app['events']->listen('client.dashboard.widgets', function ($widgets) {
                // Add client AI widgets here in future phases
                // $widgets[] = new AiAssistantWidget();
            });
        }
    }

    /**
     * Register any custom middleware.
     */
    protected function registerMiddleware(): void
    {
        // AI-specific middleware can be registered here
        // For example: rate limiting, authentication checks, etc.
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            OllamaService::class,
        ];
    }
}