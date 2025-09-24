<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\SimpleUpdateService;

class SimpleUpdateController extends Controller
{
    private SimpleUpdateService $updateService;

    public function __construct(SimpleUpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Show update dashboard
     */
    public function index()
    {
        $updateInfo = $this->updateService->checkForUpdates();
        $versionService = app(\Pterodactyl\Services\VersionService::class);
        
        return view('admin.simple-updates.index', [
            'current_version' => $versionService->getCurrentVersion(),
            'update_info' => $updateInfo
        ]);
    }

    /**
     * Check for updates via AJAX
     */
    public function checkUpdates(): JsonResponse
    {
        $updateInfo = $this->updateService->checkForUpdates();
        
        return response()->json($updateInfo);
    }

    /**
     * Perform update via AJAX
     */
    public function performUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'version' => 'required|string'
        ]);

        // Get the download URL for the requested version
        $updateInfo = $this->updateService->checkForUpdates();
        
        if (!$updateInfo['available']) {
            return response()->json([
                'success' => false,
                'message' => 'No updates available or failed to check for updates'
            ]);
        }

        $downloadUrl = $updateInfo['download_url'];
        if (!$downloadUrl) {
            return response()->json([
                'success' => false,
                'message' => 'Download URL not available'
            ]);
        }

        $result = $this->updateService->performUpdate($downloadUrl);
        
        return response()->json($result);
    }
}