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
    private VersionService $versionService;

    public function __construct(VersionService $versionService = null)
    {
        $this->tempDir = storage_path('app/temp/updates');
        $this->http = new Client(['timeout' => 30]);
        $this->versionService = $versionService ?: app(VersionService::class);
        
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
            
            $currentVersion = $this->versionService->getCurrentVersion();
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
    public function performUpdate(string $downloadUrl): array
    {
        $this->log('Starting update process');

        try {
            // Fix ownership of the entire application directory before starting
            $this->log('Fixing application directory ownership');
            $this->fixOwnershipRecursive(base_path());

            // Ensure temp directory exists with proper permissions
            if (!file_exists($this->tempDir)) {
                mkdir($this->tempDir, 0755, true);
                $this->fixOwnership($this->tempDir);
            }

            $zipFile = $this->tempDir . '/update.zip';
            $extractDir = $this->tempDir . '/extracted';
            
            // Download update
            $this->log('Downloading update file');
            if (!$this->downloadFile($downloadUrl, $zipFile)) {
                return ['success' => false, 'message' => 'Failed to download update file'];
            }

            // Create backup before making changes
            $this->log('Creating backup of current installation');
            try {
                $backupPath = $this->createBackup();
                $this->log("Backup created successfully: {$backupPath}");
            } catch (\Exception $e) {
                $this->log("Backup creation failed: " . $e->getMessage(), 'warning');
                // Continue anyway - backup failure shouldn't stop update
            }

            // Extract and apply update
            $this->log('Extracting update files');
            $this->extractUpdate($zipFile);

            $this->log('Update extraction completed');

            // Skip composer install for now - files are already updated
            $this->log('Skipping composer install - update files already in place');

            // Skip migrations for now - no database changes in this update
            $this->log('Skipping database migrations - no schema changes required');

            // Update version in database
            $this->log('Updating version information');
            $newVersion = $this->extractVersionFromUrl($downloadUrl);
            if ($newVersion) {
                try {
                    $this->versionService->updateVersion($newVersion);
                    $this->log("Updated to version: {$newVersion}");
                } catch (\Exception $e) {
                    $this->log("Version update failed: " . $e->getMessage(), 'error');
                }
            } else {
                $this->log("Could not extract version from URL: {$downloadUrl}", 'warning');
            }

            // Clear caches
            $this->log('Clearing application caches');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            // Cleanup
            $this->log('Cleaning up temporary files');
            unlink($zipFile);
            $this->deleteDirectory($extractDir);

            $this->log('Update completed successfully');
            return ['success' => true, 'message' => 'Update completed successfully'];

        } catch (\Exception $e) {
            $this->log('Update failed: ' . $e->getMessage(), 'error');
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }

    /**
     * Download file from URL
     */
    private function downloadFile(string $url, string $destination): bool
    {
        try {
            $this->log("Downloading from: {$url}");
            $response = $this->http->get($url);
            
            if ($response->getStatusCode() !== 200) {
                $this->log("Download failed with status: " . $response->getStatusCode(), 'error');
                return false;
            }
            
            File::put($destination, $response->getBody());
            $this->log("File downloaded successfully to: {$destination}");
            
            return true;
        } catch (\Exception $e) {
            $this->log("Download failed: " . $e->getMessage(), 'error');
            return false;
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
        $this->log('Opening ZIP archive for extraction');
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new \Exception("Cannot open update archive");
        }
        
        $extractPath = storage_path('app/temp/updates') . '/extracted';
        $this->log('Creating extraction directory: ' . $extractPath);
        
        if (!File::exists($extractPath)) {
            File::makeDirectory($extractPath, 0755, true);
        }
        
        $this->log('Extracting ZIP contents to: ' . $extractPath);
        $zip->extractTo($extractPath);
        $zip->close();
        $this->log('ZIP extraction completed');
        
        // Find the extracted folder (GitHub archives have a folder name like "Repo-Name-version")
        $folders = File::directories($extractPath);
        $this->log('Found ' . count($folders) . ' directories in extracted archive');
        
        if (empty($folders)) {
            throw new \Exception("Invalid update archive structure");
        }
        
        $sourceDir = $folders[0];
        $targetDir = base_path();
        
        $this->log('Source directory: ' . $sourceDir);
        $this->log('Target directory: ' . $targetDir);
        $this->log('Starting file copy process');
        
        // Copy files (skip sensitive ones)
        $this->copyUpdateFiles($sourceDir, $targetDir);
        
        $this->log('File copy completed, cleaning up extraction directory');
        // Clean up
        File::deleteDirectory($extractPath);
        $this->log('Cleanup completed');
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
        
        $this->log('Scanning source directory for files');
        $files = File::allFiles($source);
        $this->log('Found ' . count($files) . ' files to process');
        
        $copied = 0;
        $skipped = 0;
        $totalFiles = count($files);
        
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
                
                try {
                    // Ensure target directory exists
                    if (!File::exists($targetDir)) {
                        File::makeDirectory($targetDir, 0755, true);
                        // Fix ownership immediately after creation
                        $this->fixOwnership($targetDir);
                    }
                    
                    // Copy the file
                    File::copy($file->getPathname(), $targetPath);
                    
                    // Fix ownership of the copied file
                    $this->fixOwnership($targetPath);
                    
                    $copied++;
                    
                    // Log progress every 100 files
                    if ($copied % 100 === 0) {
                        $percentage = round(($copied + $skipped) / $totalFiles * 100, 1);
                        $this->log("Progress: {$copied} files copied, {$skipped} skipped ({$percentage}% complete)");
                    }
                    
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'Permission denied')) {
                        $this->log("Permission denied copying {$relativePath}, attempting fix...", 'warning');
                        
                        // Try to fix ownership of parent directory and retry
                        $this->fixOwnership($target);
                        $this->fixOwnership($targetDir);
                        
                        // Remove existing file if it exists but has wrong permissions
                        if (File::exists($targetPath)) {
                            try {
                                File::delete($targetPath);
                            } catch (\Exception $deleteError) {
                                // If we can't delete, try changing ownership first
                                $this->fixOwnership($targetPath);
                                File::delete($targetPath);
                            }
                        }
                        
                        // Retry the copy
                        File::copy($file->getPathname(), $targetPath);
                        $this->fixOwnership($targetPath);
                        
                        $this->log("Successfully copied {$relativePath} after fixing permissions");
                        $copied++;
                    } else {
                        throw $e;
                    }
                }
            } else {
                $skipped++;
                
                // Log progress every 100 files (including skipped)
                if (($copied + $skipped) % 100 === 0) {
                    $percentage = round(($copied + $skipped) / $totalFiles * 100, 1);
                    $this->log("Progress: {$copied} files copied, {$skipped} skipped ({$percentage}% complete)");
                }
            }
        }
        
        $this->log("File copy completed: {$copied} files copied, {$skipped} files skipped (100% complete)");
    }

    /**
     * Extract version from GitHub download URL
     */
    private function extractVersionFromUrl(string $url): ?string
    {
        // GitHub zipball URLs end with /zipball/v1.3.22 or similar
        if (preg_match('/\/zipball\/v?(\d+\.\d+\.\d+)$/', $url, $matches)) {
            return $matches[1];
        }
        return null;
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
        // Update version in database (primary source of truth)
        $this->versionService->updateVersion($version);
        
        // Also update .env file as backup
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $content = File::get($envPath);
            
            // Update or add APP_VERSION in .env
            if (str_contains($content, 'APP_VERSION=')) {
                $content = preg_replace('/APP_VERSION=.*/', "APP_VERSION={$version}", $content);
            } else {
                $content .= "\nAPP_VERSION={$version}\n";
            }
            
            File::put($envPath, $content);
        }
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
     * Fix ownership of files and directories to web server user
     */
    private function fixOwnership(string $path): void
    {
        try {
            $webUser = 'www-data';
            
            // Check if we're running as root or have sudo access
            $currentUser = posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
            
            if ($currentUser === 'root') {
                // We can directly change ownership
                if (is_dir($path)) {
                    chown($path, $webUser);
                    chgrp($path, $webUser);
                    chmod($path, 0755);
                } else {
                    chown($path, $webUser);
                    chgrp($path, $webUser); 
                    chmod($path, 0644);
                }
            } else {
                // Try using sudo for ownership changes
                if (is_dir($path)) {
                    exec("sudo chown {$webUser}:{$webUser} " . escapeshellarg($path) . " 2>/dev/null");
                    exec("sudo chmod 755 " . escapeshellarg($path) . " 2>/dev/null");
                } else {
                    exec("sudo chown {$webUser}:{$webUser} " . escapeshellarg($path) . " 2>/dev/null");
                    exec("sudo chmod 644 " . escapeshellarg($path) . " 2>/dev/null");
                }
            }
            
        } catch (\Exception $e) {
            $this->log("Could not fix ownership for {$path}: " . $e->getMessage(), 'debug');
        }
    }

    /**
     * Fix ownership recursively for directories
     */
    private function fixOwnershipRecursive(string $directory): void
    {
        try {
            $webUser = 'www-data';
            $currentUser = posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
            
            if ($currentUser === 'root') {
                // Use native PHP functions
                exec("chown -R {$webUser}:{$webUser} " . escapeshellarg($directory));
                exec("find " . escapeshellarg($directory) . " -type d -exec chmod 755 {} \\;");
                exec("find " . escapeshellarg($directory) . " -type f -exec chmod 644 {} \\;");
            } else {
                // Use sudo
                exec("sudo chown -R {$webUser}:{$webUser} " . escapeshellarg($directory));
                exec("sudo find " . escapeshellarg($directory) . " -type d -exec chmod 755 {} \\;");
                exec("sudo find " . escapeshellarg($directory) . " -type f -exec chmod 644 {} \\;");
            }
            
            $this->log("Fixed recursive ownership for {$directory}");
            
        } catch (\Exception $e) {
            $this->log("Could not fix recursive ownership for {$directory}: " . $e->getMessage(), 'warning');
        }
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $directory): bool
    {
        try {
            if (!is_dir($directory)) {
                return true;
            }

            File::deleteDirectory($directory);
            $this->log("Deleted directory: {$directory}");
            
            return true;
        } catch (\Exception $e) {
            $this->log("Could not delete directory {$directory}: " . $e->getMessage(), 'error');
            return false;
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