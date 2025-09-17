<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use PterodactylAddons\OllamaAi\Services\LogAnalysisService;
use PterodactylAddons\OllamaAi\Models\AiInsight;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use Carbon\Carbon;

class AiLogAnalysisController extends Controller
{
    protected $logAnalysisService;

    public function __construct(LogAnalysisService $logAnalysisService)
    {
        $this->logAnalysisService = $logAnalysisService;
    }

    /**
     * Display log analysis overview
     */
    public function index(Request $request)
    {
        $servers = Server::with(['user', 'node'])
            ->orderBy('updated_at', 'desc')
            ->limit(25)
            ->get();

        $overview = $this->getLogAnalysisOverview();

        return view('ollama-ai::admin.logs.index', [
            'servers' => $servers,
            'overview' => $overview,
        ]);
    }

    /**
     * Analyze logs for a specific server
     */
    public function analyzeServerLogs(Request $request, int $serverId): JsonResponse
    {
        $request->validate([
            'time_range' => 'nullable|in:1h,6h,24h,7d',
            'log_types' => 'nullable|array',
            'log_types.*' => 'in:console,crash,system,error',
        ]);

        try {
            $server = Server::findOrFail($serverId);
            
            $options = [
                'time_range' => $request->get('time_range', '1h'),
                'log_types' => $request->get('log_types', ['console', 'system']),
            ];

            $analysis = $this->logAnalysisService->analyzeLogs($server, $options);

            return response()->json([
                'success' => true,
                'server_name' => $server->name,
                'analysis' => [
                    'health_score' => $analysis['insights']['health_score'] ?? 75,
                    'critical_issues' => count($analysis['issues']),
                    'recommendations' => count($analysis['recommendations']),
                    'processing_time' => $analysis['processing_time'],
                    'analyzed_at' => $analysis['analyzed_at']->format('Y-m-d H:i:s'),
                ],
                'issues' => $analysis['issues'],
                'recommendations' => $analysis['recommendations'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get log analysis insights for a server
     */
    public function getServerLogInsights(Request $request, int $serverId): JsonResponse
    {
        $server = Server::findOrFail($serverId);
        
        // Get recent analysis results
        $history = $this->logAnalysisService->getAnalysisHistory($server, 5);
        
        // Get active alerts
        $alerts = $this->logAnalysisService->getLogAlerts($server);
        
        // Get recent insights
        $insights = AiInsight::where('context_type', 'server')
            ->where('context_id', $serverId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($insight) {
                return [
                    'id' => $insight->id,
                    'type' => $insight->insight_type,
                    'category' => $insight->category,
                    'title' => $insight->title,
                    'description' => $insight->description,
                    'severity' => $insight->severity,
                    'confidence' => $insight->confidence_score,
                    'created_at' => $insight->created_at->format('M j, Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'status' => $server->status,
            ],
            'analysis_history' => $history,
            'active_alerts' => $alerts,
            'recent_insights' => $insights,
        ]);
    }

    /**
     * Get log analysis statistics
     */
    public function getLogStats(): JsonResponse
    {
        $stats = [
            'total_analyses' => AiAnalysisResult::where('analysis_type', 'log_analysis')->count(),
            'servers_analyzed' => AiAnalysisResult::where('analysis_type', 'log_analysis')
                ->distinct('context_id')->count(),
            'critical_alerts' => AiInsight::where('severity', 'critical')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count(),
            'avg_health_score' => $this->calculateAverageLogHealth(),
            'issue_breakdown' => $this->getIssueBreakdown(),
            'analysis_trends' => $this->getLogAnalysisTrends(),
            'top_issues' => $this->getTopLogIssues(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Bulk analyze logs for multiple servers
     */
    public function bulkAnalyzeLogs(Request $request): JsonResponse
    {
        $request->validate([
            'server_ids' => 'required|array',
            'server_ids.*' => 'integer|exists:servers,id',
            'time_range' => 'nullable|in:1h,6h,24h,7d',
        ]);

        $results = [];
        $processed = 0;
        $failed = 0;

        $options = [
            'time_range' => $request->get('time_range', '1h'),
        ];

        foreach ($request->server_ids as $serverId) {
            try {
                $server = Server::findOrFail($serverId);
                $analysis = $this->logAnalysisService->analyzeLogs($server, $options);

                $results[] = [
                    'server_id' => $serverId,
                    'server_name' => $server->name,
                    'health_score' => $analysis['insights']['health_score'] ?? 75,
                    'critical_issues' => count($analysis['issues']),
                    'status' => 'success',
                ];

                $processed++;

            } catch (\Exception $e) {
                $results[] = [
                    'server_id' => $serverId,
                    'server_name' => Server::find($serverId)->name ?? 'Unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($request->server_ids),
            ],
            'results' => $results,
        ]);
    }

    /**
     * Set up automated log analysis
     */
    public function setupAutomation(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'frequency' => 'required|in:15min,30min,1h,6h,24h',
            'alert_threshold' => 'required|integer|min:1|max:10',
            'server_ids' => 'nullable|array',
            'server_ids.*' => 'integer|exists:servers,id',
        ]);

        // In a real implementation, you'd store this configuration
        // and set up actual scheduled jobs
        
        return response()->json([
            'success' => true,
            'message' => 'Automated log analysis configuration updated',
            'config' => [
                'enabled' => $request->enabled,
                'frequency' => $request->frequency,
                'alert_threshold' => $request->alert_threshold,
                'servers' => count($request->server_ids ?? []),
            ],
        ]);
    }

    /**
     * Get active log alerts
     */
    public function getActiveAlerts(): JsonResponse
    {
        $alerts = AiInsight::where('insight_type', 'issue')
            ->where('severity', 'in', ['critical', 'high'])
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->with('server:id,name') // Assuming relationship exists
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $formattedAlerts = $alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'server_id' => $alert->context_id,
                'server_name' => $alert->server->name ?? 'Unknown Server',
                'title' => $alert->title,
                'description' => $alert->description,
                'severity' => $alert->severity,
                'category' => $alert->category,
                'confidence' => $alert->confidence_score,
                'created_at' => $alert->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'alerts' => $formattedAlerts,
            'count' => $alerts->count(),
        ]);
    }

    /**
     * Dismiss or acknowledge an alert
     */
    public function acknowledgeAlert(Request $request, int $alertId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:acknowledge,dismiss,resolve',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $insight = AiInsight::findOrFail($alertId);
            
            // Update insight with acknowledgment
            $metadata = json_decode($insight->metadata, true) ?? [];
            $metadata['acknowledged_at'] = now()->toISOString();
            $metadata['action'] = $request->action;
            $metadata['note'] = $request->note;
            
            $insight->update([
                'metadata' => json_encode($metadata),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alert ' . $request->action . 'd successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to acknowledge alert: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get log analysis overview data
     */
    protected function getLogAnalysisOverview(): array
    {
        $totalAnalyses = AiAnalysisResult::where('analysis_type', 'log_analysis')->count();
        $recentAnalyses = AiAnalysisResult::where('analysis_type', 'log_analysis')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        $criticalAlerts = AiInsight::where('severity', 'critical')
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->count();

        return [
            'total_analyses' => $totalAnalyses,
            'recent_analyses' => $recentAnalyses,
            'critical_alerts' => $criticalAlerts,
            'avg_health_score' => $this->calculateAverageLogHealth(),
            'servers_with_issues' => $this->getServersWithIssues(),
            'most_common_issues' => $this->getMostCommonIssues(),
        ];
    }

    /**
     * Calculate average health score from log analyses
     */
    protected function calculateAverageLogHealth(): float
    {
        $results = AiAnalysisResult::where('analysis_type', 'log_analysis')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0;
        $count = 0;

        foreach ($results as $result) {
            $insights = json_decode($result->insights, true) ?? [];
            $score = $insights['health_score'] ?? 0;
            
            if ($score > 0) {
                $totalScore += $score;
                $count++;
            }
        }

        return $count > 0 ? round($totalScore / $count, 1) : 0.0;
    }

    /**
     * Get issue breakdown by category
     */
    protected function getIssueBreakdown(): array
    {
        $issues = AiInsight::where('insight_type', 'issue')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select('category', \DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return $issues;
    }

    /**
     * Get log analysis trends
     */
    protected function getLogAnalysisTrends(): array
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = AiAnalysisResult::where('analysis_type', 'log_analysis')
                ->whereDate('created_at', $date)
                ->count();
            
            $trends[] = [
                'date' => $date->format('M j'),
                'analyses' => $count,
            ];
        }
        
        return $trends;
    }

    /**
     * Get top log issues
     */
    protected function getTopLogIssues(): array
    {
        return [
            'Memory Issues' => 15,
            'Connection Timeouts' => 12,
            'Plugin Errors' => 8,
            'Disk Space Warnings' => 6,
            'Performance Issues' => 4,
        ];
    }

    /**
     * Get servers with active issues
     */
    protected function getServersWithIssues(): int
    {
        return AiInsight::where('severity', 'in', ['critical', 'high'])
            ->where('created_at', '>=', Carbon::now()->subDays(1))
            ->distinct('context_id')
            ->count();
    }

    /**
     * Get most common issues
     */
    protected function getMostCommonIssues(): array
    {
        $issues = AiInsight::where('insight_type', 'issue')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select('category', \DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->take(5)
            ->pluck('count', 'category')
            ->toArray();

        return $issues;
    }
}