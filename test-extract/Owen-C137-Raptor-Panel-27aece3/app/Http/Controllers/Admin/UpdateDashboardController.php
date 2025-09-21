<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Updates\UpdateOrchestrator;
use Pterodactyl\Services\Updates\UpdateStatusService;
use Pterodactyl\Services\Updates\UpdateConfigurationService;
use Pterodactyl\Services\Updates\UpdateHistoryService;
use Pterodactyl\Services\Updates\UpdateSchedulerService;
use Pterodactyl\Services\Updates\UpdateHealthService;
use Pterodactyl\Services\Updates\UpdateSecurityService;
use Pterodactyl\Services\Updates\UpdateNotificationService;
use Pterodactyl\Models\UpdateSession;
use Pterodactyl\Models\UpdateSchedule;
use Pterodactyl\Models\UpdateConfiguration;

/**
 * Update Dashboard Controller
 * 
 * Provides comprehensive web interface for managing the auto-update system.
 * Handles dashboard views, update management, configuration, and monitoring.
 */
class UpdateDashboardController extends Controller
{
    /**
     * @var UpdateOrchestrator
     */
    private UpdateOrchestrator $orchestrator;

    /**
     * @var UpdateStatusService
     */
    private UpdateStatusService $statusService;

    /**
     * @var UpdateConfigurationService
     */
    private UpdateConfigurationService $configService;

    /**
     * @var UpdateHistoryService
     */
    private UpdateHistoryService $historyService;

    /**
     * @var UpdateSchedulerService
     */
    private UpdateSchedulerService $schedulerService;

    /**
     * @var UpdateHealthService
     */
    private UpdateHealthService $healthService;

    /**
     * @var UpdateSecurityService
     */
    private UpdateSecurityService $securityService;

    /**
     * @var UpdateNotificationService
     */
    private UpdateNotificationService $notificationService;

    /**
     * UpdateDashboardController constructor.
     */
    public function __construct(
        UpdateOrchestrator $orchestrator,
        UpdateStatusService $statusService,
        UpdateConfigurationService $configService,
        UpdateHistoryService $historyService,
        UpdateSchedulerService $schedulerService,
        UpdateHealthService $healthService,
        UpdateSecurityService $securityService,
        UpdateNotificationService $notificationService
    ) {
        $this->orchestrator = $orchestrator;
        $this->statusService = $statusService;
        $this->configService = $configService;
        $this->historyService = $historyService;
        $this->schedulerService = $schedulerService;
        $this->healthService = $healthService;
        $this->securityService = $securityService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display the main update dashboard.
     */
    public function index(): View
    {
        try {
            // Get current update status
            $currentStatus = $this->statusService->getCurrentStatus();
            $systemHealth = $this->healthService->getSystemHealth();
            $updateHistory = $this->historyService->getRecentHistory(10);
            $scheduledUpdates = $this->schedulerService->getUpcomingSchedules(5);
            $configuration = $this->configService->getCurrentConfiguration();

            // Get active update session if any
            $activeSession = null;
            if ($currentStatus['status'] === 'running') {
                $activeSession = UpdateSession::where('status', 'running')
                    ->with(['steps', 'migrations'])
                    ->first();
            }

            // Get system statistics
            $statistics = [
                'total_updates' => UpdateSession::count(),
                'successful_updates' => UpdateSession::where('status', 'completed')->count(),
                'failed_updates' => UpdateSession::where('status', 'failed')->count(),
                'rollbacks_performed' => UpdateSession::where('rolled_back', true)->count(),
                'avg_update_duration' => $this->historyService->getAverageUpdateDuration(),
                'last_successful_update' => $this->historyService->getLastSuccessfulUpdate(),
            ];

            return view('admin.updates.dashboard', compact(
                'currentStatus',
                'systemHealth',
                'updateHistory',
                'scheduledUpdates',
                'configuration',
                'activeSession',
                'statistics'
            ));
        } catch (Exception $e) {
            return view('admin.updates.dashboard', [
                'error' => 'Failed to load dashboard: ' . $e->getMessage(),
                'currentStatus' => ['status' => 'unknown'],
                'systemHealth' => ['status' => 'unknown'],
                'updateHistory' => [],
                'scheduledUpdates' => [],
                'configuration' => [],
                'activeSession' => null,
                'statistics' => [],
            ]);
        }
    }

    /**
     * Get current update status (AJAX endpoint).
     */
    public function getStatus(): JsonResponse
    {
        try {
            $status = $this->statusService->getCurrentStatus();
            return response()->json([
                'success' => true,
                'status' => $status,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health information (AJAX endpoint).
     */
    public function getHealth(): JsonResponse
    {
        try {
            $health = $this->healthService->getSystemHealth();
            return response()->json([
                'success' => true,
                'health' => $health,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get live progress updates for active session (AJAX endpoint).
     */
    public function getProgress(): JsonResponse
    {
        try {
            $activeSession = UpdateSession::where('status', 'running')->first();
            
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active update session',
                ], 404);
            }

            $progress = $this->statusService->getSessionProgress($activeSession->id);
            
            return response()->json([
                'success' => true,
                'progress' => $progress,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display update management interface.
     */
    public function manage(): View
    {
        try {
            $availableUpdates = $this->orchestrator->checkForUpdates();
            $currentVersion = $this->statusService->getCurrentVersion();
            $updateHistory = $this->historyService->getRecentHistory(20);
            $configuration = $this->configService->getCurrentConfiguration();
            $scheduledUpdates = $this->schedulerService->getAllSchedules();

            return view('admin.updates.manage', compact(
                'availableUpdates',
                'currentVersion',
                'updateHistory',
                'configuration',
                'scheduledUpdates'
            ));
        } catch (Exception $e) {
            return view('admin.updates.manage', [
                'error' => 'Failed to load management interface: ' . $e->getMessage(),
                'availableUpdates' => [],
                'currentVersion' => 'unknown',
                'updateHistory' => [],
                'configuration' => [],
                'scheduledUpdates' => [],
            ]);
        }
    }

    /**
     * Initiate a manual update.
     */
    public function initiateUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'target_version' => 'required|string',
            'update_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:update_type,scheduled|date|after:now',
            'force_update' => 'boolean',
            'skip_backup' => 'boolean',
            'skip_maintenance' => 'boolean',
        ]);

        try {
            // Security check
            $securityCheck = $this->securityService->validateUpdateRequest($request);
            if (!$securityCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'error' => $securityCheck['reason'],
                ], 403);
            }

            if ($request->update_type === 'immediate') {
                // Start immediate update
                $session = $this->orchestrator->initiateUpdate(
                    $request->target_version,
                    [
                        'force' => $request->boolean('force_update'),
                        'skip_backup' => $request->boolean('skip_backup'),
                        'skip_maintenance' => $request->boolean('skip_maintenance'),
                        'initiated_by' => auth()->id(),
                        'initiated_via' => 'web_interface',
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Update initiated successfully',
                    'session_id' => $session->id,
                ]);
            } else {
                // Schedule update
                $schedule = $this->schedulerService->scheduleUpdate(
                    $request->target_version,
                    $request->scheduled_at,
                    [
                        'force' => $request->boolean('force_update'),
                        'skip_backup' => $request->boolean('skip_backup'),
                        'skip_maintenance' => $request->boolean('skip_maintenance'),
                        'created_by' => auth()->id(),
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Update scheduled successfully',
                    'schedule_id' => $schedule->id,
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop/cancel a running update.
     */
    public function stopUpdate(): JsonResponse
    {
        try {
            $activeSession = UpdateSession::where('status', 'running')->first();
            
            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active update session to stop',
                ], 404);
            }

            $result = $this->orchestrator->stopUpdate($activeSession->id);

            return response()->json([
                'success' => true,
                'message' => 'Update stop initiated',
                'session_id' => $activeSession->id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rollback to a previous version.
     */
    public function rollback(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|exists:update_sessions,id',
            'rollback_type' => 'required|in:full,selective',
            'selective_steps' => 'array|required_if:rollback_type,selective',
        ]);

        try {
            // Security check
            $securityCheck = $this->securityService->validateRollbackRequest($request);
            if (!$securityCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'error' => $securityCheck['reason'],
                ], 403);
            }

            $rollbackSession = $this->orchestrator->initiateRollback(
                $request->session_id,
                $request->rollback_type,
                $request->selective_steps ?? [],
                [
                    'initiated_by' => auth()->id(),
                    'initiated_via' => 'web_interface',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Rollback initiated successfully',
                'rollback_session_id' => $rollbackSession->id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display update history and logs.
     */
    public function history(): View
    {
        try {
            $sessions = UpdateSession::with(['steps', 'migrations'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $statistics = [
                'total_sessions' => UpdateSession::count(),
                'successful_rate' => $this->historyService->getSuccessRate(),
                'avg_duration' => $this->historyService->getAverageUpdateDuration(),
                'most_common_failures' => $this->historyService->getMostCommonFailures(),
            ];

            return view('admin.updates.history', compact('sessions', 'statistics'));
        } catch (Exception $e) {
            return view('admin.updates.history', [
                'error' => 'Failed to load history: ' . $e->getMessage(),
                'sessions' => collect(),
                'statistics' => [],
            ]);
        }
    }

    /**
     * Display update session details.
     */
    public function sessionDetails(string $sessionId): View
    {
        try {
            $session = UpdateSession::with(['steps', 'migrations'])
                ->findOrFail($sessionId);

            $logs = $this->historyService->getSessionLogs($sessionId);
            $timeline = $this->historyService->getSessionTimeline($sessionId);

            return view('admin.updates.session-details', compact('session', 'logs', 'timeline'));
        } catch (Exception $e) {
            return view('admin.updates.session-details', [
                'error' => 'Failed to load session details: ' . $e->getMessage(),
                'session' => null,
                'logs' => [],
                'timeline' => [],
            ]);
        }
    }

    /**
     * Display update configuration interface.
     */
    public function configuration(): View
    {
        try {
            $configuration = UpdateConfiguration::first() ?? new UpdateConfiguration();
            $schedules = UpdateSchedule::where('status', 'active')->get();
            $healthChecks = $this->healthService->getHealthCheckConfiguration();
            $notifications = $this->notificationService->getNotificationConfiguration();

            return view('admin.updates.configuration', compact(
                'configuration',
                'schedules',
                'healthChecks',
                'notifications'
            ));
        } catch (Exception $e) {
            return view('admin.updates.configuration', [
                'error' => 'Failed to load configuration: ' . $e->getMessage(),
                'configuration' => new UpdateConfiguration(),
                'schedules' => collect(),
                'healthChecks' => [],
                'notifications' => [],
            ]);
        }
    }

    /**
     * Update system configuration.
     */
    public function updateConfiguration(Request $request): RedirectResponse
    {
        $request->validate([
            'auto_update_enabled' => 'boolean',
            'check_interval' => 'integer|min:1|max:168',
            'maintenance_mode' => 'boolean',
            'backup_enabled' => 'boolean',
            'notification_enabled' => 'boolean',
            'rollback_enabled' => 'boolean',
            'max_concurrent_updates' => 'integer|min:1|max:5',
            'update_timeout' => 'integer|min:300|max:7200',
        ]);

        try {
            $this->configService->updateConfiguration($request->validated());

            return redirect()->back()->with('success', 'Configuration updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update configuration: ' . $e->getMessage());
        }
    }

    /**
     * Display system health dashboard.
     */
    public function health(): View
    {
        try {
            $health = $this->healthService->getComprehensiveHealth();
            $metrics = $this->healthService->getPerformanceMetrics();
            $alerts = $this->healthService->getActiveAlerts();

            return view('admin.updates.health', compact('health', 'metrics', 'alerts'));
        } catch (Exception $e) {
            return view('admin.updates.health', [
                'error' => 'Failed to load health dashboard: ' . $e->getMessage(),
                'health' => [],
                'metrics' => [],
                'alerts' => [],
            ]);
        }
    }

    /**
     * Run health check manually.
     */
    public function runHealthCheck(): JsonResponse
    {
        try {
            $results = $this->healthService->runComprehensiveHealthCheck();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time system metrics (AJAX endpoint).
     */
    public function getMetrics(): JsonResponse
    {
        try {
            $metrics = $this->healthService->getRealTimeMetrics();

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for available updates.
     */
    public function checkUpdates(): JsonResponse
    {
        try {
            $updates = $this->orchestrator->checkForUpdates();

            return response()->json([
                'success' => true,
                'updates' => $updates,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test update system components.
     */
    public function testSystem(): JsonResponse
    {
        try {
            $testResults = $this->healthService->runSystemTests();

            return response()->json([
                'success' => true,
                'test_results' => $testResults,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}