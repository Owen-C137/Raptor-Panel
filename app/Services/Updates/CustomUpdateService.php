<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use GuzzleHttp\Client;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CustomUpdateService
{
    public const VERSION_CACHE_KEY = 'raptor:update_data';
    
    private static array $result;

    public function __construct(
        protected CacheRepository $cache,
        protected Client $client,
        protected GitHubFileService $githubFileService,
        protected ChangelogService $changelogService,
        protected BackupService $backupService
    ) {
        self::$result = $this->cacheUpdateData();
    }

    /**
     * Get the current local version.
     */
    public function getCurrentVersion(): string
    {
        return config('app.version');
    }

    /**
     * Get the latest version from the GitHub repository.
     */
    public function getLatestVersion(): string
    {
        try {
            $configUrl = config('app.update_source.raw_base') . '/config/app.php';
            $response = $this->client->get($configUrl);
            
            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();
                
                // Extract version from config file using regex
                if (preg_match("/'version'\s*=>\s*'([^']+)'/", $content, $matches)) {
                    return $matches[1];
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch latest version from GitHub: ' . $e->getMessage());
        }

        return 'error';
    }

    /**
     * Check if an update is available.
     */
    public function isUpdateAvailable(): bool
    {
        $currentVersion = config('app.version');
        $latestVersion = $this->getLatestVersion();
        
        if ($latestVersion === 'error' || $currentVersion === 'canary') {
            return false;
        }

        return version_compare($currentVersion, $latestVersion, '<');
    }

    /**
     * Get update information including changelog and file changes.
     */
    public function getUpdateInfo(): array
    {
        if (!$this->isUpdateAvailable()) {
            return [
                'available' => false,
                'current_version' => config('app.version'),
                'latest_version' => config('app.version'),
            ];
        }

        $latestVersion = $this->getLatestVersion();
        $changelog = $this->changelogService->getChangelogForVersion($latestVersion);
        $changedFiles = $this->getChangedFiles();

        return [
            'available' => true,
            'current_version' => config('app.version'),
            'latest_version' => $latestVersion,
            'changelog' => $changelog,
            'file_changes' => [
                'total' => count($changedFiles),
                'files' => array_slice($changedFiles, 0, 10), // Show first 10 files
                'has_more' => count($changedFiles) > 10,
            ],
            'backup_required' => config('app.update_settings.auto_backup', true),
        ];
    }

    /**
     * Get list of files that have changed between local and remote.
     */
    public function getChangedFiles(): array
    {
        // TODO: Implement proper manifest-based or git-diff based file detection
        // For now, return a minimal set of files that are likely to change
        // This avoids the expensive filesystem scanning and API rate limiting
        
        $essentialFiles = [
            'config/app.php', // Version updates
            'resources/views/admin/index.blade.php', // Admin interface updates
        ];
        
        $changedFiles = [];
        
        foreach ($essentialFiles as $file) {
            if ($this->isFileChanged($file)) {
                $changedFiles[] = $file;
            }
        }
        
        return $changedFiles;
    }

    /**
     * Scan a directory for changed files.
     */
    protected function scanDirectoryForChanges(string $path, array $excludedPaths): array
    {
        $changedFiles = [];
        $fullPath = base_path($path);

        if (!file_exists($fullPath)) {
            return $changedFiles;
        }

        if (is_file($fullPath)) {
            if ($this->shouldUpdateFile($path, $excludedPaths) && $this->isFileChanged($path)) {
                $changedFiles[] = $path;
            }
            return $changedFiles;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                
                if ($this->shouldUpdateFile($relativePath, $excludedPaths) && $this->isFileChanged($relativePath)) {
                    $changedFiles[] = $relativePath;
                }
            }
        }

        return $changedFiles;
    }

    /**
     * Check if a file should be updated based on exclusion patterns.
     */
    protected function shouldUpdateFile(string $filePath, array $excludedPaths): bool
    {
        foreach ($excludedPaths as $pattern) {
            if (fnmatch($pattern, $filePath)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if a local file differs from the remote version.
     */
    protected function isFileChanged(string $filePath): bool
    {
        try {
            $localFile = base_path($filePath);
            
            if (!file_exists($localFile)) {
                return true; // File doesn't exist locally, needs to be downloaded
            }

            $remoteContent = $this->githubFileService->getFileContent($filePath);
            if ($remoteContent === null) {
                return false; // Can't fetch remote file, assume no change
            }

            $localHash = hash_file('sha256', $localFile);
            $remoteHash = hash('sha256', $remoteContent);

            return $localHash !== $remoteHash;
        } catch (Exception $e) {
            Log::warning("Failed to compare file {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Apply the update by downloading and replacing changed files.
     */
    public function applyUpdate(): array
    {
        try {
            $changedFiles = $this->getChangedFiles();
            
            if (empty($changedFiles)) {
                return [
                    'success' => false,
                    'message' => 'No files to update',
                ];
            }

            // Create backup if enabled
            $backupPath = null;
            if (config('app.update_settings.auto_backup', true)) {
                $backupPath = $this->backupService->createBackup($changedFiles);
            }

            $updatedFiles = [];
            $failedFiles = [];

            foreach ($changedFiles as $filePath) {
                try {
                    $content = $this->githubFileService->getFileContent($filePath);
                    
                    if ($content !== null) {
                        $fullPath = base_path($filePath);
                        
                        // Ensure directory exists
                        $directory = dirname($fullPath);
                        if (!is_dir($directory)) {
                            mkdir($directory, 0755, true);
                        }

                        if (file_put_contents($fullPath, $content) !== false) {
                            $updatedFiles[] = $filePath;
                        } else {
                            $failedFiles[] = $filePath;
                        }
                    } else {
                        $failedFiles[] = $filePath;
                    }
                } catch (Exception $e) {
                    Log::error("Failed to update file {$filePath}: " . $e->getMessage());
                    $failedFiles[] = $filePath;
                }
            }

            // Update version number
            $latestVersion = $this->getLatestVersion();
            $this->updateConfigVersion($latestVersion);

            // Clear caches
            $this->clearCaches();

            return [
                'success' => empty($failedFiles),
                'message' => empty($failedFiles) 
                    ? 'Update completed successfully' 
                    : 'Update completed with some errors',
                'updated_files_count' => count($updatedFiles),
                'failed_files_count' => count($failedFiles),
                'updated_files_list' => $updatedFiles,
                'failed_files_list' => $failedFiles,
                'backup_path' => $backupPath,
                'new_version' => $latestVersion,
                'old_version' => $this->getCurrentVersion(),
                'update_timestamp' => now()->toISOString(),
            ];

        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update the version number in config/app.php.
     */
    protected function updateConfigVersion(string $newVersion): void
    {
        $configFile = base_path('config/app.php');
        $content = file_get_contents($configFile);
        
        $content = preg_replace(
            "/'version'\s*=>\s*'[^']+'/",
            "'version' => '{$newVersion}'",
            $content
        );
        
        file_put_contents($configFile, $content);
    }

    /**
     * Clear various caches after update.
     */
    protected function clearCaches(): void
    {
        $this->cache->forget(self::VERSION_CACHE_KEY);
        
        try {
            // Clear and rebuild config cache to ensure version update is reflected
            \Artisan::call('config:clear');
            \Artisan::call('config:cache');
            \Artisan::call('view:clear');
            \Artisan::call('route:clear');
            \Artisan::call('cache:clear');
        } catch (Exception $e) {
            Log::warning('Failed to clear some caches: ' . $e->getMessage());
        }
    }

    /**
     * Cache update data to avoid excessive API calls.
     */
    protected function cacheUpdateData(): array
    {
        return $this->cache->remember(
            self::VERSION_CACHE_KEY, 
            CarbonImmutable::now()->addHours(config('app.update_settings.check_interval', 24)),
            function () {
                return [
                    'latest_version' => $this->getLatestVersion(),
                    'checked_at' => now()->toISOString(),
                ];
            }
        );
    }
}