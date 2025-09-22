<?php

namespace Pterodactyl\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CleanupOldUpdateSystemCommand extends Command
{
    protected $signature = 'update:cleanup-old';
    protected $description = 'Remove all files and database tables from the old complex update system';

    public function handle()
    {
        $this->info('Starting cleanup of old update system...');

        // Remove database tables
        $this->cleanupDatabase();

        // Remove files
        $this->cleanupFiles();

        $this->info('✅ Old update system cleanup completed!');
        $this->info('The new SimpleUpdateService is now ready to use.');
    }

    private function cleanupDatabase()
    {
        $this->info('🗑️  Cleaning up database tables...');

        // Disable foreign key checks temporarily
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tablesToDrop = [
            'update_file_changes',
            'update_backups', 
            'update_settings',
            'update_migrations',
            'panel_versions',
            'update_sessions', // Drop this last due to potential foreign keys
        ];

        foreach ($tablesToDrop as $table) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::dropIfExists($table);
                    $this->line("   Dropped table: {$table}");
                } catch (\Exception $e) {
                    $this->warn("   Failed to drop {$table}: " . $e->getMessage());
                }
            }
        }

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function cleanupFiles()
    {
        $this->info('🗑️  Cleaning up old update system files...');

        $pathsToRemove = [
            // Controllers
            'app/Http/Controllers/Admin/UpdateController.php',
            'app/Http/Controllers/Admin/Updates',
            
            // Models
            'app/Models/Updates',
            
            // Services 
            'app/Services/Updates',
            
            // Commands
            'app/Console/Commands/Updates',
            
            // Middleware
            'app/Http/Middleware/Admin/Updates',
            
            // Requests
            'app/Http/Requests/Admin/Updates',
            
            // Views
            'resources/views/admin/updates',
            
            // Migrations (keep them for reference but they won't run)
            // 'database/migrations/*update*.php',
            
            // Routes
            'routes/admin-updates.php',
        ];

        $basePath = base_path();
        $removedCount = 0;

        foreach ($pathsToRemove as $path) {
            $fullPath = $basePath . '/' . $path;
            
            if (File::exists($fullPath)) {
                if (File::isDirectory($fullPath)) {
                    File::deleteDirectory($fullPath);
                    $this->line("   Removed directory: {$path}");
                } else {
                    File::delete($fullPath);
                    $this->line("   Removed file: {$path}");
                }
                $removedCount++;
            }
        }

        $this->info("   Removed {$removedCount} files/directories");
    }
}