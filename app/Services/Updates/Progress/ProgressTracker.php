<?php

namespace Pterodactyl\Services\Updates\Progress;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\DatabaseOperationException;
use Pterodactyl\Models\UpdateSession;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Progress Tracker Service
 * 
 * Provides real-time progress tracking and reporting capabilities
 * for update processes with detailed step tracking.
 */
class ProgressTracker extends BaseUpdateService
{
    private array $progressData = [];
    private array $steps = [];
    private string $currentSessionId = '';
    private float $startTime;

    public function getServiceName(): string
    {
        return 'Progress Tracker';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if database connection is available
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Initialize progress tracking for a session.
     */
    public function initializeProgress(string $sessionId, array $steps): void
    {
        try {
            $this->logInfo('Initializing progress tracking', [
                'session_id' => $sessionId,
                'total_steps' => count($steps)
            ]);

            $this->currentSessionId = $sessionId;
            $this->startTime = microtime(true);
            $this->steps = $steps;

            // Initialize progress data
            $this->progressData = [
                'session_id' => $sessionId,
                'total_steps' => count($steps),
                'current_step' => 0,
                'completed_steps' => 0,
                'current_operation' => 'Initializing...',
                'percentage' => 0.0,
                'estimated_time_remaining' => null,
                'elapsed_time' => 0,
                'started_at' => Carbon::now(),
                'status' => 'running',
                'steps' => [],
                'substeps' => [],
                'files_processed' => 0,
                'total_files' => 0,
                'bytes_processed' => 0,
                'total_bytes' => 0,
                'errors' => [],
                'warnings' => []
            ];

            // Initialize step data
            foreach ($steps as $index => $step) {
                $this->progressData['steps'][$index] = [
                    'name' => $step['name'] ?? "Step " . ($index + 1),
                    'description' => $step['description'] ?? '',
                    'status' => 'pending',
                    'started_at' => null,
                    'completed_at' => null,
                    'duration' => null,
                    'progress' => 0.0,
                    'substeps' => $step['substeps'] ?? [],
                    'errors' => []
                ];
            }

            $this->saveProgress();

            $this->logDebug('Progress tracking initialized', [
                'session_id' => $sessionId,
                'steps' => array_column($steps, 'name')
            ]);

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to initialize progress tracking');
            throw new DatabaseOperationException('Failed to initialize progress tracking: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Start tracking a specific step.
     */
    public function startStep(int $stepIndex, ?string $operation = null): void
    {
        try {
            if (!isset($this->progressData['steps'][$stepIndex])) {
                throw new \InvalidArgumentException("Step index {$stepIndex} does not exist");
            }

            $this->logInfo('Starting step', [
                'session_id' => $this->currentSessionId,
                'step_index' => $stepIndex,
                'step_name' => $this->progressData['steps'][$stepIndex]['name'],
                'operation' => $operation
            ]);

            $now = Carbon::now();

            // Update step data
            $this->progressData['steps'][$stepIndex]['status'] = 'running';
            $this->progressData['steps'][$stepIndex]['started_at'] = $now;

            // Update overall progress
            $this->progressData['current_step'] = $stepIndex;
            $this->progressData['current_operation'] = $operation ?? $this->progressData['steps'][$stepIndex]['name'];
            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;

            $this->calculateProgress();
            $this->saveProgress();

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to start step');
        }
    }

    /**
     * Complete a specific step.
     */
    public function completeStep(int $stepIndex, ?string $message = null): void
    {
        try {
            if (!isset($this->progressData['steps'][$stepIndex])) {
                throw new \InvalidArgumentException("Step index {$stepIndex} does not exist");
            }

            $this->logInfo('Completing step', [
                'session_id' => $this->currentSessionId,
                'step_index' => $stepIndex,
                'step_name' => $this->progressData['steps'][$stepIndex]['name'],
                'message' => $message
            ]);

            $now = Carbon::now();

            // Update step data
            $this->progressData['steps'][$stepIndex]['status'] = 'completed';
            $this->progressData['steps'][$stepIndex]['completed_at'] = $now;
            $this->progressData['steps'][$stepIndex]['progress'] = 100.0;

            // Calculate duration
            if ($this->progressData['steps'][$stepIndex]['started_at']) {
                $startTime = Carbon::parse($this->progressData['steps'][$stepIndex]['started_at']);
                $this->progressData['steps'][$stepIndex]['duration'] = $now->diffInSeconds($startTime);
            }

            // Update overall progress
            $this->progressData['completed_steps']++;
            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;

            $this->calculateProgress();
            $this->saveProgress();

            if ($message) {
                $this->addLogEntry('info', $message, $stepIndex);
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to complete step');
        }
    }

    /**
     * Mark a step as failed.
     */
    public function failStep(int $stepIndex, string $error, ?\Exception $exception = null): void
    {
        try {
            if (!isset($this->progressData['steps'][$stepIndex])) {
                throw new \InvalidArgumentException("Step index {$stepIndex} does not exist");
            }

            $this->logError('Step failed', [
                'session_id' => $this->currentSessionId,
                'step_index' => $stepIndex,
                'step_name' => $this->progressData['steps'][$stepIndex]['name'],
                'error' => $error
            ]);

            $now = Carbon::now();

            // Update step data
            $this->progressData['steps'][$stepIndex]['status'] = 'failed';
            $this->progressData['steps'][$stepIndex]['completed_at'] = $now;

            // Calculate duration
            if ($this->progressData['steps'][$stepIndex]['started_at']) {
                $startTime = Carbon::parse($this->progressData['steps'][$stepIndex]['started_at']);
                $this->progressData['steps'][$stepIndex]['duration'] = $now->diffInSeconds($startTime);
            }

            // Add error to step and overall errors
            $errorData = [
                'message' => $error,
                'timestamp' => $now,
                'exception' => $exception ? [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ] : null
            ];

            $this->progressData['steps'][$stepIndex]['errors'][] = $errorData;
            $this->progressData['errors'][] = $errorData;

            // Update overall status
            $this->progressData['status'] = 'failed';
            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;

            $this->saveProgress();

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to record step failure');
        }
    }

    /**
     * Update progress for current step.
     */
    public function updateStepProgress(int $stepIndex, float $progress, ?string $operation = null): void
    {
        try {
            if (!isset($this->progressData['steps'][$stepIndex])) {
                throw new \InvalidArgumentException("Step index {$stepIndex} does not exist");
            }

            $progress = max(0.0, min(100.0, $progress)); // Ensure 0-100 range

            $this->progressData['steps'][$stepIndex]['progress'] = $progress;
            
            if ($operation) {
                $this->progressData['current_operation'] = $operation;
            }

            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;
            $this->calculateProgress();

            // Only save periodically to avoid excessive database writes
            if ($this->shouldSaveProgress()) {
                $this->saveProgress();
            }

        } catch (\Exception $e) {
            $this->logError('Failed to update step progress', [
                'step_index' => $stepIndex,
                'progress' => $progress,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update file processing progress.
     */
    public function updateFileProgress(int $filesProcessed, int $totalFiles, int $bytesProcessed = 0, int $totalBytes = 0): void
    {
        try {
            $this->progressData['files_processed'] = $filesProcessed;
            $this->progressData['total_files'] = $totalFiles;
            $this->progressData['bytes_processed'] = $bytesProcessed;
            $this->progressData['total_bytes'] = $totalBytes;

            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;

            if ($this->shouldSaveProgress()) {
                $this->saveProgress();
            }

        } catch (\Exception $e) {
            $this->logError('Failed to update file progress', [
                'files_processed' => $filesProcessed,
                'total_files' => $totalFiles,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add a substep to track detailed progress.
     */
    public function addSubstep(int $stepIndex, string $name, string $status = 'pending'): void
    {
        try {
            if (!isset($this->progressData['steps'][$stepIndex])) {
                throw new \InvalidArgumentException("Step index {$stepIndex} does not exist");
            }

            $this->progressData['steps'][$stepIndex]['substeps'][] = [
                'name' => $name,
                'status' => $status,
                'started_at' => $status === 'running' ? Carbon::now() : null,
                'completed_at' => $status === 'completed' ? Carbon::now() : null
            ];

            if ($this->shouldSaveProgress()) {
                $this->saveProgress();
            }

        } catch (\Exception $e) {
            $this->logError('Failed to add substep', [
                'step_index' => $stepIndex,
                'substep_name' => $name,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add a warning message.
     */
    public function addWarning(string $message, ?int $stepIndex = null): void
    {
        try {
            $warningData = [
                'message' => $message,
                'timestamp' => Carbon::now(),
                'step_index' => $stepIndex
            ];

            $this->progressData['warnings'][] = $warningData;

            if ($stepIndex !== null && isset($this->progressData['steps'][$stepIndex])) {
                if (!isset($this->progressData['steps'][$stepIndex]['warnings'])) {
                    $this->progressData['steps'][$stepIndex]['warnings'] = [];
                }
                $this->progressData['steps'][$stepIndex]['warnings'][] = $warningData;
            }

            $this->logWarning($message, ['step_index' => $stepIndex]);

        } catch (\Exception $e) {
            $this->logError('Failed to add warning', ['message' => $message, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Complete the entire progress tracking.
     */
    public function completeProgress(string $status = 'completed'): void
    {
        try {
            $this->logInfo('Completing progress tracking', [
                'session_id' => $this->currentSessionId,
                'status' => $status
            ]);

            $this->progressData['status'] = $status;
            $this->progressData['completed_at'] = Carbon::now();
            $this->progressData['elapsed_time'] = microtime(true) - $this->startTime;
            
            if ($status === 'completed') {
                $this->progressData['percentage'] = 100.0;
                $this->progressData['current_operation'] = 'Update completed successfully';
            }

            $this->saveProgress();

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to complete progress tracking');
        }
    }

    /**
     * Get current progress data.
     */
    public function getProgress(): array
    {
        return $this->progressData;
    }

    /**
     * Get progress summary.
     */
    public function getProgressSummary(): array
    {
        return [
            'session_id' => $this->currentSessionId,
            'percentage' => $this->progressData['percentage'] ?? 0.0,
            'current_operation' => $this->progressData['current_operation'] ?? 'Unknown',
            'status' => $this->progressData['status'] ?? 'unknown',
            'elapsed_time' => $this->progressData['elapsed_time'] ?? 0,
            'estimated_time_remaining' => $this->progressData['estimated_time_remaining'],
            'completed_steps' => $this->progressData['completed_steps'] ?? 0,
            'total_steps' => $this->progressData['total_steps'] ?? 0,
            'files_processed' => $this->progressData['files_processed'] ?? 0,
            'total_files' => $this->progressData['total_files'] ?? 0,
            'errors_count' => count($this->progressData['errors'] ?? []),
            'warnings_count' => count($this->progressData['warnings'] ?? [])
        ];
    }

    /**
     * Calculate overall progress percentage and estimates.
     */
    private function calculateProgress(): void
    {
        try {
            $totalSteps = $this->progressData['total_steps'];
            $completedSteps = $this->progressData['completed_steps'];
            $currentStep = $this->progressData['current_step'];

            if ($totalSteps === 0) {
                $this->progressData['percentage'] = 0.0;
                return;
            }

            // Base percentage from completed steps
            $basePercentage = ($completedSteps / $totalSteps) * 100;

            // Add partial progress from current step
            $currentStepProgress = 0.0;
            if (isset($this->progressData['steps'][$currentStep])) {
                $stepProgress = $this->progressData['steps'][$currentStep]['progress'] ?? 0.0;
                $currentStepProgress = ($stepProgress / 100) * (100 / $totalSteps);
            }

            $this->progressData['percentage'] = min(100.0, $basePercentage + $currentStepProgress);

            // Calculate estimated time remaining
            $elapsedTime = $this->progressData['elapsed_time'];
            if ($this->progressData['percentage'] > 0 && $this->progressData['percentage'] < 100) {
                $totalEstimatedTime = ($elapsedTime / ($this->progressData['percentage'] / 100));
                $this->progressData['estimated_time_remaining'] = max(0, $totalEstimatedTime - $elapsedTime);
            } else {
                $this->progressData['estimated_time_remaining'] = null;
            }

        } catch (\Exception $e) {
            $this->logError('Failed to calculate progress', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Save progress to database.
     */
    private function saveProgress(): void
    {
        try {
            if (empty($this->currentSessionId)) {
                return;
            }

            $session = UpdateSession::where('session_id', $this->currentSessionId)->first();
            if ($session) {
                $session->update(['progress' => $this->progressData]);
            }

        } catch (\Exception $e) {
            $this->logError('Failed to save progress to database', [
                'session_id' => $this->currentSessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine if progress should be saved (to avoid excessive database writes).
     */
    private function shouldSaveProgress(): bool
    {
        static $lastSave = 0;
        $now = microtime(true);
        
        // Save at most every 2 seconds
        if ($now - $lastSave >= 2.0) {
            $lastSave = $now;
            return true;
        }
        
        return false;
    }

    /**
     * Add a log entry to the progress data.
     */
    private function addLogEntry(string $level, string $message, ?int $stepIndex = null): void
    {
        if (!isset($this->progressData['logs'])) {
            $this->progressData['logs'] = [];
        }

        $logEntry = [
            'level' => $level,
            'message' => $message,
            'timestamp' => Carbon::now(),
            'step_index' => $stepIndex
        ];

        $this->progressData['logs'][] = $logEntry;

        // Keep only last 100 log entries to prevent memory issues
        if (count($this->progressData['logs']) > 100) {
            $this->progressData['logs'] = array_slice($this->progressData['logs'], -100);
        }
    }
}