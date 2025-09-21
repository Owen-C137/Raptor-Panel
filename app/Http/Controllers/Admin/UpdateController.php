<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Updates\GitHubReleaseUpdateService;
use Pterodactyl\Services\Updates\ChangelogService;

/**
 * Simple, clean UpdateController using GitHub Releases
 */
class UpdateController extends Controller
{
    public function __construct(
        protected GitHubReleaseUpdateService $updateService,
        protected ChangelogService $changelogService
    ) {}

    /**
     * Display the update page
     */
    public function index()
    {
        return view('admin.update.index');
    }

    /**
     * Check for available updates via AJAX
     */
    public function checkForUpdates(Request $request): JsonResponse
    {
        try {
            $updateInfo = $this->updateService->checkForUpdates();

            return response()->json([
                'success' => true,
                'data' => $updateInfo
            ]);

        } catch (Exception $e) {
            Log::error('Update check failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to check for updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download and apply update
     */
    public function downloadUpdate(Request $request): JsonResponse
    {
        try {
            Log::info('Update requested by admin', [
                'user_id' => auth()->user()->id,
                'ip' => $request->ip()
            ]);

            $result = $this->updateService->downloadAndApplyUpdate();

            if ($result['success']) {
                Log::info('Update completed successfully', [
                    'new_version' => $result['new_version'] ?? 'unknown',
                    'admin_user' => auth()->user()->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'new_version' => $result['new_version'] ?? null
                ]);
            } else {
                Log::error('Update failed', [
                    'error' => $result['message'],
                    'admin_user' => auth()->user()->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Update process crashed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update process failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get changelog for current version
     */
    public function getChangelog(Request $request): JsonResponse
    {
        try {
            $currentVersion = $this->updateService->getCurrentVersion();
            $changelog = $this->changelogService->getChangelogForVersion($currentVersion);

            return response()->json([
                'success' => true,
                'data' => $changelog
            ]);

        } catch (Exception $e) {
            Log::error('Changelog fetch failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load changelog: ' . $e->getMessage(),
                'data' => [
                    'version' => $this->updateService->getCurrentVersion(),
                    'date' => null,
                    'added' => [],
                    'changed' => ['Failed to load changelog from repository'],
                    'fixed' => [],
                    'removed' => []
                ]
            ]);
        }
    }
}
