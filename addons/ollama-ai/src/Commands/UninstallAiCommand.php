<?php

namespace PterodactylAddons\OllamaAi\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiMessage;

/**
 * Uninstall command for Ollama AI Addon.
 * 
 * Handles complete addon removal including:
 * - Service provider unregistration
 * - Database cleanup (with confirmation)
 * - Configuration removal
 */
class UninstallAiCommand extends Command
{
    protected $signature = 'ai:uninstall 
                           {--keep-data : Keep conversation data and only remove addon code}
                           {--force : Skip confirmation prompts}';
    
    protected $description = 'Uninstall the Ollama AI addon from Pterodactyl';

    public function handle(): int
    {
        $this->error('🗑️  Uninstalling Ollama AI Addon...');
        $this->newLine();

        // Show what will be removed
        $this->showUninstallPlan();

        // Confirmation (unless forced)
        if (!$this->option('force') && !$this->confirmUninstall()) {
            $this->info('❌ Uninstallation cancelled.');
            return 1;
        }

        try {
            // Step 1: Remove service provider
            $this->task('Removing service provider registration', function () {
                return $this->unregisterServiceProvider();
            });

            // Step 2: Handle database data
            if (!$this->option('keep-data')) {
                $this->task('Removing database data', function () {
                    return $this->removeData();
                });

                $this->task('Rolling back migrations', function () {
                    return $this->rollbackMigrations();
                });
            }

            // Step 3: Clear caches
            $this->task('Clearing application caches', function () {
                return $this->clearCaches();
            });

            $this->newLine();
            $this->info('✅ Ollama AI Addon uninstalled successfully!');
            
            if ($this->option('keep-data')) {
                $this->info('💾 Conversation data has been preserved.');
                $this->info('📝 To remove data later, run: php artisan ai:cleanup --all');
            }

            $this->newLine();
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Uninstallation failed: ' . $e->getMessage());
            $this->newLine();
            return 1;
        }
    }

    /**
     * Show what will be uninstalled
     */
    protected function showUninstallPlan(): void
    {
        $keepData = $this->option('keep-data');
        
        $this->info('📋 Uninstall Plan:');
        $this->newLine();
        
        $this->line('✓ Remove service provider from config/app.php');
        $this->line('✓ Clear application caches');
        
        if (!$keepData) {
            // Count existing data
            $conversations = 0;
            $messages = 0;
            
            try {
                $conversations = AiConversation::count();
                $messages = AiMessage::count();
            } catch (\Exception $e) {
                // Tables might not exist
            }
            
            $this->line("✓ Remove {$conversations} AI conversations");
            $this->line("✓ Remove {$messages} AI messages");
            $this->line('✓ Drop AI database tables');
        } else {
            $this->line('⚠️  Keep all AI data (--keep-data flag used)');
        }
        
        $this->newLine();
        
        $this->info('📁 Files that will remain:');
        $this->line('• addons/ollama-ai/ (addon directory - can be manually removed)');
        
        if ($keepData) {
            $this->line('• AI database tables and data');
        }
        
        $this->newLine();
    }

    /**
     * Confirm uninstallation with user
     */
    protected function confirmUninstall(): bool
    {
        $keepData = $this->option('keep-data');
        
        if (!$keepData) {
            $this->error('⚠️  WARNING: This will permanently delete ALL AI conversation data!');
            $this->newLine();
            
            if (!$this->confirm('Are you absolutely sure you want to continue?')) {
                return false;
            }
            
            $this->newLine();
            if (!$this->confirm('Type "DELETE ALL DATA" to confirm data destruction:', false)) {
                $response = $this->ask('Please type "DELETE ALL DATA" to confirm');
                if ($response !== 'DELETE ALL DATA') {
                    $this->error('❌ Confirmation failed. Uninstallation cancelled.');
                    return false;
                }
            }
        } else {
            if (!$this->confirm('Continue with uninstallation (data will be preserved)?')) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Remove service provider registration
     */
    protected function unregisterServiceProvider(): bool
    {
        try {
            // Remove registration file
            $registrationFile = base_path('.pterodactyl/addons/ollama-ai.json');
            if (File::exists($registrationFile)) {
                File::delete($registrationFile);
            }
            
            // Remove from composer autoload
            $this->removeFromComposerAutoload();
            
            // Regenerate composer autoload
            exec('cd ' . base_path() . ' && composer dump-autoload', $output, $returnCode);
            if ($returnCode !== 0) {
                throw new \Exception('Failed to regenerate composer autoload');
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to unregister service provider: ' . $e->getMessage());
        }
    }

    /**
     * Remove addon from composer autoload PSR-4
     */
    protected function removeFromComposerAutoload(): bool
    {
        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) {
            return true;
        }

        $composer = json_decode(File::get($composerPath), true);
        
        // Remove from autoload PSR-4
        if (isset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\'])) {
            unset($composer['autoload']['psr-4']['PterodactylAddons\\OllamaAi\\']);
            
            // Write back to composer.json
            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        
        return true;
    }

    /**
     * Remove all AI data from database
     */
    protected function removeData(): bool
    {
        try {
            // Delete in correct order (foreign key constraints)
            AiMessage::truncate();
            AiConversation::truncate();
            
            // Also clear other AI tables if they exist
            if (\Schema::hasTable('ai_analysis_results')) {
                \DB::table('ai_analysis_results')->truncate();
            }
            
            if (\Schema::hasTable('ai_insights')) {
                \DB::table('ai_insights')->truncate();
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to remove data: ' . $e->getMessage());
        }
    }

    /**
     * Rollback database migrations
     */
    protected function rollbackMigrations(): bool
    {
        try {
            // Get migration files
            $migrationPath = base_path('addons/ollama-ai/database/migrations');
            $migrations = File::glob($migrationPath . '/*.php');
            
            // Drop tables in reverse order
            $tables = [
                'ai_insights',
                'ai_analysis_results', 
                'ai_messages',
                'ai_conversations',
            ];
            
            foreach ($tables as $table) {
                if (\Schema::hasTable($table)) {
                    \Schema::dropIfExists($table);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to rollback migrations: ' . $e->getMessage());
        }
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
}