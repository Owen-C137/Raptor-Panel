<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Improved Auto-Update Service
 * 
 * This service provides a more reliable and efficient way to detect and apply updates
 * by using multiple strategies for file detection and comparison.
 */
class ImprovedUpdateService
{
    public const VERSION_CACHE_KEY = 'raptor:improved_update_data';
    public const MANIFEST_CACHE_KEY = 'raptor:update_manifest';
    
    protected array $updateStrategies = [];

    public function __construct(
        protected CacheRepository $cache,
        protected Client $client,
        protected GitHubFileService $githubFileService,
        protected ChangelogService $changelogService,
        protected BackupService $backupService
    ) {
        $this->initializeUpdateStrategies();
    }

    /**
     * Initialize different update detection strategies
     */
    protected function initializeUpdateStrategies(): void
    {
        $this->updateStrategies = [
            'github_releases' => new \Pterodactyl\Services\Updates\Strategies\GitHubReleasesStrategy($this->client),
            'manifest_comparison' => new \Pterodactyl\Services\Updates\Strategies\ManifestComparisonStrategy($this->client, $this->githubFileService),
            'directory_scan' => new \Pterodactyl\Services\Updates\Strategies\DirectoryScanStrategy($this->githubFileService),
            'git_tree_comparison' => new \Pterodactyl\Services\Updates\Strategies\GitTreeComparisonStrategy($this->client),
        ];
    }

    /**
     * Get the current local version
     */
    public function getCurrentVersion(): string
    {
        return config('app.version');
    }

    /**
     * Get the latest version using multiple strategies
     */
    public function getLatestVersion(): string
    {
        // Try GitHub releases first (most reliable)
        try {
            $version = $this->updateStrategies['github_releases']->getLatestVersion();
            if ($version && $version !== 'error') {
                return $version;
            }
        } catch (Exception $e) {
            Log::warning('GitHub releases strategy failed: ' . $e->getMessage());
        }

        // Fallback to config file parsing
        try {
            $configUrl = config('app.update_source.raw_base') . '/config/app.php';
            $response = $this->client->get($configUrl);
            
            if ($response->getStatusCode() === 200) {
                $content = $response->getBody()->getContents();
                if (preg_match("/'version'\s*=>\s*'([^']+)'/", $content, $matches)) {
                    return $matches[1];
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch version from config file: ' . $e->getMessage());
        }

        return 'error';
    }

    /**
     * Check if an update is available
     */
    public function isUpdateAvailable(): bool
    {
        $currentVersion = $this->getCurrentVersion();
        $latestVersion = $this->getLatestVersion();
        
        if ($latestVersion === 'error' || $currentVersion === 'canary') {
            return false;
        }

        return version_compare($currentVersion, $latestVersion, '<');
    }

    /**
     * Get comprehensive update information
     */
    public function getUpdateInfo(): array
    {
        if (!$this->isUpdateAvailable()) {
            return [
                'available' => false,
                'current_version' => $this->getCurrentVersion(),
                'latest_version' => $this->getCurrentVersion(),
            ];
        }

        $latestVersion = $this->getLatestVersion();
        $changelog = $this->changelogService->getChangelogForVersion($latestVersion);
        $changedFiles = $this->getChangedFiles();
        $updateStats = $this->generateUpdateStats($changedFiles);

        return [
            'available' => true,
            'current_version' => $this->getCurrentVersion(),
            'latest_version' => $latestVersion,
            'changelog' => $changelog,
            'file_changes' => [
                'total' => count($changedFiles),
                'files' => array_slice($changedFiles, 0, 20), // Show more files
                'has_more' => count($changedFiles) > 20,
                'categories' => $updateStats['categories'],
                'size_estimate' => $updateStats['size_estimate'],
            ],
            'update_strategies_used' => $this->getLastUsedStrategies(),
            'backup_required' => config('app.update_settings.auto_backup', true),
        ];
    }

    /**
     * Get changed files using multiple strategies
     */
    public function getChangedFiles(): array
    {
        $allFiles = [];
        $strategiesUsed = [];

        // Strategy 1: Try GitHub Releases (most reliable)
        try {
            $files = $this->updateStrategies['github_releases']->getChangedFiles(
                $this->getCurrentVersion(),
                $this->getLatestVersion()
            );
            if (!empty($files)) {
                $allFiles = array_merge($allFiles, $files);
                $strategiesUsed[] = 'github_releases';
                Log::info('GitHub releases strategy found ' . count($files) . ' files');
            }
        } catch (Exception $e) {
            Log::warning('GitHub releases strategy failed: ' . $e->getMessage());
        }

        // Strategy 2: Manifest comparison (if available)
        if (empty($allFiles)) {
            try {
                $files = $this->updateStrategies['manifest_comparison']->getChangedFiles(
                    $this->getCurrentVersion(),
                    $this->getLatestVersion()
                );
                if (!empty($files)) {
                    $allFiles = array_merge($allFiles, $files);
                    $strategiesUsed[] = 'manifest_comparison';
                    Log::info('Manifest comparison strategy found ' . count($files) . ' files');
                }
            } catch (Exception $e) {
                Log::warning('Manifest comparison strategy failed: ' . $e->getMessage());
            }
        }

        // Strategy 3: Git tree comparison (comprehensive)
        if (empty($allFiles)) {
            try {
                $files = $this->updateStrategies['git_tree_comparison']->getChangedFiles(
                    $this->getCurrentVersion(),
                    $this->getLatestVersion()
                );
                if (!empty($files)) {
                    $allFiles = array_merge($allFiles, $files);
                    $strategiesUsed[] = 'git_tree_comparison';
                    Log::info('Git tree comparison strategy found ' . count($files) . ' files');
                }
            } catch (Exception $e) {
                Log::warning('Git tree comparison strategy failed: ' . $e->getMessage());
            }
        }

        // Strategy 4: Directory scan (fallback)
        if (empty($allFiles)) {
            try {
                $files = $this->updateStrategies['directory_scan']->getChangedFiles(
                    $this->getCurrentVersion(),
                    $this->getLatestVersion()
                );
                if (!empty($files)) {
                    $allFiles = array_merge($allFiles, $files);
                    $strategiesUsed[] = 'directory_scan';
                    Log::info('Directory scan strategy found ' . count($files) . ' files');
                }
            } catch (Exception $e) {
                Log::warning('Directory scan strategy failed: ' . $e->getMessage());
            }
        }

        // Remove duplicates and filter files
        $uniqueFiles = array_unique($allFiles);
        $filteredFiles = array_filter($uniqueFiles, [$this, 'shouldIncludeFile']);
        
        // Cache the strategies used for reporting
        $this->cache->put('raptor:update_strategies_used', $strategiesUsed, now()->addHour());

        Log::info('Total unique files after filtering: ' . count($filteredFiles), [
            'strategies_used' => $strategiesUsed,
            'raw_count' => count($allFiles),
            'unique_count' => count($uniqueFiles),
            'filtered_count' => count($filteredFiles),
        ]);

        return array_values($filteredFiles);
    }

    /**
     * Determine if a file should be included in updates
     */
    public function shouldIncludeFile(string $filename): bool
    {
        // Exclude patterns
        $excludePatterns = [
            '.env*',
            'storage/',
            'vendor/',
            'node_modules/',
            '.git/',
            'bootstrap/cache/',
            'storage/framework/',
            'storage/logs/',
            '*.log',
            'log.txt',
            'yarn.lock',
            'composer.lock',
            'package-lock.json',
            '.DS_Store',
            'Thumbs.db',
            '.gitignore',
            '*.md',
            'tests/',
            'phpunit.xml',
            'jest.config.js',
            'babel.config.js',
            '.phpunit.result.cache',
        ];

        foreach ($excludePatterns as $pattern) {
            if (fnmatch($pattern, $filename) || str_contains($filename, $pattern)) {
                return false;
            }
        }

        // Include patterns (only files that should be updatable)
        $includePatterns = [
            'app/',
            'resources/',
            'routes/',
            'config/',
            'public/themes/',
            'public/assets/',
            'public/js/',
            'addons/',
            'database/migrations/',
        ];

        foreach ($includePatterns as $pattern) {
            if (str_starts_with($filename, $pattern)) {
                return true;
            }
        }

        // Include specific files
        $specificFiles = [
            'artisan',
            'composer.json',
            'package.json',
            'webpack.config.js',
            'tailwind.config.js',
            'postcss.config.js',
            'tsconfig.json',
        ];

        return in_array($filename, $specificFiles);
    }

    /**
     * Generate update statistics
     */
    protected function generateUpdateStats(array $files): array
    {
        $categories = [
            'app' => 0,
            'resources' => 0,
            'config' => 0,
            'routes' => 0,
            'public' => 0,
            'addons' => 0,
            'database' => 0,
            'other' => 0,
        ];

        $totalSize = 0;

        foreach ($files as $file) {
            // Categorize files
            if (str_starts_with($file, 'app/')) {
                $categories['app']++;
            } elseif (str_starts_with($file, 'resources/')) {
                $categories['resources']++;
            } elseif (str_starts_with($file, 'config/')) {
                $categories['config']++;
            } elseif (str_starts_with($file, 'routes/')) {
                $categories['routes']++;
            } elseif (str_starts_with($file, 'public/')) {
                $categories['public']++;
            } elseif (str_starts_with($file, 'addons/')) {
                $categories['addons']++;
            } elseif (str_starts_with($file, 'database/')) {
                $categories['database']++;
            } else {
                $categories['other']++;
            }

            // Estimate file size
            $localPath = base_path($file);
            if (file_exists($localPath)) {
                $totalSize += filesize($localPath);
            } else {
                $totalSize += 2048; // Estimate for new files
            }
        }

        return [
            'categories' => array_filter($categories), // Remove zero counts
            'size_estimate' => $totalSize,
        ];
    }

    /**
     * Get the last used strategies from cache
     */
    protected function getLastUsedStrategies(): array
    {
        return $this->cache->get('raptor:update_strategies_used', []);
    }

    /**
     * Apply the update with improved progress tracking
     */
    public function applyUpdate(callable $progressCallback = null): array
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
                Log::info('Backup created at: ' . $backupPath);
            }

            $updatedFiles = [];
            $failedFiles = [];
            $total = count($changedFiles);
            $processed = 0;

            foreach ($changedFiles as $filePath) {
                try {
                    if ($progressCallback) {
                        $progressCallback($processed, $total, $filePath);
                    }

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
                            Log::info('Updated file: ' . $filePath);
                        } else {
                            $failedFiles[] = $filePath;
                            Log::error('Failed to write file: ' . $filePath);
                        }
                    } else {
                        $failedFiles[] = $filePath;
                        Log::error('Failed to download file: ' . $filePath);
                    }
                } catch (Exception $e) {
                    Log::error("Failed to update file {$filePath}: " . $e->getMessage());
                    $failedFiles[] = $filePath;
                }

                $processed++;
            }

            // Update version number
            $latestVersion = $this->getLatestVersion();
            $this->updateConfigVersion($latestVersion);

            // Clear caches
            $this->clearCaches();

            $success = empty($failedFiles);
            $result = [
                'success' => $success,
                'message' => $success 
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
                'strategies_used' => $this->getLastUsedStrategies(),
            ];

            Log::info('Update completed', $result);
            return $result;

        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update the version number in config/app.php
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
        Log::info('Updated version to: ' . $newVersion);
    }

    /**
     * Clear various caches after update
     */
    protected function clearCaches(): void
    {
        $this->cache->forget(self::VERSION_CACHE_KEY);
        $this->cache->forget(self::MANIFEST_CACHE_KEY);
        $this->cache->forget('raptor:update_strategies_used');
        
        try {
            \Artisan::call('config:clear');
            \Artisan::call('config:cache');
            \Artisan::call('view:clear');
            \Artisan::call('route:clear');
            \Artisan::call('cache:clear');
            Log::info('All caches cleared successfully');
        } catch (Exception $e) {
            Log::warning('Failed to clear some caches: ' . $e->getMessage());
        }
    }
}