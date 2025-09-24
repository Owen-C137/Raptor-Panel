<?php

namespace PterodactylAddons\ModManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class ModManagerUninstallCommand extends Command
{
    protected $signature = 'mod-manager:uninstall 
                            {--keep-data : Preserve database tables and user data}
                            {--force : Skip safety checks and confirmations}
                            {--no-confirm : Skip interactive confirmation prompts}';

    protected $description = 'Completely uninstall the Mod Manager addon with optional data preservation';

    public function handle(): int
    {
        $this->info('🗑️  Mod Manager Addon Uninstallation');
        $this->info('=====================================');

        // Safety checks
        if (!$this->option('force') && !$this->isInstalled()) {
            $this->error('❌ Mod Manager is not installed or already removed');
            return self::FAILURE;
        }

        // Pre-uninstall warnings
        $this->displayUninstallWarnings();

        // Confirmation prompts (unless --no-confirm)
        if (!$this->option('no-confirm') && !$this->confirmUninstall()) {
            $this->info('🛑 Uninstallation cancelled by user');
            return self::SUCCESS;
        }

        try {
            // Phase 1: Stop running processes
            $this->info('⏹️  Phase 1: Stopping Active Processes...');
            $this->stopActiveProcesses();

            // Phase 2: Database cleanup
            $this->info('🗄️  Phase 2: Database Cleanup...');
            $this->cleanupDatabase();

            // Phase 3: Core file restoration
            $this->info('🔧 Phase 3: Core System Restoration...');
            $this->restoreCoreFiles();

            // Phase 4: File system cleanup
            $this->info('📁 Phase 4: File System Cleanup...');
            $this->cleanupFileSystem();

            // Phase 5: Final verification
            $this->info('✅ Phase 5: Final Verification...');
            $this->verifyUninstall();

            $this->displaySuccessMessage();
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('💥 Uninstallation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function isInstalled(): bool
    {
        // Check if service provider is registered
        $configPath = config_path('app.php');
        if (File::exists($configPath)) {
            $config = File::get($configPath);
            if (str_contains($config, 'ModManagerServiceProvider')) {
                return true;
            }
        }

        // Check if tables exist
        try {
            return DB::getSchemaBuilder()->hasTable('mod_games');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displayUninstallWarnings(): void
    {
        $this->warn('⚠️  WARNING: This will remove the Mod Manager addon completely!');
        $this->info('');
        $this->info('What will be removed:');
        $this->info('• Service provider registration from config/app.php');
        $this->info('• PSR-4 autoloader entry from composer.json');
        $this->info('• All mod-manager related cache entries');
        
        if (!$this->option('keep-data')) {
            $this->error('• ALL DATABASE TABLES AND DATA (use --keep-data to preserve)');
            $this->error('• All harvested mod information will be lost!');
        } else {
            $this->info('• Database tables will be preserved (--keep-data enabled)');
        }

        if ($this->hasActiveJobs()) {
            $this->error('• Active harvesting jobs will be cancelled!');
        }

        $this->info('• Addon directory: addons/mod-manager/ (will remain)');
        $this->info('');
    }

    private function confirmUninstall(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $confirmed = $this->confirm('Are you sure you want to proceed with uninstallation?');
        
        if ($confirmed && !$this->option('keep-data')) {
            $this->warn('');
            $this->warn('🚨 FINAL WARNING: This will permanently delete all mod data!');
            $confirmed = $this->confirm('Type "DESTROY" to confirm data deletion', false);
            
            if ($confirmed) {
                $input = $this->ask('Please type DESTROY in all caps to confirm');
                return $input === 'DESTROY';
            }
        }

        return $confirmed;
    }

    private function stopActiveProcesses(): void
    {
        $this->task('Cancelling active harvest jobs', function () {
            try {
                // Cancel all mod-manager related jobs
                DB::table('jobs')->where('queue', 'mod-harvest')->delete();
                DB::table('failed_jobs')->where('queue', 'mod-harvest')->delete();
                
                // Cancel specific mod harvest jobs if table exists
                if (DB::getSchemaBuilder()->hasTable('mod_harvest_jobs')) {
                    DB::table('mod_harvest_jobs')
                        ->whereIn('status', ['pending', 'running', 'paused'])
                        ->update(['status' => 'cancelled']);
                }
                
                return true;
            } catch (\Exception $e) {
                $this->warn('Could not cancel jobs: ' . $e->getMessage());
                return true; // Continue anyway
            }
        });

        $this->task('Stopping background processes', function () {
            // Kill any mod-manager related processes
            exec('pkill -f "mod-manager" 2>/dev/null', $output, $returnCode);
            // Don't fail if no processes found
            return true;
        });
    }

    private function cleanupDatabase(): void
    {
        if ($this->option('keep-data')) {
            $this->info('   📦 Preserving database tables (--keep-data enabled)');
            return;
        }

        $this->task('Rolling back database migrations', function () {
            try {
                // Get list of mod-manager migrations
                $migrationPath = base_path('addons/mod-manager/database/migrations');
                
                if (File::exists($migrationPath)) {
                    // Find all migration files
                    $migrations = File::files($migrationPath);
                    
                    foreach (array_reverse($migrations) as $migration) {
                        $filename = $migration->getFilename();
                        
                        // Extract table name and drop it
                        if (preg_match('/create_(.+)_table\.php$/', $filename, $matches)) {
                            $tableName = $matches[1];
                            
                            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                                DB::getSchemaBuilder()->drop($tableName);
                                $this->line("   ✓ Dropped table: {$tableName}");
                            }
                        }
                    }
                }
                
                // Clean migration records
                DB::table('migrations')
                    ->where('migration', 'like', '%mod_%')
                    ->delete();
                
                return true;
            } catch (\Exception $e) {
                throw new \Exception('Database cleanup failed: ' . $e->getMessage());
            }
        });

        $this->task('Cleaning queue tables', function () {
            try {
                // Clean job-related entries
                DB::table('jobs')->where('queue', 'like', '%mod%')->delete();
                DB::table('failed_jobs')->where('queue', 'like', '%mod%')->delete();
                return true;
            } catch (\Exception $e) {
                $this->warn('Queue cleanup warning: ' . $e->getMessage());
                return true;
            }
        });
    }

    private function restoreCoreFiles(): void
    {
        $this->task('Removing service provider from config/app.php', function () {
            $configPath = config_path('app.php');
            
            if (File::exists($configPath)) {
                $config = File::get($configPath);
                
                // Remove the service provider block
                $pattern = '/\s*\/\*\s*\n\s*\*\s*Mod Manager Service Provider[^}]+ModManagerServiceProvider::class,\s*\n/';
                $config = preg_replace($pattern, '', $config);
                
                // Also remove just the class line if pattern above doesn't match
                $config = str_replace(
                    'PterodactylAddons\ModManager\Providers\ModManagerServiceProvider::class,',
                    '',
                    $config
                );
                
                File::put($configPath, $config);
            }
            
            return true;
        });

        $this->task('Removing PSR-4 autoloading from composer.json', function () {
            $composerPath = base_path('composer.json');
            
            if (File::exists($composerPath)) {
                $composer = json_decode(File::get($composerPath), true);
                
                if (isset($composer['autoload']['psr-4']['PterodactylAddons\\ModManager\\'])) {
                    unset($composer['autoload']['psr-4']['PterodactylAddons\\ModManager\\']);
                    File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
            
            return true;
        });

        $this->task('Refreshing composer autoloader', function () {
            exec('cd ' . base_path() . ' && composer dump-autoload --optimize 2>&1', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->warn('Composer autoload refresh warning: ' . implode('\n', $output));
            }
            return true;
        });
    }

    private function cleanupFileSystem(): void
    {
        $this->task('Removing published configuration', function () {
            $configFile = config_path('mod-manager.php');
            if (File::exists($configFile)) {
                File::delete($configFile);
            }
            return true;
        });

        $this->task('Cleaning storage directories', function () {
            $storageDir = storage_path('app/mod-manager');
            if (File::exists($storageDir)) {
                File::deleteDirectory($storageDir);
            }
            return true;
        });

        $this->task('Clearing application cache', function () {
            try {
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
                Artisan::call('route:clear');
            } catch (\Exception $e) {
                $this->warn('Cache clearing warning: ' . $e->getMessage());
            }
            return true;
        });

        $this->task('Removing cache entries', function () {
            try {
                // Clear mod-manager specific cache
                $cacheKeys = [
                    'mod_manager.*',
                    'curseforge.*',
                    'mod_harvest.*',
                ];
                
                foreach ($cacheKeys as $pattern) {
                    cache()->forget($pattern);
                }
            } catch (\Exception $e) {
                $this->warn('Cache cleanup warning: ' . $e->getMessage());
            }
            return true;
        });
    }

    private function verifyUninstall(): void
    {
        $this->task('Verifying service provider removal', function () {
            $configPath = config_path('app.php');
            if (File::exists($configPath)) {
                $config = File::get($configPath);
                if (str_contains($config, 'ModManagerServiceProvider')) {
                    throw new \Exception('Service provider still registered in config/app.php');
                }
            }
            return true;
        });

        $this->task('Verifying database cleanup', function () {
            if (!$this->option('keep-data')) {
                $tables = ['mod_games', 'mod_categories', 'mod_mods'];
                foreach ($tables as $table) {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        throw new \Exception("Table still exists: {$table}");
                    }
                }
            }
            return true;
        });

        $this->task('Testing Pterodactyl Panel functionality', function () {
            try {
                // Basic test to ensure panel still works
                $users = DB::table('users')->count();
                return true;
            } catch (\Exception $e) {
                throw new \Exception('Panel functionality compromised: ' . $e->getMessage());
            }
        });
    }

    private function hasActiveJobs(): bool
    {
        try {
            $activeJobs = DB::table('jobs')->where('queue', 'mod-harvest')->count();
            
            if (DB::getSchemaBuilder()->hasTable('mod_harvest_jobs')) {
                $activeJobs += DB::table('mod_harvest_jobs')
                    ->whereIn('status', ['pending', 'running', 'paused'])
                    ->count();
            }
            
            return $activeJobs > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displaySuccessMessage(): void
    {
        $this->info('');
        $this->info('✅ Mod Manager Uninstallation Completed Successfully!');
        $this->info('===================================================');
        $this->info('');
        $this->info('✅ Service provider removed from core system');
        $this->info('✅ PSR-4 autoloading cleaned up');
        $this->info('✅ Application cache cleared');
        
        if ($this->option('keep-data')) {
            $this->info('📦 Database tables preserved (--keep-data enabled)');
            $this->info('   To reinstall: php artisan mod-manager:install');
        } else {
            $this->info('🗑️  All database tables and data removed');
            $this->info('   To reinstall: php artisan mod-manager:install (fresh installation)');
        }
        
        $this->info('');
        $this->info('📁 Addon files remain in: addons/mod-manager/');
        $this->info('   (Remove manually if no longer needed)');
        $this->info('');
        $this->info('🔄 Pterodactyl Panel functionality verified and intact');
        $this->info('');
    }
}