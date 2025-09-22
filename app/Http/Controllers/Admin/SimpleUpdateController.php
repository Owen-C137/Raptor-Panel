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
        
        return view('admin.simple-updates.index', [
            'current_version' => config('app.version'),
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

        $result = $this->updateService->performUpdate($request->version);
        
        return response()->json($result);
    }
}