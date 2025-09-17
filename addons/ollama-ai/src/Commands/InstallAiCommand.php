<?php

namespace PterodactylAddons\OllamaAi\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

/**
 * Install command for Ollama AI Addon.
 * 
 * Handles complete addon installation including:
 * - Service provider registration
 * - Database migrations
 * - Configuration validation
 * - Model setup guidance
 */
class InstallAiCommand extends Command
{
    protected $signature = 'ai:install {--force : Force reinstallation}';
    protected $description = 'Install the Ollama AI addon for Pterodactyl';

    public function handle(): int
    {
        $this->info('🤖 Installing Ollama AI Addon...');
        $this->newLine();
        
        $force = $this->option('force');

        try {
            // Step 1: Check for existing installation
            if (!$force && $this->isAlreadyInstalled()) {
                $this->error('❌ Ollama AI Addon is already installed!');
                $this->newLine();
                $this->info('💡 Use --force to reinstall');
                return 1;
            }            // Step 2: Register service provider
            $this->info('📝 Registering service provider...');
            if ($this->registerServiceProvider()) {
                $this->info('✅ Service provider registered successfully');
            } else {
                throw new \Exception('Failed to register service provider');
            }

            // Step 3: Run migrations
            $this->info('🗄️  Running database migrations...');
            if ($this->runMigrations()) {
                $this->info('✅ Database migrations completed');
            } else {
                throw new \Exception('Failed to run migrations');
            }

            // Step 4: Clear caches
            $this->info('🧹 Clearing application caches...');
            if ($this->clearCaches()) {
                $this->info('✅ Caches cleared successfully');
            } else {
                $this->info('⚠️  Cache clearing had some issues (non-critical)');
            }

            // Step 5: Validate installation
            $this->info('🔍 Validating installation...');
            if ($this->validateInstallation()) {
                $this->info('✅ Installation validation passed');
            } else {
                throw new \Exception('Installation validation failed');
            }

            $this->newLine();
            $this->info('🎉 Ollama AI Addon installed successfully!');
            $this->newLine();
            $this->info('📚 Next steps:');
            $this->info('   1. Configure Ollama server endpoint in Admin panel');
            $this->info('   2. Download AI models using: ollama pull llama3.1:8b');
            $this->info('   3. Start using AI features in Pterodactyl!');
            $this->newLine();
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            $this->newLine();
            $this->info('🔍 Check the logs for more details.');
            return 1;
        }
    }

    /**
     * Check if addon is already installed
     */
    protected function isAlreadyInstalled(): bool
    {
        // Check if registration file exists
        $registrationPath = base_path('addons/ollama-ai/.pterodactyl');
        
        if (!File::exists($registrationPath)) {
            return false;
        }
        
        // Check if autoload is configured
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            return isset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\']);
        }
        
        return false;
    }

    /**
     * Register the service provider using Pterodactyl's addon system
     */
    protected function registerServiceProvider(): bool
    {
        // Create the addon registration file for Pterodactyl to auto-load
        $registrationPath = base_path('addons/ollama-ai/.pterodactyl');
        
        if (!File::exists($registrationPath)) {
            $registrationData = [
                'name' => 'Ollama AI Addon',
                'version' => '1.5.0',
                'provider' => 'PterodactylAddons\\OllamaAi\\AiServiceProvider',
                'enabled' => true,
                'installed_at' => now()->toISOString(),
            ];
            
            File::put($registrationPath, json_encode($registrationData, JSON_PRETTY_PRINT));
        }

        // Update composer autoload to ensure our classes are discoverable
        $this->updateComposerAutoload();
        
        // Register the service provider dynamically for this installation
        app()->register(\PterodactylAddons\OllamaAi\AiServiceProvider::class);
        
        return true;
    }

    /**
     * Update composer autoload with addon PSR-4 mapping
     */
    protected function updateComposerAutoload(): bool
    {
        $composerPath = base_path('composer.json');
        
        if (!File::exists($composerPath)) {
            throw new \Exception('Composer file not found');
        }

        $composer = json_decode(File::get($composerPath), true);
        
        // Add PSR-4 autoload mapping for our addon
        if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'])) {
            $composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'] = 'addons/ollama-ai/src/';
            
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            // Regenerate autoload files
            exec('cd ' . base_path() . ' && composer dump-autoload', $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception('Failed to regenerate composer autoload');
            }
        }
        
        return true;
    }

    /**
     * Run database migrations
     */
    protected function runMigrations(): bool
    {
        try {
            Artisan::call('migrate', [
                '--path' => 'addons/ollama-ai/database/migrations',
                '--force' => true,
            ]);
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Migration failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate the installation
     */
    protected function validateInstallation(): bool
    {
        // Check if registration file exists
        if (!File::exists(base_path('addons/ollama-ai/.pterodactyl'))) {
            throw new \Exception('Addon registration file not created');
        }

        // Check if autoload is configured
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);
            if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'])) {
                throw new \Exception('Composer autoload not configured');
            }
        }

        // Check if service provider can be instantiated
        try {
            new \PterodactylAddons\OllamaAi\AiServiceProvider(app());
        } catch (\Exception $e) {
            throw new \Exception('Service provider cannot be instantiated: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Clear application caches
     */
    protected function clearCaches(): bool
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return true;
        } catch (\Exception $e) {
            // Non-critical error
            return true;
        }
    }

    /**
     * Show setup guidance to user
     */
    protected function showSetupGuidance(): void
    {
        $this->newLine();
        $this->info('🚀 Next Steps:');
        $this->newLine();

        $this->info('1. Install Ollama (if not already installed):');
        $this->line('   curl -fsSL https://ollama.com/install.sh | sh');
        $this->newLine();

        $this->info('2. Download AI models (choose based on your needs):');
        $this->line('   ollama pull llama3.1:8b     # For general chat (4.7GB)');
        $this->line('   ollama pull codellama:7b     # For code generation (3.8GB)');
        $this->line('   ollama pull mistral:7b       # For data analysis (4.1GB)');
        $this->line('   ollama pull gemma:7b         # For documentation (4.8GB)');
        $this->newLine();

        $this->info('3. Configure environment variables (optional):');
        $this->line('   AI_ENABLED=true');
        $this->line('   OLLAMA_HOST=localhost');
        $this->line('   OLLAMA_PORT=11434');
        $this->line('   AI_CHAT_MODEL=llama3.1:8b');
        $this->newLine();

        $this->info('4. Access AI settings:');
        $this->line('   Admin Panel > AI Settings (/admin/ai)');
        $this->newLine();

        $this->info('5. Test the installation:');
        $this->line('   php artisan ai:test');
        $this->newLine();
    }
}