<?php

namespace Pterodactyl\Services\Updates\Strategies;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Services\Updates\GitHubFileService;

/**
 * Manifest Comparison Strategy
 * 
 * This strategy uses a manifest file (if available) to quickly determine
 * what files have changed. This is the fastest method but requires
 * the repository to maintain a manifest file.
 */
class ManifestComparisonStrategy
{
    public function __construct(
        protected Client $client,
        protected GitHubFileService $githubFileService
    ) {}

    /**
     * Get changed files using manifest comparison
     */
    public function getChangedFiles(string $currentVersion, string $latestVersion): array
    {
        try {
            $currentManifest = $this->getManifest($currentVersion);
            $latestManifest = $this->getManifest($latestVersion);
            
            if (!$currentManifest || !$latestManifest) {
                throw new Exception('Manifest files not available for comparison');
            }

            $changedFiles = $this->compareManifests($currentManifest, $latestManifest);
            
            Log::info('Manifest comparison found ' . count($changedFiles) . ' changed files');
            return $changedFiles;
            
        } catch (Exception $e) {
            Log::error('Manifest comparison strategy failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get manifest for a specific version
     */
    protected function getManifest(string $version): ?array
    {
        try {
            // Try version-specific manifest first
            $manifestPaths = [
                "manifests/v{$version}.json",
                "manifest-v{$version}.json",
                "update-manifest-{$version}.json",
                "manifest.json", // Fallback to current manifest
            ];

            foreach ($manifestPaths as $path) {
                $content = $this->githubFileService->getFileContent($path);
                if ($content) {
                    $manifest = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        Log::info("Found manifest at: {$path} for version {$version}");
                        return $manifest;
                    }
                }
            }

            // If no manifest exists, generate one from current state
            return $this->generateManifestFromCurrentState($version);
            
        } catch (Exception $e) {
            Log::warning("Failed to get manifest for version {$version}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compare two manifests to find changed files
     */
    protected function compareManifests(array $currentManifest, array $latestManifest): array
    {
        $changedFiles = [];
        
        // Get file lists from both manifests
        $currentFiles = $currentManifest['files'] ?? [];
        $latestFiles = $latestManifest['files'] ?? [];
        
        // Find new or modified files
        foreach ($latestFiles as $file => $data) {
            $currentData = $currentFiles[$file] ?? null;
            
            if (!$currentData) {
                // New file
                $changedFiles[] = $file;
                Log::debug("New file detected: {$file}");
            } elseif ($this->hasFileChanged($currentData, $data)) {
                // Modified file
                $changedFiles[] = $file;
                Log::debug("Modified file detected: {$file}");
            }
        }
        
        return $changedFiles;
    }

    /**
     * Check if a file has changed based on manifest data
     */
    protected function hasFileChanged(array $currentData, array $latestData): bool
    {
        // Compare checksums
        if (isset($currentData['checksum']) && isset($latestData['checksum'])) {
            return $currentData['checksum'] !== $latestData['checksum'];
        }
        
        // Compare file sizes
        if (isset($currentData['size']) && isset($latestData['size'])) {
            if ($currentData['size'] !== $latestData['size']) {
                return true;
            }
        }
        
        // Compare modification times
        if (isset($currentData['modified']) && isset($latestData['modified'])) {
            return strtotime($currentData['modified']) < strtotime($latestData['modified']);
        }
        
        // If we can't determine, assume it changed to be safe
        return true;
    }

    /**
     * Generate manifest from current repository state
     */
    protected function generateManifestFromCurrentState(string $version): ?array
    {
        try {
            $cacheKey = "generated_manifest_{$version}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return $cached;
            }

            $manifest = [
                'version' => $version,
                'generated_at' => now()->toISOString(),
                'files' => [],
            ];

            // Scan updatable directories
            $paths = [
                'app/',
                'resources/',
                'routes/',
                'config/',
                'public/themes/',
                'public/assets/',
                'addons/',
                'database/migrations/',
            ];

            foreach ($paths as $path) {
                $files = $this->scanDirectoryForManifest($path);
                $manifest['files'] = array_merge($manifest['files'], $files);
            }

            // Add specific root files
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
                $fileData = $this->getFileDataForManifest($file);
                if ($fileData) {
                    $manifest['files'][$file] = $fileData;
                }
            }

            // Cache for 1 hour
            Cache::put($cacheKey, $manifest, now()->addHour());
            
            Log::info("Generated manifest for version {$version} with " . count($manifest['files']) . " files");
            return $manifest;
            
        } catch (Exception $e) {
            Log::error("Failed to generate manifest for version {$version}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Scan directory and add files to manifest
     */
    protected function scanDirectoryForManifest(string $path): array
    {
        $files = [];
        
        try {
            // Use GitHub API to get directory contents
            $response = $this->githubFileService->makeRequest('contents/' . rtrim($path, '/'));
            
            if (is_array($response)) {
                foreach ($response as $item) {
                    if ($item['type'] === 'file') {
                        $fileData = $this->getFileDataForManifest($item['path']);
                        if ($fileData) {
                            $files[$item['path']] = $fileData;
                        }
                    }
                    // Note: This is a simple implementation. For deep recursion,
                    // we'd need to make additional API calls for subdirectories
                }
            }
        } catch (Exception $e) {
            Log::warning("Failed to scan directory {$path} for manifest: " . $e->getMessage());
        }
        
        return $files;
    }

    /**
     * Get file data for manifest entry
     */
    protected function getFileDataForManifest(string $filePath): ?array
    {
        try {
            $metadata = $this->githubFileService->getFileMetadata($filePath);
            
            if (!$metadata) {
                return null;
            }

            return [
                'checksum' => $metadata['sha'] ?? null,
                'size' => $metadata['size'] ?? null,
                'path' => $filePath,
            ];
            
        } catch (Exception $e) {
            Log::debug("Failed to get metadata for {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if manifest-based comparison is available
     */
    public function isAvailable(): bool
    {
        // Check if any manifest files exist
        $manifestPaths = [
            'manifest.json',
            'update-manifest.json',
            'manifests/',
        ];

        foreach ($manifestPaths as $path) {
            if ($this->githubFileService->fileExists($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a manifest file for the current version
     */
    public function createManifestForCurrentVersion(): array
    {
        $version = config('app.version');
        return $this->generateManifestFromCurrentState($version) ?? [];
    }
}