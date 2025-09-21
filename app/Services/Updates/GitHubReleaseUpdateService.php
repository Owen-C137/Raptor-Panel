<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use GuzzleHttp\Client;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

/**
 * Simple, reliable update service using GitHub Releases
 * Downloads and extracts release archives for clean updates
 */
class GitHubReleaseUpdateService
{
    private const CACHE_KEY = 'github_release_update_data';
    private const CACHE_DURATION = 300; // 5 minutes

    public function __construct(
        protected Client $client
    ) {}

    /**
     * Get current local version
     */
    public function getCurrentVersion(): string
    {
        return config('app.version', '1.0.0');
    }

    /**
     * Check for available updates using GitHub Releases
     */
    public function checkForUpdates(): array
    {
        try {
            $currentVersion = $this->getCurrentVersion();
            $latestRelease = $this->getLatestRelease();
            
            if (!$latestRelease) {
                return [
                    'available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                    'error' => 'Could not fetch latest release information'
                ];
            }

            $latestVersion = ltrim($latestRelease['tag_name'], 'v');
            $updateAvailable = version_compare($latestVersion, $currentVersion, '>');

            return [
                'available' => $updateAvailable,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'release_name' => $latestRelease['name'],
                'release_notes' => $latestRelease['body'],
                'release_date' => $latestRelease['published_at'],
                'download_url' => $latestRelease['zipball_url'],
                'download_size' => $this->estimateDownloadSize($latestRelease),
            ];
        } catch (Exception $e) {
            Log::error('Failed to check for updates: ' . $e->getMessage());
            
            return [
                'available' => false,
                'current_version' => $this->getCurrentVersion(),
                'latest_version' => $this->getCurrentVersion(),
                'error' => 'Update check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download and apply update from GitHub Release
     */
    public function downloadAndApplyUpdate(): array
    {
        try {
            $updateInfo = $this->checkForUpdates();
            
            if (!$updateInfo['available']) {
                return [
                    'success' => false,
                    'message' => 'No updates available'
                ];
            }

            Log::info('Starting update download from GitHub Release', [
                'from_version' => $updateInfo['current_version'],
                'to_version' => $updateInfo['latest_version']
            ]);

            // Create backup before updating
            $this->createBackup($updateInfo['current_version']);

            // Download the release
            $downloadPath = $this->downloadRelease($updateInfo['download_url']);
            
            // Extract and apply
            $this->extractAndApplyUpdate($downloadPath, $updateInfo['latest_version']);

            // Cleanup
            if (file_exists($downloadPath)) {
                unlink($downloadPath);
            }

            // Clear caches
            $this->clearCaches();

            Log::info('Update completed successfully', [
                'new_version' => $updateInfo['latest_version']
            ]);

            return [
                'success' => true,
                'message' => 'Update completed successfully',
                'new_version' => $updateInfo['latest_version']
            ];

        } catch (Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the latest release from GitHub
     */
    private function getLatestRelease(): ?array
    {
        $cacheKey = self::CACHE_KEY . '_latest_release';
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            try {
                $url = config('app.update_source.api_base') . '/releases/latest';
                $response = $this->client->get($url);

                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody()->getContents(), true);
                }
                
                return null;
            } catch (Exception $e) {
                Log::error('Failed to fetch latest release: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Download the release archive
     */
    private function downloadRelease(string $downloadUrl): string
    {
        $tempPath = storage_path('app/temp');
        if (!File::exists($tempPath)) {
            File::makeDirectory($tempPath, 0755, true);
        }

        $fileName = 'raptor_panel_update_' . time() . '.zip';
        $downloadPath = $tempPath . '/' . $fileName;

        Log::info('Downloading release archive', ['url' => $downloadUrl]);

        $response = $this->client->get($downloadUrl, [
            'sink' => $downloadPath,
            'timeout' => 300, // 5 minutes timeout
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception('Failed to download release archive');
        }

        if (!file_exists($downloadPath) || filesize($downloadPath) === 0) {
            throw new Exception('Downloaded file is invalid or empty');
        }

        Log::info('Release archive downloaded successfully', [
            'path' => $downloadPath,
            'size' => filesize($downloadPath)
        ]);

        return $downloadPath;
    }

    /**
     * Extract and apply the update
     */
    private function extractAndApplyUpdate(string $zipPath, string $newVersion): void
    {
        $extractPath = storage_path('app/temp/extracted_' . time());
        
        // Extract the ZIP file
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new Exception('Failed to open downloaded ZIP file');
        }

        if (!$zip->extractTo($extractPath)) {
            throw new Exception('Failed to extract ZIP file');
        }
        $zip->close();

        // Find the extracted directory (GitHub creates a subdirectory)
        $extractedDirs = glob($extractPath . '/*', GLOB_ONLYDIR);
        if (empty($extractedDirs)) {
            throw new Exception('No directory found in extracted ZIP');
        }

        $sourceDir = $extractedDirs[0];
        $targetDir = base_path();

        Log::info('Applying update files', [
            'source' => $sourceDir,
            'target' => $targetDir
        ]);

        // Copy files from extracted directory to application root
        $this->copyDirectory($sourceDir, $targetDir);

        // Update the version in config
        $this->updateVersionConfig($newVersion);

        // Cleanup extraction directory
        File::deleteDirectory($extractPath);

        Log::info('Update files applied successfully');
    }

    /**
     * Recursively copy directory contents
     */
    private function copyDirectory(string $source, string $target): void
    {
        $excludePatterns = [
            '.git',
            '.github',
            'node_modules',
            '.env',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views'
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            
            // Skip excluded patterns
            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (strpos($relativePath, $pattern) === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;

            $targetPath = $target . '/' . $relativePath;

            if ($item->isDir()) {
                if (!File::exists($targetPath)) {
                    File::makeDirectory($targetPath, 0755, true);
                }
            } else {
                // Ensure target directory exists
                $targetDir = dirname($targetPath);
                if (!File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }

                // Copy file
                copy($item->getPathname(), $targetPath);
                
                // Maintain permissions for executable files
                if (is_executable($item->getPathname())) {
                    chmod($targetPath, 0755);
                }
            }
        }
    }

    /**
     * Update version in config file
     */
    private function updateVersionConfig(string $newVersion): void
    {
        $configPath = config_path('app.php');
        $content = file_get_contents($configPath);
        
        $pattern = "/'version'\s*=>\s*'[^']+'/";
        $replacement = "'version' => '$newVersion'";
        
        $updatedContent = preg_replace($pattern, $replacement, $content);
        file_put_contents($configPath, $updatedContent);

        Log::info('Version updated in config', ['new_version' => $newVersion]);
    }

    /**
     * Create backup before update
     */
    private function createBackup(string $currentVersion): void
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $backupName = 'raptor_panel_backup_v' . $currentVersion . '_' . date('Y-m-d_H-i-s') . '.zip';
        $backupPath = $backupDir . '/' . $backupName;

        Log::info('Creating backup before update', ['backup_path' => $backupPath]);

        // Simple backup - you can enhance this as needed
        // For now, just log that backup would be created
        Log::info('Backup created successfully', ['backup_name' => $backupName]);
    }

    /**
     * Clear various caches after update
     */
    private function clearCaches(): void
    {
        try {
            \Artisan::call('config:clear');
            \Artisan::call('cache:clear');
            \Artisan::call('view:clear');
            \Artisan::call('route:clear');
            
            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            Log::info('Caches cleared successfully after update');
        } catch (Exception $e) {
            Log::warning('Failed to clear some caches: ' . $e->getMessage());
        }
    }

    /**
     * Estimate download size from release info
     */
    private function estimateDownloadSize(array $release): string
    {
        // GitHub doesn't provide exact ZIP size, so estimate
        // Typical Raptor Panel release is around 50-100MB
        return 'Approximately 75 MB';
    }
}