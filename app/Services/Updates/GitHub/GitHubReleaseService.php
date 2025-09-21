<?php

namespace Pterodactyl\Services\Updates\GitHub;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\Updates\GitHubApiException;
use Pterodactyl\Models\Updates\PanelVersion;
use Pterodactyl\Models\Updates\UpdateSetting;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * GitHub Release Service
 * 
 * Handles fetching release information from GitHub API,
 * comparing versions, and managing release metadata.
 */
class GitHubReleaseService extends BaseUpdateService
{
    private Client $httpClient;
    private array $config;

    public function __construct()
    {
        $this->config = $this->getGitHubConfig();
        
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'Raptor-Panel-Updater/1.0',
            'X-GitHub-Api-Version' => '2022-11-28'
        ];
        
        // Use token if available (development) for higher rate limits
        $token = env('GITHUB_TOKEN');
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
            $this->logInfo('Using GitHub token for authenticated requests (5,000/hour limit)');
        } else {
            $this->logInfo('Using public GitHub API (60/hour limit per IP)');
        }
        
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => $headers
        ]);
    }

    public function getServiceName(): string
    {
        return 'GitHub Release Service';
    }

    public function getConfigurationErrors(): array
    {
        return $this->validateRequiredConfig($this->config, [
            'owner', 'repo', 'api_base'
        ]);
    }

    /**
     * Fetch the latest release from GitHub using the public API (no authentication required).
     */
    public function getLatestRelease(bool $includeBeta = false): ?array
    {
        // Use cache to reduce requests (cache for 1 hour)
        $cacheKey = 'github_latest_release_' . ($includeBeta ? 'beta' : 'stable');
        $cached = cache()->get($cacheKey);
        
        if ($cached !== null) {
            $this->logInfo('Using cached release data', ['cache_key' => $cacheKey]);
            return $cached;
        }

        try {
            $this->logInfo('Fetching latest release from GitHub public API', [
                'owner' => $this->config['owner'],
                'repo' => $this->config['repo'],
                'include_beta' => $includeBeta
            ]);

            // Use the public GitHub API - no authentication required for public repos
            $apiUrl = $this->config['api_base'] . '/releases';
            
            if (!$includeBeta) {
                // Get latest stable release
                $apiUrl .= '/latest';
                $response = $this->httpClient->get($apiUrl);
                $data = json_decode($response->getBody()->getContents(), true);
                $result = $this->normalizeReleaseData($data);
            } else {
                // Get all releases and return the latest (including pre-releases)
                $response = $this->httpClient->get($apiUrl . '?per_page=5');
                $releases = json_decode($response->getBody()->getContents(), true);
                
                if (empty($releases)) {
                    return null;
                }
                
                $result = $this->normalizeReleaseData($releases[0]);
            }
            
            // Cache the result for 4 hours (releases don't happen frequently)
            if ($result !== null) {
                cache()->put($cacheKey, $result, 14400); // 4 hours
                cache()->put($cacheKey . '_stale', $result, 86400); // 24 hours stale cache
                $this->logInfo('Cached release data from public API', [
                    'cache_key' => $cacheKey,
                    'version' => $result['tag_name'] ?? 'unknown',
                    'cache_duration' => '4 hours',
                    'stale_cache_duration' => '24 hours'
                ]);
            }
            
            return $result;

        } catch (GuzzleException $e) {
            // Check if this is a rate limit error
            if (strpos($e->getMessage(), 'rate limit exceeded') !== false) {
                $this->logWarning('GitHub API rate limit reached, will use cached data if available');
                
                // Try to return stale cached data (up to 24 hours old) as fallback
                $staleCacheKey = $cacheKey . '_stale';
                $staleCache = cache()->get($staleCacheKey);
                if ($staleCache !== null) {
                    $this->logInfo('Using stale cached data due to rate limit');
                    return $staleCache;
                }
                
                // If no stale cache, return a generic response indicating rate limit
                return [
                    'tag_name' => 'unknown',
                    'name' => 'Rate limit reached',
                    'html_url' => "https://github.com/{$this->config['owner']}/{$this->config['repo']}/releases",
                    'body' => 'GitHub API rate limit reached. Please check the releases page directly or try again later.',
                    'prerelease' => false,
                    'published_at' => date('c')
                ];
            }
            
            $this->logError('Failed to fetch latest release from public API', [
                'error' => $e->getMessage(),
                'url' => $apiUrl ?? 'unknown'
            ]);
            throw new GitHubApiException('Failed to fetch releases: ' . $e->getMessage());
        }
    }

    /**
     * Get all available releases.
     */
    public function getAllReleases(int $perPage = 30): array
    {
        try {
            $this->logInfo('Fetching all releases from GitHub', [
                'per_page' => $perPage
            ]);

            $url = $this->config['api_base'] . '/releases?per_page=' . $perPage;
            $response = $this->httpClient->get($url);
            $releases = json_decode($response->getBody()->getContents(), true);

            return array_map([$this, 'normalizeReleaseData'], $releases);

        } catch (GuzzleException $e) {
            $this->handleException($e, 'Failed to fetch all releases');
            throw new GitHubApiException("Failed to fetch releases: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * Get a specific release by tag.
     */
    public function getReleaseByTag(string $tag): ?array
    {
        try {
            $this->logInfo('Fetching release by tag', ['tag' => $tag]);

            $url = $this->config['api_base'] . '/releases/tags/' . $tag;
            $response = $this->httpClient->get($url);
            $data = json_decode($response->getBody()->getContents(), true);

            return $this->normalizeReleaseData($data);

        } catch (GuzzleException $e) {
            if ($e->getCode() === 404) {
                $this->logWarning('Release not found', ['tag' => $tag]);
                return null;
            }

            $this->handleException($e, 'Failed to fetch release by tag');
            throw new GitHubApiException("Failed to fetch release: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    /**
     * Check if there's a newer version available.
     */
    public function checkForUpdates(): ?array
    {
        try {
            $currentVersion = PanelVersion::getCurrentVersion();
            if (!$currentVersion) {
                throw new GitHubApiException('No current version found in database');
            }

            $includeBeta = UpdateSetting::allowsBetaUpdates();
            $latestRelease = $this->getLatestRelease($includeBeta);

            if (!$latestRelease) {
                $this->logWarning('No releases found on GitHub');
                return null;
            }

            $this->logInfo('Comparing versions', [
                'current' => $currentVersion->version,
                'latest' => $latestRelease['version']
            ]);

            // Compare versions
            if (version_compare($latestRelease['version'], $currentVersion->version, '>')) {
                $this->logInfo('Update available', [
                    'from' => $currentVersion->version,
                    'to' => $latestRelease['version']
                ]);

                return [
                    'update_available' => true,
                    'current_version' => $currentVersion->version,
                    'latest_version' => $latestRelease['version'],
                    'release_data' => $latestRelease
                ];
            }

            $this->logInfo('No updates available');
            return [
                'update_available' => false,
                'current_version' => $currentVersion->version,
                'latest_version' => $latestRelease['version']
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to check for updates');
            throw $e;
        }
    }

    /**
     * Save release information to the database.
     */
    public function saveReleaseToDatabase(array $releaseData): PanelVersion
    {
        try {
            $this->logInfo('Saving release to database', [
                'version' => $releaseData['version']
            ]);

            return PanelVersion::updateOrCreate(
                ['version' => $releaseData['version']],
                [
                    'release_date' => $releaseData['published_at'],
                    'release_notes' => $releaseData['body'],
                    'changelog_data' => [
                        'changelog' => $releaseData['body'],
                        'author' => $releaseData['author'],
                        'is_prerelease' => $releaseData['prerelease'],
                        'assets' => $releaseData['assets']
                    ],
                    'github_release_id' => $releaseData['id'],
                    'github_tag' => $releaseData['tag_name'],
                    'release_url' => $releaseData['html_url'],
                    'download_url' => $releaseData['download_url'],
                    'archive_checksum' => null, // Will be set when downloaded
                    'requires_migration' => $this->checkRequiresMigration($releaseData),
                ]
            );

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to save release to database');
            throw $e;
        }
    }

    /**
     * Normalize GitHub API release data to consistent format.
     */
    private function normalizeReleaseData(array $data): array
    {
        // Find the source code archive (zipball)
        $downloadUrl = $data['zipball_url'] ?? null;
        $assets = [];

        if (isset($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                $assets[] = [
                    'name' => $asset['name'],
                    'download_url' => $asset['browser_download_url'],
                    'size' => $asset['size'],
                    'content_type' => $asset['content_type']
                ];

                // Prefer a specific archive format if available
                if (str_ends_with($asset['name'], '.zip')) {
                    $downloadUrl = $asset['browser_download_url'];
                }
            }
        }

        return [
            'id' => $data['id'],
            'version' => ltrim($data['tag_name'], 'v'), // Remove 'v' prefix if present
            'tag_name' => $data['tag_name'],
            'name' => $data['name'] ?? $data['tag_name'],
            'body' => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'prerelease' => $data['prerelease'] ?? false,
            'draft' => $data['draft'] ?? false,
            'html_url' => $data['html_url'],
            'download_url' => $downloadUrl,
            'assets' => $assets,
            'author' => [
                'login' => $data['author']['login'] ?? 'Unknown',
                'url' => $data['author']['html_url'] ?? null
            ]
        ];
    }

    /**
     * Check if a release likely requires database migrations.
     */
    private function checkRequiresMigration(array $releaseData): bool
    {
        $body = strtolower($releaseData['body'] ?? '');
        
        // Look for migration-related keywords in release notes
        $migrationKeywords = [
            'migration', 'database', 'schema', 'table', 'column',
            'migrate', 'db:', 'database changes'
        ];

        foreach ($migrationKeywords as $keyword) {
            if (str_contains($body, $keyword)) {
                $this->logInfo('Release appears to require migrations', [
                    'version' => $releaseData['version'],
                    'keyword_found' => $keyword
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Get the API rate limit status.
     */
    public function getRateLimit(): array
    {
        try {
            $response = $this->httpClient->get('https://api.github.com/rate_limit');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            $this->logWarning('Failed to get rate limit status', [
                'error' => $e->getMessage()
            ]);
            return ['core' => ['remaining' => 0, 'limit' => 0, 'reset' => time()]];
        }
    }

    /**
     * Get available updates for a specific current version.
     */
    public function getAvailableUpdates(string $currentVersion): array
    {
        try {
            $this->logInfo('Getting available updates', [
                'current_version' => $currentVersion
            ]);

            $releases = $this->getAllReleases();
            $availableUpdates = [];

            foreach ($releases as $release) {
                // Skip drafts
                if ($release['draft']) {
                    continue;
                }

                // Compare versions
                if (version_compare($release['version'], $currentVersion, '>')) {
                    $availableUpdates[] = $release;
                }
            }

            // Sort by version (newest first)
            usort($availableUpdates, function ($a, $b) {
                return version_compare($b['version'], $a['version']);
            });

            $this->logInfo('Found available updates', [
                'count' => count($availableUpdates)
            ]);

            return $availableUpdates;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get available updates');
            throw $e;
        }
    }

    /**
     * Get detailed information about a specific release.
     */
    public function getReleaseDetails(string $version): ?array
    {
        try {
            // Try to find by version tag (with and without 'v' prefix)
            $possibleTags = [$version, 'v' . $version];
            
            foreach ($possibleTags as $tag) {
                $release = $this->getReleaseByTag($tag);
                if ($release) {
                    return $release;
                }
            }

            $this->logWarning('Release not found', ['version' => $version]);
            return null;

        } catch (\Exception $e) {
            $this->logError('Failed to get release details', [
                'version' => $version,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Test GitHub API connectivity.
     */
    public function testConnection(): bool
    {
        try {
            $url = $this->config['api_base'];
            $response = $this->httpClient->get($url);
            
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            $this->logError('GitHub API connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}