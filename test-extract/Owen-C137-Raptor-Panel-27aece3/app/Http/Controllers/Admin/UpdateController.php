<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Updates\UpdateOrchestrator;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Validation\ValidationService;
use Pterodactyl\Models\PanelVersion;
use Pterodactyl\Models\UpdateSession;
use Pterodactyl\Models\UpdateBackup;
use Pterodactyl\Models\Updates\UpdateSetting;
use Pterodactyl\Http\Requests\Admin\Updates\UpdateSettingsFormRequest;
use Carbon\Carbon;

class UpdateController extends Controller
{
    public function __construct(
        private UpdateOrchestrator $updateOrchestrator,
        private GitHubReleaseService $gitHubService,
        private ValidationService $systemValidation
    ) {}

    /**
     * Display the update page
     */
    public function index()
    {
        return view('admin.update.index');
    }

    /**
     * Check for available updates from GitHub releases.
     */
    public function checkForUpdates(): JsonResponse
    {
        try {
            $currentVersion = PanelVersion::getCurrentVersion();
            $availableUpdates = $this->gitHubService->getAvailableUpdates($currentVersion);
            
            // Get update preferences
            $allowBeta = UpdateSetting::getValue('allow_beta_updates', false);
            
            if (!$allowBeta) {
                $availableUpdates = array_filter($availableUpdates, function ($update) {
                    return !$update['prerelease'];
                });
            }
            
            return response()->json([
                'success' => true,
                'current_version' => $currentVersion,
                'available_updates' => array_values($availableUpdates),
                'has_updates' => count($availableUpdates) > 0,
                'checked_at' => Carbon::now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Update check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to check for updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed information about a specific update version.
     */
    public function getUpdateDetails(string $version): JsonResponse
    {
        try {
            $details = $this->gitHubService->getReleaseDetails($version);
            
            if (!$details) {
                return response()->json([
                    'success' => false,
                    'error' => 'Version not found'
                ], 404);
            }
            
            // Analyze the update for potential risks
            $riskAssessment = $this->analyzeUpdateRisks($details);
            
            return response()->json([
                'success' => true,
                'version' => $version,
                'details' => $details,
                'risk_assessment' => $riskAssessment,
                'requirements_check' => $this->systemValidation->checkUpdateRequirements($details),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get update details', [
                'version' => $version,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get update details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start an update process for the specified version.
     */
    public function startUpdate(Request $request, string $version): JsonResponse
    {
        try {
            // Validate that user has confirmed the update
            $requireConfirmation = UpdateSetting::getValue('require_confirmation', true);
            
            if ($requireConfirmation && !$request->boolean('confirmed', false)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Update confirmation required'
                ], 422);
            }
            
            // Check if another update is already in progress
            $activeSession = UpdateSession::getActiveSession();
            if ($activeSession) {
                return response()->json([
                    'success' => false,
                    'error' => 'Another update is already in progress',
                    'active_session_id' => $activeSession->id
                ], 409);
            }
            
            // Validate the version exists
            $releaseDetails = $this->gitHubService->getReleaseDetails($version);
            if (!$releaseDetails) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid version specified'
                ], 404);
            }
            
            // Perform pre-update validation
            $validationResult = $this->systemValidation->validateSystemForUpdate($releaseDetails);
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => 'System validation failed',
                    'validation_errors' => $validationResult['errors']
                ], 422);
            }
            
            // Start the update process
            $sessionId = $this->updateOrchestrator->startUpdate($version, $releaseDetails);
            
            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Update process started successfully',
                'estimated_duration' => $this->estimateUpdateDuration($releaseDetails),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to start update', [
                'version' => $version,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to start update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time progress for an update session.
     */
    public function getProgress(string $sessionId): JsonResponse
    {
        try {
            $session = UpdateSession::findOrFail($sessionId);
            $progress = $this->updateOrchestrator->getUpdateProgress($sessionId);
            
            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'status' => $session->status,
                'progress' => $progress,
                'started_at' => $session->started_at?->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'estimated_completion' => $this->estimateCompletion($session, $progress),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Session not found or progress unavailable'
            ], 404);
        }
    }

    /**
     * Cancel an in-progress update if it's safe to do so.
     */
    public function cancelUpdate(string $sessionId): JsonResponse
    {
        try {
            $session = UpdateSession::findOrFail($sessionId);
            
            $result = $this->updateOrchestrator->cancelUpdate($sessionId);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Update cancelled successfully',
                    'rollback_performed' => $result['rollback_performed'] ?? false,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'can_rollback' => $result['can_rollback'] ?? false,
                ], 422);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to cancel update', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rollback a completed or failed update.
     */
    public function rollbackUpdate(string $sessionId): JsonResponse
    {
        try {
            $session = UpdateSession::findOrFail($sessionId);
            
            // Check if rollback is possible
            if (!in_array($session->status, ['completed', 'failed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cannot rollback an update that is still in progress'
                ], 422);
            }
            
            $result = $this->updateOrchestrator->rollbackUpdate($sessionId);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Update rolled back successfully',
                    'rollback_details' => $result['details'],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'details' => $result['details'] ?? null,
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to rollback update', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to rollback update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all available backups.
     */
    public function listBackups(): JsonResponse
    {
        try {
            $backups = UpdateBackup::with('session')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($backup) {
                    return [
                        'id' => $backup->id,
                        'session_id' => $backup->session_id,
                        'version' => $backup->session->target_version ?? 'Unknown',
                        'backup_path' => basename($backup->backup_path),
                        'size_bytes' => $backup->size_bytes,
                        'size_human' => $this->formatBytes($backup->size_bytes),
                        'created_at' => $backup->created_at->toISOString(),
                        'can_restore' => $backup->isRestorable(),
                        'status' => $backup->status,
                    ];
                });

            return response()->json([
                'success' => true,
                'backups' => $backups,
                'total_backups' => $backups->count(),
                'total_size' => $backups->sum('size_bytes'),
                'total_size_human' => $this->formatBytes($backups->sum('size_bytes')),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to list backups: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get update history.
     */
    public function getUpdateHistory(): JsonResponse
    {
        try {
            $history = UpdateSession::with(['fileChanges', 'migrations', 'backups'])
                ->orderBy('started_at', 'desc')
                ->take(50)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'target_version' => $session->target_version,
                        'status' => $session->status,
                        'started_at' => $session->started_at?->toISOString(),
                        'completed_at' => $session->completed_at?->toISOString(),
                        'duration_seconds' => $session->getDurationInSeconds(),
                        'files_changed' => $session->fileChanges->count(),
                        'migrations_run' => $session->migrations->count(),
                        'has_backup' => $session->backups->isNotEmpty(),
                        'progress_percentage' => $session->progress_percentage,
                        'error_message' => $session->error_message,
                    ];
                });

            return response()->json([
                'success' => true,
                'history' => $history,
                'total_updates' => UpdateSession::count(),
                'successful_updates' => UpdateSession::where('status', 'completed')->count(),
                'failed_updates' => UpdateSession::where('status', 'failed')->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get update history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current update settings.
     */
    public function getSettings(): JsonResponse
    {
        try {
            $settings = UpdateSetting::getAllSettings();
            
            return response()->json([
                'success' => true,
                'settings' => $settings,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update system settings.
     */
    public function updateSettings(UpdateSettingsFormRequest $request): JsonResponse
    {
        try {
            $settings = $request->validated();
            
            foreach ($settings as $key => $value) {
                UpdateSetting::setValue($key, $value);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'settings' => UpdateSetting::getAllSettings(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to update settings', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run system tests for dashboard health check.
     */
    public function runSystemTests(): JsonResponse
    {
        try {
            $testResults = $this->systemValidation->runSystemTests();
            
            return response()->json([
                'success' => true,
                'tests' => $testResults['tests'],
                'overall_status' => $testResults['overall_status'],
                'message' => $testResults['message'] ?? 'System tests completed successfully',
                'tested_at' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to run system tests', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to run system tests: ' . $e->getMessage(),
                'tests' => [],
                'overall_status' => 'fail',
            ], 500);
        }
    }

    /**
     * Analyze update risks based on release information.
     */
    private function analyzeUpdateRisks(array $releaseDetails): array
    {
        $risks = [];
        $riskLevel = 'low';

        // Check for breaking changes
        if (str_contains(strtolower($releaseDetails['body'] ?? ''), 'breaking')) {
            $risks[] = 'Contains breaking changes';
            $riskLevel = 'high';
        }

        // Check if it's a major version update
        $currentVersion = PanelVersion::getCurrentVersion();
        if ($this->isMajorVersionUpdate($currentVersion, $releaseDetails['tag_name'])) {
            $risks[] = 'Major version update';
            $riskLevel = ($riskLevel === 'high') ? 'high' : 'medium';
        }

        // Check if it's a prerelease
        if ($releaseDetails['prerelease']) {
            $risks[] = 'Pre-release version';
            $riskLevel = ($riskLevel === 'high') ? 'high' : 'medium';
        }

        // Check for database migrations (would need to analyze release assets)
        if (str_contains(strtolower($releaseDetails['body'] ?? ''), 'migration')) {
            $risks[] = 'Contains database migrations';
            $riskLevel = ($riskLevel === 'high') ? 'high' : 'medium';
        }

        return [
            'level' => $riskLevel,
            'risks' => $risks,
            'recommendation' => $this->getRiskRecommendation($riskLevel, $risks),
        ];
    }

    /**
     * Determine if this is a major version update.
     */
    private function isMajorVersionUpdate(string $current, string $target): bool
    {
        $currentMajor = (int) explode('.', ltrim($current, 'v'))[0];
        $targetMajor = (int) explode('.', ltrim($target, 'v'))[0];
        
        return $targetMajor > $currentMajor;
    }

    /**
     * Get risk recommendation based on level and risks.
     */
    private function getRiskRecommendation(string $level, array $risks): string
    {
        return match ($level) {
            'high' => 'High risk update. Create manual backup and test in staging environment first.',
            'medium' => 'Medium risk update. Ensure automatic backup is enabled and monitor closely.',
            'low' => 'Low risk update. Safe to proceed with automatic backup.',
            default => 'Unknown risk level.',
        };
    }

    /**
     * Estimate update duration based on release details.
     */
    private function estimateUpdateDuration(array $releaseDetails): array
    {
        $baseTime = 120; // 2 minutes base
        $additionalTime = 0;

        // Add time for large releases
        $assetCount = count($releaseDetails['assets'] ?? []);
        $additionalTime += $assetCount * 10; // 10 seconds per asset

        // Add time if migrations are likely
        if (str_contains(strtolower($releaseDetails['body'] ?? ''), 'migration')) {
            $additionalTime += 60; // 1 minute for migrations
        }

        $totalSeconds = $baseTime + $additionalTime;

        return [
            'estimated_seconds' => $totalSeconds,
            'estimated_minutes' => round($totalSeconds / 60, 1),
            'estimated_human' => $this->formatDuration($totalSeconds),
        ];
    }

    /**
     * Estimate completion time for an in-progress update.
     */
    private function estimateCompletion(UpdateSession $session, array $progress): ?string
    {
        if ($session->status !== 'in_progress' || $progress['percentage'] <= 0) {
            return null;
        }

        $elapsed = $session->started_at->diffInSeconds(now());
        $remainingPercentage = 100 - $progress['percentage'];
        $estimatedRemaining = ($elapsed / $progress['percentage']) * $remainingPercentage;

        return now()->addSeconds($estimatedRemaining)->toISOString();
    }

    /**
     * Format bytes into human-readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes >= 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Format duration in seconds to human-readable format.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = round($seconds / 60, 1);
            return $minutes . ' minutes';
        } else {
            $hours = round($seconds / 3600, 1);
            return $hours . ' hours';
        }
    }
}
