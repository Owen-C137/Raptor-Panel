<?php

namespace Pterodactyl\Services\Updates\Strategies;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * GitHub Releases Strategy
 * 
 * This strategy uses GitHub's releases API to detect updates and changed files.
 * This is the most reliable method when proper releases are created.
 */
class GitHubReleasesStrategy
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Get the latest version from GitHub releases
     */
    public function getLatestVersion(): ?string
    {
        try {
            $url = config('app.update_source.api_base') . '/releases/latest';
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $tagName = $data['tag_name'] ?? null;
                
                // Remove 'v' prefix if present
                return $tagName ? ltrim($tagName, 'v') : null;
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('GitHub releases API failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get changed files between two versions using GitHub releases
     */
    public function getChangedFiles(string $currentVersion, string $latestVersion): array
    {
        try {
            // Get release information for both versions
            $currentRelease = $this->getRelease('v' . $currentVersion);
            $latestRelease = $this->getRelease('v' . $latestVersion);
            
            if (!$currentRelease || !$latestRelease) {
                throw new Exception('Could not find release information for comparison');
            }

            // Use compare API to get changed files between tags
            $compareUrl = config('app.update_source.api_base') . '/compare/v' . $currentVersion . '...v' . $latestVersion;
            $response = $this->client->get($compareUrl);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $files = [];
                
                if (isset($data['files'])) {
                    foreach ($data['files'] as $file) {
                        $filename = $file['filename'];
                        
                        // Only include files that were added or modified
                        if (in_array($file['status'], ['added', 'modified'])) {
                            $files[] = $filename;
                        }
                    }
                }
                
                Log::info('GitHub releases strategy found ' . count($files) . ' changed files');
                return $files;
            }
            
            throw new Exception('Compare API request failed');
            
        } catch (Exception $e) {
            Log::error('GitHub releases strategy failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get release information for a specific tag
     */
    protected function getRelease(string $tag): ?array
    {
        try {
            $url = config('app.update_source.api_base') . '/releases/tags/' . $tag;
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return null;
        } catch (Exception $e) {
            Log::warning('Could not get release for tag ' . $tag . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if GitHub releases are available
     */
    public function isAvailable(): bool
    {
        try {
            $url = config('app.update_source.api_base') . '/releases';
            $response = $this->client->get($url, [
                'query' => ['per_page' => 1]
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }
}