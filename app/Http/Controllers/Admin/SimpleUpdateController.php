<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\SimpleUpdateService;
use Pterodactyl\Services\VersionService;

class SimpleUpdateController extends Controller
{
    private SimpleUpdateService $updateService;
    private VersionService $versionService;

    public function __construct(SimpleUpdateService $updateService, VersionService $versionService)
    {
        $this->updateService = $updateService;
        $this->versionService = $versionService;
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
        // Force refresh version cache to ensure accurate update detection
        $this->versionService->forceRefresh();
        
        // Also clear config cache to pick up any changes
        \Artisan::call('config:clear');
        
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

        $result = $this->updateService->performUpdate($request->version);
        
        return response()->json($result);
    }
}