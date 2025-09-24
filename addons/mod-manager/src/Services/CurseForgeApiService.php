<?php

namespace PterodactylAddons\ModManager\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurseForgeApiService
{
    private Client $client;
    private string $apiKey;
    private string $baseUrl;
    private array $rateLimit;
    private float $lastRefill = 0.0;
    private float $tokens = 0.0;
    private bool $rateLimitEnabled = true;
    private bool $debugLogging = false;

    public function __construct(string $apiKey, string $baseUrl, array $rateLimit)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->rateLimit = $rateLimit;
        $this->rateLimitEnabled = $rateLimit['enabled'] ?? true;
        $this->debugLogging = (bool) config('mod-manager.curseforge.debug_logging', false);
        $this->lastRefill = microtime(true);
        $this->tokens = $rateLimit['burst_limit'] ?? 5; // start full
        
        $this->client = new Client([
            'base_uri' => $baseUrl . '/', // Ensure trailing slash for proper relative path resolution
            'timeout' => config('mod-manager.curseforge.timeout', 30),
            'headers' => [
                'Accept' => 'application/json',
                'x-api-key' => $apiKey,
                'User-Agent' => 'RaptorPanel-ModManager/1.0.0',
            ],
        ]);
    }

    /**
     * Test API connectivity and validate credentials
     */
    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('GET', 'games');
            return [
                'success' => true,
                'message' => 'API connection successful',
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get all available games
     */
    public function getGames(): array
    {
        return $this->makeRequest('GET', 'games');
    }

    /**
     * Get game details by ID
     */
    public function getGame(int $gameId): array
    {
        return $this->makeRequest('GET', "games/{$gameId}");
    }

    /**
     * Get categories for a specific game
     */
    public function getCategories(int $gameId): array
    {
        $cacheKey = "curseforge.categories.{$gameId}";
        
        return Cache::remember($cacheKey, config('mod-manager.cache.ttl.categories'), function () use ($gameId) {
            return $this->makeRequest('GET', 'categories', [
                'gameId' => $gameId,
            ]);
        });
    }

    /**
     * Get categories for a specific game (alias for consistency)
     */
    public function getGameCategories(int $gameId): array
    {
        return $this->getCategories($gameId);
    }

    /**
     * Search for mods with pagination support
     */
    public function searchMods(array $params = []): array
    {
        $defaultParams = [
            'gameId' => 432, // Minecraft
            'classId' => 6,  // Mods
            'pageSize' => 50,
            'index' => 0,
            'sortField' => 1, // Featured
            'sortOrder' => 'desc',
        ];

        $params = array_merge($defaultParams, $params);

        // Build cache key based on params
        $cacheKey = 'curseforge.search.' . md5(json_encode($params));
        $cacheTtl = config('mod-manager.cache.ttl.search', 300); // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($params) {
            return $this->makeRequest('GET', 'mods/search', $params);
        });
    }

    /**
     * Get mod details by ID
     */
    public function getMod(int $modId): array
    {
        $cacheKey = "curseforge.mod.{$modId}";
        $cacheTtl = config('mod-manager.cache.ttl.mod', 3600); // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($modId) {
            return $this->makeRequest('GET', "mods/{$modId}");
        });
    }

    /**
     * Get files for a specific mod
     */
    public function getModFiles(int $modId, array $params = []): array
    {
        $defaultParams = [
            'pageSize' => 50,
            'index' => 0,
        ];

        $params = array_merge($defaultParams, $params);
        
        $cacheKey = "curseforge.mod.{$modId}.files." . md5(json_encode($params));
        $cacheTtl = config('mod-manager.cache.ttl.files', 1800); // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($modId, $params) {
            return $this->makeRequest('GET', "mods/{$modId}/files", $params);
        });
    }

    /**
     * Get specific file details
     */
    public function getModFile(int $modId, int $fileId): array
    {
        $cacheKey = "curseforge.mod.{$modId}.file.{$fileId}";
        $cacheTtl = config('mod-manager.cache.ttl.file', 3600); // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($modId, $fileId) {
            return $this->makeRequest('GET', "mods/{$modId}/files/{$fileId}");
        });
    }

    /**
     * Get featured mods for a game
     */
    public function getFeaturedMods(int $gameId): array
    {
        $cacheKey = "curseforge.featured.{$gameId}";
        $cacheTtl = config('mod-manager.cache.ttl.featured', 1800); // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($gameId) {
            return $this->makeRequest('POST', 'mods/featured', [
                'gameId' => $gameId,
                'excludedModIds' => [],
                'gameVersionTypeId' => null
            ]);
        });
    }

    /**
     * Get multiple mods by IDs (BATCH OPTIMIZATION)
     */
    public function getModsByIds(array $modIds): array
    {
        if (empty($modIds)) {
            return ['data' => []];
        }

        // CurseForge batch limit is typically 100 mods per request
        $batches = array_chunk($modIds, 100);
        $allMods = [];
        
        foreach ($batches as $batch) {
            $response = $this->makeRequest('POST', 'mods', [
                'modIds' => $batch,
                'filterPcOnly' => false
            ]);
            
            if (isset($response['data'])) {
                $allMods = array_merge($allMods, $response['data']);
            }
        }
        
        return ['data' => $allMods];
    }

    /**
     * Get multiple files by IDs (BATCH OPTIMIZATION)
     */
    public function getFilesByIds(array $fileIds): array
    {
        if (empty($fileIds)) {
            return ['data' => []];
        }

        // CurseForge batch limit for files
        $batches = array_chunk($fileIds, 100);
        $allFiles = [];
        
        foreach ($batches as $batch) {
            $response = $this->makeRequest('POST', 'mods/files', [
                'fileIds' => $batch
            ]);
            
            if (isset($response['data'])) {
                $allFiles = array_merge($allFiles, $response['data']);
            }
        }
        
        return ['data' => $allFiles];
    }

    /**
     * Enhanced search with intelligent pagination
     */
    public function searchModsOptimized(array $params = []): array
    {
        $defaultParams = [
            'gameId' => 432,
            'classId' => 6,
            'pageSize' => 50, // Max allowed
            'index' => 0,
            'sortField' => 2, // Download count for better relevance
            'sortOrder' => 'desc',
        ];

        $params = array_merge($defaultParams, $params);
        
        // Use shorter cache for search results (more dynamic)
        $cacheKey = 'curseforge.search.opt.' . md5(json_encode($params));
        $cacheTtl = config('mod-manager.cache.ttl.search', 180); // 3 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($params) {
            return $this->makeRequest('GET', 'mods/search', $params);
        });
    }

    /**
     * Get mods by category
     */
    public function getModsByCategory(int $categoryId, int $page = 0, int $pageSize = 50): array
    {
        return $this->searchMods([
            'categoryId' => $categoryId,
            'index' => $page * $pageSize,
            'pageSize' => $pageSize,
        ]);
    }

    /**
     * Get popular mods
     */
    public function getPopularMods(int $limit = 50, int $offset = 0): array
    {
        return $this->searchMods([
            'sortField' => 6, // Popularity
            'sortOrder' => 'desc',
            'index' => $offset,
            'pageSize' => $limit,
        ]);
    }

    /**
     * Get recently updated mods
     */
    public function getRecentMods(int $limit = 50, int $offset = 0): array
    {
        return $this->searchMods([
            'sortField' => 2, // Last Updated
            'sortOrder' => 'desc',
            'index' => $offset,
            'pageSize' => $limit,
        ]);
    }

    /**
     * Get file download URL
     */
    public function getFileDownloadUrl(int $modId, int $fileId): array
    {
        return $this->makeRequest('GET', "mods/{$modId}/files/{$fileId}/download-url");
    }

    /**
     * Make HTTP request with rate limiting and error handling
     */
    private function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        $attempts = (int) config('mod-manager.curseforge.retry_attempts', 3);
        $baseDelayMs = (int) env('CURSEFORGE_RETRY_BASE_DELAY_MS', 400);
        $backoffMultiplier = (int) ($this->rateLimit['backoff_multiplier'] ?? 2);
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->enforceRateLimit();
            try {
                $options = [];
                if ($method === 'GET' && !empty($params)) {
                    $options['query'] = $params;
                } elseif (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($params)) {
                    $options['json'] = $params;
                }

                $response = $this->client->request($method, $endpoint, $options);
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
                }
                if ($this->debugLogging) {
                    Log::info('CurseForge API call successful', [
                        'method' => $method,
                        'endpoint' => $endpoint,
                        'status' => $response->getStatusCode(),
                        'attempt' => $attempt,
                    ]);
                }
                return $data;
            } catch (RequestException $e) {
                $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 0;
                $errorMessage = $e->getMessage();
                $lastException = $e;

                // For 403/429 apply backoff unless final attempt
                if (in_array($statusCode, [403, 429]) && $attempt < $attempts) {
                    $delay = ($baseDelayMs * ($backoffMultiplier ** ($attempt - 1)));
                    if ($this->debugLogging) {
                        Log::warning('CurseForge API rate/permission issue, backing off', [
                            'endpoint' => $endpoint,
                            'status' => $statusCode,
                            'attempt' => $attempt,
                            'sleep_ms' => $delay,
                        ]);
                    }
                    usleep($delay * 1000);
                    continue;
                }

                Log::error('CurseForge API request failed', [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'attempt' => $attempt,
                    'error' => $errorMessage,
                ]);

                // Graceful degradation for file list 403: return empty but annotate
                if ($statusCode === 403 && str_contains($endpoint, '/files')) {
                    return ['data' => [], '_meta' => ['skipped_reason' => 'files_forbidden', 'status' => 403, 'endpoint' => $endpoint]];
                }

                throw new \Exception("API request failed ({$statusCode}): {$errorMessage}");
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::error('CurseForge API unexpected failure', [
                    'endpoint' => $endpoint,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
                if ($attempt < $attempts) {
                    usleep($baseDelayMs * 1000);
                    continue;
                }
                throw $e;
            }
        }
        throw new \Exception('API request failed after retries: ' . ($lastException ? $lastException->getMessage() : 'unknown error'));
    }

    /**
     * Enforce rate limiting between API calls
     */
    private function enforceRateLimit(): void
    {
        if (!$this->rateLimitEnabled) {
            return; // Disabled by config/env
        }

        $now = microtime(true);
        $rate = max(0.01, (float) ($this->rateLimit['calls_per_second'] ?? 1));
        $burst = max(1.0, (float) ($this->rateLimit['burst_limit'] ?? 5));

        // Refill tokens based on elapsed time
        $elapsed = $now - $this->lastRefill;
        if ($elapsed > 0) {
            $this->tokens = min($burst, $this->tokens + $elapsed * $rate);
            $this->lastRefill = $now;
        }

        // If no tokens, sleep until next token available
        if ($this->tokens < 1.0) {
            $needed = 1.0 - $this->tokens;
            $sleepSeconds = $needed / $rate;
            usleep((int) ($sleepSeconds * 1_000_000));
            // Refill after sleep
            $now = microtime(true);
            $elapsed = $now - $this->lastRefill;
            $this->tokens = min($burst, $this->tokens + $elapsed * $rate);
            $this->lastRefill = $now;
        }

        // Consume one token
        $this->tokens -= 1.0;
    }

    /**
     * Get API statistics
     */
    public function getApiStats(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'rate_limit' => $this->rateLimit,
            'tokens' => $this->tokens,
            'rate_limit_enabled' => $this->rateLimitEnabled,
            'api_key_configured' => !empty($this->apiKey),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }
    
    /**
     * Monitor memory usage and perform cleanup if needed
     */
    private function monitorMemory(): void
    {
        $memoryLimit = $this->parseMemoryLimit(config('mod-manager.file_fetching.memory_limit', '1G'));
        $currentUsage = memory_get_usage(true);
        $threshold = $memoryLimit * 0.8; // Alert at 80%
        
        if ($currentUsage > $threshold) {
            Log::warning('High memory usage detected', [
                'current' => $this->formatBytes($currentUsage),
                'limit' => $this->formatBytes($memoryLimit),
                'percentage' => round(($currentUsage / $memoryLimit) * 100, 2)
            ]);
            
            // Force garbage collection
            $this->performMemoryCleanup();
        }
    }
    
    /**
     * Perform memory cleanup operations
     */
    private function performMemoryCleanup(): void
    {
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            $collected = gc_collect_cycles();
            Log::info("Garbage collection freed {$collected} cycles");
        }
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $unit = strtoupper(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'G': return $value * 1024 * 1024 * 1024;
            case 'M': return $value * 1024 * 1024;
            case 'K': return $value * 1024;
            default: return (int) $limit;
        }
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
}