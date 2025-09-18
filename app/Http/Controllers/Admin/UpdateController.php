<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Updates\CustomUpdateService;
use Pterodactyl\Services\Updates\ChangelogService;
use Pterodactyl\Services\Updates\BackupService;

class UpdateController extends Controller
{
    private CustomUpdateService $updateService;
    private ChangelogService $changelogService;
    private BackupService $backupService;

    public function __construct(
        CustomUpdateService $updateService,
        ChangelogService $changelogService,
        BackupService $backupService
    ) {
        $this->updateService = $updateService;
        $this->changelogService = $changelogService;
        $this->backupService = $backupService;
    }

    /**
     * Check for available updates via AJAX
     */
    public function checkForUpdates(Request $request): JsonResponse
    {
        try {
            $cacheKey = 'raptor_panel_update_check';
            $cachedResult = Cache::get($cacheKey);
            
            if ($cachedResult && !$request->get('force', false)) {
                return response()->json($cachedResult);
            }

            $currentVersion = $this->updateService->getCurrentVersion();
            $latestVersion = $this->updateService->getLatestVersion();
            $updateAvailable = $this->updateService->isUpdateAvailable();
            
            $result = [
                'update_available' => $updateAvailable,
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'checked_at' => Carbon::now()->toISOString(),
            ];

            if ($updateAvailable) {
                // Get changelog for the new version
                $changelog = $this->changelogService->getFormattedChangelog($latestVersion);
                $result['changelog'] = $changelog;
                $result['features_count'] = count($changelog['features'] ?? []);
                $result['fixes_count'] = count($changelog['fixes'] ?? []);
            }

            // Cache for 1 hour
            Cache::put($cacheKey, $result, now()->addHour());

            return response()->json($result);

        } catch (Exception $e) {
            Log::error('Failed to check for updates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to check for updates. Please try again later.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get detailed update information
     */
    public function getUpdateDetails(Request $request): JsonResponse
    {
        try {
            $version = $request->get('version');
            if (!$version) {
                $version = $this->updateService->getLatestVersion();
            }

            $changelog = $this->changelogService->getFormattedChangelog($version);
            $changedFiles = $this->updateService->getChangedFiles();
            
            return response()->json([
                'version' => $version,
                'changelog' => $changelog,
                'changed_files' => $changedFiles,
                'files_count' => count($changedFiles),
                'current_version' => $this->updateService->getCurrentVersion(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get update details', [
                'version' => $version ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get update details.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Apply the update
     */
    public function applyUpdate(Request $request): JsonResponse
    {
        try {
            // Verify update is available
            if (!$this->updateService->isUpdateAvailable()) {
                return response()->json([
                    'error' => 'No updates available.',
                ], 400);
            }

            $createBackup = $request->get('backup', true);
            
            // Create backup if requested
            $backupId = null;
            if ($createBackup) {
                $backupId = $this->backupService->createBackup('pre-update-' . now()->format('Y-m-d-H-i-s'));
            }

            // Apply the update
            $result = $this->updateService->applyUpdate($backupId);

            // Clear update cache
            Cache::forget('raptor_panel_update_check');

            return response()->json([
                'success' => true,
                'message' => 'Update applied successfully!',
                'updated_version' => $result['new_version'],
                'updated_files' => $result['updated_files'],
                'backup_id' => $backupId,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to apply update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Update failed: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get update progress (for real-time updates)
     */
    public function getUpdateProgress(Request $request): JsonResponse
    {
        $progressKey = 'update_progress_' . $request->user()->id;
        $progress = Cache::get($progressKey, [
            'status' => 'idle',
            'step' => 0,
            'total_steps' => 0,
            'message' => 'Ready to update',
            'progress' => 0,
        ]);

        return response()->json($progress);
    }

    /**
     * Cancel an ongoing update
     */
    public function cancelUpdate(Request $request): JsonResponse
    {
        try {
            $progressKey = 'update_progress_' . $request->user()->id;
            Cache::forget($progressKey);

            return response()->json([
                'success' => true,
                'message' => 'Update cancelled.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel update.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): JsonResponse
    {
        try {
            $backups = $this->backupService->listBackups();
            
            return response()->json([
                'backups' => $backups,
                'count' => count($backups),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to list backups', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to list backups.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore from a backup
     */
    public function restoreBackup(Request $request): JsonResponse
    {
        $request->validate([
            'backup_id' => 'required|string',
        ]);

        try {
            $backupId = $request->get('backup_id');
            $this->backupService->restoreBackup($backupId);

            // Clear update cache after restore
            Cache::forget('raptor_panel_update_check');

            return response()->json([
                'success' => true,
                'message' => 'Backup restored successfully.',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to restore backup', [
                'backup_id' => $request->get('backup_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to restore backup: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function deleteBackup(Request $request): JsonResponse
    {
        $request->validate([
            'backup_id' => 'required|string',
        ]);

        try {
            $backupId = $request->get('backup_id');
            $this->backupService->deleteBackup($backupId);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully.',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $request->get('backup_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to delete backup: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Clean up old backups
     */
    public function cleanupBackups(Request $request): JsonResponse
    {
        try {
            $keepDays = $request->get('keep_days', 30);
            $deletedCount = $this->backupService->cleanupOldBackups($keepDays);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old backups.",
                'deleted_count' => $deletedCount,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to cleanup backups', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to cleanup backups.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get system update status
     */
    public function getSystemStatus(): JsonResponse
    {
        try {
            $status = [
                'current_version' => $this->updateService->getCurrentVersion(),
                'app_name' => config('app.name'),
                'last_check' => Cache::get('raptor_panel_update_check.checked_at'),
                'update_available' => false,
                'system_healthy' => true,
                'writable_paths' => [],
                'github_accessible' => false,
            ];

            // Check if GitHub is accessible
            try {
                $this->updateService->getLatestVersion();
                $status['github_accessible'] = true;
            } catch (Exception $e) {
                $status['github_accessible'] = false;
                $status['github_error'] = $e->getMessage();
            }

            // Check writable paths
            $updatePaths = config('app.update_settings.updatable_paths', []);
            foreach ($updatePaths as $path) {
                $fullPath = base_path($path);
                $status['writable_paths'][$path] = is_writable($fullPath);
                if (!is_writable($fullPath)) {
                    $status['system_healthy'] = false;
                }
            }

            // Check for updates if GitHub is accessible
            if ($status['github_accessible']) {
                try {
                    $status['update_available'] = $this->updateService->isUpdateAvailable();
                } catch (Exception $e) {
                    // Non-critical error
                    Log::warning('Could not check for updates in system status', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json($status);

        } catch (Exception $e) {
            Log::error('Failed to get system status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get system status.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}