<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Services\OllamaService;
use PterodactylAddons\OllamaAi\Services\AiAssistantService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin controller for AI settings and configuration.
 * 
 * Handles:
 * - AI system configuration
 * - Model management
 * - System status monitoring
 * - Performance analytics
 */
class AiSettingsController extends Controller
{
    protected OllamaService $ollamaService;
    protected AiAssistantService $assistantService;

    public function __construct(OllamaService $ollamaService, AiAssistantService $assistantService)
    {
        $this->ollamaService = $ollamaService;
        $this->assistantService = $assistantService;
    }

    /**
     * Show the main AI settings page
     */
    public function index(): View
    {
        $systemStatus = $this->ollamaService->getSystemStatus();
        $validation = $this->ollamaService->validateConfiguration();
        $availableModels = $this->ollamaService->getAvailableModels();
        $configuredModels = config('ai.models', []);
        $usageStats = $this->assistantService->getUsageStatistics();

        return view('ollama-ai::admin.settings.index', [
            'systemStatus' => $systemStatus,
            'validation' => $validation,
            'availableModels' => $availableModels,
            'configuredModels' => $configuredModels,
            'usageStats' => $usageStats,
            'features' => config('ai.features', []),
            'limits' => config('ai.limits', []),
            'ollamaConfig' => config('ai.ollama', []),
        ]);
    }

    /**
     * Update AI settings
     */
    public function update(Request $request)
    {
        // Add debug logging
        \Log::info('AI Settings Update - Start', [
            'request_data' => $request->all(),
            'form_fields' => array_keys($request->all())
        ]);

        // Validate the request
        $validatedData = $request->validate([
            'ollama_api_url' => 'required|url',
            'ollama_timeout' => 'required|integer|min:5|max:300',
            'default_model' => 'nullable|string',
            'enable_ai' => 'nullable|boolean',
            'max_tokens' => 'nullable|integer|min:128|max:8192',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'top_p' => 'nullable|numeric|min:0|max:1',
            'enable_chat' => 'nullable|boolean',
            'enable_analysis' => 'nullable|boolean',
            'enable_insights' => 'nullable|boolean',
        ]);

        \Log::info('AI Settings Update - Validation passed', [
            'validated_data' => $validatedData
        ]);

        // Map form fields to environment variables
        $fieldMapping = [
            'ollama_api_url' => 'OLLAMA_BASE_URL',
            'ollama_timeout' => 'OLLAMA_TIMEOUT',
            'default_model' => 'AI_DEFAULT_MODEL', // Fixed: use AI_DEFAULT_MODEL instead of AI_CHAT_MODEL
            'enable_ai' => 'AI_ENABLED',
            'max_tokens' => 'AI_MAX_TOKENS',
            'temperature' => 'AI_TEMPERATURE',
            'top_p' => 'AI_TOP_P',
            'enable_chat' => 'AI_CHAT_ENABLED',
            'enable_analysis' => 'AI_ANALYSIS_ENABLED',
            'enable_insights' => 'AI_ADMIN_INSIGHTS_ENABLED',
        ];

        // Prepare environment variables to update
        $envUpdates = [];
        
        // Handle all fields including those not sent (like unchecked checkboxes)
        $allFields = [
            'ollama_api_url' => $request->get('ollama_api_url'),
            'ollama_timeout' => $request->get('ollama_timeout'),
            'default_model' => $request->get('default_model'),
            'enable_ai' => $request->has('enable_ai') ? (bool)$request->get('enable_ai') : false,
            'max_tokens' => $request->get('max_tokens'),
            'temperature' => $request->get('temperature'),
            'top_p' => $request->get('top_p'),
            'enable_chat' => $request->has('enable_chat') ? (bool)$request->get('enable_chat') : false,
            'enable_analysis' => $request->has('enable_analysis') ? (bool)$request->get('enable_analysis') : false,
            'enable_insights' => $request->has('enable_insights') ? (bool)$request->get('enable_insights') : false,
        ];
        
        foreach ($allFields as $key => $value) {
            if (isset($fieldMapping[$key]) && $value !== null) {
                $envKey = $fieldMapping[$key];
                // Convert boolean values to strings
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $envUpdates[$envKey] = $value;
            }
        }

        \Log::info('AI Settings Update - Environment updates prepared', [
            'env_updates' => $envUpdates
        ]);

        // Update environment file
        if (!$this->updateEnvFile($envUpdates)) {
            \Log::error('AI Settings Update - Failed to update environment file');
            return redirect()->back()
                ->with('error', 'Failed to update configuration. Please check file permissions.')
                ->withInput();
        }

        \Log::info('AI Settings Update - Environment file updated successfully');

        // Clear configuration cache
        \Artisan::call('config:clear');
        \Artisan::call('route:clear'); // Also clear route cache just in case
        \Log::info('AI Settings Update - Configuration cache cleared');

        return redirect()->route('admin.ai.settings')->with('success', 'AI settings updated successfully. Please refresh the page to see the updated values.');
    }

    /**
     * Update environment file with new values
     */
    private function updateEnvFile($updates)
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return false;
        }

        $envContent = file_get_contents($envFile);
        
        foreach ($updates as $key => $value) {
            // Escape value for .env format
            $escapedValue = is_string($value) && (str_contains($value, ' ') || str_contains($value, '#')) ? '"' . $value . '"' : $value;
            
            // Check if key exists
            if (preg_match("/^{$key}=.*$/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$escapedValue}", $envContent);
            } else {
                // Add new key at the end
                $envContent .= "\n{$key}={$escapedValue}";
            }
        }
        
        return file_put_contents($envFile, $envContent) !== false;
    }

    /**
     * Test Ollama connection
     */
    public function testConnection()
    {
        $isConnected = $this->ollamaService->testConnection();
        $systemStatus = $this->ollamaService->getSystemStatus();

        return response()->json([
            'connected' => $isConnected,
            'status' => $systemStatus,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Show the models management page
     */
    public function showModels(): View
    {
        $models = $this->ollamaService->getAvailableModels();
        $configuredModels = config('ai.models', []);
        $systemStatus = $this->ollamaService->getSystemStatus();

        return view('ollama-ai::admin.models.index', [
            'models' => $models,
            'configuredModels' => $configuredModels,
            'systemStatus' => $systemStatus,
            'ollamaConfig' => config('ai.ollama', []),
        ]);
    }

    /**
     * Get available models (API endpoint)
     */
    public function getModels()
    {
        $models = $this->ollamaService->getAvailableModels();
        
        return response()->json([
            'models' => $models,
            'configured' => config('ai.models', []),
        ]);
    }

    /**
     * Show model library with all available models from Ollama.com
     */
    public function modelLibrary(Request $request)
    {
        $forceRefresh = $request->boolean('refresh', false);
        $libraryModels = $this->ollamaService->getOllamaLibraryModels($forceRefresh);
        
        return view('ollama-ai::admin.models.library', [
            'libraryModels' => $libraryModels,
            'totalModels' => count($libraryModels),
            'lastUpdated' => now(),
        ]);
    }

    /**
     * Clear model library cache and refresh data
     */
    public function refreshLibrary()
    {
        $scraperService = new \PterodactylAddons\OllamaAi\Services\OllamaLibraryScraperService();
        $scraperService->clearCache();
        
        return redirect()->route('admin.ai.models.library', ['refresh' => 1])
            ->with('success', 'Model library cache cleared and refreshed successfully.');
    }

    /**
     * Pull/download a model
     */
    public function pullModel(Request $request)
    {
        $request->validate([
            'model' => 'required|string|max:100',
        ]);

        $modelName = $request->input('model');
        $success = $this->ollamaService->pullModel($modelName);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "Model '{$modelName}' downloaded successfully.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to download model '{$modelName}'.",
        ], 400);
    }

    /**
     * Pull model with streaming progress
     */
    public function pullModelProgress(Request $request)
    {
        $request->validate([
            'model' => 'required|string|max:100',
        ]);

        $modelName = $request->input('model');

        return response()->stream(function () use ($modelName) {
            // Set up streaming headers and ensure no buffering
            set_time_limit(0); // No time limit for streaming
            ignore_user_abort(false); // Stop if user disconnects
            
            // Clear any existing buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Send initial progress immediately
            echo "data: " . json_encode([
                'status' => 'starting', 
                'message' => 'Initializing download...', 
                'percent' => 0
            ]) . "\n\n";
            flush();
            
            // Track progress for fallback
            $progressCounter = 5;
            $lastProgressTime = time();
            
            try {
                foreach ($this->ollamaService->pullModelWithProgress($modelName) as $progress) {
                if (isset($progress['error'])) {
                    echo "data: " . json_encode([
                        'status' => 'error',
                        'message' => $progress['error']
                    ]) . "\n\n";
                    
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                    break;
                }

                // Parse Ollama progress data
                $status = 'downloading';
                $message = 'Downloading...';
                $percent = 0;

                // Debug: Log what we receive from Ollama (remove this in production)
                if (config('app.debug')) {
                    \Log::info('Ollama Progress Data:', $progress);
                }

                if (isset($progress['status'])) {
                    if ($progress['status'] === 'pulling manifest') {
                        $message = 'Pulling manifest...';
                        $percent = 5;
                    } elseif ($progress['status'] === 'downloading' || $progress['status'] === 'pulling') {
                        // Check for different possible progress fields
                        $completed = $progress['completed'] ?? 0;
                        $total = $progress['total'] ?? 0;
                        
                        // Also check for 'digest' field which Ollama sometimes uses
                        if ($total == 0 && isset($progress['digest'])) {
                            // For digest-based progress, check if we have size info
                            if (isset($progress['size'])) {
                                $total = $progress['size'];
                            }
                        }
                        
                        if ($total > 0 && $completed >= 0) {
                            $percent = min(round($completed / $total * 100, 1), 99); // Cap at 99% until complete
                            $message = 'Downloading: ' . $this->formatBytes($completed) . ' / ' . $this->formatBytes($total);
                            
                            // Add speed calculation if we have timing information
                            if (isset($progress['speed'])) {
                                $message .= ' (' . $this->formatBytes($progress['speed']) . '/s)';
                            }
                        } else {
                            // Fallback: use incremental progress based on time
                            $currentTime = time();
                            if ($currentTime > $lastProgressTime) {
                                $progressCounter = min($progressCounter + 3, 85); // Increment by 3% every second, cap at 85%
                                $lastProgressTime = $currentTime;
                            }
                            $percent = $progressCounter;
                            
                            if ($completed > 0) {
                                $message = 'Downloading: ' . $this->formatBytes($completed) . '...';
                            } else {
                                $message = 'Downloading model files...';
                            }
                        }
                    } elseif ($progress['status'] === 'verifying sha256 digest') {
                        $message = 'Verifying download...';
                        $percent = 90;
                    } elseif ($progress['status'] === 'writing manifest') {
                        $message = 'Writing manifest...';
                        $percent = 95;
                    } elseif ($progress['status'] === 'success') {
                        $status = 'complete';
                        $message = 'Download completed successfully!';
                        $percent = 100;
                    } elseif ($progress['status'] === 'pulling fs layer') {
                        $message = 'Pulling file system layer...';
                        $percent = 15;
                    } elseif ($progress['status'] === 'extracting') {
                        $message = 'Extracting model...';
                        $percent = 85;
                    }
                }

                echo "data: " . json_encode([
                    'status' => $status,
                    'message' => $message,
                    'percent' => $percent,
                    'model' => $modelName
                ]) . "\n\n";

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();

                if (connection_aborted()) {
                    break;
                }

                if ($status === 'complete') {
                    break;
                }
            }
            } catch (\Exception $e) {
                echo "data: " . json_encode([
                    'status' => 'error',
                    'message' => 'Download failed: ' . $e->getMessage()
                ]) . "\n\n";
                flush();
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Delete a model
     */
    public function deleteModel(Request $request, $model)
    {
        // Get model name from URL parameter instead of request input
        $modelName = $model;
        
        // Validate model name exists
        if (!$modelName) {
            return response()->json([
                'success' => false,
                'message' => 'Model name is required.',
            ], 422);
        }
        
        // Check if model is currently configured
        $configuredModels = config('ai.models', []);
        $inUse = in_array($modelName, $configuredModels);

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete model '{$modelName}' - it's currently configured for use.",
            ], 400);
        }

        $success = $this->ollamaService->deleteModel($modelName);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => "Model '{$modelName}' deleted successfully.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "Failed to delete model '{$modelName}'.",
        ], 400);
    }

    /**
     * Get model information
     */
    public function modelInfo(string $modelName)
    {
        $modelInfo = $this->ollamaService->getModelInfo($modelName);

        return view('ollama-ai::admin.models.info', [
            'modelName' => $modelName,
            'modelInfo' => $modelInfo,
        ]);
    }

    /**
     * Validate current configuration
     */
    public function validateConfig()
    {
        $validation = $this->ollamaService->validateConfiguration();

        return response()->json($validation);
    }

    /**
     * Get system statistics
     */
    public function getStats()
    {
        $usageStats = $this->assistantService->getUsageStatistics();
        $systemStatus = $this->ollamaService->getSystemStatus();

        return response()->json([
            'usage' => $usageStats,
            'system' => $systemStatus,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Clean up old data
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'type' => 'required|in:conversations,all',
            'confirm' => 'required|boolean|accepted',
        ]);

        if (!$request->boolean('confirm')) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup must be confirmed.',
            ], 400);
        }

        $result = $this->assistantService->cleanupOldData();

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Cleanup completed. Deleted %d conversations and %d messages.',
                $result['conversations_deleted'],
                $result['messages_deleted']
            ),
            'stats' => $result,
        ]);
    }

    /**
     * Archive old conversations
     */
    public function archiveOld(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $daysOld = $request->integer('days');
        $archived = $this->assistantService->archiveOldConversations($daysOld);

        return response()->json([
            'success' => true,
            'message' => "Archived {$archived} conversations older than {$daysOld} days.",
            'archived_count' => $archived,
        ]);
    }

    /**
     * Test AI functionality
     */
    public function testAi(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'model_type' => 'nullable|string|in:chat,code,analysis,security,documentation',
        ]);

        $message = $request->input('message');
        $modelType = $request->input('model_type', 'chat');
        $modelName = config("ai.models.{$modelType}");

        if (!$modelName) {
            return response()->json([
                'success' => false,
                'message' => "No model configured for type: {$modelType}",
            ], 400);
        }

        $startTime = microtime(true);
        $response = $this->ollamaService->chat($message, $modelName);
        $duration = (microtime(true) - $startTime) * 1000;

        if ($response) {
            return response()->json([
                'success' => true,
                'response' => $response['response'],
                'model_used' => $response['model'],
                'tokens_used' => $response['tokens_used'],
                'duration_ms' => round($duration, 2),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to get AI response. Check logs for details.',
        ], 500);
    }
}