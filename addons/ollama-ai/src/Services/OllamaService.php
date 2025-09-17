<?php

namespace PterodactylAddons\OllamaAi\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

/**
 * Core service for interacting with local Ollama AI models.
 * 
 * This service provides a comprehensive interface for:
 * - Managing AI model interactions
 * - Handling chat conversations
 * - Processing analysis requests
 * - Managing model downloads and updates
 */
class OllamaService
{
    protected Client $client;
    protected string $baseUrl;
    protected int $timeout;
    protected array $models;

    public function __construct()
    {
        $this->baseUrl = config('ai.ollama.base_url', 'http://localhost:11434');
        $this->timeout = config('ai.ollama.timeout', 30);
        $this->models = config('ai.models', []);
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
            'verify' => config('ai.ollama.verify_ssl', false),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Test connection to Ollama service
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client->get('/api/tags');
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Ollama connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get models from Ollama library using web scraping
     *
     * @param bool $forceRefresh Skip cache and fetch fresh data
     * @return array
     */
    public function getOllamaLibraryModels(bool $forceRefresh = false): array
    {
        try {
            $scraperService = new \PterodactylAddons\OllamaAi\Services\OllamaLibraryScraperService();
            $libraryModels = $scraperService->getAllModels($forceRefresh);
            
            $installedModels = $this->getModelNames();
            
            // Enhance each model with installation status and performance estimation
            $enhancedModels = [];
            $processedCount = 0;
            $maxModels = 50; // Limit to avoid too many requests
            
            foreach ($libraryModels as $model) {
                if ($processedCount >= $maxModels) {
                    break;
                }
                
                // Check if any variants are installed
                $hasInstalledVariant = false;
                if (!empty($model['variants'])) {
                    foreach ($model['variants'] as $variant) {
                        if (in_array($variant['full_name'], $installedModels)) {
                            $hasInstalledVariant = true;
                            break;
                        }
                    }
                }
                
                // Get first variant for main card display
                $firstVariant = !empty($model['variants']) ? $model['variants'][0] : null;
                
                $enhancedModels[] = [
                    'name' => $model['slug'],
                    'slug' => $model['slug'], 
                    'title' => $model['title'] ?: ucfirst(str_replace('-', ' ', $model['slug'])),
                    'description' => $model['description'],
                    'family' => $this->extractModelFamily($model['slug']),
                    'size' => $firstVariant ? $firstVariant['file_size'] : 'Varies by variant', // Use actual file size
                    'parameters' => $firstVariant ? $firstVariant['size'] : 'Unknown', // Parameter size
                    'speed' => $this->estimateSpeed($firstVariant ? $firstVariant['size'] : 'Unknown', $model['slug']),
                    'quality' => $this->estimateQuality($firstVariant ? $firstVariant['size'] : 'Unknown', $model['downloads']),
                    'use_cases' => $this->inferUseCases($model['title'], $model['description'], $model['tags'] ?? []),
                    'tags' => $model['tags'] ?? [],
                    'installed' => $hasInstalledVariant,
                    'downloads' => $model['downloads'],
                    'updated' => $model['updated'],
                    'context' => $firstVariant ? $firstVariant['context'] : '4K',
                    'input_type' => $firstVariant ? $firstVariant['input_type'] : 'Text',
                    'variants' => $model['variants'] ?? [], // Keep all variants for modal selection
                ];
                
                $processedCount++;
            }
            
            // Sort by downloads (popularity) and limit to reasonable number
            usort($enhancedModels, function($a, $b) {
                // Sort by downloads first, then by model name for consistency
                if ($a['downloads'] == $b['downloads']) {
                    return strcmp($a['name'], $b['name']);
                }
                return $b['downloads'] <=> $a['downloads'];
            });
            
            return $enhancedModels; // Already limited by $maxModels during processing
            
        } catch (\Exception $e) {
            Log::error('Failed to get Ollama library models', ['error' => $e->getMessage()]);
            
            // Fallback to basic static list if scraping fails
            return $this->getFallbackModels();
        }
    }

    /**
     * Extract parameter count from model name
     */
    private function extractParameterCount(string $name): string
    {
        if (preg_match('/(\d+)b/i', $name, $matches)) {
            return $matches[1] . 'B';
        }
        if (preg_match('/(\d+)m/i', $name, $matches)) {
            return $matches[1] . 'M';
        }
        return 'Unknown';
    }

    /**
     * Estimate response speed based on parameter size and model type
     */
    private function estimateSpeed(string $parameterSize, string $modelName): string
    {
        // Handle parameter sizes (like "8B", "70B", etc.)
        $parameters = $this->parseParameterCount($parameterSize);
        
        // Speed estimation based on parameter count (in billions)
        if ($parameters <= 1) {
            return 'Extremely Fast (<1s)';
        } elseif ($parameters <= 3) {
            return 'Very Fast (1-3s)';
        } elseif ($parameters <= 8) {
            return 'Fast (2-5s)';
        } elseif ($parameters <= 15) {
            return 'Medium (5-10s)';
        } elseif ($parameters <= 35) {
            return 'Slow (10-20s)';
        } elseif ($parameters <= 75) {
            return 'Very Slow (20-60s)';
        } else {
            return 'Extremely Slow (60s+)';
        }
    }

    /**
     * Parse parameter count from size string (e.g., "8B" -> 8, "1.5B" -> 1.5)
     */
    private function parseParameterCount(string $size): float
    {
        if ($size === 'Unknown') return 7; // Default assumption
        if ($size === 'Latest') return 7; // Latest is typically around 7-8B
        
        $size = strtoupper(trim($size));
        
        // Handle parameter sizes like "8B", "70B", "1.5B"
        if (preg_match('/(\d+(?:\.\d+)?)B?$/', $size, $matches)) {
            return floatval($matches[1]);
        }
        
        return 7; // Default fallback
    }

    /**
     * Estimate quality based on size and popularity
     */
    private function estimateQuality(string $size, int $downloads): string
    {
        $sizeBytes = $this->parseSizeToBytes($size);
        
        if ($downloads > 1000000) { // Very popular
            if ($sizeBytes > 10 * 1024 * 1024 * 1024) return 'Outstanding';
            if ($sizeBytes > 3 * 1024 * 1024 * 1024) return 'Excellent';
            return 'Very Good';
        } elseif ($downloads > 100000) { // Popular
            if ($sizeBytes > 5 * 1024 * 1024 * 1024) return 'Excellent';
            return 'Very Good';
        } else {
            return 'Good';
        }
    }

    /**
     * Parse size string to bytes
     */
    private function parseSizeToBytes(string $size): int
    {
        if ($size === 'Unknown') return 0;
        
        $size = strtoupper(trim($size));
        $number = floatval($size);
        
        if (strpos($size, 'GB') !== false) {
            return (int)($number * 1024 * 1024 * 1024);
        } elseif (strpos($size, 'MB') !== false) {
            return (int)($number * 1024 * 1024);
        }
        
        return (int)$number;
    }

    /**
     * Extract model family from name
     */
    private function extractModelFamily(string $name): string
    {
        if (strpos(strtolower($name), 'llama') !== false) return 'llama';
        if (strpos(strtolower($name), 'phi') !== false) return 'phi3';
        if (strpos(strtolower($name), 'qwen') !== false) return 'qwen2';
        if (strpos(strtolower($name), 'gemma') !== false) return 'gemma2';
        if (strpos(strtolower($name), 'mistral') !== false) return 'mistral';
        if (strpos(strtolower($name), 'code') !== false) return 'codellama';
        return 'other';
    }

    /**
     * Infer use cases from title and description
     */
    private function inferUseCases(string $title, string $description): array
    {
        $text = strtolower($title . ' ' . $description);
        $useCases = [];
        
        if (strpos($text, 'code') !== false) $useCases[] = 'Code Generation';
        if (strpos($text, 'chat') !== false) $useCases[] = 'Chat';
        if (strpos($text, 'reason') !== false) $useCases[] = 'Analysis';
        if (strpos($text, 'creative') !== false) $useCases[] = 'Creative Writing';
        
        return empty($useCases) ? ['General'] : $useCases;
    }

    /**
     * Generate additional tags based on model characteristics
     */
    private function generateTags(array $details, array $variant): array
    {
        $tags = [];
        
        $sizeBytes = $this->parseSizeToBytes($variant['size']);
        if ($sizeBytes < 2 * 1024 * 1024 * 1024) $tags[] = 'lightweight';
        if ($sizeBytes > 20 * 1024 * 1024 * 1024) $tags[] = 'large';
        
        if ($details['downloads'] > 1000000) $tags[] = 'popular';
        if (strpos($variant['name'], 'latest') !== false) $tags[] = 'latest';
        
        return $tags;
    }

    /**
     * Fallback static model list in case scraping fails
     */
    private function getFallbackModels(): array
    {
        $installedModels = $this->getModelNames();
        
        return [
            [
                'name' => 'llama3.2:1b',
                'title' => 'Llama 3.2 1B',
                'description' => 'Meta\'s smallest Llama 3.2 model, extremely fast responses',
                'size' => '1.3GB',
                'parameters' => '1B',
                'use_cases' => ['Chat', 'Q&A'],
                'speed' => 'Very Fast (1-3s)',
                'quality' => 'Good',
                'family' => 'llama',
                'tags' => ['ultra-fast', 'lightweight'],
                'installed' => in_array('llama3.2:1b', $installedModels),
                'downloads' => 0,
                'updated' => '',
                'context' => '',
                'input_type' => 'Text',
            ],
            [
                'name' => 'phi3:mini',
                'title' => 'Phi-3 Mini',
                'description' => 'Microsoft\'s efficient 3.8B parameter model',
                'size' => '2.3GB',
                'parameters' => '3.8B',
                'use_cases' => ['Chat', 'Reasoning'],
                'speed' => 'Fast (2-5s)',
                'quality' => 'Very Good',
                'family' => 'phi3',
                'tags' => ['efficient', 'microsoft'],
                'installed' => in_array('phi3:mini', $installedModels),
                'downloads' => 0,
                'updated' => '',
                'context' => '',
                'input_type' => 'Text',
            ],
        ];
    }

    /**
     * Get list of available models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->client->get('/api/tags');
            $data = json_decode($response->getBody()->getContents(), true);
            
            return collect($data['models'] ?? [])
                ->map(function ($model) {
                    return [
                        'name' => $model['name'],
                        'size' => $model['size'] ?? 0,
                        'modified_at' => $model['modified_at'] ?? null,
                        'details' => $model['details'] ?? [],
                    ];
                })
                ->toArray();
        } catch (GuzzleException $e) {
            Log::error('Failed to fetch available models', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get array of model names only
     */
    public function getModelNames(): array
    {
        $models = $this->getAvailableModels();
        return collect($models)->pluck('name')->toArray();
    }

    /**
     * Check if a specific model is available
     */
    public function isModelAvailable(string $modelName): bool
    {
        $models = $this->getAvailableModels();
        return collect($models)->contains('name', $modelName);
    }

    /**
     * Download a model if not available
     */
    public function pullModel(string $modelName): bool
    {
        try {
            $response = $this->client->post('/api/pull', [
                'json' => ['name' => $modelName],
                'stream' => false,
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Failed to pull model', [
                'model' => $modelName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Pull model with streaming progress
     */
    public function pullModelWithProgress(string $modelName): \Generator
    {
        try {
            $response = $this->client->post('/api/pull', [
                'json' => ['name' => $modelName],
                'stream' => true,
                'timeout' => 300, // 5 minutes for large models
            ]);

            $body = $response->getBody();
            
            while (!$body->eof()) {
                $line = '';
                while (($char = $body->read(1)) !== "\n" && !$body->eof()) {
                    $line .= $char;
                }
                
                if (!empty(trim($line))) {
                    $data = json_decode(trim($line), true);
                    if ($data) {
                        yield $data;
                    }
                }
            }
        } catch (GuzzleException $e) {
            Log::error('Failed to pull model with progress', [
                'model' => $modelName,
                'error' => $e->getMessage()
            ]);
            yield ['error' => $e->getMessage()];
        }
    }

    /**
     * Send a chat message to AI model
     */
    public function chat(string $message, string $modelName = null, array $context = []): ?array
    {
        $modelName = $modelName ?? $this->models['chat'] ?? 'llama3.1:8b';
        
        try {
            // Ensure model is available
            if (!$this->isModelAvailable($modelName)) {
                if (!$this->pullModel($modelName)) {
                    throw new \Exception("Model {$modelName} is not available and cannot be downloaded");
                }
            }

            $payload = [
                'model' => $modelName,
                'messages' => array_merge($context, [
                    ['role' => 'user', 'content' => $message]
                ]),
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'num_predict' => config('ai.limits.max_tokens', 2048),
                ]
            ];

            $response = $this->client->post('/api/chat', [
                'json' => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['message']['content'])) {
                return [
                    'response' => $data['message']['content'],
                    'model' => $modelName,
                    'created_at' => now()->toISOString(),
                    'tokens_used' => $data['eval_count'] ?? null,
                    'eval_duration' => $data['eval_duration'] ?? null,
                ];
            }

            return null;
        } catch (GuzzleException $e) {
            Log::error('AI chat request failed', [
                'model' => $modelName,
                'message' => substr($message, 0, 100),
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('AI chat processing failed', [
                'model' => $modelName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate code or configuration snippets
     */
    public function generateCode(string $prompt, string $language = 'php'): ?array
    {
        $modelName = $this->models['code'] ?? 'codellama:7b';
        
        $enhancedPrompt = "Generate {$language} code for the following request. " .
                         "Provide clean, well-commented, and secure code:\n\n{$prompt}";
        
        return $this->chat($enhancedPrompt, $modelName);
    }

    /**
     * Analyze data and provide insights
     */
    public function analyzeData(array $data, string $analysisType = 'general'): ?array
    {
        $modelName = $this->models['analysis'] ?? 'mistral:7b';
        
        $prompt = "Analyze the following data and provide insights:\n" .
                 "Analysis Type: {$analysisType}\n" .
                 "Data: " . json_encode($data, JSON_PRETTY_PRINT);
        
        return $this->chat($prompt, $modelName);
    }

    /**
     * Process security-related analysis
     */
    public function analyzeSecurityThreat(array $logData): ?array
    {
        $modelName = $this->models['security'] ?? 'llama3.1:8b';
        
        $prompt = "Analyze the following log data for potential security threats. " .
                 "Identify any suspicious patterns, failed login attempts, or unusual activity:\n" .
                 json_encode($logData, JSON_PRETTY_PRINT);
        
        return $this->chat($prompt, $modelName);
    }

    /**
     * Generate documentation or help content
     */
    public function generateDocumentation(string $topic, array $context = []): ?array
    {
        $modelName = $this->models['documentation'] ?? 'gemma:7b';
        
        $contextStr = !empty($context) ? "\nContext: " . json_encode($context, JSON_PRETTY_PRINT) : '';
        $prompt = "Generate clear, helpful documentation for: {$topic}{$contextStr}";
        
        return $this->chat($prompt, $modelName);
    }

    /**
     * Get model information and status
     */
    public function getModelInfo(string $modelName): ?array
    {
        try {
            $response = $this->client->post('/api/show', [
                'json' => ['name' => $modelName],
            ]);
            
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Failed to get model info', [
                'model' => $modelName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Delete a model from local storage
     */
    public function deleteModel(string $modelName): bool
    {
        try {
            $response = $this->client->delete('/api/delete', [
                'json' => ['name' => $modelName],
            ]);
            
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            Log::error('Failed to delete model', [
                'model' => $modelName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get system resource usage
     */
    public function getSystemStatus(): array
    {
        try {
            $response = $this->client->get('/api/tags');
            $isConnected = $response->getStatusCode() === 200;
            
            // If connected, get more detailed status
            if ($isConnected) {
                try {
                    $psResponse = $this->client->get('/api/ps');
                    $psData = json_decode($psResponse->getBody()->getContents(), true);
                    
                    return [
                        'connected' => true,
                        'running_models' => $psData['models'] ?? [],
                        'connection_status' => 'connected',
                        'last_check' => now()->toISOString(),
                    ];
                } catch (GuzzleException $e) {
                    // Connection works but /api/ps failed, still consider connected
                    return [
                        'connected' => true,
                        'running_models' => [],
                        'connection_status' => 'connected',
                        'last_check' => now()->toISOString(),
                    ];
                }
            }
            
        } catch (GuzzleException $e) {
            // Connection failed
            return [
                'connected' => false,
                'running_models' => [],
                'connection_status' => 'disconnected',
                'last_check' => now()->toISOString(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate AI configuration
     */
    public function validateConfiguration(): array
    {
        $issues = [];
        
        // Check connection
        if (!$this->testConnection()) {
            $issues[] = 'Cannot connect to Ollama service at ' . $this->baseUrl;
        }
        
        // Check configured models
        foreach ($this->models as $type => $modelName) {
            if (!$this->isModelAvailable($modelName)) {
                $issues[] = "Model '{$modelName}' for {$type} is not available";
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'models_available' => $this->getAvailableModels(),
            'system_status' => $this->getSystemStatus(),
        ];
    }

    /**
     * Send message with conversation context (for chat controller)
     */
    public function sendMessage(string $message, string $modelName, array $conversationHistory = [], string $systemPrompt = null): array
    {
        try {
            $modelName = $modelName ?? config('ai.models.chat', 'llama3.2');
            
            // Build messages array with system prompt and conversation history
            $messages = [];
            
            // Add system prompt if provided
            if ($systemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }
            
            // Add conversation history
            foreach ($conversationHistory as $historyMessage) {
                $messages[] = [
                    'role' => $historyMessage['role'],
                    'content' => $historyMessage['content']
                ];
            }
            
            // Add current user message
            $messages[] = ['role' => 'user', 'content' => $message];

            // Ensure model is available
            if (!$this->isModelAvailable($modelName)) {
                throw new \Exception("Model {$modelName} is not available");
            }

            $startTime = microtime(true);

            $payload = [
                'model' => $modelName,
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => (float) config('ai.ollama.temperature', 0.7),
                    'top_p' => (float) config('ai.ollama.top_p', 0.9),
                    'num_predict' => (int) config('ai.limits.max_tokens', 2048),
                ]
            ];

            $response = $this->client->post('/api/chat', [
                'json' => $payload,
                'timeout' => $this->timeout,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['message']['content'])) {
                return [
                    'success' => true,
                    'content' => $data['message']['content'],
                    'model' => $modelName,
                    'response_time' => round((microtime(true) - $startTime) * 1000, 2), // ms
                    'tokens' => $data['eval_count'] ?? null,
                    'prompt_tokens' => $data['prompt_eval_count'] ?? null,
                ];
            } else {
                throw new \Exception('Invalid response format from AI model');
            }

        } catch (GuzzleException $e) {
            Log::error('AI sendMessage failed', [
                'error' => $e->getMessage(),
                'model' => $modelName ?? 'unknown',
                'message_length' => strlen($message)
            ]);
            
            return [
                'success' => false,
                'error' => 'AI service error: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('AI sendMessage error', [
                'error' => $e->getMessage(),
                'model' => $modelName ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get models in the format expected by the chat interface
     */
    public function getModels(): array
    {
        try {
            $availableModels = $this->getAvailableModels();
            
            return [
                'success' => true,
                'models' => collect($availableModels)->map(function ($model) {
                    return [
                        'name' => $model['name'],
                        'size' => $model['size'] ?? 0,
                        'modified_at' => $model['modified_at'] ?? null,
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'models' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}