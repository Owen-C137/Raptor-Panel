<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class ModManagerStatusCommand extends Command
{
    protected $signature = 'mod-manager:status';
    protected $description = 'Display the current status of the Mod Manager addon';

    public function handle(): int
    {
        $this->info('📊 Mod Manager Addon Status');
        $this->info('============================');
        $this->info('');

        $this->displayInstallationStatus();
        $this->displayDatabaseStatus();
        $this->displayApiStatus();
        $this->displaySystemStatus();
        $this->displayJobStatus();

        return self::SUCCESS;
    }

    private function displayInstallationStatus(): void
    {
        $this->info('🔧 Installation Status:');
        
        // Check service provider registration
        $providerRegistered = $this->checkServiceProvider();
        $this->line("   Service Provider: " . ($providerRegistered ? '✅ Registered' : '❌ Not Registered'));
        
        // Check PSR-4 autoloading
        $autoloadConfigured = $this->checkAutoloading();
        $this->line("   PSR-4 Autoloading: " . ($autoloadConfigured ? '✅ Configured' : '❌ Not Configured'));
        
        // Check addon structure
        $structureValid = $this->checkAddonStructure();
        $this->line("   Addon Structure: " . ($structureValid ? '✅ Valid' : '❌ Invalid'));
        
        $this->info('');
    }

    private function displayDatabaseStatus(): void
    {
        $this->info('🗄️  Database Status:');
        
        try {
            $tables = [
                'mod_games' => 'Games',
                'mod_categories' => 'Categories', 
                'mod_mods' => 'Mods',
                'mod_files' => 'Files',
                'mod_installations' => 'Installations',
                'mod_harvest_jobs' => 'Harvest Jobs',
            ];

            foreach ($tables as $table => $label) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->line("   {$label}: ✅ {$count} records");
                } else {
                    $this->line("   {$label}: ❌ Table missing");
                }
            }
        } catch (\Exception $e) {
            $this->line("   Database: ❌ Connection failed - " . $e->getMessage());
        }
        
        $this->info('');
    }

    private function displayApiStatus(): void
    {
        $this->info('🌐 API Status:');
        
        try {
            $apiService = app(CurseForgeApiService::class);
            $stats = $apiService->getApiStats();
            
            $this->line("   API Key: " . ($stats['api_key_configured'] ? '✅ Configured' : '❌ Missing'));
            $this->line("   Base URL: {$stats['base_url']}");
            $this->line("   Rate Limit: {$stats['rate_limit']['calls_per_second']}/sec");
            
            // Test connectivity
            $testResult = $apiService->testConnection();
            $this->line("   Connectivity: " . ($testResult['success'] ? '✅ Connected' : '❌ Failed'));
            
            if (!$testResult['success']) {
                $this->line("      Error: " . $testResult['message']);
            }
            
        } catch (\Exception $e) {
            $this->line("   API Service: ❌ Error - " . $e->getMessage());
        }
        
        $this->info('');
    }

    private function displaySystemStatus(): void
    {
        $this->info('⚙️  System Status:');
        
        // PHP version
        $this->line("   PHP Version: " . PHP_VERSION);
        
        // Required extensions
        $extensions = ['curl', 'json', 'zip', 'pdo_mysql'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->line("   {$ext}: " . ($loaded ? '✅ Loaded' : '❌ Missing'));
        }
        
        // Cache status
        $cacheDriver = config('cache.default');
        $this->line("   Cache Driver: {$cacheDriver}");
        
        // Queue status
        $queueDriver = config('queue.default');
        $this->line("   Queue Driver: {$queueDriver}");
        
        // Storage directories
        $storageDir = storage_path('app/mod-manager');
        $storageExists = File::exists($storageDir);
        $this->line("   Storage Directory: " . ($storageExists ? '✅ Exists' : '❌ Missing'));
        
        $this->info('');
    }

    private function displayJobStatus(): void
    {
        $this->info('🚀 Job Status:');
        
        try {
            if (DB::getSchemaBuilder()->hasTable('mod_harvest_jobs')) {
                $statuses = DB::table('mod_harvest_jobs')
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get();
                
                if ($statuses->count() > 0) {
                    foreach ($statuses as $status) {
                        $icon = match($status->status) {
                            'completed' => '✅',
                            'running' => '🔄',
                            'failed' => '❌',
                            'pending' => '⏳',
                            'cancelled' => '⏹️',
                            default => '📋'
                        };
                        $this->line("   {$status->status}: {$icon} {$status->count} jobs");
                    }
                } else {
                    $this->line("   No harvest jobs found");
                }
                
                // Recent activity
                $recentJobs = DB::table('mod_harvest_jobs')
                    ->orderBy('updated_at', 'desc')
                    ->limit(3)
                    ->get(['job_name', 'status', 'updated_at']);
                
                if ($recentJobs->count() > 0) {
                    $this->line('');
                    $this->line('   Recent Jobs:');
                    foreach ($recentJobs as $job) {
                        $this->line("   • {$job->job_name} ({$job->status}) - {$job->updated_at}");
                    }
                }
            } else {
                $this->line("   Harvest Jobs Table: ❌ Not created yet");
            }
        } catch (\Exception $e) {
            $this->line("   Job Status: ❌ Error - " . $e->getMessage());
        }
        
        $this->info('');
    }

    private function checkServiceProvider(): bool
    {
        $configPath = config_path('app.php');
        if (!File::exists($configPath)) {
            return false;
        }
        
        $config = File::get($configPath);
        return str_contains($config, 'ModManagerServiceProvider');
    }

    private function checkAutoloading(): bool
    {
        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) {
            return false;
        }
        
        $composer = json_decode(File::get($composerPath), true);
        return isset($composer['autoload']['psr-4']['PterodactylAddons\\ModManager\\']);
    }

    private function checkAddonStructure(): bool
    {
        $requiredPaths = [
            'addons/mod-manager/src/Providers/ModManagerServiceProvider.php',
            'addons/mod-manager/src/Commands/ModManagerInstallCommand.php',
            'addons/mod-manager/src/Services/CurseForgeApiService.php',
            'addons/mod-manager/config/mod-manager.php',
            'addons/mod-manager/composer.json',
        ];

        foreach ($requiredPaths as $path) {
            if (!File::exists(base_path($path))) {
                return false;
            }
        }

        return true;
    }
}