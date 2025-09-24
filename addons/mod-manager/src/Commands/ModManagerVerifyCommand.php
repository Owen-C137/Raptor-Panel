<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PterodactylAddons\ModManager\Services\CurseForgeApiService;

class ModManagerVerifyCommand extends Command
{
    protected $signature = 'mod-manager:verify {--fix : Automatically fix issues where possible}';
    protected $description = 'Verify the Mod Manager addon installation and diagnose issues';

    public function handle(): int
    {
        $this->info('🔍 Mod Manager Addon Verification');
        $this->info('==================================');
        $this->info('');

        $issues = [];
        $fixable = [];

        $issues = array_merge($issues, $this->verifyInstallation($fixable));
        $issues = array_merge($issues, $this->verifyDatabase($fixable));
        $issues = array_merge($issues, $this->verifyApi($fixable));
        $issues = array_merge($issues, $this->verifyPermissions($fixable));

        $this->displayResults($issues, $fixable);

        if ($this->option('fix')) {
            $this->fixIssues($fixable);
        }

        return empty($issues) ? self::SUCCESS : self::FAILURE;
    }

    private function verifyInstallation(array &$fixable): array
    {
        $this->info('🔧 Verifying Installation...');
        $issues = [];

        // Check service provider registration
        $configPath = config_path('app.php');
        if (!File::exists($configPath)) {
            $issues[] = 'config/app.php file not found';
        } else {
            $config = File::get($configPath);
            if (!str_contains($config, 'ModManagerServiceProvider')) {
                $issues[] = 'Service provider not registered in config/app.php';
                $fixable[] = 'register_service_provider';
            }
        }

        // Check PSR-4 autoloading
        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) {
            $issues[] = 'composer.json file not found';
        } else {
            $composer = json_decode(File::get($composerPath), true);
            if (!isset($composer['autoload']['psr-4']['PterodactylAddons\\ModManager\\'])) {
                $issues[] = 'PSR-4 autoloading not configured in composer.json';
                $fixable[] = 'configure_autoloading';
            }
        }

        // Check addon structure
        $requiredFiles = [
            'addons/mod-manager/src/Providers/ModManagerServiceProvider.php' => 'Service Provider',
            'addons/mod-manager/src/Commands/ModManagerInstallCommand.php' => 'Install Command',
            'addons/mod-manager/src/Commands/ModManagerUninstallCommand.php' => 'Uninstall Command',
            'addons/mod-manager/src/Services/CurseForgeApiService.php' => 'API Service',
            'addons/mod-manager/config/mod-manager.php' => 'Configuration',
            'addons/mod-manager/composer.json' => 'Composer Config',
        ];

        foreach ($requiredFiles as $path => $name) {
            if (!File::exists(base_path($path))) {
                $issues[] = "{$name} file missing: {$path}";
            }
        }

        // Check required directories
        $requiredDirs = [
            'addons/mod-manager/src/Models',
            'addons/mod-manager/database/migrations',
            'addons/mod-manager/database/seeders',
        ];

        foreach ($requiredDirs as $dir) {
            if (!File::isDirectory(base_path($dir))) {
                $issues[] = "Required directory missing: {$dir}";
                $fixable[] = "create_directory:{$dir}";
            }
        }

        $this->line("   Found " . count($issues) . " installation issues");
        return $issues;
    }

    private function verifyDatabase(array &$fixable): array
    {
        $this->info('🗄️  Verifying Database...');
        $issues = [];

        try {
            // Test database connection
            DB::connection()->getPdo();
            $this->line("   ✅ Database connection successful");

            // Check required tables
            $requiredTables = [
                'mod_games' => 'Games table',
                'mod_categories' => 'Categories table',
                'mod_mods' => 'Mods table',
                'mod_files' => 'Files table',
                'mod_installations' => 'Installations table',
                'mod_harvest_jobs' => 'Harvest jobs table',
            ];

            foreach ($requiredTables as $table => $description) {
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    $issues[] = "{$description} missing: {$table}";
                    $fixable[] = "run_migrations";
                }
            }

            // Check table structure for key tables
            if (DB::getSchemaBuilder()->hasTable('mod_games')) {
                $columns = DB::getSchemaBuilder()->getColumnListing('mod_games');
                $requiredColumns = ['id', 'curse_game_id', 'name', 'slug'];
                
                foreach ($requiredColumns as $column) {
                    if (!in_array($column, $columns)) {
                        $issues[] = "mod_games table missing column: {$column}";
                    }
                }
            }

        } catch (\Exception $e) {
            $issues[] = "Database connection failed: " . $e->getMessage();
        }

        $this->line("   Found " . count($issues) . " database issues");
        return $issues;
    }

    private function verifyApi(array &$fixable): array
    {
        $this->info('🌐 Verifying API Configuration...');
        $issues = [];

        // Check API key
        $apiKey = env('CURSEFORGE_API_KEY');
        if (empty($apiKey)) {
            $issues[] = 'CURSEFORGE_API_KEY environment variable not set';
        } elseif (strlen($apiKey) < 10) {
            $issues[] = 'CURSEFORGE_API_KEY appears to be invalid (too short)';
        }

        // Test API connectivity
        if (!empty($apiKey)) {
            try {
                $apiService = app(CurseForgeApiService::class);
                $result = $apiService->testConnection();
                
                if (!$result['success']) {
                    $issues[] = "CurseForge API connection failed: " . $result['message'];
                }
            } catch (\Exception $e) {
                $issues[] = "API service error: " . $e->getMessage();
            }
        }

        // Check configuration file
        $configPath = config_path('mod-manager.php');
        if (!File::exists($configPath)) {
            $issues[] = 'Configuration file not published: mod-manager.php';
            $fixable[] = 'publish_config';
        }

        $this->line("   Found " . count($issues) . " API issues");
        return $issues;
    }

    private function verifyPermissions(array &$fixable): array
    {
        $this->info('🔐 Verifying Permissions...');
        $issues = [];

        // Check storage directories
        $storageDir = storage_path('app/mod-manager');
        if (!File::exists($storageDir)) {
            $issues[] = 'Storage directory does not exist: ' . $storageDir;
            $fixable[] = 'create_storage_dirs';
        } elseif (!File::isWritable($storageDir)) {
            $issues[] = 'Storage directory not writable: ' . $storageDir;
            $fixable[] = 'fix_storage_permissions';
        }

        // Check cache directory permissions
        $cacheDir = storage_path('framework/cache');
        if (!File::isWritable($cacheDir)) {
            $issues[] = 'Cache directory not writable: ' . $cacheDir;
        }

        // Check log directory permissions
        $logDir = storage_path('logs');
        if (!File::isWritable($logDir)) {
            $issues[] = 'Log directory not writable: ' . $logDir;
        }

        $this->line("   Found " . count($issues) . " permission issues");
        return $issues;
    }

    private function displayResults(array $issues, array $fixable): void
    {
        $this->info('');
        $this->info('📋 Verification Results:');
        $this->info('========================');

        if (empty($issues)) {
            $this->info('✅ All checks passed! Mod Manager addon is properly installed and configured.');
        } else {
            $this->error("❌ Found " . count($issues) . " issues:");
            
            foreach ($issues as $index => $issue) {
                $this->line("   " . ($index + 1) . ". {$issue}");
            }

            if (!empty($fixable)) {
                $this->info('');
                $this->info("🔧 " . count($fixable) . " issues can be automatically fixed:");
                $this->info("   Run: php artisan mod-manager:verify --fix");
            }
        }

        $this->info('');
    }

    private function fixIssues(array $fixable): void
    {
        if (empty($fixable)) {
            $this->info('No fixable issues found.');
            return;
        }

        $this->info('🔧 Fixing Issues...');
        $this->info('==================');

        foreach ($fixable as $fix) {
            if (str_starts_with($fix, 'create_directory:')) {
                $dir = str_replace('create_directory:', '', $fix);
                $this->fixCreateDirectory($dir);
            } else {
                match($fix) {
                    'register_service_provider' => $this->fixServiceProvider(),
                    'configure_autoloading' => $this->fixAutoloading(),
                    'run_migrations' => $this->fixMigrations(),
                    'publish_config' => $this->fixConfig(),
                    'create_storage_dirs' => $this->fixStorageDirectories(),
                    'fix_storage_permissions' => $this->fixStoragePermissions(),
                    default => $this->warn("Unknown fix: {$fix}")
                };
            }
        }

        $this->info('');
        $this->info('✅ Auto-fix completed. Run verification again to check results.');
    }

    private function fixServiceProvider(): void
    {
        $this->task('Registering service provider', function () {
            $configPath = config_path('app.php');
            $config = File::get($configPath);

            $providerClass = 'PterodactylAddons\\ModManager\\Providers\\ModManagerServiceProvider::class';

            if (!str_contains($config, $providerClass)) {
                $pattern = "/'providers'\s*=>\s*\[([^]]+)\]/";
                
                if (preg_match($pattern, $config, $matches)) {
                    $providers = $matches[1];
                    $newProviders = $providers . "\n\n        /*\n         * Mod Manager Service Provider - Self-contained addon\n         */\n        {$providerClass},\n    ";
                    $newConfig = str_replace($matches[1], $newProviders, $config);
                    File::put($configPath, $newConfig);
                }
            }
            return true;
        });
    }

    private function fixAutoloading(): void
    {
        $this->task('Configuring PSR-4 autoloading', function () {
            $composerPath = base_path('composer.json');
            $composer = json_decode(File::get($composerPath), true);

            $autoloadKey = 'PterodactylAddons\\ModManager\\';
            $autoloadPath = 'addons/mod-manager/src/';

            if (!isset($composer['autoload']['psr-4'][$autoloadKey])) {
                $composer['autoload']['psr-4'][$autoloadKey] = $autoloadPath;
                File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                exec('cd ' . base_path() . ' && composer dump-autoload --optimize');
            }
            return true;
        });
    }

    private function fixMigrations(): void
    {
        $this->task('Running database migrations', function () {
            try {
                $output = \Artisan::call('migrate', [
                    '--path' => 'addons/mod-manager/database/migrations',
                    '--force' => true,
                ]);
                return true;
            } catch (\Exception $e) {
                throw new \Exception('Migration failed: ' . $e->getMessage());
            }
        });
    }

    private function fixConfig(): void
    {
        $this->task('Publishing configuration file', function () {
            $sourcePath = base_path('addons/mod-manager/config/mod-manager.php');
            $destPath = config_path('mod-manager.php');
            
            if (File::exists($sourcePath) && !File::exists($destPath)) {
                File::copy($sourcePath, $destPath);
            }
            return true;
        });
    }

    private function fixStorageDirectories(): void
    {
        $this->task('Creating storage directories', function () {
            $dirs = [
                storage_path('app/mod-manager'),
                storage_path('app/mod-manager/temp'),
                storage_path('app/mod-manager/backups'),
            ];

            foreach ($dirs as $dir) {
                if (!File::exists($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
            }
            return true;
        });
    }

    private function fixStoragePermissions(): void
    {
        $this->task('Fixing storage permissions', function () {
            $storageDir = storage_path('app/mod-manager');
            if (File::exists($storageDir)) {
                exec('chmod -R 755 ' . escapeshellarg($storageDir));
            }
            return true;
        });
    }

    private function fixCreateDirectory(string $dir): void
    {
        $this->task("Creating directory: {$dir}", function () use ($dir) {
            $fullPath = base_path($dir);
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
            }
            return true;
        });
    }
}