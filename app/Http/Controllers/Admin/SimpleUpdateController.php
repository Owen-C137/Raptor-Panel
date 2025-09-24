<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    public function performUpdateStream(Request $request): StreamedResponse
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
            // Disable output buffering for real-time streaming
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set up streaming environment
            ignore_user_abort(true);
            set_time_limit(0);
            
            // Send initial connection test
            $this->sendSSEData(['type' => 'start', 'message' => 'Update process starting...']);
            $this->sendSSEData(['type' => 'log', 'message' => 'Establishing real-time connection...']);

            // Clear any existing output log
            $this->updateService->clearOutputLog();

            // Hook into the service to stream logs in real-time
            $this->updateService->setStreamCallback(function($logEntry) {
                $this->sendSSEData([
                    'type' => 'log',
                    'message' => $logEntry,
                    'timestamp' => date('H:i:s')
                ]);
                
                // Add small delay to prevent overwhelming the stream
                usleep(10000); // 10ms delay
            });

            try {
                $result = $this->updateService->performUpdate($downloadUrl);
                
                // Send final result
                $this->sendSSEData([
                    'type' => 'complete',
                    'success' => $result['success'],
                    'message' => $result['message'] ?? 'Update completed'
                ]);

            } catch (\Exception $e) {
                $this->sendSSEData([
                    'type' => 'error',
                    'message' => $e->getMessage()
                ]);
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control'
        ]);
    }
    
    /**
     * Send Server-Sent Event data with proper formatting
     */
    private function sendSSEData(array $data): void
    {
        echo "data: " . json_encode($data) . "\n\n";
        
        // Force immediate output
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
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