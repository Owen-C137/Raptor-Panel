<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GitHubFileService
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Get the content of a file from the GitHub repository.
     */
    public function getFileContent(string $filePath): ?string
    {
        try {
            $url = config('app.update_source.raw_base') . '/' . $filePath;
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            }
            
            if ($response->getStatusCode() === 404) {
                Log::info("File not found in repository: {$filePath}");
                return null;
            }
            
            Log::warning("Failed to fetch file {$filePath}, HTTP status: " . $response->getStatusCode());
            return null;
            
        } catch (Exception $e) {
            Log::error("Failed to fetch file {$filePath} from GitHub: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a file exists in the GitHub repository.
     */
    public function fileExists(string $filePath): bool
    {
        try {
            $url = config('app.update_source.raw_base') . '/' . $filePath;
            $response = $this->client->head($url);
            
            return $response->getStatusCode() === 200;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get file metadata from GitHub API.
     */
    public function getFileMetadata(string $filePath): ?array
    {
        try {
            $url = config('app.update_source.api_base') . '/contents/' . $filePath;
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Failed to fetch file metadata for {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the SHA hash of a file from GitHub.
     */
    public function getFileSha(string $filePath): ?string
    {
        $metadata = $this->getFileMetadata($filePath);
        return $metadata['sha'] ?? null;
    }

    /**
     * Download multiple files concurrently.
     */
    public function downloadFiles(array $filePaths, callable $progressCallback = null): array
    {
        $results = [];
        $total = count($filePaths);
        $completed = 0;

        foreach ($filePaths as $filePath) {
            $content = $this->getFileContent($filePath);
            $results[$filePath] = [
                'success' => $content !== null,
                'content' => $content,
                'size' => $content ? strlen($content) : 0,
            ];
            
            $completed++;
            
            if ($progressCallback) {
                $progressCallback($completed, $total, $filePath);
            }
        }

        return $results;
    }

    /**
     * Get repository information.
     */
    public function getRepositoryInfo(): ?array
    {
        try {
            $url = config('app.update_source.api_base');
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Failed to fetch repository info: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent commits from the repository.
     */
    public function getRecentCommits(int $limit = 10, string $since = null): array
    {
        try {
            $url = config('app.update_source.api_base') . '/commits';
            $params = ['per_page' => $limit];
            
            if ($since) {
                $params['since'] = $since;
            }
            
            $response = $this->client->get($url, ['query' => $params]);
            
            if ($response->getStatusCode() === 200) {
                $commits = json_decode($response->getBody()->getContents(), true);
                
                return array_map(function ($commit) {
                    return [
                        'sha' => $commit['sha'],
                        'message' => $commit['commit']['message'],
                        'author' => $commit['commit']['author']['name'],
                        'date' => $commit['commit']['author']['date'],
                        'url' => $commit['html_url'],
                    ];
                }, $commits);
            }
            
            return [];
            
        } catch (Exception $e) {
            Log::error("Failed to fetch recent commits: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the latest commit SHA for a specific branch.
     */
    public function getLatestCommitSha(string $branch = 'main'): ?string
    {
        try {
            $url = config('app.update_source.api_base') . "/branches/{$branch}";
            $response = $this->client->get($url);
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return $data['commit']['sha'] ?? null;
            }
            
            return null;
            
        } catch (Exception $e) {
            Log::error("Failed to fetch latest commit SHA: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check GitHub API rate limit status.
     */
    public function getRateLimitStatus(): array
    {
        try {
            $response = $this->client->get('https://api.github.com/rate_limit');
            
            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                return [
                    'limit' => $data['rate']['limit'],
                    'remaining' => $data['rate']['remaining'],
                    'reset' => $data['rate']['reset'],
                    'reset_at' => date('Y-m-d H:i:s', $data['rate']['reset']),
                ];
            }
            
            return ['error' => 'Failed to fetch rate limit'];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}