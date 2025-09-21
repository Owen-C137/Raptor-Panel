<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Models\Updates\UpdateSession;
use Pterodactyl\Services\Updates\Database\SessionService;

/**
 * ProgressTrackingService manages update progress tracking and reporting.
 * 
 * This service provides comprehensive progress tracking including:
 * - Real-time progress updates
 * - Step-by-step tracking
 * - Progress percentage calculations
 * - Time estimation
 * - Progress history and analytics
 * - WebSocket broadcasting for real-time updates
 */
class ProgressTrackingService extends BaseUpdateService
{
    private SessionService $sessionService;
    private array $progressListeners = [];
    
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Initialize progress tracking for a session.
     *
     * @param string $sessionId
     * @param array $steps
     * @return array
     */
    public function initializeProgress(string $sessionId, array $steps = []): array
    {
        try {
            $defaultSteps = $this->getDefaultProgressSteps();
            $allSteps = array_merge($defaultSteps, $steps);
            
            // Store progress structure in cache
            $progressData = [
                'session_id' => $sessionId,
                'total_steps' => count($allSteps),
                'current_step_index' => 0,
                'current_step' => $allSteps[0] ?? 'Initializing',
                'progress_percentage' => 0,
                'started_at' => now(),
                'steps' => $allSteps,
                'step_timings' => [],
                'estimated_completion' => null,
            ];
            
            $this->storeProgressData($sessionId, $progressData);
            
            // Update session
            $this->sessionService->updateSessionProgress($sessionId, [
                'percentage' => 0,
                'current_step' => $allSteps[0] ?? 'Initializing',
            ]);
            
            // Broadcast initial progress
            $this->broadcastProgress($sessionId, $progressData);
            
            Log::info("Progress tracking initialized", [
                'session_id' => $sessionId,
                'total_steps' => count($allSteps),
            ]);
            
            return [
                'success' => true,
                'progress_data' => $progressData,
            ];
            
        } catch (Exception $e) {
            Log::error("Failed to initialize progress tracking", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update progress for a session.
     *
     * @param string $sessionId
     * @param array $updates
     * @return array
     */
    public function updateProgress(string $sessionId, array $updates): array
    {
        try {
            $progressData = $this->getProgressData($sessionId);
            
            if (!$progressData) {
                return [
                    'success' => false,
                    'error' => 'Progress data not found',
                ];
            }
            
            // Update progress data
            if (isset($updates['step_index'])) {
                $progressData['current_step_index'] = $updates['step_index'];
                $progressData['current_step'] = $progressData['steps'][$updates['step_index']] ?? 'Unknown Step';
                $progressData['progress_percentage'] = $this->calculateProgressPercentage($progressData);
            }
            
            if (isset($updates['current_step'])) {
                $progressData['current_step'] = $updates['current_step'];
            }
            
            if (isset($updates['progress_percentage'])) {
                $progressData['progress_percentage'] = max(0, min(100, $updates['progress_percentage']));
            }
            
            if (isset($updates['additional_info'])) {
                $progressData['additional_info'] = $updates['additional_info'];
            }
            
            // Record step timing
            if (isset($updates['step_completed'])) {
                $this->recordStepTiming($progressData, $updates['step_completed']);
            }
            
            // Calculate estimated completion time
            $progressData['estimated_completion'] = $this->calculateEstimatedCompletion($progressData);
            
            // Store updated progress
            $this->storeProgressData($sessionId, $progressData);
            
            // Update session record
            $this->sessionService->updateSessionProgress($sessionId, [
                'percentage' => $progressData['progress_percentage'],
                'current_step' => $progressData['current_step'],
            ]);
            
            // Broadcast progress update
            $this->broadcastProgress($sessionId, $progressData);
            
            // Notify listeners
            $this->notifyProgressListeners($sessionId, $progressData);
            
            Log::debug("Progress updated", [
                'session_id' => $sessionId,
                'progress' => $progressData['progress_percentage'],
                'step' => $progressData['current_step'],
            ]);
            
            return [
                'success' => true,
                'progress_data' => $progressData,
            ];
            
        } catch (Exception $e) {
            Log::error("Failed to update progress", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete progress tracking for a session.
     *
     * @param string $sessionId
     * @param array $completionData
     * @return array
     */
    public function completeProgress(string $sessionId, array $completionData = []): array
    {
        try {
            $progressData = $this->getProgressData($sessionId);
            
            if (!$progressData) {
                return [
                    'success' => false,
                    'error' => 'Progress data not found',
                ];
            }
            
            // Mark as completed
            $progressData['progress_percentage'] = 100;
            $progressData['current_step'] = $completionData['final_step'] ?? 'Completed';
            $progressData['completed_at'] = now();
            $progressData['total_duration'] = now()->diffInSeconds($progressData['started_at']);
            
            if (isset($completionData['success'])) {
                $progressData['success'] = $completionData['success'];
            }
            
            if (isset($completionData['error'])) {
                $progressData['error'] = $completionData['error'];
            }
            
            // Store final progress state
            $this->storeProgressData($sessionId, $progressData);
            
            // Update session record
            $this->sessionService->updateSessionProgress($sessionId, [
                'percentage' => 100,
                'current_step' => $progressData['current_step'],
            ]);
            
            // Broadcast completion
            $this->broadcastProgress($sessionId, $progressData);
            
            Log::info("Progress tracking completed", [
                'session_id' => $sessionId,
                'duration' => $progressData['total_duration'],
                'success' => $progressData['success'] ?? true,
            ]);
            
            return [
                'success' => true,
                'progress_data' => $progressData,
            ];
            
        } catch (Exception $e) {
            Log::error("Failed to complete progress tracking", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get current progress for a session.
     *
     * @param string $sessionId
     * @return array|null
     */
    public function getProgress(string $sessionId): ?array
    {
        return $this->getProgressData($sessionId);
    }

    /**
     * Add a progress listener.
     *
     * @param callable $listener
     * @return void
     */
    public function addProgressListener(callable $listener): void
    {
        $this->progressListeners[] = $listener;
    }

    /**
     * Remove a progress listener.
     *
     * @param callable $listener
     * @return void
     */
    public function removeProgressListener(callable $listener): void
    {
        $key = array_search($listener, $this->progressListeners, true);
        if ($key !== false) {
            unset($this->progressListeners[$key]);
        }
    }

    /**
     * Create a progress callback for use in other services.
     *
     * @param string $sessionId
     * @return callable
     */
    public function createProgressCallback(string $sessionId): callable
    {
        return function ($updates) use ($sessionId) {
            $this->updateProgress($sessionId, $updates);
        };
    }

    /**
     * Get default progress steps.
     *
     * @return array
     */
    private function getDefaultProgressSteps(): array
    {
        return [
            'Initializing update process',
            'Checking system requirements',
            'Downloading update files',
            'Validating download integrity',
            'Creating system backup',
            'Extracting update files',
            'Updating application files',
            'Executing database migrations',
            'Clearing system caches',
            'Validating system integrity',
            'Finalizing update',
            'Update completed',
        ];
    }

    /**
     * Calculate progress percentage based on current step.
     *
     * @param array $progressData
     * @return int
     */
    private function calculateProgressPercentage(array $progressData): int
    {
        if ($progressData['total_steps'] == 0) {
            return 0;
        }
        
        $percentage = ($progressData['current_step_index'] / $progressData['total_steps']) * 100;
        return (int) round($percentage);
    }

    /**
     * Record timing for a completed step.
     *
     * @param array &$progressData
     * @param string $stepName
     * @return void
     */
    private function recordStepTiming(array &$progressData, string $stepName): void
    {
        $now = now();
        
        if (!isset($progressData['step_timings'])) {
            $progressData['step_timings'] = [];
        }
        
        $progressData['step_timings'][] = [
            'step' => $stepName,
            'completed_at' => $now,
            'duration' => isset($progressData['last_step_time']) 
                ? $now->diffInSeconds($progressData['last_step_time'])
                : null,
        ];
        
        $progressData['last_step_time'] = $now;
    }

    /**
     * Calculate estimated completion time.
     *
     * @param array $progressData
     * @return \Carbon\Carbon|null
     */
    private function calculateEstimatedCompletion(array $progressData): ?\Carbon\Carbon
    {
        if ($progressData['progress_percentage'] <= 0) {
            return null;
        }
        
        $elapsed = now()->diffInSeconds($progressData['started_at']);
        $totalEstimated = ($elapsed / $progressData['progress_percentage']) * 100;
        $remaining = $totalEstimated - $elapsed;
        
        return now()->addSeconds($remaining);
    }

    /**
     * Store progress data in cache.
     *
     * @param string $sessionId
     * @param array $progressData
     * @return void
     */
    private function storeProgressData(string $sessionId, array $progressData): void
    {
        $cacheKey = "update_progress_{$sessionId}";
        Cache::put($cacheKey, $progressData, 3600); // Store for 1 hour
    }

    /**
     * Get progress data from cache.
     *
     * @param string $sessionId
     * @return array|null
     */
    private function getProgressData(string $sessionId): ?array
    {
        $cacheKey = "update_progress_{$sessionId}";
        return Cache::get($cacheKey);
    }

    /**
     * Broadcast progress update via WebSocket.
     *
     * @param string $sessionId
     * @param array $progressData
     * @return void
     */
    private function broadcastProgress(string $sessionId, array $progressData): void
    {
        try {
            // Broadcast to WebSocket if available
            if (class_exists(\Pusher\Pusher::class)) {
                broadcast(new \Pterodactyl\Events\Updates\UpdateProgressEvent($sessionId, $progressData));
            }
        } catch (Exception $e) {
            Log::warning("Failed to broadcast progress update", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify progress listeners.
     *
     * @param string $sessionId
     * @param array $progressData
     * @return void
     */
    private function notifyProgressListeners(string $sessionId, array $progressData): void
    {
        foreach ($this->progressListeners as $listener) {
            try {
                $listener($sessionId, $progressData);
            } catch (Exception $e) {
                Log::warning("Progress listener failed", [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get progress statistics for analytics.
     *
     * @param array $sessionIds
     * @return array
     */
    public function getProgressStatistics(array $sessionIds = []): array
    {
        try {
            $statistics = [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'failed_sessions' => 0,
                'average_duration' => 0,
                'step_statistics' => [],
            ];
            
            // If no specific sessions provided, get recent sessions
            if (empty($sessionIds)) {
                $recentSessions = UpdateSession::where('created_at', '>=', now()->subDays(30))
                    ->pluck('id')
                    ->toArray();
                $sessionIds = $recentSessions;
            }
            
            $totalDuration = 0;
            $completedCount = 0;
            $stepDurations = [];
            
            foreach ($sessionIds as $sessionId) {
                $progressData = $this->getProgressData($sessionId);
                
                if (!$progressData) {
                    continue;
                }
                
                $statistics['total_sessions']++;
                
                if (isset($progressData['completed_at'])) {
                    if ($progressData['success'] ?? true) {
                        $statistics['completed_sessions']++;
                    } else {
                        $statistics['failed_sessions']++;
                    }
                }
                
                if (isset($progressData['total_duration'])) {
                    $totalDuration += $progressData['total_duration'];
                    $completedCount++;
                }
                
                // Collect step timing data
                if (isset($progressData['step_timings'])) {
                    foreach ($progressData['step_timings'] as $timing) {
                        if ($timing['duration']) {
                            if (!isset($stepDurations[$timing['step']])) {
                                $stepDurations[$timing['step']] = [];
                            }
                            $stepDurations[$timing['step']][] = $timing['duration'];
                        }
                    }
                }
            }
            
            // Calculate averages
            if ($completedCount > 0) {
                $statistics['average_duration'] = $totalDuration / $completedCount;
            }
            
            // Calculate step statistics
            foreach ($stepDurations as $step => $durations) {
                $statistics['step_statistics'][$step] = [
                    'average_duration' => array_sum($durations) / count($durations),
                    'min_duration' => min($durations),
                    'max_duration' => max($durations),
                    'sample_count' => count($durations),
                ];
            }
            
            return $statistics;
            
        } catch (Exception $e) {
            Log::error("Failed to get progress statistics", [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'failed_sessions' => 0,
                'average_duration' => 0,
                'step_statistics' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up old progress data.
     *
     * @param int $olderThanDays
     * @return int Number of entries cleaned
     */
    public function cleanupOldProgressData(int $olderThanDays = 7): int
    {
        try {
            $cutoffDate = now()->subDays($olderThanDays);
            $cleanedCount = 0;
            
            // Get old session IDs
            $oldSessions = UpdateSession::where('created_at', '<', $cutoffDate)
                ->pluck('id')
                ->toArray();
            
            foreach ($oldSessions as $sessionId) {
                $cacheKey = "update_progress_{$sessionId}";
                if (Cache::forget($cacheKey)) {
                    $cleanedCount++;
                }
            }
            
            Log::info("Cleaned up old progress data", [
                'cleaned_count' => $cleanedCount,
                'cutoff_date' => $cutoffDate,
            ]);
            
            return $cleanedCount;
            
        } catch (Exception $e) {
            Log::error("Failed to cleanup old progress data", [
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Get configuration errors specific to progress tracking.
     *
     * @return array
     */
    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if caching is available for progress tracking
        try {
            Cache::put('progress_test', true, 1);
            Cache::forget('progress_test');
        } catch (Exception $e) {
            $errors[] = 'Cache system not available for progress tracking: ' . $e->getMessage();
        }

        // Check if session service is properly configured
        if (!$this->sessionService) {
            $errors[] = 'SessionService not properly injected';
        }

        // Check WebSocket broadcasting configuration
        $broadcastConfig = config('broadcasting.default');
        if (!$broadcastConfig || $broadcastConfig === 'null') {
            $errors[] = 'Broadcasting driver not configured - real-time progress updates may not work';
        }

        // Check required directories for progress logs
        $progressLogDir = storage_path('logs/updates/progress');
        if (!is_dir($progressLogDir) && !mkdir($progressLogDir, 0755, true)) {
            $errors[] = 'Cannot create progress log directory: ' . $progressLogDir;
        }

        return $errors;
    }

    /**
     * Get the service name for identification purposes.
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return 'ProgressTrackingService';
    }
}