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
     * Perform update via streaming SSE
     */
    public function performUpdateStream(Request $request): Response
    {
        // Increase timeout for long-running update process
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        
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

        // Set up Server-Sent Events response
        return response()->stream(function () use ($downloadUrl) {
            // Configure SSE headers
            echo "data: " . json_encode(['type' => 'start', 'message' => 'Update process starting...']) . "\n\n";
            flush();

            // Clear any existing output log
            $this->updateService->clearOutputLog();

            // Hook into the service to stream logs in real-time
            $this->updateService->setStreamCallback(function($logEntry) {
                echo "data: " . json_encode([
                    'type' => 'log',
                    'message' => $logEntry,
                    'timestamp' => date('H:i:s')
                ]) . "\n\n";
                flush();
            });

            try {
                $result = $this->updateService->performUpdate($downloadUrl);
                
                // Send final result
                echo "data: " . json_encode([
                    'type' => 'complete',
                    'success' => $result['success'],
                    'message' => $result['message'] ?? 'Update completed'
                ]) . "\n\n";
                flush();

            } catch (\Exception $e) {
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => $e->getMessage()
                ]) . "\n\n";
                flush();
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no' // Disable nginx buffering
        ]);
    }

    /**
     * Perform update via AJAX (fallback method)
     */
    public function performUpdate(Request $request): JsonResponse
    {
        // Increase timeout for long-running update process
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        
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

        // Clear any existing output log
        $this->updateService->clearOutputLog();
        
        $result = $this->updateService->performUpdate($downloadUrl);
        
        // Get the terminal output from service
        $terminalOutput = $this->updateService->getOutputLog();
        
        // Add terminal output to result
        $result['output'] = $terminalOutput;
        
        return response()->json($result);
    }
}