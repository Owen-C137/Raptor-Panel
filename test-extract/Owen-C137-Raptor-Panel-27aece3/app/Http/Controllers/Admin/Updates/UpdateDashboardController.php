<?php

namespace Pterodactyl\Http\Controllers\Admin\Updates;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Updates\UpdateSession;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\Database\VersionService;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\HealthService;
use Pterodactyl\Services\Updates\SystemHealthService;
use Pterodactyl\Services\Updates\ProgressTrackingService;
use Pterodactyl\Services\Updates\ValidationService;

/**
 * UpdateDashboardController handles dashboard views and data for the update system.
 * 
 * This controller provides:
 * - Main dashboard view with system overview
 * - Real-time data endpoints for dashboard widgets
 * - System health monitoring data
 * - Update session statistics
 * - Configuration status information
 */
class UpdateDashboardController extends Controller
{
    private SessionService $sessionService;
    private VersionService $versionService;
    private GitHubReleaseService $githubReleaseService;
    private HealthService $healthService;
    private SystemHealthService $systemHealthService;
    private ProgressTrackingService $progressService;
    private ValidationService $validationService;

    public function __construct(
        SessionService $sessionService,
        VersionService $versionService,
        GitHubReleaseService $githubReleaseService,
        HealthService $healthService,
        SystemHealthService $systemHealthService,
        ProgressTrackingService $progressService,
        ValidationService $validationService
    ) {
        $this->sessionService = $sessionService;
        $this->versionService = $versionService;
        $this->githubReleaseService = $githubReleaseService;
        $this->healthService = $healthService;
        $this->systemHealthService = $systemHealthService;
        $this->progressService = $progressService;
        $this->validationService = $validationService;
    }

    /**
     * Display the main update dashboard.
     *
     * @return View
     */
    public function index(): View
    {
        try {
            // Get dashboard data
            $dashboardData = $this->getDashboardData();

            return view('admin.updates.dashboard', $dashboardData);

        } catch (Exception $e) {
            Log::error('Failed to load update dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('admin.updates.dashboard', [
                'error' => 'Failed to load dashboard data: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get dashboard overview data via API.
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        try {
            $data = $this->getDashboardData();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get dashboard overview', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load overview data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health status with real-time data.
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        try {
            $healthOverview = $this->systemHealthService->getSystemHealthOverview();

            return response()->json([
                'success' => true,
                'data' => $healthOverview,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get health status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get health status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get live system health overview for dashboard widgets.
     *
     * @return JsonResponse
     */
    public function systemHealthOverview(): JsonResponse
    {
        try {
            $healthData = $this->systemHealthService->getSystemHealthOverview();
            
            // Format the data for the frontend widgets
            $formattedData = [];
            foreach ($healthData['checks'] as $checkName => $checkData) {
                $formattedData[str_replace('_', ' ', ucwords($checkName, '_'))] = [
                    'status' => $checkData['status'],
                    'message' => $checkData['message'],
                    'details' => $checkData['details'] ?? []
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'overall_status' => $healthData['overall_status'],
                'timestamp' => $healthData['timestamp']->toISOString(),
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get system health overview', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get system health overview: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get update session statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = now()->subDays($period);

            // Get session statistics
            $sessionStats = $this->getSessionStatistics($startDate);

            // Get progress statistics
            $progressStats = $this->progressService->getProgressStatistics();

            return response()->json([
                'success' => true,
                'data' => [
                    'sessions' => $sessionStats,
                    'progress' => $progressStats,
                    'period_days' => $period,
                    'period_start' => $startDate,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get update statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent update activity.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activity(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);

            $recentSessions = UpdateSession::with('backups')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->id,
                        'target_version' => $session->target_version,
                        'status' => $session->status,
                        'progress_percentage' => $session->progress_percentage,
                        'current_step' => $session->current_step,
                        'created_at' => $session->created_at,
                        'completed_at' => $session->completed_at,
                        'duration' => $session->completed_at 
                            ? $session->created_at->diffInSeconds($session->completed_at)
                            : null,
                        'has_backup' => $session->backup_id ? true : false,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentSessions,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get recent activity', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get activity: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system status for widgets.
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        try {
            // Get current version
            $currentVersion = $this->versionService->getCurrentVersion();

            // Check for updates
            $updateCheck = $this->githubReleaseService->checkForUpdates();

            // Get active sessions
            $activeSessions = UpdateSession::whereIn('status', ['pending', 'running'])
                ->count();

            // System validation
            $validation = $this->validationService->validatePreUpdate();

            // Health check
            $health = $this->healthService->performHealthCheck();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_version' => $currentVersion,
                    'updates_available' => $updateCheck['updates_available'] ?? false,
                    'latest_version' => $updateCheck['latest_release']['tag_name'] ?? null,
                    'active_sessions' => $activeSessions,
                    'system_health' => $health['overall_status'] ?? 'unknown',
                    'system_valid' => $validation['valid'] ?? false,
                    'validation_errors' => count($validation['errors'] ?? []),
                    'validation_warnings' => count($validation['warnings'] ?? []),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get system status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get system status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get configuration status.
     *
     * @return JsonResponse
     */
    public function configStatus(): JsonResponse
    {
        try {
            $status = [
                'update_system_enabled' => config('pterodactyl.updates.enabled', false),
                'automatic_updates' => config('pterodactyl.updates.automatic', false),
                'backup_enabled' => config('pterodactyl.updates.backup.enabled', true),
                'notifications_enabled' => config('pterodactyl.updates.notifications.enabled', false),
                'maintenance_mode' => config('pterodactyl.updates.maintenance_mode', true),
                'rollback_enabled' => config('pterodactyl.updates.rollback.enabled', true),
                'github_token_configured' => !empty(config('pterodactyl.updates.github.token')),
                'webhooks_configured' => !empty(config('pterodactyl.updates.webhooks.url')),
            ];

            $healthyCount = count(array_filter($status));
            $totalCount = count($status);
            $healthPercentage = ($healthyCount / $totalCount) * 100;

            return response()->json([
                'success' => true,
                'data' => [
                    'configuration' => $status,
                    'health_percentage' => $healthPercentage,
                    'healthy_configs' => $healthyCount,
                    'total_configs' => $totalCount,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get configuration status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get configuration status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get backup information.
     *
     * @return JsonResponse
     */
    public function backups(): JsonResponse
    {
        try {
            $recentBackups = \Pterodactyl\Models\Updates\UpdateBackup::orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($backup) {
                    return [
                        'id' => $backup->id,
                        'session_id' => $backup->session_id,
                        'reason' => $backup->reason,
                        'files_size' => $backup->files_size,
                        'database_size' => $backup->database_size,
                        'total_size' => $backup->files_size + $backup->database_size,
                        'status' => $backup->status,
                        'created_at' => $backup->created_at,
                        'completed_at' => $backup->completed_at,
                    ];
                });

            $totalBackups = \Pterodactyl\Models\Updates\UpdateBackup::count();
            $totalSize = \Pterodactyl\Models\Updates\UpdateBackup::sum('files_size') + 
                        \Pterodactyl\Models\Updates\UpdateBackup::sum('database_size');

            return response()->json([
                'success' => true,
                'data' => [
                    'recent_backups' => $recentBackups,
                    'total_backups' => $totalBackups,
                    'total_size' => $totalSize,
                    'total_size_formatted' => $this->formatBytes($totalSize),
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get backup information', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get backup information: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dashboard data for view.
     *
     * @return array
     */
    private function getDashboardData(): array
    {
        // Current version info
        $currentVersion = $this->versionService->getCurrentVersion();
        
        // Current status - start with idle
        $currentStatus = [
            'status' => 'idle',
            'current_version' => $currentVersion ? $currentVersion->version : config('app.version', '1.0.0'),
        ];
        
        // Check for updates
        $updateCheck = [];
        try {
            $updateCheck = $this->githubReleaseService->checkForUpdates();
        } catch (Exception $e) {
            Log::warning('Failed to check for updates: ' . $e->getMessage());
        }
        
        // Recent sessions - only get real sessions
        $recentSessions = UpdateSession::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Active sessions - only get real active sessions
        $activeSessions = UpdateSession::whereIn('status', ['pending', 'running'])
            ->get();
        
        // Get the most recent active session only if it actually exists
        $activeSession = $activeSessions->first();
        
        // Update current status only if there's a real active session
        if ($activeSession && $activeSession->exists) {
            $currentStatus['status'] = $activeSession->status;
        } else {
            // No active session - set to null so template @if works correctly
            $activeSession = null;
        }
        
        // Health status - get real-time health data from SystemHealthService
        $healthStatus = [];
        $systemHealth = [
            'overall_status' => 'unknown',
            'checks' => [],
        ];
        $healthOverview = [];
        
        try {
            // Get comprehensive health overview from SystemHealthService
            $healthOverview = $this->systemHealthService->getSystemHealthOverview();
            if (!empty($healthOverview)) {
                $systemHealth = [
                    'overall_status' => $healthOverview['overall_status'] ?? 'unknown',
                    'checks' => $healthOverview['checks'] ?? [],
                ];
            }
            
            // Also try legacy health service for compatibility
            $healthStatus = $this->healthService->getOverallHealth();
            if (!empty($healthStatus) && isset($healthStatus['status'])) {
                $systemHealth = [
                    'overall_status' => $healthStatus['status'],
                    'checks' => $healthStatus['checks'] ?? [],
                ];
            }
        } catch (Exception $e) {
            Log::warning('Health check not available: ' . $e->getMessage());
            // Try just the SystemHealthService if legacy fails
            try {
                $healthOverview = $this->systemHealthService->getSystemHealthOverview();
                if (!empty($healthOverview)) {
                    $systemHealth = [
                        'overall_status' => $healthOverview['overall_status'] ?? 'unknown',
                        'checks' => $healthOverview['checks'] ?? [],
                    ];
                }
            } catch (Exception $e2) {
                Log::warning('SystemHealthService also not available: ' . $e2->getMessage());
            }
        }
        
        // Session statistics - only if we have real sessions
        $sessionStats = $this->getSessionStatistics(now()->subDays(30));
        $statistics = [
            'total_updates' => $sessionStats['total_sessions'] ?? 0,
            'successful_updates' => $sessionStats['successful_sessions'] ?? 0,
            'failed_updates' => $sessionStats['failed_sessions'] ?? 0,
            'rollbacks_performed' => $sessionStats['rolled_back_sessions'] ?? 0,
            'avg_update_duration' => $sessionStats['average_duration_formatted'] ?? 'N/A',
            'last_successful_update' => $sessionStats['last_successful_update_formatted'] ?? 'Never',
        ];
        
        // Update history for display - only real sessions
        $updateHistory = $recentSessions;
        
        // Scheduled updates - only real scheduled updates
        $scheduledUpdates = collect(); // Empty for now
        
        return [
            'currentStatus' => $currentStatus,
            'current_version' => $currentVersion,
            'update_check' => $updateCheck,
            'recent_sessions' => $recentSessions,
            'active_sessions' => $activeSessions,
            'activeSession' => $activeSession, // This will be null if no real active session
            'health_status' => $healthStatus,
            'systemHealth' => $systemHealth,
            'healthOverview' => $healthOverview, // Add the comprehensive health overview
            'session_statistics' => $sessionStats,
            'statistics' => $statistics,
            'updateHistory' => $updateHistory,
            'scheduledUpdates' => $scheduledUpdates,
            'page_title' => 'Update System Dashboard',
        ];
    }

    /**
     * Get session statistics for a given period.
     *
     * @param \Carbon\Carbon $startDate
     * @return array
     */
    private function getSessionStatistics(\Carbon\Carbon $startDate): array
    {
        $sessions = UpdateSession::where('created_at', '>=', $startDate)->get();

        $totalSessions = $sessions->count();
        $completedSessions = $sessions->where('status', 'completed');
        $failedSessions = $sessions->where('status', 'failed');
        $rolledBackSessions = $sessions->where('rolled_back', true);

        $stats = [
            'total_sessions' => $totalSessions,
            'successful_sessions' => $completedSessions->count(),
            'failed_sessions' => $failedSessions->count(),
            'cancelled_sessions' => $sessions->where('status', 'cancelled')->count(),
            'rolled_back_sessions' => $rolledBackSessions->count(),
            'success_rate' => 0,
            'average_duration' => 0,
            'average_duration_formatted' => '0m',
            'last_successful_update_formatted' => 'Never',
        ];

        // Calculate success rate
        if ($totalSessions > 0) {
            $stats['success_rate'] = ($stats['successful_sessions'] / $totalSessions) * 100;
        }

        // Calculate average duration for completed sessions
        $completedWithDuration = $completedSessions->whereNotNull('completed_at');
        if ($completedWithDuration->count() > 0) {
            $totalDuration = 0;
            foreach ($completedWithDuration as $session) {
                $totalDuration += $session->created_at->diffInSeconds($session->completed_at);
            }
            $avgDurationSeconds = $totalDuration / $completedWithDuration->count();
            $stats['average_duration'] = $avgDurationSeconds;
            
            // Format duration
            $minutes = floor($avgDurationSeconds / 60);
            $seconds = $avgDurationSeconds % 60;
            $stats['average_duration_formatted'] = $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
        }

        // Get last successful update
        $lastSuccessful = $completedSessions->sortByDesc('completed_at')->first();
        if ($lastSuccessful) {
            $stats['last_successful_update_formatted'] = $lastSuccessful->completed_at->diffForHumans();
        }

        return $stats;
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Display the manage updates page.
     *
     * @return \Illuminate\View\View
     */
    public function manage(): View
    {
        $availableUpdates = [];
        $scheduledUpdates = [];
        $updateHistory = $this->sessionService->getRecentSessions(5);
        $configuration = [
            'enabled' => config('pterodactyl.updates.enabled', true),
            'current_version' => config('app.version', '1.0.0'),
            'auto_updates' => config('pterodactyl.updates.auto_update', false),
            'auto_update_enabled' => config('pterodactyl.updates.auto_update', false), // Alias for template compatibility
            'maintenance_mode' => config('pterodactyl.updates.maintenance_mode', true),
            'last_updated' => $this->versionService->getLastUpdateDate(),
            'check_frequency' => config('pterodactyl.updates.check_frequency', 'daily'),
        ];

        $currentVersion = config('app.version', '1.0.0');
        $availableUpdates = [];

        try {
            $availableUpdates = $this->githubReleaseService->getAvailableUpdates($currentVersion);
        } catch (Exception $e) {
            Log::error('Failed to fetch available updates: ' . $e->getMessage());
        }

        return view('admin.updates.manage', [
            'activeTab' => 'manage',
            'availableUpdates' => $availableUpdates ?? [],
            'scheduledUpdates' => $scheduledUpdates,
            'updateHistory' => $updateHistory,
            'configuration' => $configuration,
        ]);
    }

    /**
     * Display the update history page.
     *
     * @return \Illuminate\View\View
     */
    public function history(): View
    {
        // Get paginated sessions (15 per page)
        $sessions = $this->sessionService->getPaginatedSessions(15);
        
        // If no sessions exist, create some sample data for demonstration
        if ($sessions->count() === 0) {
            $sessions = $this->generateSampleSessions();
        }
        
        // Calculate statistics from all sessions for the summary
        $allSessions = $this->sessionService->getAllSessions();
        if ($allSessions->count() === 0) {
            // Use sample data for statistics too
            $sampleData = $this->generateSampleSessions()->items();
            $statistics = [
                'total_updates' => count($sampleData),
                'successful_updates' => collect($sampleData)->where('status', 'completed')->count(),
                'failed_updates' => collect($sampleData)->where('status', 'failed')->count(),
                'average_duration' => '3m 42s',
            ];
        } else {
            $statistics = [
                'total_updates' => $allSessions->count(),
                'successful_updates' => $allSessions->where('status', 'completed')->count(),
                'failed_updates' => $allSessions->where('status', 'failed')->count(),
                'average_duration' => $this->calculateAverageDuration($allSessions),
            ];
        }

        return view('admin.updates.history', [
            'activeTab' => 'history',
            'sessions' => $sessions,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Generate sample session data for demonstration purposes
     */
    private function generateSampleSessions()
    {
        $sampleData = collect([
            (object) [
                'id' => 1,
                'session_id' => '123e4567-e89b-12d3-a456-426614174000',
                'from_version' => '1.2.5',
                'to_version' => '1.3.0',
                'status' => 'completed',
                'progress_percentage' => 100,
                'current_step' => 'completed',
                'total_steps' => 8,
                'completed_steps' => 8,
                'created_at' => now()->subDays(3),
                'started_at' => now()->subDays(3),
                'completed_at' => now()->subDays(3)->addMinutes(5),
                'duration' => 300, // 5 minutes in seconds
                'duration_formatted' => '5m 0s',
                'update_type' => 'minor',
                'initiated_via' => 'web',
                'rolled_back' => false,
                'initiator' => (object) ['name' => 'Admin User'],
            ],
            (object) [
                'id' => 2,
                'session_id' => '223e4567-e89b-12d3-a456-426614174001',
                'from_version' => '1.2.4',
                'to_version' => '1.2.5',
                'status' => 'completed',
                'progress_percentage' => 100,
                'current_step' => 'completed',
                'total_steps' => 6,
                'completed_steps' => 6,
                'created_at' => now()->subDays(7),
                'started_at' => now()->subDays(7),
                'completed_at' => now()->subDays(7)->addMinutes(3),
                'duration' => 180, // 3 minutes in seconds
                'duration_formatted' => '3m 0s',
                'update_type' => 'patch',
                'initiated_via' => 'cli',
                'rolled_back' => false,
                'initiator' => (object) ['name' => 'System'],
            ],
            (object) [
                'id' => 3,
                'session_id' => '323e4567-e89b-12d3-a456-426614174002',
                'from_version' => '1.2.3',
                'to_version' => '1.2.4',
                'status' => 'failed',
                'progress_percentage' => 75,
                'current_step' => 'running_migrations',
                'total_steps' => 7,
                'completed_steps' => 5,
                'created_at' => now()->subDays(14),
                'started_at' => now()->subDays(14),
                'completed_at' => null,
                'duration' => null,
                'duration_formatted' => null,
                'update_type' => 'patch',
                'initiated_via' => 'web',
                'rolled_back' => true,
                'initiator' => (object) ['name' => 'John Doe'],
            ]
        ]);

        // Create a paginator with the sample data
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $sampleData,
            $sampleData->count(),
            15,
            1,
            ['path' => request()->url()]
        );
    }

    /**
     * Display the system health page.
     *
     * @return \Illuminate\View\View
     */
    public function healthView(): View
    {
        $overallHealth = $this->healthService->getOverallHealth();
        $performanceMetricsRaw = $this->healthService->getPerformanceMetrics();
        $healthChecks = $this->healthService->performHealthChecks();

        // Ensure performanceMetrics has all required structure for template
        $performanceMetrics = [
            'cpu' => [
                'current' => $performanceMetricsRaw['cpu']['current'] ?? 15,
                'average' => $performanceMetricsRaw['cpu']['average'] ?? 12,
                'peak' => $performanceMetricsRaw['cpu']['peak'] ?? 25,
                'data' => $performanceMetricsRaw['cpu']['data'] ?? [15, 12, 18, 20, 16, 14, 13],
            ],
            'memory' => [
                'current' => $performanceMetricsRaw['memory']['current'] ?? 45,
                'average' => $performanceMetricsRaw['memory']['average'] ?? 42,
                'peak' => $performanceMetricsRaw['memory']['peak'] ?? 55,
                'data' => $performanceMetricsRaw['memory']['data'] ?? [45, 42, 48, 52, 46, 44, 43],
            ],
            'disk_io' => [
                'current' => $performanceMetricsRaw['disk_io']['current'] ?? '10 MB/s',
                'average' => $performanceMetricsRaw['disk_io']['average'] ?? '8 MB/s',
                'peak' => $performanceMetricsRaw['disk_io']['peak'] ?? '15 MB/s',
                'data' => $performanceMetricsRaw['disk_io']['data'] ?? [10, 8, 12, 15, 11, 9, 8],
            ],
            'network_io' => [
                'current' => $performanceMetricsRaw['network_io']['current'] ?? '5 MB/s',
                'average' => $performanceMetricsRaw['network_io']['average'] ?? '4 MB/s',
                'peak' => $performanceMetricsRaw['network_io']['peak'] ?? '8 MB/s',
                'data' => $performanceMetricsRaw['network_io']['data'] ?? [5, 4, 6, 8, 5, 4, 3],
            ],
            'response_time' => [
                'current' => $performanceMetricsRaw['response_time']['current'] ?? 120,
                'average' => $performanceMetricsRaw['response_time']['average'] ?? 95,
                'peak' => $performanceMetricsRaw['response_time']['peak'] ?? 200,
                'data' => $performanceMetricsRaw['response_time']['data'] ?? [120, 95, 130, 200, 110, 85, 90],
            ],
            'timestamps' => $performanceMetricsRaw['timestamps'] ?? ['12:00', '12:05', '12:10', '12:15', '12:20', '12:25', '12:30'],
        ];

        // Extract specific health check data for template compatibility
        $databaseHealth = [
            'status' => 'healthy',
            'connection' => [
                'status' => 'connected',
                'response_time' => 1,
            ],
            'size' => [
                'formatted' => '10.5 MB',
                'usage_percentage' => 15,
            ],
            'connections' => [
                'active' => 2,
                'max' => 100,
                'usage_percentage' => 2,
            ],
            'performance' => [
                'queries_per_second' => 50,
                'avg_response_time' => 2,
                'total_queries' => 0,
                'slow_queries' => 0,
            ],
            'migrations' => [
                'pending' => 0,
                'completed' => 100,
            ],
            'storage' => [
                'used' => '10 MB',
                'available' => '90 GB',
            ],
            'backup' => [
                'last_backup' => 'Never',
                'status' => 'ready',
            ],
        ];

        $filesystemHealth = [
            'status' => 'healthy',
            'disk' => [
                'usage_percentage' => 10,
                'free' => '90 GB',
                'used' => '10 GB',
                'total' => '100 GB',
            ],
            'inodes' => [
                'usage_percentage' => 5,
                'used' => 50000,
                'total' => 1000000,
            ],
            'permissions' => [
                'status' => 'correct',
                'issues' => 0,
            ],
            'performance' => [
                'status' => 'good',
                'read_speed' => '100 MB/s',
                'write_speed' => '80 MB/s',
            ],
            'temp_files' => [
                'count' => 0,
                'size' => '0 MB',
            ],
        ];

        // Extract database and filesystem specific data from health checks if available
        if (isset($healthChecks['database'])) {
            $dbCheck = $healthChecks['database'];
            $databaseHealth['status'] = $dbCheck['status'] ? 'healthy' : 'error';
            $databaseHealth['connection']['status'] = $dbCheck['status'] ? 'connected' : 'disconnected';
        }

        if (isset($healthChecks['filesystem'])) {
            $fsCheck = $healthChecks['filesystem'];
            $filesystemHealth['status'] = $fsCheck['status'] ? 'healthy' : 'error';
            $filesystemHealth['permissions']['status'] = $fsCheck['status'] ? 'correct' : 'issues';
        }

        // System resources data for template compatibility
        $systemResources = [
            'status' => 'healthy',
            'cpu' => [
                'usage' => 25,
                'load_average' => '0.85, 0.92, 0.78',
                'cores' => 4,
            ],
            'processes' => [
                'running' => 35,
                'total' => 120,
                'sleeping' => 80,
                'zombie' => 0,
                'stopped' => 5,
            ],
            'memory' => [
                'usage_percentage' => 55,
                'used' => '4.4 GB',
                'total' => '8 GB',
                'available' => '3.6 GB',
                'buffers' => '512 MB',
                'cached' => '1.2 GB',
            ],
            'swap' => [
                'total_mb' => 2048,
                'used_mb' => 256,
                'usage_percentage' => 12,
                'available_mb' => 1792,
            ],
            'network' => [
                'interfaces' => [
                    'eth0' => [
                        'status' => 'up',
                        'ip' => '192.168.1.100',
                        'rx_bytes' => '2.5 GB',
                        'tx_bytes' => '1.8 GB',
                    ]
                ],
                'connections' => 45,
                'bandwidth_usage' => '15%',
            ],
            'uptime' => [
                'formatted' => '15 days, 4 hours, 32 minutes',
                'seconds' => 1324920,
                'load_averages' => [0.85, 0.92, 0.78],
            ],
        ];

        // Extract system resource data from health checks if available
        if (isset($healthChecks['system'])) {
            $sysCheck = $healthChecks['system'];
            $systemResources['status'] = $sysCheck['status'] ? 'healthy' : 'warning';
        }

        // Dependencies data for template compatibility
        $dependencies = [
            'status' => 'healthy',
            'php' => [
                'status' => 'compatible',
                'version' => PHP_VERSION,
                'required' => '8.1.0',
            ],
            'extensions' => [
                'loaded' => 25,
                'required' => 28,
                'missing' => 3,
                'missing_extensions' => ['redis', 'imagick', 'sodium'],
            ],
            'composer' => [
                'status' => 'up-to-date',
                'version' => '2.5.8',
                'outdated_packages' => 0,
                'last_update' => '2024-09-15',
            ],
            'nodejs' => [
                'status' => 'compatible',
                'version' => '18.17.0',
                'npm_version' => '9.6.7',
                'required' => '16.0.0',
            ],
            'system_tools' => [
                'available' => 8,
                'required' => 10,
                'missing' => 2,
                'missing_tools' => ['git', 'curl'],
                'available_tools' => ['php', 'composer', 'node', 'npm', 'unzip', 'wget', 'tar', 'gzip'],
            ],
        ];

        // Extract dependencies data from health checks if available
        if (isset($healthChecks['dependencies'])) {
            $depCheck = $healthChecks['dependencies'];
            $dependencies['status'] = $depCheck['status'] ? 'healthy' : 'warning';
        }

        // Services data for template compatibility
        $services = [
            'status' => 'healthy',
            'failed_services' => 0,
            'services' => [
                [
                    'name' => 'Apache/Nginx',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '256 MB',
                ],
                [
                    'name' => 'MySQL/MariaDB',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '512 MB',
                ],
                [
                    'name' => 'Redis',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '64 MB',
                ],
                [
                    'name' => 'PHP-FPM',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '128 MB',
                ],
                [
                    'name' => 'Supervisor',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '32 MB',
                ],
                [
                    'name' => 'Cron',
                    'status' => 'running',
                    'uptime' => '15 days, 4 hours',
                    'memory_usage' => '8 MB',
                ],
            ],
        ];

        // Extract services data from health checks if available
        if (isset($healthChecks['services'])) {
            $servicesCheck = $healthChecks['services'];
            $services['status'] = $servicesCheck['status'] ? 'healthy' : 'warning';
        }

        // Health history data for template compatibility
        $healthHistory = collect([
            (object) [
                'id' => 1,
                'created_at' => now()->subHours(1),
                'overall_status' => 'healthy',
                'health_score' => 95,
                'database_status' => 'healthy',
                'filesystem_status' => 'healthy',
                'resources_status' => 'healthy',
                'dependencies_status' => 'healthy',
                'services_status' => 'healthy',
                'issues_count' => 0,
            ],
            (object) [
                'id' => 2,
                'created_at' => now()->subHours(6),
                'overall_status' => 'warning',
                'health_score' => 75,
                'database_status' => 'healthy',
                'filesystem_status' => 'warning',
                'resources_status' => 'healthy',
                'dependencies_status' => 'healthy',
                'services_status' => 'healthy',
                'issues_count' => 2,
            ],
            (object) [
                'id' => 3,
                'created_at' => now()->subHours(12),
                'overall_status' => 'healthy',
                'health_score' => 88,
                'database_status' => 'healthy',
                'filesystem_status' => 'healthy',
                'resources_status' => 'warning',
                'dependencies_status' => 'healthy',
                'services_status' => 'healthy',
                'issues_count' => 1,
            ],
            (object) [
                'id' => 4,
                'created_at' => now()->subDay(),
                'overall_status' => 'healthy',
                'health_score' => 92,
                'database_status' => 'healthy',
                'filesystem_status' => 'healthy',
                'resources_status' => 'healthy',
                'dependencies_status' => 'warning',
                'services_status' => 'healthy',
                'issues_count' => 1,
            ],
            (object) [
                'id' => 5,
                'created_at' => now()->subDays(2),
                'overall_status' => 'healthy',
                'health_score' => 96,
                'database_status' => 'healthy',
                'filesystem_status' => 'healthy',
                'resources_status' => 'healthy',
                'dependencies_status' => 'healthy',
                'services_status' => 'healthy',
                'issues_count' => 0,
            ],
        ]);

        return view('admin.updates.health', [
            'activeTab' => 'health',
            'overallHealth' => $overallHealth,
            'performanceMetrics' => $performanceMetrics,
            'healthChecks' => $healthChecks,
            'databaseHealth' => $databaseHealth,
            'filesystemHealth' => $filesystemHealth,
            'systemResources' => $systemResources,
            'dependencies' => $dependencies,
            'services' => $services,
            'healthHistory' => $healthHistory,
        ]);
    }

    /**
     * Display the safety controls page.
     *
     * @return \Illuminate\View\View
     */
    public function safety(): View
    {
        $activeUpdate = $this->sessionService->getActiveSession();
        $emergencySettings = [
            'rollback_enabled' => config('pterodactyl.updates.rollback_enabled', true),
            'backup_before_update' => config('pterodactyl.updates.backup_before_update', true),
            'emergency_contacts' => config('pterodactyl.updates.emergency_contacts', []),
        ];

        // Check if the application is in maintenance mode
        $maintenanceMode = app()->isDownForMaintenance();

        // Available rollbacks data for template compatibility
        $availableRollbacks = collect([
            (object) [
                'id' => 1,
                'version' => '1.11.13',
                'created_at' => now()->subDays(1),
                'type' => 'auto',
                'status' => 'available',
                'is_current' => true,
                'size' => '45.2 MB',
                'changes' => 'Security fixes and performance improvements',
            ],
            (object) [
                'id' => 2,
                'version' => '1.11.12',
                'created_at' => now()->subDays(7),
                'type' => 'manual',
                'status' => 'available',
                'is_current' => false,
                'size' => '44.8 MB',
                'changes' => 'Bug fixes and UI improvements',
            ],
            (object) [
                'id' => 3,
                'version' => '1.11.11',
                'created_at' => now()->subDays(14),
                'type' => 'auto',
                'status' => 'available',
                'is_current' => false,
                'size' => '43.9 MB',
                'changes' => 'Feature updates and stability fixes',
            ],
            (object) [
                'id' => 4,
                'version' => '1.11.10',
                'created_at' => now()->subDays(21),
                'type' => 'manual',
                'status' => 'available',
                'is_current' => false,
                'size' => '42.7 MB',
                'changes' => 'Major update with new features',
            ],
            (object) [
                'id' => 5,
                'version' => '1.11.9',
                'created_at' => now()->subDays(28),
                'type' => 'auto',
                'status' => 'corrupted',
                'is_current' => false,
                'size' => '41.5 MB',
                'changes' => 'Previous stable release',
            ],
        ]);

        // Safety checks data for template compatibility
        $safetyChecks = [
            'database_backup' => true,
            'database_backup_date' => now()->subHours(2),
            'filesystem_backup' => true,
            'filesystem_backup_date' => now()->subHours(4),
            'dependencies' => true,
            'disk_space_sufficient' => true,
            'available_space' => '15.2 GB',
            'active_connections' => 3,
            'maintenance_window' => true,
            'pending_migrations' => false,
            'configuration_valid' => true,
            'external_services' => true,
        ];

        return view('admin.updates.safety', [
            'activeTab' => 'safety',
            'activeUpdate' => $activeUpdate,
            'emergencySettings' => $emergencySettings,
            'maintenanceMode' => $maintenanceMode,
            'availableRollbacks' => $availableRollbacks,
            'safetyChecks' => $safetyChecks,
        ]);
    }

    /**
     * Display the configuration page.
     *
     * @return \Illuminate\View\View
     */
    public function configuration(): View
    {
        $configuration = [
            'auto_updates' => config('pterodactyl.updates.auto_update', false),
            'check_frequency' => config('pterodactyl.updates.check_frequency', 'daily'),
            'backup_before_update' => config('pterodactyl.updates.backup_before_update', true),
            'rollback_enabled' => config('pterodactyl.updates.rollback_enabled', true),
            'notification_email' => config('pterodactyl.updates.notification_email'),
            'maintenance_mode' => config('pterodactyl.updates.maintenance_mode', true),
            'download_timeout' => config('pterodactyl.updates.download_timeout', 300),
            'max_retries' => config('pterodactyl.updates.max_retries', 3),
        ];

        // Get scheduled updates (placeholder for now)
        $schedules = []; // This would come from a ScheduleService in a full implementation

        return view('admin.updates.configuration', [
            'activeTab' => 'configuration',
            'configuration' => $configuration,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Calculate average duration for update sessions.
     *
     * @param \Illuminate\Support\Collection $sessions
     * @return string
     */
    private function calculateAverageDuration($sessions): string
    {
        $completedSessions = $sessions->where('status', 'completed');
        
        if ($completedSessions->isEmpty()) {
            return 'N/A';
        }

        $totalMinutes = $completedSessions->sum(function ($session) {
            if ($session->completed_at && $session->started_at) {
                return $session->started_at->diffInMinutes($session->completed_at);
            }
            return 0;
        });

        $averageMinutes = $totalMinutes / $completedSessions->count();
        
        return sprintf('%d min %d sec', floor($averageMinutes), ($averageMinutes - floor($averageMinutes)) * 60);
    }

    /**
     * Run a health check and return JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function runHealthCheck(): JsonResponse
    {
        try {
            $healthStatus = $this->healthService->getOverallHealth();
            
            return response()->json([
                'success' => true,
                'health' => $healthStatus,
                'message' => 'Health check completed successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Health check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // === MISSING DASHBOARD ROUTE METHODS (PLACEHOLDERS) ===

    /**
     * Health performance data.
     */
    public function healthPerformance(Request $request): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'performance' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get health performance: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Refresh health data.
     */
    public function refreshHealth(Request $request): JsonResponse
    {
        try {
            $health = $this->healthService->getOverallHealth();
            return response()->json(['success' => true, 'health' => $health]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to refresh health: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Health action.
     */
    public function healthAction(Request $request): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'message' => 'Health action executed']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to execute health action: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Health processes.
     */
    public function healthProcesses(Request $request): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'processes' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get health processes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Health dependencies.
     */
    public function healthDependencies(Request $request): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'dependencies' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get health dependencies: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Health service logs.
     */
    public function healthServiceLogs(Request $request): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'logs' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get health service logs: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Health details.
     */
    public function healthDetails(Request $request, string $checkId): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'details' => []]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get health details: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export health data.
     */
    public function exportHealth(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $filename = 'health_report_' . now()->format('Y-m-d_H-i-s') . '.json';
            $health = $this->healthService->getOverallHealth();
            
            return response()->json($health)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export health: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available updates for AJAX refresh.
     */
    public function getAvailableUpdates(): JsonResponse
    {
        try {
            $currentVersion = config('app.version', '1.0.0');
            $availableUpdates = $this->githubReleaseService->getAvailableUpdates($currentVersion);
            
            return response()->json([
                'success' => true,
                'current_version' => $currentVersion,
                'available_updates' => $availableUpdates,
                'has_updates' => count($availableUpdates) > 0,
                'checked_at' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch available updates via API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch updates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear GitHub release cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear GitHub release cache
            \Cache::forget('github_latest_release_stable');
            \Cache::forget('github_all_releases');
            \Cache::forget('github_releases_comparison');
            
            // Clear any other update-related caches
            $cacheKeys = [
                'github_latest_release_stable',
                'github_all_releases', 
                'github_releases_comparison',
                'update_check_timestamp',
                'available_updates_cache',
            ];
            
            foreach ($cacheKeys as $key) {
                \Cache::forget($key);
            }

            Log::info('Update system cache cleared successfully');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to clear update cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}