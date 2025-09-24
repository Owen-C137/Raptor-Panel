<?php

namespace Pterodactyl\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * Simple Update Service
 * 
 * A straightforward update system that just works:
 * 1. Check for updates via GitHub API
 * 2. Download and extract update
 * 3. Run composer and migrations
 * 4. Clear cache
 */
class SimpleUpdateService
{
    private string $repoOwner = 'Owen-C137';
    private string $repoName = 'Raptor-Panel';
    private string $tempDir;
    private Client $http;

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp/updates');
        $this->http = new Client(['timeout' => 30]);
        
        if (!File::exists($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
        }
    }

    /**
     * Check if updates are available
     */
    public function checkForUpdates(): array
    {
        try {
            $response = $this->http->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases/latest");
            $release = json_decode($response->getBody(), true);
            
            $currentVersion = config('app.version');
            $latestVersion = ltrim($release['tag_name'], 'v');
            
            return [
                'available' => version_compare($latestVersion, $currentVersion, '>'),
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'download_url' => $release['zipball_url'] ?? null,
                'release_notes' => $release['body'] ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to check for updates', ['error' => $e->getMessage()]);
            return ['available' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Perform the update
     */
    public function performUpdate(string $version): array
    {
        try {
            // Step 1: Download the update
            $this->log("Downloading version {$version}...");
            $downloadPath = $this->downloadUpdate($version);
            
            // Step 2: Create backup
            $this->log("Creating backup...");
            $backupPath = $this->createBackup();
            
            // Step 3: Extract and apply update
            $this->log("Extracting update...");
            $this->extractUpdate($downloadPath);
            
            // Step 4: Run post-update tasks
            $this->log("Running composer install...");
            $this->runComposerInstall();
            
            $this->log("Running database migrations...");
            $this->runMigrations();
            
            $this->log("Clearing cache...");
            $this->clearCache();
            
            // Step 5: Update version in config
            $this->updateVersion($version);
            
            // Clean up
            File::delete($downloadPath);
            
            $this->log("Update completed successfully!");
            
            return [
                'success' => true,
                'backup_path' => $backupPath,
                'message' => 'Update completed successfully'
            ];
            
        } catch (\Exception $e) {
            $this->log("Update failed: " . $e->getMessage(), 'error');
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download update from GitHub
     */
    private function downloadUpdate(string $version): string
    {
        $url = "https://github.com/{$this->repoOwner}/{$this->repoName}/archive/refs/tags/v{$version}.zip";
        $filename = $this->tempDir . "/update-{$version}.zip";
        
        $response = $this->http->get($url);
        File::put($filename, $response->getBody());
        
        return $filename;
    }

    /**
     * Create simple backup
     */
    private function createBackup(): string
    {
        $backupDir = storage_path('app/backups/updates');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $timestamp = date('Y_m_d_H_i_s');
        $currentVersion = config('app.version');
        $backupPath = "{$backupDir}/backup_v{$currentVersion}_{$timestamp}.zip";
        
        $zip = new ZipArchive();
        if ($zip->open($backupPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create backup archive");
        }
        
        // Backup critical files including addons
        $criticalPaths = [
            'app/',
            'addons/',
            'config/',
            'database/migrations/',
            'routes/',
            '.env',
            'composer.json',
            'composer.lock'
        ];
        
        $basePath = base_path();
        foreach ($criticalPaths as $path) {
            $fullPath = $basePath . '/' . $path;
            if (File::exists($fullPath)) {
                if (File::isDirectory($fullPath)) {
                    $this->addDirectoryToZip($zip, $fullPath, $path);
                } else {
                    $zip->addFile($fullPath, $path);
                }
            }
        }
        
        $zip->close();
        
        return $backupPath;
    }

    /**
     * Extract update files
     */
    private function extractUpdate(string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception("Cannot open update archive");
        }
        
        $extractPath = $this->tempDir . '/extracted';
        $zip->extractTo($extractPath);
        $zip->close();
        
        // Find the extracted folder (GitHub archives have a folder name like "Repo-Name-version")
        $folders = File::directories($extractPath);
        if (empty($folders)) {
            throw new \Exception("Invalid update archive structure");
        }
        
        $sourceDir = $folders[0];
        $targetDir = base_path();
        
        // Copy files (skip sensitive ones)
        $this->copyUpdateFiles($sourceDir, $targetDir);
        
        // Clean up
        File::deleteDirectory($extractPath);
    }

    /**
     * Copy update files, skipping sensitive directories
     */
    private function copyUpdateFiles(string $source, string $target): void
    {
        $skipPaths = [
            'storage/',
            'bootstrap/cache/',
            '.env',
            '.env.example',
            'vendor/',
            'node_modules/',
            '.git/',
            '.gitignore'
        ];
        
        $files = File::allFiles($source);
        
        foreach ($files as $file) {
            $relativePath = str_replace($source . '/', '', $file->getPathname());
            
            // Skip sensitive paths
            $skip = false;
            foreach ($skipPaths as $skipPath) {
                if (str_starts_with($relativePath, $skipPath)) {
                    $skip = true;
                    break;
                }
            }
            
            if (!$skip) {
                $targetPath = $target . '/' . $relativePath;
                $targetDir = dirname($targetPath);
                
                if (!File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }
                
                File::copy($file->getPathname(), $targetPath);
            }
        }
    }

    /**
     * Run composer install
     */
    private function runComposerInstall(): void
    {
        $command = 'cd ' . base_path() . ' && composer install --no-dev --optimize-autoloader';
        exec($command . ' 2>&1', $output, $exitCode);
        
        if ($exitCode !== 0) {
            throw new \Exception("Composer install failed: " . implode("\n", $output));
        }
    }

    /**
     * Run database migrations
     * Includes both core migrations and addon migrations
     */
    private function runMigrations(): void
    {
        // Run core migrations first
        Artisan::call('migrate', ['--force' => true]);
        
        $output = Artisan::output();
        if (str_contains($output, 'error') || str_contains($output, 'failed')) {
            throw new \Exception("Core migration failed: " . $output);
        }
        
        // Run addon migrations
        $this->runAddonMigrations();
    }
    
    /**
     * Run migrations for all installed addons
     */
    private function runAddonMigrations(): void
    {
        $addonsPath = base_path('addons');
        
        if (!File::exists($addonsPath)) {
            return; // No addons directory
        }
        
        $addonDirs = File::directories($addonsPath);
        
        foreach ($addonDirs as $addonDir) {
            $migrationPath = $addonDir . '/database/migrations';
            
            if (File::exists($migrationPath)) {
                $addonName = basename($addonDir);
                
                try {
                    // Run migrations for this addon
                    Artisan::call('migrate', [
                        '--path' => 'addons/' . $addonName . '/database/migrations',
                        '--force' => true
                    ]);
                    
                    $output = Artisan::output();
                    if (str_contains($output, 'error') || str_contains($output, 'failed')) {
                        Log::warning("Addon migration warning for {$addonName}: " . $output);
                        // Don't throw exception for addon migrations to prevent update failure
                        // Just log the warning and continue
                    }
                    
                    Log::info("Successfully ran migrations for addon: {$addonName}");
                    
                } catch (\Exception $e) {
                    Log::error("Failed to run migrations for addon {$addonName}: " . $e->getMessage());
                    // Don't throw exception for addon migrations to prevent update failure
                }
            }
        }
    }

    /**
     * Clear all caches
     */
    private function clearCache(): void
    {
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
    }

    /**
     * Update version in config
     */
    private function updateVersion(string $version): void
    {
        $configPath = config_path('app.php');
        $content = File::get($configPath);
        
        $content = preg_replace(
            "/'version' => env\('APP_VERSION', '[^']*'\),/",
            "'version' => env('APP_VERSION', '{$version}'),",
            $content
        );
        
        File::put($configPath, $content);
    }

    /**
     * Add directory to ZIP archive recursively
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $baseName = ''): void
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $relativePath = $baseName . str_replace($dir, '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        Log::log($level, "[SimpleUpdate] {$message}");
    }
}