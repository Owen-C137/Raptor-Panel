<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use PterodactylAddons\OllamaAi\Services\PredictiveAnalyticsService;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use Carbon\Carbon;

class AiPredictiveAnalyticsController extends Controller
{
    protected $predictiveAnalyticsService;

    public function __construct(PredictiveAnalyticsService $predictiveAnalyticsService)
    {
        $this->predictiveAnalyticsService = $predictiveAnalyticsService;
    }

    /**
     * Display predictive analytics dashboard
     */
    public function index(): View
    {
        $servers = Server::with(['node', 'allocation'])->paginate(10);
        $nodes = Node::with(['servers'])->get();
        
        // Get recent predictions
        $recentPredictions = AiAnalysisResult::where('analysis_type', 'predictive_analytics')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Calculate summary statistics
        $summaryStats = [
            'total_servers' => Server::count(),
            'predictions_generated' => AiAnalysisResult::where('analysis_type', 'predictive_analytics')->count(),
            'alerts_active' => $this->countActivePredictiveAlerts(),
            'high_confidence_predictions' => AiAnalysisResult::where('analysis_type', 'predictive_analytics')
                ->where('confidence_score', '>', 0.8)
                ->count(),
        ];

        return view('ollama-ai::admin.predictive-analytics.index', [
            'servers' => $servers,
            'nodes' => $nodes,
            'recentPredictions' => $recentPredictions,
            'summaryStats' => $summaryStats,
        ]);
    }

    /**
     * Generate predictions for a specific server
     */
    public function generateServerPredictions(Request $request, Server $server): JsonResponse
    {
        $request->validate([
            'period' => 'sometimes|string|in:7_days,30_days,90_days,180_days',
            'force_refresh' => 'sometimes|boolean',
        ]);

        try {
            $period = $request->input('period', '30_days');
            $forceRefresh = $request->input('force_refresh', false);

            // Check for cached predictions unless force refresh
            if (!$forceRefresh) {
                $cached = $this->predictiveAnalyticsService->getCachedPredictions($server);
                if ($cached) {
                    return response()->json([
                        'success' => true,
                        'data' => $cached,
                        'source' => 'cache',
                    ]);
                }
            }

            // Generate new predictions
            $predictions = $this->predictiveAnalyticsService->generateServerPredictions($server, [
                'period' => $period,
            ]);

            if (!empty($predictions['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $predictions['error'],
                ], 422);
            }

            // Cache the results
            $this->predictiveAnalyticsService->cachePredictions($server, $predictions);

            return response()->json([
                'success' => true,
                'data' => $predictions,
                'source' => 'generated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate predictions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate predictions for multiple servers
     */
    public function generateBulkPredictions(Request $request): JsonResponse
    {
        $request->validate([
            'server_ids' => 'required|array|max:20', // Limit bulk operations
            'server_ids.*' => 'exists:servers,id',
            'period' => 'sometimes|string|in:7_days,30_days,90_days,180_days',
        ]);

        try {
            $serverIds = $request->input('server_ids');
            $period = $request->input('period', '30_days');
            
            $results = [];
            $errors = [];

            foreach ($serverIds as $serverId) {
                try {
                    $server = Server::findOrFail($serverId);
                    $predictions = $this->predictiveAnalyticsService->generateServerPredictions($server, [
                        'period' => $period,
                    ]);

                    if (!empty($predictions['error'])) {
                        $errors[] = [
                            'server_id' => $serverId,
                            'error' => $predictions['error'],
                        ];
                    } else {
                        $results[] = [
                            'server_id' => $serverId,
                            'server_name' => $server->name,
                            'predictions' => $predictions,
                        ];
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'server_id' => $serverId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => count($errors) === 0,
                'data' => $results,
                'errors' => $errors,
                'summary' => [
                    'total_requested' => count($serverIds),
                    'successful' => count($results),
                    'failed' => count($errors),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Bulk prediction generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get prediction details for a server
     */
    public function getServerPredictions(Server $server): JsonResponse
    {
        try {
            // Try to get cached predictions first
            $cached = $this->predictiveAnalyticsService->getCachedPredictions($server);
            if ($cached) {
                return response()->json([
                    'success' => true,
                    'data' => $cached,
                    'source' => 'cache',
                ]);
            }

            // Get latest stored predictions
            $latestAnalysis = AiAnalysisResult::where('context_type', 'server')
                ->where('context_id', $server->id)
                ->where('analysis_type', 'predictive_analytics')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latestAnalysis) {
                return response()->json([
                    'success' => false,
                    'error' => 'No predictions found. Generate predictions first.',
                ], 404);
            }

            $predictions = json_decode($latestAnalysis->insights, true);
            $recommendations = json_decode($latestAnalysis->recommendations, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'predictions' => $predictions,
                    'recommendations' => $recommendations,
                    'confidence_score' => $latestAnalysis->confidence_score,
                    'generated_at' => $latestAnalysis->created_at,
                    'age_hours' => $latestAnalysis->created_at->diffInHours(now()),
                ],
                'source' => 'database',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve predictions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get predictive alerts across all servers
     */
    public function getPredictiveAlerts(Request $request): JsonResponse
    {
        $request->validate([
            'severity' => 'sometimes|string|in:critical,warning,info',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $limit = $request->input('limit', 50);
            $severity = $request->input('severity');

            // Get recent predictions with alerts
            $query = AiAnalysisResult::where('analysis_type', 'predictive_analytics')
                ->whereNotNull('insights')
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            $analyses = $query->get();
            
            $alerts = [];
            
            foreach ($analyses as $analysis) {
                $insights = json_decode($analysis->insights, true);
                if (!$insights) continue;

                foreach ($insights as $period => $prediction) {
                    if (!isset($prediction['alerts'])) continue;

                    foreach ($prediction['alerts'] as $alert) {
                        if ($severity && $alert['severity'] !== $severity) continue;

                        $alerts[] = [
                            'id' => $analysis->id,
                            'server_id' => $analysis->context_id,
                            'server_name' => $this->getServerName($analysis->context_id),
                            'period' => $period,
                            'severity' => $alert['severity'],
                            'metric' => $alert['metric'],
                            'message' => $alert['message'],
                            'predicted_value' => $alert['predicted_value'],
                            'threshold' => $alert['threshold'],
                            'confidence' => $alert['confidence'],
                            'generated_at' => $analysis->created_at,
                        ];
                    }
                }
            }

            // Sort by severity (critical first) and then by confidence
            usort($alerts, function ($a, $b) {
                $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
                $severityDiff = ($severityOrder[$a['severity']] ?? 999) - ($severityOrder[$b['severity']] ?? 999);
                
                if ($severityDiff !== 0) {
                    return $severityDiff;
                }
                
                return $b['confidence'] <=> $a['confidence']; // Higher confidence first
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($alerts, 0, $limit),
                'summary' => [
                    'total_alerts' => count($alerts),
                    'critical_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
                    'warning_alerts' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve alerts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get prediction trends and analytics
     */
    public function getPredictionTrends(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
            'metric' => 'sometimes|string|in:cpu_usage,memory_usage,disk_usage,network_io',
        ]);

        try {
            $days = $request->input('days', 30);
            $metric = $request->input('metric');
            
            $startDate = Carbon::now()->subDays($days);
            
            // Get prediction history
            $analyses = AiAnalysisResult::where('analysis_type', 'predictive_analytics')
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'asc')
                ->get();

            $trends = [];
            $accuracyData = [];

            foreach ($analyses as $analysis) {
                $insights = json_decode($analysis->insights, true);
                if (!$insights) continue;

                $date = $analysis->created_at->format('Y-m-d');
                
                foreach ($insights as $period => $prediction) {
                    if (!isset($prediction['resource_predictions'])) continue;
                    
                    foreach ($prediction['resource_predictions'] as $predMetric => $predData) {
                        if ($metric && $predMetric !== $metric) continue;
                        
                        if (!isset($trends[$predMetric])) {
                            $trends[$predMetric] = [];
                        }
                        
                        $trends[$predMetric][] = [
                            'date' => $date,
                            'period' => $period,
                            'server_id' => $analysis->context_id,
                            'predicted_value' => $predData['predicted_value'],
                            'confidence' => $predData['confidence'],
                            'trend_direction' => $predData['trend_direction'],
                        ];
                    }
                }
            }

            // Calculate summary statistics
            $summary = [
                'total_predictions' => $analyses->count(),
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'metrics_analyzed' => array_keys($trends),
                'average_confidence' => $this->calculateAverageConfidence($analyses),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'trends' => $trends,
                    'summary' => $summary,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve trends: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export predictions data
     */
    public function exportPredictions(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:json,csv',
            'server_ids' => 'sometimes|array',
            'server_ids.*' => 'exists:servers,id',
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        try {
            $format = $request->input('format');
            $serverIds = $request->input('server_ids');
            $days = $request->input('days', 30);

            $startDate = Carbon::now()->subDays($days);
            
            $query = AiAnalysisResult::where('analysis_type', 'predictive_analytics')
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc');

            if ($serverIds) {
                $query->whereIn('context_id', $serverIds);
            }

            $analyses = $query->get();
            
            $exportData = [];
            
            foreach ($analyses as $analysis) {
                $insights = json_decode($analysis->insights, true);
                $recommendations = json_decode($analysis->recommendations, true);
                
                $exportData[] = [
                    'id' => $analysis->id,
                    'server_id' => $analysis->context_id,
                    'server_name' => $this->getServerName($analysis->context_id),
                    'predictions' => $insights,
                    'recommendations' => $recommendations,
                    'confidence_score' => $analysis->confidence_score,
                    'generated_at' => $analysis->created_at->toISOString(),
                ];
            }

            if ($format === 'csv') {
                // For CSV, flatten the data structure
                $flatData = [];
                foreach ($exportData as $record) {
                    if (isset($record['predictions'])) {
                        foreach ($record['predictions'] as $period => $prediction) {
                            $flatRecord = [
                                'server_id' => $record['server_id'],
                                'server_name' => $record['server_name'],
                                'period' => $period,
                                'confidence_score' => $record['confidence_score'],
                                'generated_at' => $record['generated_at'],
                            ];
                            
                            // Add resource predictions
                            if (isset($prediction['resource_predictions'])) {
                                foreach ($prediction['resource_predictions'] as $metric => $predData) {
                                    $flatRecord["{$metric}_predicted"] = $predData['predicted_value'];
                                    $flatRecord["{$metric}_confidence"] = $predData['confidence'];
                                    $flatRecord["{$metric}_trend"] = $predData['trend_direction'];
                                }
                            }
                            
                            $flatData[] = $flatRecord;
                        }
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $flatData,
                    'format' => 'csv',
                    'count' => count($flatData),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'format' => 'json',
                'count' => count($exportData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    protected function countActivePredictiveAlerts(): int
    {
        // Count alerts from recent predictions (last 24 hours)
        $recentAnalyses = AiAnalysisResult::where('analysis_type', 'predictive_analytics')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->get();

        $alertCount = 0;
        foreach ($recentAnalyses as $analysis) {
            $insights = json_decode($analysis->insights, true);
            if (!$insights) continue;

            foreach ($insights as $prediction) {
                if (isset($prediction['alerts'])) {
                    $alertCount += count($prediction['alerts']);
                }
            }
        }

        return $alertCount;
    }

    protected function getServerName(int $serverId): string
    {
        $server = Server::find($serverId);
        return $server ? $server->name : "Server #{$serverId}";
    }

    protected function calculateAverageConfidence($analyses): float
    {
        if ($analyses->isEmpty()) {
            return 0.0;
        }

        $totalConfidence = 0;
        $count = 0;

        foreach ($analyses as $analysis) {
            if ($analysis->confidence_score > 0) {
                $totalConfidence += $analysis->confidence_score;
                $count++;
            }
        }

        return $count > 0 ? round($totalConfidence / $count, 2) : 0.0;
    }
}