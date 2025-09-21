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
     * Get the latest version from GitHub releases (with fallback to tags)
     */
    public function getLatestVersion(): ?string
    {
        // First try formal releases
        try {
            $url = config('app.update_source.api_base') . '/releases/latest';
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $tagName = $data['tag_name'] ?? null;
                
                if ($tagName) {
                    Log::info('GitHub Releases Strategy: Found formal release', ['tag' => $tagName]);
                    return ltrim($tagName, 'v');
                }
            }
        } catch (Exception $e) {
            Log::info('GitHub Releases Strategy: No formal releases, trying tags fallback');
        }

        // Fallback to tags (tagged releases)
        try {
            $url = config('app.update_source.api_base') . '/tags';
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $tags = json_decode($response->getBody()->getContents(), true);
                
                if (!empty($tags) && is_array($tags)) {
                    // Get the first (latest) tag
                    $latestTag = $tags[0]['name'] ?? null;
                    
                    if ($latestTag) {
                        Log::info('GitHub Releases Strategy: Found tagged release', ['tag' => $latestTag]);
                        return ltrim($latestTag, 'v');
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('GitHub Releases Strategy: Tags API also failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get changed files between two versions using GitHub releases or tags
     */
    public function getChangedFiles(string $currentVersion, string $latestVersion): array
    {
        try {
            // Use GitHub's compare API - works with both formal releases AND tagged releases
            $compareUrl = config('app.update_source.api_base') . '/compare/v' . $currentVersion . '...v' . $latestVersion;
            $response = $this->client->get($compareUrl);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $files = [];
                
                if (isset($data['files'])) {
                    foreach ($data['files'] as $file) {
                        $filename = $file['filename'];
                        
                        // Only include files that were added or modified (not removed)
                        if (in_array($file['status'], ['added', 'modified'])) {
                            $files[] = $filename;
                        }
                    }
                }
                
                Log::info('GitHub Releases Strategy: Compare API found ' . count($files) . ' changed files', [
                    'from' => $currentVersion,
                    'to' => $latestVersion,
                    'files' => $files
                ]);
                return $files;
            }
            
            throw new Exception('Compare API request failed with status: ' . $response->getStatusCode());
            
        } catch (Exception $e) {
            Log::error('GitHub Releases Strategy: Compare API failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get release information for a specific tag (formal release or tagged release)
     */
    protected function getRelease(string $tag): ?array
    {
        // First try formal releases
        try {
            $url = config('app.update_source.api_base') . '/releases/tags/' . $tag;
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                Log::info('Found formal release for tag: ' . $tag);
                return json_decode($response->getBody()->getContents(), true);
            }
        } catch (Exception $e) {
            Log::info('No formal release for tag ' . $tag . ', checking if tag exists');
        }

        // Fallback: check if tag exists (tagged release)
        try {
            $url = config('app.update_source.api_base') . '/git/refs/tags/' . $tag;
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $tagData = json_decode($response->getBody()->getContents(), true);
                Log::info('Found tagged release for tag: ' . $tag);
                
                // Return a mock release structure for tagged releases
                return [
                    'tag_name' => $tag,
                    'name' => $tag,
                    'published_at' => null, // Tagged releases don't have publish dates
                    'body' => 'Tagged release - see commit for details',
                ];
            }
        } catch (Exception $e) {
            Log::warning('Tag ' . $tag . ' does not exist: ' . $e->getMessage());
        }
        
        return null;
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