<?php

namespace Pterodactyl\Services\Updates\Strategies;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Git Tree Comparison Strategy
 * 
 * This strategy compares the git trees between two commits to find changed files.
 * This is more reliable than commit message parsing and works even without releases.
 */
class GitTreeComparisonStrategy
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Get changed files using git tree comparison
     */
    public function getChangedFiles(string $currentVersion, string $latestVersion): array
    {
        try {
            // First, find the commit SHAs for both versions
            $currentCommit = $this->findCommitForVersion($currentVersion);
            $latestCommit = $this->findCommitForVersion($latestVersion);
            
            if (!$currentCommit || !$latestCommit) {
                // Fallback: use HEAD and compare with recent commits
                return $this->getRecentChangedFiles();
            }

            // Compare the commits using GitHub's compare API
            $compareUrl = config('app.update_source.api_base') . "/compare/{$currentCommit}...{$latestCommit}";
            $response = $this->client->get($compareUrl);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $files = [];
                
                if (isset($data['files'])) {
                    foreach ($data['files'] as $file) {
                        if (in_array($file['status'], ['added', 'modified'])) {
                            $files[] = $file['filename'];
                        }
                    }
                }
                
                Log::info('Git tree comparison found ' . count($files) . ' changed files');
                return $files;
            }
            
            throw new Exception('Git tree comparison API request failed');
            
        } catch (Exception $e) {
            Log::error('Git tree comparison strategy failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find commit SHA for a version
     */
    protected function findCommitForVersion(string $version): ?string
    {
        try {
            // Search for commits that mention the version
            $searchQueries = [
                "v{$version}",
                "version {$version}",
                "Release {$version}",
                "{$version}",
            ];

            foreach ($searchQueries as $query) {
                $commits = $this->searchCommits($query);
                
                foreach ($commits as $commit) {
                    $message = strtolower($commit['commit']['message']);
                    
                    // Check if this commit is likely the version commit
                    if ($this->isVersionCommit($message, $version)) {
                        return $commit['sha'];
                    }
                }
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::warning("Could not find commit for version {$version}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search commits using GitHub search API
     */
    protected function searchCommits(string $query): array
    {
        try {
            $url = config('app.update_source.api_base') . '/commits';
            $response = $this->client->get($url, [
                'query' => [
                    'q' => $query,
                    'per_page' => 20,
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::warning('Commit search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a commit message indicates a version commit
     */
    protected function isVersionCommit(string $message, string $version): bool
    {
        $patterns = [
            "/v?{$version}/i",
            "/release.*{$version}/i",
            "/version.*{$version}/i",
            "/{$version}.*release/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fallback: get files changed in recent commits
     */
    protected function getRecentChangedFiles(int $commitCount = 10): array
    {
        try {
            $url = config('app.update_source.api_base') . '/commits';
            $response = $this->client->get($url, [
                'query' => ['per_page' => $commitCount]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $commits = json_decode($response->getBody()->getContents(), true);
                $allFiles = [];
                
                foreach ($commits as $commit) {
                    $commitFiles = $this->getCommitFiles($commit['sha']);
                    $allFiles = array_merge($allFiles, $commitFiles);
                }
                
                $uniqueFiles = array_unique($allFiles);
                Log::info('Recent changes fallback found ' . count($uniqueFiles) . ' unique files');
                return $uniqueFiles;
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::error('Recent changes fallback failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get files changed in a specific commit
     */
    protected function getCommitFiles(string $sha): array
    {
        try {
            $url = config('app.update_source.api_base') . "/commits/{$sha}";
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                $files = [];
                
                if (isset($data['files'])) {
                    foreach ($data['files'] as $file) {
                        if (in_array($file['status'], ['added', 'modified'])) {
                            $files[] = $file['filename'];
                        }
                    }
                }
                
                return $files;
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::warning('Failed to get files for commit ' . $sha . ': ' . $e->getMessage());
            return [];
        }
    }
}