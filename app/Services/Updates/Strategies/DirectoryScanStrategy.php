<?php

namespace Pterodactyl\Services\Updates\Strategies;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Services\Updates\GitHubFileService;

/**
 * Directory Scan Strategy
 * 
 * This strategy scans predefined directories for changes by comparing
 * local files with remote files. It's a comprehensive but slower approach.
 */
class DirectoryScanStrategy
{
    protected array $scannablePaths = [
        'app/',
        'resources/',
        'routes/',
        'config/',
        'public/themes/',
        'public/assets/',
        'addons/',
        'database/migrations/',
    ];

    public function __construct(
        protected GitHubFileService $githubFileService
    ) {}

    /**
     * Get changed files by scanning directories
     */
    public function getChangedFiles(string $currentVersion, string $latestVersion): array
    {
        $changedFiles = [];
        $scannedCount = 0;
        $maxScans = config('app.update_settings.max_file_scans', 1000); // Limit to prevent timeout

        foreach ($this->scannablePaths as $path) {
            try {
                $pathFiles = $this->scanPath($path, $maxScans - $scannedCount);
                $changedFiles = array_merge($changedFiles, $pathFiles);
                $scannedCount += count($pathFiles);

                if ($scannedCount >= $maxScans) {
                    Log::warning("Directory scan reached maximum file limit ({$maxScans})");
                    break;
                }

            } catch (Exception $e) {
                Log::warning("Failed to scan path {$path}: " . $e->getMessage());
                continue;
            }
        }

        // Also check specific root files
        $rootFiles = [
            'artisan',
            'composer.json',
            'package.json',
            'webpack.config.js',
            'tailwind.config.js',
            'postcss.config.js',
            'tsconfig.json',
        ];

        foreach ($rootFiles as $file) {
            if ($this->isFileChanged($file)) {
                $changedFiles[] = $file;
            }
        }

        Log::info('Directory scan found ' . count($changedFiles) . ' changed files');
        return array_unique($changedFiles);
    }

    /**
     * Scan a specific path for changed files
     */
    protected function scanPath(string $path, int $maxFiles): array
    {
        $changedFiles = [];
        $basePath = base_path($path);

        if (!file_exists($basePath)) {
            // Path doesn't exist locally, might be new files
            return $this->scanRemotePath($path, $maxFiles);
        }

        if (is_file($basePath)) {
            // Single file
            if ($this->isFileChanged($path)) {
                return [$path];
            }
            return [];
        }

        // Directory - scan recursively
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($fileCount >= $maxFiles) {
                break;
            }

            if ($file->isFile()) {
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                
                if ($this->shouldCheckFile($relativePath)) {
                    if ($this->isFileChanged($relativePath)) {
                        $changedFiles[] = $relativePath;
                    }
                    $fileCount++;
                }
            }
        }

        return $changedFiles;
    }

    /**
     * Scan a remote path for files (when local path doesn't exist)
     */
    protected function scanRemotePath(string $path, int $maxFiles): array
    {
        try {
            // Use GitHub API to list files in the remote directory
            $url = config('app.update_source.api_base') . '/contents/' . rtrim($path, '/');
            $response = $this->githubFileService->makeRequest('contents/' . rtrim($path, '/'));
            
            if (is_array($response)) {
                $files = [];
                $fileCount = 0;
                
                foreach ($response as $item) {
                    if ($fileCount >= $maxFiles) {
                        break;
                    }
                    
                    if ($item['type'] === 'file') {
                        $files[] = $item['path'];
                        $fileCount++;
                    } elseif ($item['type'] === 'dir') {
                        // Recursively scan subdirectories
                        $subFiles = $this->scanRemotePath($item['path'], $maxFiles - $fileCount);
                        $files = array_merge($files, $subFiles);
                        $fileCount += count($subFiles);
                    }
                }
                
                return $files;
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::warning("Failed to scan remote path {$path}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a local file differs from the remote version
     */
    protected function isFileChanged(string $filePath): bool
    {
        try {
            $localPath = base_path($filePath);
            
            // If file doesn't exist locally, it's a new file
            if (!file_exists($localPath)) {
                // Check if it exists remotely
                return $this->githubFileService->fileExists($filePath);
            }

            // Get remote content and compare
            $remoteContent = $this->githubFileService->getFileContent($filePath);
            
            if ($remoteContent === null) {
                // Remote file doesn't exist or couldn't be fetched
                return false;
            }

            // Compare file hashes
            $localHash = hash_file('sha256', $localPath);
            $remoteHash = hash('sha256', $remoteContent);

            $changed = $localHash !== $remoteHash;
            
            if ($changed) {
                Log::debug("File changed: {$filePath}", [
                    'local_hash' => substr($localHash, 0, 8),
                    'remote_hash' => substr($remoteHash, 0, 8),
                ]);
            }

            return $changed;

        } catch (Exception $e) {
            Log::warning("Failed to compare file {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Determine if a file should be checked for changes
     */
    protected function shouldCheckFile(string $filePath): bool
    {
        // Skip files that are obviously not updatable
        $skipPatterns = [
            '.git/',
            'storage/',
            'vendor/',
            'node_modules/',
            'bootstrap/cache/',
            '.env',
            '*.log',
            '*.cache',
            '.DS_Store',
            'Thumbs.db',
        ];

        foreach ($skipPatterns as $pattern) {
            if (fnmatch($pattern, $filePath) || str_contains($filePath, $pattern)) {
                return false;
            }
        }

        // Only check certain file extensions
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $allowedExtensions = [
            'php', 'js', 'css', 'json', 'yml', 'yaml', 'xml', 'md', 'txt',
            'html', 'htm', 'vue', 'ts', 'jsx', 'tsx', 'scss', 'sass', 'less'
        ];

        // Special case for .blade.php files
        if (str_ends_with($filePath, '.blade.php')) {
            return true;
        }

        return in_array($extension, $allowedExtensions) || empty($extension); // Include files without extensions (like artisan)
    }

    /**
     * Set custom scannable paths
     */
    public function setScannablePaths(array $paths): void
    {
        $this->scannablePaths = $paths;
    }

    /**
     * Add a path to scan
     */
    public function addScannablePath(string $path): void
    {
        if (!in_array($path, $this->scannablePaths)) {
            $this->scannablePaths[] = $path;
        }
    }
}