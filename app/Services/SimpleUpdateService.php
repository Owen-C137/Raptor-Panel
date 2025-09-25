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
    private array $outputLog = [];
    private ?\Closure $streamCallback = null;

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
            // Build headers for API request
            $headers = [
                'User-Agent' => 'Raptor-Panel-Updater/1.0',
                'Accept' => 'application/vnd.github.v3+json'
            ];
            
            // Add authentication if GitHub API token is configured
            if ($token = config('updates.github.api_token')) {
                $headers['Authorization'] = "Bearer {$token}";
                $this->log("Using authenticated GitHub API requests (higher rate limits)");
            } else {
                $this->log("Using unauthenticated GitHub API requests (60/hour limit)");
            }
            
            $response = $this->http->get("https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/releases/latest", [
                'headers' => $headers
            ]);
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
        // Clear any previous output
        $this->clearOutputLog();
        
        $this->log('Starting update process');
        $this->log('Initializing update system...');
        usleep(500000); // 0.5 second delay for visibility

        try {
            // Fix ownership of the entire application directory before starting
            $this->log('Fixing application directory ownership');
            $this->fixOwnershipRecursive(base_path());
            $this->log('Fixed recursive ownership for ' . base_path());

            // Ensure temp directory exists with proper permissions
            if (!file_exists($this->tempDir)) {
                mkdir($this->tempDir, 0755, true);
                $this->fixOwnership($this->tempDir);
            }

            $zipFile = $this->tempDir . '/update.zip';
            $extractDir = $this->tempDir . '/extracted';
            
            // Download update
            $this->log('Downloading update file');
            $this->log('Downloading from: ' . $downloadUrl);
            usleep(250000); // 0.25 second delay for visibility
            
            if (!$this->downloadFile($downloadUrl, $zipFile)) {
                return ['success' => false, 'message' => 'Failed to download update file'];
            }
            
            $this->log('File downloaded successfully to: ' . $zipFile);
            $fileSize = number_format(filesize($zipFile) / 1024 / 1024, 2);
            $this->log("Download completed: {$fileSize} MB");

            // Create backup before making changes
            $this->log('Creating backup of current installation');
            $backupPath = null;
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

            // Clear and rebuild caches for updated application (CRITICAL for proper functionality)
            $this->clearCache();

            // Cleanup
            $this->log('Cleaning up temporary files');
            unlink($zipFile);
            $this->deleteDirectory($extractDir);
            $this->log('Temporary files cleaned up');

            $this->log('Update completed successfully');
            return [
                'success' => true, 
                'message' => 'Update completed successfully',
                'output' => $this->getOutputLog(),
                'backup_path' => $backupPath ?? null
            ];

        } catch (\Exception $e) {
            $this->log('Update failed: ' . $e->getMessage(), 'error');
            return [
                'success' => false, 
                'message' => 'Update failed: ' . $e->getMessage(),
                'output' => $this->getOutputLog(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Download file from URL
     */
    private function downloadFile(string $url, string $destination): bool
    {
        try {
            $this->log("Downloading from: {$url}");
            
            // Build headers for download request
            $headers = [
                'User-Agent' => 'Raptor-Panel-Updater/1.0'
            ];
            
            // Add authentication if GitHub API token is configured  
            if ($token = config('updates.github.api_token')) {
                $headers['Authorization'] = "Bearer {$token}";
            }
            
            $response = $this->http->get($url, ['headers' => $headers]);
            
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
        $fileCount = $zip->numFiles;
        $this->log("ZIP contains {$fileCount} files to extract");
        
        $zip->extractTo($extractPath);
        $zip->close();
        $this->log('ZIP extraction completed');
        usleep(250000); // 0.25 second delay for visibility
        
        // Find the extracted folder (GitHub archives have a folder name like "Repo-Name-version")
        $directories = glob($extractPath . '/*', GLOB_ONLYDIR);
        $this->log('Found ' . count($directories) . ' directories in extracted archive');
        
        if (count($directories) !== 1) {
            throw new \Exception('Expected exactly one directory in extracted archive, found ' . count($directories));
        }
        
        $sourceDir = $directories[0];
        $targetDir = base_path();
        
        $this->log('Source directory: ' . $sourceDir);
        $this->log('Target directory: ' . $targetDir);
        
        // Copy files
        $this->log('Starting file copy process');
        $this->copyUpdateFiles($sourceDir, $targetDir);
        
        // Clean up extraction directory
        $this->log('File copy completed, cleaning up extraction directory');
        $this->deleteDirectory($extractPath);
        $this->log('Cleanup completed');
    }

    /**
     * Copy update files using rsync for much better performance
     */
    private function copyUpdateFiles(string $source, string $target): void
    {
        $skipPaths = [
            'storage',
            'bootstrap/cache',
            '.env*',
            'vendor',
            'node_modules',
            '.git*'
        ];
        
        $this->log('Using rsync for high-performance file transfer');
        
        // Create rsync exclude patterns
        $excludeArgs = [];
        foreach ($skipPaths as $skipPath) {
            $excludeArgs[] = "--exclude='{$skipPath}'";
        }
        $excludeString = implode(' ', $excludeArgs);
        
        // Use rsync for blazing fast file copy with progress
        $this->log('Starting bulk file transfer with rsync...');
        
        // Build rsync command for optimal performance
        $rsyncCommand = "rsync -av --progress {$excludeString} --stats '{$source}/' '{$target}/' 2>&1";
        
        $this->log('Executing: rsync with optimized settings');
        
        // Execute rsync and capture output for progress
        $process = popen($rsyncCommand, 'r');
        if (!$process) {
            throw new \Exception('Failed to start rsync process');
        }
        
        $transferredFiles = 0;
        while (($line = fgets($process)) !== false) {
            $line = trim($line);
            
            // Parse rsync progress output
            if (preg_match('/^\s*(\d+)\s+\d+%\s+[\d.]+[GMK]?B\/s/', $line, $matches)) {
                // Progress line with file count
                $transferredFiles = (int)$matches[1];
                if ($transferredFiles > 0 && $transferredFiles % 500 === 0) {
                    $this->log("Progress: {$transferredFiles} files transferred (high-speed bulk copy)");
                }
            } elseif (preg_match('/^Number of files transferred:\s*(\d+)/', $line, $matches)) {
                $finalCount = (int)$matches[1];
                $this->log("Bulk transfer completed: {$finalCount} files transferred");
            } elseif (str_contains($line, 'sent') && str_contains($line, 'bytes')) {
                // Final statistics line
                $this->log("Transfer statistics: {$line}");
            }
        }
        
        $exitCode = pclose($process);
        
        if ($exitCode !== 0) {
            throw new \Exception("Rsync failed with exit code: {$exitCode}");
        }
        
        $this->log('High-speed file transfer completed successfully');
        
        // Fix ownership in bulk at the end (much faster)
        $this->log('Applying bulk ownership fixes...');
        $this->fixOwnership($target);
        $this->log('Ownership fixes completed');
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
     * Clear all caches after successful update
     */
    private function clearCache(): void
    {
        $this->log('Clearing Laravel caches for updated application');
        
        try {
            // Clear all Laravel caches
            $this->log('Clearing application cache...');
            Artisan::call('cache:clear');
            
            $this->log('Clearing configuration cache...');
            Artisan::call('config:clear');
            
            $this->log('Clearing route cache...');
            Artisan::call('route:clear');
            
            $this->log('Clearing view cache...');
            Artisan::call('view:clear');
            
            // Rebuild config cache for better performance
            $this->log('Rebuilding configuration cache...');
            Artisan::call('config:cache');
            
            $this->log('All Laravel caches cleared and rebuilt successfully');
            
        } catch (\Exception $e) {
            $this->log('Warning: Cache clearing encountered an issue: ' . $e->getMessage(), 'warning');
            $this->log('Update completed successfully, but some caches may need manual clearing');
        }
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
     * Set streaming callback for real-time logs
     */
    public function setStreamCallback(?\Closure $callback): void
    {
        $this->streamCallback = $callback;
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        $logEntry = "[" . date('H:i:s') . "] {$message}";
        $this->outputLog[] = $logEntry;
        
        // Stream log in real-time if callback is set
        if ($this->streamCallback) {
            call_user_func($this->streamCallback, $logEntry);
        }
        
        // Also log to Laravel logs
        Log::log($level, "[SimpleUpdate] {$message}");
    }

    /**
     * Get the output log
     */
    public function getOutputLog(): array
    {
        return $this->outputLog;
    }

    /**
     * Clear the output log
     */
    public function clearOutputLog(): void
    {
        $this->outputLog = [];
    }
}