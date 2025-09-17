<?php

namespace PterodactylAddons\OllamaAi\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OllamaLibraryScraperService
{
    private Client $httpClient;
    private const CACHE_TTL = 3600; // 1 hour cache
    private const BASE_URL = 'https://ollama.com';
    
    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; PterodactylAddon/1.0)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ]
        ]);
    }

    /**
     * Get all models from Ollama library with caching
     * 
     * @param bool $forceRefresh Skip cache and fetch fresh data
     * @return array
     */
    public function getAllModels(bool $forceRefresh = false): array
    {
        $cacheKey = 'ollama_library_models';
        
        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey, []);
        }
        
        try {
            $models = $this->scrapeLibraryPage();
            
            // Cache the results
            Cache::put($cacheKey, $models, self::CACHE_TTL);
            
            return $models;
            
        } catch (Exception $e) {
            Log::error('Failed to scrape Ollama library: ' . $e->getMessage());
            
            // Return cached data if available, otherwise empty array
            return Cache::get($cacheKey, []);
        }
    }

    /**
     * Scrape the main library page to get all models
     * 
     * @return array
     */
    private function scrapeLibraryPage(): array
    {
        $response = $this->httpClient->get(self::BASE_URL . '/library');
        $html = $response->getBody()->getContents();
        
        $models = [];
        
        // Parse HTML to extract model cards
        if (preg_match_all('/<a[^>]+href="\/library\/([^"]+)"[^>]*>(.*?)<\/a>/s', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $modelSlug = $match[1];
                $cardHtml = $match[2];
                
                // Skip if this looks like a pagination link or other non-model link
                if (strpos($modelSlug, '?') !== false || in_array($modelSlug, ['featured', 'popular', 'newest'])) {
                    continue;
                }
                
                $model = $this->parseModelCard($modelSlug, $cardHtml);
                if ($model) {
                    $models[] = $model;
                }
            }
        }
        
        // Sort models by popularity (downloads)
        usort($models, function($a, $b) {
            return $b['downloads'] <=> $a['downloads'];
        });
        
        return $models;
    }

    /**
     * Get debug HTML sample for inspecting structure
     * 
     * @param string $slug
     * @return string|null
     */
    public function getDebugHtmlSample(string $slug): ?string
    {
        try {
            $response = $this->httpClient->get("https://ollama.com/library/{$slug}");
            
            if ($response->getStatusCode() === 200) {
                $html = $response->getBody()->getContents();
                
                // Look for the main content area - try different patterns
                $patterns = [
                    '/<main[^>]*>(.*?)<\/main>/s',
                    '/<div[^>]*class="[^"]*page[^"]*"[^>]*>(.*?)<\/div>/s',
                    '/<div[^>]*class="[^"]*container[^"]*"[^>]*>(.*?)<\/div>/s'
                ];
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $matches)) {
                        return "Pattern matched: " . substr($matches[1], 0, 800);
                    }
                }
                
                // If no pattern matched, let's look for common elements
                $htmlSample = substr($html, 0, 2000);
                
                // Look for some key elements
                if (strpos($html, 'x-test-pull-count') !== false) {
                    $htmlSample .= "\n\nFound x-test-pull-count element!";
                } else {
                    $htmlSample .= "\n\nNo x-test-pull-count element found.";
                }
                
                if (strpos($html, 'x-test-size') !== false) {
                    $htmlSample .= "\nFound x-test-size element!";
                } else {
                    $htmlSample .= "\nNo x-test-size element found.";
                }
                
                return $htmlSample;
            }
            
            return "HTTP Status: " . $response->getStatusCode();
        } catch (\Exception $e) {
            Log::error("Failed to get debug HTML for {$slug}: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }

    /**
     * Parse individual model card from library page
     * 
     * @param string $slug
     * @param string $cardHtml
     * @return array|null
     */
    private function parseModelCard(string $slug, string $cardHtml): ?array
    {
        $model = [
            'slug' => $slug,
            'name' => $slug,
            'title' => $slug, // Use slug as fallback title
            'description' => '',
            'downloads' => 0,
            'updated' => '',
            'tags' => [],
            'variants' => []
        ];
        
        // Extract title - try multiple patterns
        if (preg_match('/<h2[^>]*>.*?<span[^>]*>([^<]+)<\/span>.*?<\/h2>/s', $cardHtml, $matches)) {
            $model['title'] = trim($matches[1]);
        } elseif (preg_match('/<h2[^>]*>(.*?)<\/h2>/i', $cardHtml, $matches)) {
            $model['title'] = trim(strip_tags($matches[1]));
        } elseif (preg_match('/<h3[^>]*>(.*?)<\/h3>/i', $cardHtml, $matches)) {
            $model['title'] = trim(strip_tags($matches[1]));
        } elseif (preg_match('/>[^<]*' . preg_quote($slug, '/') . '[^<]*</i', $cardHtml, $matches)) {
            // Try to find the slug in the text content
            $model['title'] = $slug;
        }
        
        // Extract description - multiple patterns
        if (preg_match('/<p[^>]*class="[^"]*text-neutral-800[^"]*"[^>]*>([^<]+)<\/p>/i', $cardHtml, $matches)) {
            $model['description'] = trim($matches[1]);
        } elseif (preg_match('/<p[^>]*class="[^"]*text-gray[^"]*"[^>]*>([^<]+)<\/p>/i', $cardHtml, $matches)) {
            $model['description'] = trim($matches[1]);
        } elseif (preg_match('/<p[^>]*>([^<]+)<\/p>/i', $cardHtml, $matches)) {
            $possibleDescription = trim($matches[1]);
            // Only use if it looks like a description (not a number or short text)
            if (strlen($possibleDescription) > 20 && !preg_match('/^\d+(\.\d+)?[KMB]?$/', $possibleDescription)) {
                $model['description'] = $possibleDescription;
            }
        }
        
        // Extract download/pull count - try multiple patterns
        $downloadPatterns = [
            '/(\d+(?:\.\d+)?[KMB]?)\s*pulls?/i',
            '/(\d+(?:\.\d+)?[KMB]?)\s*downloads?/i',
            '/<span[^>]*(?:x-test-pull-count|pull-count)[^>]*>.*?(\d+(?:\.\d+)?[KMB]?).*?<\/span>/i',
            '/>\s*(\d+(?:\.\d+)?[KMB]?)\s*pulls?\s*</i'
        ];
        
        foreach ($downloadPatterns as $pattern) {
            if (preg_match($pattern, $cardHtml, $matches)) {
                $model['downloads'] = $this->parseDownloadCount(trim($matches[1]));
                break;
            }
        }
        
        // Extract updated time
        if (preg_match('/updated\s+(\d+\s+\w+\s+ago)/i', $cardHtml, $matches)) {
            $model['updated'] = trim($matches[1]);
        } elseif (preg_match('/(\d+\s+\w+\s+ago)/i', $cardHtml, $matches)) {
            $model['updated'] = trim($matches[1]);
        }
        
        // Get detailed variants with exact file sizes
        $model['variants'] = $this->fetchModelDetails($slug);
        
        return $model;
    }

    /**
     * Get detailed model information including variants
     * 
     * @param string $modelSlug
     * @return array|null
     */
    public function getModelDetails(string $modelSlug): ?array
    {
        $cacheKey = "ollama_model_details_{$modelSlug}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            $response = $this->httpClient->get(self::BASE_URL . '/library/' . $modelSlug);
            $html = $response->getBody()->getContents();
            
            $details = $this->parseModelDetailsPage($modelSlug, $html);
            
            // Cache for 30 minutes
            Cache::put($cacheKey, $details, 1800);
            
            return $details;
            
        } catch (Exception $e) {
            Log::error("Failed to get model details for {$modelSlug}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse model details page to extract variants and specifications
     * 
     * @param string $modelSlug
     * @param string $html
     * @return array
     */
    private function parseModelDetailsPage(string $modelSlug, string $html): array
    {
        $details = [
            'slug' => $modelSlug,
            'name' => $modelSlug,
            'title' => $modelSlug, // Use slug as fallback
            'description' => '',
            'downloads' => 0,
            'updated' => '',
            'tags' => [],
            'variants' => [],
            'use_cases' => [],
            'family' => $this->extractModelFamily($modelSlug)
        ];
        
        // Extract title from og:title meta tag first, then title tag, then h1
        if (preg_match('/<meta[^>]+property="og:title"[^>]+content="([^"]+)"/i', $html, $matches)) {
            $details['title'] = trim($matches[1]);
        } elseif (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            $details['title'] = trim($matches[1]);
        } elseif (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            $details['title'] = trim($matches[1]);
        }
        
        // Ensure title is not empty - use slug as fallback
        if (empty($details['title'])) {
            $details['title'] = $modelSlug;
        }
        
        // Extract description from meta description (most reliable)
        if (preg_match('/<meta[^>]+name="description"[^>]+content="([^"]+)"/i', $html, $matches)) {
            $details['description'] = trim($matches[1]);
        } elseif (preg_match('/<meta[^>]+property="og:description"[^>]+content="([^"]+)"/i', $html, $matches)) {
            $details['description'] = trim($matches[1]);
        } elseif (preg_match('/<div[^>]*class="[^"]*description[^"]*"[^>]*>(.*?)<\/div>/is', $html, $matches)) {
            $details['description'] = trim(strip_tags($matches[1]));
        }
        
        // Try to extract download count and update time (though likely not in static HTML)
        if (preg_match('/(\d+(?:\.\d+)?[KMB]?)\s*downloads?/i', $html, $matches)) {
            $details['downloads'] = $this->parseDownloadCount($matches[1]);
        }
        
        if (preg_match('/updated\s+(\d+\s+\w+\s+ago)/i', $html, $matches)) {
            $details['updated'] = $matches[1];
        }
        
        // Try to extract variants from page content
        $variants = $this->extractModelVariants($html, $modelSlug);
        
        // If only fallback variants found (with "Unknown" size), try intelligent generation
        if (empty($variants) || (count($variants) === 1 && $variants[0]['size'] === 'Unknown')) {
            $intelligentVariants = $this->generateDefaultVariants($modelSlug, $details['description']);
            if (!empty($intelligentVariants)) {
                $variants = $intelligentVariants;
            }
        }
        
        $details['variants'] = $variants;
        
        // Extract tags and use cases
        $details['tags'] = $this->extractTags($html);
        $details['use_cases'] = $this->inferUseCases($details['title'], $details['description'], $details['tags']);
        
        return $details;
    }

    /**
     * Extract model variants from the model details page
     * 
     * @param string $html
     * @param string $baseModel
     * @return array
     */
    private function extractModelVariants(string $html, string $baseModel): array
    {
        $variants = [];
        
        // Look for model table or variant listings
        if (preg_match('/<table[^>]*>(.*?)<\/table>/is', $html, $tableMatch)) {
            $tableHtml = $tableMatch[1];
            
            // Extract table rows
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $rows)) {
                foreach ($rows[1] as $row) {
                    if (preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cells)) {
                        if (count($cells[1]) >= 2) {
                            $name = trim(strip_tags($cells[1][0]));
                            $size = trim(strip_tags($cells[1][1]));
                            
                            // Skip header rows
                            if (strtolower($name) === 'name' || strtolower($size) === 'size') {
                                continue;
                            }
                            
                            // Clean up name - remove any additional formatting
                            $name = preg_replace('/\s+/', ' ', $name);
                            
                            if ($name && $size) {
                                $variants[] = [
                                    'name' => $name,
                                    'full_name' => strpos($name, ':') !== false ? $name : $baseModel . ':' . $name,
                                    'size' => $size,
                                    'context' => isset($cells[1][2]) ? trim(strip_tags($cells[1][2])) : '',
                                    'input_type' => isset($cells[1][3]) ? trim(strip_tags($cells[1][3])) : 'Text'
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        // If no variants found in table, create a default variant
        if (empty($variants)) {
            $variants[] = [
                'name' => 'latest',
                'full_name' => $baseModel . ':latest',
                'size' => 'Unknown',
                'context' => '',
                'input_type' => 'Text'
            ];
        }
        
        return $variants;
    }

    /**
     * Generate intelligent default variants based on model name and description
     * 
     * @param string $modelSlug
     * @param string $description
     * @return array
     */
    private function generateDefaultVariants(string $modelSlug, string $description): array
    {
        $variants = [];
        
        // Extract parameter sizes from description
        $parameterSizes = [];
        if (preg_match_all('/(\d+(?:\.\d+)?)\s*[Bb](?!\w)/i', $description, $matches)) {
            foreach ($matches[1] as $size) {
                $parameterSizes[] = $size . 'B';
            }
        }
        
        // Common model variants based on model names and patterns
        $modelVariants = [
            'llama3.1' => ['8B', '70B', '405B'],
            'llama3.2' => ['1B', '3B', '11B', '90B'],
            'llama3' => ['8B', '70B'],
            'llama2' => ['7B', '13B', '70B'],
            'mistral' => ['7B'],
            'deepseek-r1' => ['1.5B', '7B', '8B', '14B', '32B', '67B', '671B'],
            'qwen' => ['0.5B', '1.8B', '4B', '7B', '14B', '32B', '72B', '110B'],
            'gemma' => ['2B', '7B', '9B', '27B'],
            'phi' => ['3B'],
            'falcon' => ['7B', '40B', '180B'],
            'codellama' => ['7B', '13B', '34B'],
            'vicuna' => ['7B', '13B', '33B'],
            'wizardcoder' => ['15B', '34B'],
            'starcoder' => ['1B', '3B', '7B', '15B'],
            'alpaca' => ['7B', '13B'],
            'orca' => ['2B', '13B']
        ];
        
        // Use extracted parameter sizes if found, otherwise try known model patterns
        $sizes = [];
        if (!empty($parameterSizes)) {
            $sizes = $parameterSizes;
        } else {
            // Try to match against known model families
            foreach ($modelVariants as $family => $familySizes) {
                if (stripos($modelSlug, $family) !== false) {
                    $sizes = $familySizes;
                    break;
                }
            }
        }
        
        // If no specific sizes found, create a generic variant
        if (empty($sizes)) {
            $sizes = ['latest'];
        }
        
        // Create variant objects
        foreach ($sizes as $size) {
            $variantName = ($size === 'latest') ? 'latest' : strtolower($size);
            $displayName = ($size === 'latest') ? 'Latest' : ucfirst($modelSlug) . ' ' . $size;
            
            $variants[] = [
                'name' => $variantName,
                'display_name' => $displayName,
                'full_name' => $modelSlug . ':' . $variantName,
                'size' => $size,
                'file_size' => $this->getEstimatedFileSize($size),
                'context' => $this->getContextLengthForSize($size),
                'context_length' => $this->getContextLengthForSize($size),
                'input_type' => $this->getInputTypeForModel($modelSlug)
            ];
        }
        
        return $variants;
    }

    /**
     * Fetch detailed model information including exact file sizes
     * 
     * @param string $modelSlug
     * @return array
     */
    private function fetchModelDetails(string $modelSlug): array
    {
        $url = "https://ollama.com/library/{$modelSlug}";
        
        try {
            $response = $this->httpClient->get($url, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $html = $response->getBody()->getContents();
            
            // Extract variants from the tags table
            $variants = $this->parseVariantsTable($html, $modelSlug);
            
            if (!empty($variants)) {
                return $variants;
            }
            
            // Fallback to generated variants if no table found
            return $this->generateDefaultVariants($modelSlug, '');
            
        } catch (\Exception $e) {
            // Fallback to generated variants on error
            return $this->generateDefaultVariants($modelSlug, '');
        }
    }

    /**
     * Parse variants table from model detail page
     * 
     * @param string $html
     * @param string $modelSlug
     * @return array
     */
    private function parseVariantsTable(string $html, string $modelSlug): array
    {
        $variants = [];
        
        // Look for the models section with grid layout
        if (preg_match('/<section[^>]*>.*?<h2[^>]*>Models<\/h2>.*?<\/section>/s', $html, $sectionMatch)) {
            $sectionHtml = $sectionMatch[0];
            
            // Find all model entries - they have the pattern with grid layout
            if (preg_match_all('/<div[^>]*class="[^"]*group[^"]*px-4[^"]*py-3[^"]*sm:grid[^"]*sm:grid-cols-12[^"]*"[^>]*>(.*?)<\/div>/s', $sectionHtml, $entryMatches)) {
                foreach ($entryMatches[1] as $entryHtml) {
                    // Extract the model name/tag from the link
                    if (preg_match('/<a[^>]+href="\/library\/[^:]+:([^"]+)"[^>]*>([^<]+)<\/a>/', $entryHtml, $linkMatch)) {
                        $tag = trim($linkMatch[1]);
                        $fullName = trim(strip_tags($linkMatch[2]));
                        
                        // Extract the grid columns - they should be in order: name(6), size(2), context(2), type(2)
                        if (preg_match_all('/<p[^>]*class="[^"]*col-span-2[^"]*text-neutral-500[^"]*">([^<]+)<\/p>/', $entryHtml, $colMatches)) {
                            $columns = $colMatches[1];
                            
                            if (count($columns) >= 3) {
                                $size = trim(strip_tags($columns[0])); // File size (e.g., "4.9GB")
                                $context = trim(strip_tags($columns[1])); // Context (e.g., "128K")
                                $type = trim(strip_tags($columns[2])); // Type (e.g., "Text")
                                
                                // Extract parameter size from tag
                                $parameterSize = 'Unknown';
                                if (preg_match('/(\d+(?:\.\d+)?[bB])/', $tag, $paramMatches)) {
                                    $parameterSize = strtoupper($paramMatches[1]);
                                } elseif (preg_match('/(\d+(?:\.\d+)?)$/', $tag, $paramMatches)) {
                                    $parameterSize = $paramMatches[1] . 'B';
                                } elseif ($tag === 'latest') {
                                    $parameterSize = 'Latest';
                                }
                                
                                $displayName = ($tag === 'latest') ? 'Latest' : ucfirst($modelSlug) . ' ' . $parameterSize;
                                
                                $variants[] = [
                                    'name' => $tag,
                                    'display_name' => $displayName,
                                    'full_name' => $modelSlug . ':' . $tag,
                                    'size' => $parameterSize,
                                    'file_size' => $size, // Exact size from page
                                    'context' => $context,
                                    'context_length' => $context,
                                    'input_type' => $type
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $variants;
    }

    /**
     * Get estimated file size based on parameter size
     * 
     * @param string $parameterSize
     * @return string
     */
    private function getEstimatedFileSize(string $parameterSize): string
    {
        if ($parameterSize === 'latest') {
            return '~2-4 GB';
        }
        
        // Extract numeric value
        $numSize = floatval($parameterSize);
        
        if ($numSize <= 1) {
            return '~0.5-1 GB';
        } elseif ($numSize <= 3) {
            return '~1.5-2 GB';
        } elseif ($numSize <= 7) {
            return '~3.5-4 GB';
        } elseif ($numSize <= 13) {
            return '~7-8 GB';
        } elseif ($numSize <= 34) {
            return '~20-25 GB';
        } elseif ($numSize <= 70) {
            return '~40-45 GB';
        } elseif ($numSize <= 180) {
            return '~100-120 GB';
        } else {
            return '~200+ GB';
        }
    }
    
    /**
     * Get estimated context length based on model size
     * 
     * @param string $size
     * @return string
     */
    private function getContextLengthForSize(string $size): string
    {
        if ($size === 'latest' || strpos($size, 'B') === false) {
            return '4K';
        }
        
        $numSize = floatval($size);
        
        if ($numSize >= 70) {
            return '128K';
        } elseif ($numSize >= 30) {
            return '32K';
        } elseif ($numSize >= 7) {
            return '8K';
        } else {
            return '4K';
        }
    }
    
    /**
     * Get input type based on model name
     * 
     * @param string $modelSlug
     * @return string
     */
    private function getInputTypeForModel(string $modelSlug): string
    {
        $codeModels = ['codellama', 'starcoder', 'wizardcoder', 'phind-codellama', 'deepseek-coder'];
        $visionModels = ['llava', 'bakllava', 'moondream'];
        
        foreach ($codeModels as $codeModel) {
            if (stripos($modelSlug, $codeModel) !== false) {
                return 'Code';
            }
        }
        
        foreach ($visionModels as $visionModel) {
            if (stripos($modelSlug, $visionModel) !== false) {
                return 'Vision';
            }
        }
        
        return 'Text';
    }

    /**
     * Extract tags from model page
     * 
     * @param string $html
     * @return array
     */
    private function extractTags(string $html): array
    {
        $tags = [];
        
        if (preg_match_all('/<span[^>]*class="[^"]*badge[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $tags = array_merge($tags, array_map('trim', $matches[1]));
        }
        
        if (preg_match_all('/<div[^>]*class="[^"]*tag[^"]*"[^>]*>([^<]+)<\/div>/i', $html, $matches)) {
            $tags = array_merge($tags, array_map('trim', $matches[1]));
        }
        
        return array_unique(array_filter($tags));
    }

    /**
     * Parse download count string to number
     * 
     * @param string $downloadStr
     * @return int
     */
    private function parseDownloadCount(string $downloadStr): int
    {
        $downloadStr = strtoupper(trim($downloadStr));
        $number = floatval($downloadStr);
        
        if (strpos($downloadStr, 'K') !== false) {
            return (int)($number * 1000);
        } elseif (strpos($downloadStr, 'M') !== false) {
            return (int)($number * 1000000);
        } elseif (strpos($downloadStr, 'B') !== false) {
            return (int)($number * 1000000000);
        }
        
        return (int)$number;
    }

    /**
     * Extract model family from slug
     * 
     * @param string $slug
     * @return string
     */
    private function extractModelFamily(string $slug): string
    {
        if (strpos($slug, 'llama') !== false) return 'llama';
        if (strpos($slug, 'phi') !== false) return 'phi3';
        if (strpos($slug, 'qwen') !== false) return 'qwen2';
        if (strpos($slug, 'gemma') !== false) return 'gemma2';
        if (strpos($slug, 'mistral') !== false) return 'mistral';
        if (strpos($slug, 'code') !== false) return 'codellama';
        if (strpos($slug, 'claude') !== false) return 'claude';
        if (strpos($slug, 'gpt') !== false) return 'gpt';
        
        return 'other';
    }

    /**
     * Infer use cases from model information
     * 
     * @param string $title
     * @param string $description
     * @param array $tags
     * @return array
     */
    private function inferUseCases(string $title, string $description, array $tags): array
    {
        $useCases = [];
        $text = strtolower($title . ' ' . $description . ' ' . implode(' ', $tags));
        
        if (strpos($text, 'code') !== false || strpos($text, 'programming') !== false) {
            $useCases[] = 'Code Generation';
        }
        
        if (strpos($text, 'chat') !== false || strpos($text, 'conversation') !== false) {
            $useCases[] = 'Chat';
        }
        
        if (strpos($text, 'analysis') !== false || strpos($text, 'reasoning') !== false) {
            $useCases[] = 'Analysis';
        }
        
        if (strpos($text, 'creative') !== false || strpos($text, 'writing') !== false) {
            $useCases[] = 'Creative Writing';
        }
        
        if (strpos($text, 'vision') !== false || strpos($text, 'image') !== false) {
            $useCases[] = 'Vision';
        }
        
        // Default use case if none detected
        if (empty($useCases)) {
            $useCases[] = 'General';
        }
        
        return $useCases;
    }

    /**
     * Get cached model count
     * 
     * @return int
     */
    public function getCachedModelCount(): int
    {
        $models = Cache::get('ollama_library_models', []);
        return count($models);
    }

    /**
     * Clear model cache
     * 
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('ollama_library_models');
        
        // Clear individual model detail caches
        // Use a simple approach that works with all cache stores
        $commonModels = [
            'llama', 'llama2', 'llama3', 'llama3.1', 'llama3.2',
            'mistral', 'phi3', 'qwen', 'qwen2', 'qwen3', 'gemma', 'gemma2', 'gemma3',
            'codellama', 'deepseek', 'neural-chat', 'openchat', 'vicuna',
            'wizardlm', 'orca', 'falcon', 'gpt-oss', 'starcoder', 'stable-code',
            'dolphin', 'nous-hermes', 'tinyllama', 'solar', 'yi', 'granite',
            'mathstral', 'codegemma', 'stablelm', 'zephyr', 'opencoder'
        ];
        
        foreach ($commonModels as $model) {
            Cache::forget("ollama_model_details_{$model}");
        }
        
        // Also clear some common variations
        for ($i = 1; $i <= 100; $i++) {
            Cache::forget("ollama_model_details_model_{$i}");
        }
    }
}