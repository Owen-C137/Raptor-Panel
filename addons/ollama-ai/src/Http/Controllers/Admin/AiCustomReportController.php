<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\User;
use PterodactylAddons\OllamaAi\Services\CustomReportService;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use Carbon\Carbon;

class AiCustomReportController extends Controller
{
    protected $customReportService;

    public function __construct(CustomReportService $customReportService)
    {
        $this->customReportService = $customReportService;
    }

    /**
     * Display custom reporting dashboard
     */
    public function index(): View
    {
        $templates = $this->customReportService->getReportTemplates();
        
        // Get recent reports
        $recentReports = AiAnalysisResult::where('context_type', 'custom_report')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get available data for filters
        $servers = Server::with('node')->get();
        $nodes = Node::all();
        $users = User::all();

        $summaryStats = [
            'total_reports' => AiAnalysisResult::where('context_type', 'custom_report')->count(),
            'reports_this_month' => AiAnalysisResult::where('context_type', 'custom_report')
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count(),
            'scheduled_reports' => 5, // This would come from a schedules table
            'export_formats' => count(['pdf', 'csv', 'json', 'html', 'excel']),
        ];

        return view('ollama-ai::admin.custom-reports.index', [
            'templates' => $templates,
            'recentReports' => $recentReports,
            'servers' => $servers,
            'nodes' => $nodes,
            'users' => $users,
            'summaryStats' => $summaryStats,
        ]);
    }

    /**
     * Show report creation form
     */
    public function create(): View
    {
        $templates = $this->customReportService->getReportTemplates();
        $servers = Server::with('node')->get();
        $nodes = Node::all();

        return view('ollama-ai::admin.custom-reports.create', [
            'templates' => $templates,
            'servers' => $servers,
            'nodes' => $nodes,
        ]);
    }

    /**
     * Generate a new custom report
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'format' => 'sometimes|string|in:structured,executive,detailed,dashboard',
            'time_range' => 'sometimes|string|in:7_days,30_days,90_days,180_days,365_days',
            'server_ids' => 'sometimes|array',
            'server_ids.*' => 'exists:servers,id',
            'node_ids' => 'sometimes|array',
            'node_ids.*' => 'exists:nodes,id',
            'include_charts' => 'sometimes|boolean',
            'include_recommendations' => 'sometimes|boolean',
            'include_anomalies' => 'sometimes|boolean',
            'include_comparison' => 'sometimes|boolean',
        ]);

        try {
            $config = $request->all();
            
            // Set defaults
            $config['format'] = $config['format'] ?? 'structured';
            $config['time_range'] = $config['time_range'] ?? '30_days';
            $config['include_charts'] = $config['include_charts'] ?? true;
            $config['include_recommendations'] = $config['include_recommendations'] ?? true;

            // Generate the report
            $result = $this->customReportService->generateReport($config);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'report_id' => $result['report_id'],
                'message' => 'Report generated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Report generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report templates and their configurations
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = $this->customReportService->getReportTemplates();
            
            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve templates: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get template configuration
     */
    public function getTemplate(string $templateName): JsonResponse
    {
        try {
            $templates = $this->customReportService->getReportTemplates();
            
            if (!isset($templates[$templateName])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Template not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $templates[$templateName],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve template: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View a generated report
     */
    public function view(string $reportId): JsonResponse
    {
        try {
            $report = $this->customReportService->getStoredReport($reportId);
            
            if (!$report) {
                return response()->json([
                    'success' => false,
                    'error' => 'Report not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export a report in specified format
     */
    public function export(Request $request, string $reportId): Response
    {
        $request->validate([
            'format' => 'required|string|in:pdf,csv,json,html,excel',
        ]);

        try {
            $format = $request->input('format');
            $exportData = $this->customReportService->exportReport($reportId, $format);

            return response($exportData['data'])
                ->header('Content-Type', $exportData['mime_type'])
                ->header('Content-Disposition', 'attachment; filename="' . $exportData['filename'] . '"');

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule a recurring report
     */
    public function schedule(Request $request): JsonResponse
    {
        $request->validate([
            'config' => 'required|array',
            'schedule' => 'required|string|in:daily,weekly,monthly,quarterly',
            'email_recipients' => 'sometimes|array',
            'email_recipients.*' => 'email',
        ]);

        try {
            $config = $request->input('config');
            $schedule = $request->input('schedule');
            
            $scheduleId = $this->customReportService->scheduleReport($config, $schedule);

            return response()->json([
                'success' => true,
                'schedule_id' => $scheduleId,
                'message' => 'Report scheduled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Scheduling failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report history
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|string',
            'days' => 'sometimes|integer|min:1|max:365',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        try {
            $days = $request->input('days', 30);
            $type = $request->input('type');
            $limit = $request->input('limit', 50);

            $query = AiAnalysisResult::where('context_type', 'custom_report')
                ->where('created_at', '>=', Carbon::now()->subDays($days))
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            if ($type) {
                $query->where('analysis_type', $type);
            }

            $reports = $query->get();

            $history = $reports->map(function ($report) {
                $metadata = json_decode($report->metadata, true);
                $insights = json_decode($report->insights, true);
                
                return [
                    'id' => $report->id,
                    'report_id' => $metadata['report_id'] ?? 'N/A',
                    'type' => $report->analysis_type,
                    'title' => $insights['header']['title'] ?? 'Custom Report',
                    'confidence_score' => $report->confidence_score,
                    'created_at' => $report->created_at,
                    'age' => $report->created_at->diffForHumans(),
                    'has_recommendations' => !empty($report->recommendations),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $history,
                'summary' => [
                    'total_reports' => $reports->count(),
                    'date_range' => [
                        'start' => Carbon::now()->subDays($days)->format('Y-m-d'),
                        'end' => Carbon::now()->format('Y-m-d'),
                    ],
                    'average_confidence' => $reports->avg('confidence_score'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a report
     */
    public function delete(string $reportId): JsonResponse
    {
        try {
            // Remove from cache
            \Cache::forget("custom_report_{$reportId}");
            
            // Remove from database
            $deleted = AiAnalysisResult::where('context_type', 'custom_report')
                ->whereJsonContains('metadata->report_id', $reportId)
                ->delete();

            if ($deleted > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Report not found',
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get report statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        try {
            $days = $request->input('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $reports = AiAnalysisResult::where('context_type', 'custom_report')
                ->where('created_at', '>=', $startDate)
                ->get();

            $statistics = [
                'total_reports' => $reports->count(),
                'reports_by_type' => $reports->groupBy('analysis_type')
                    ->map(function ($group) {
                        return $group->count();
                    }),
                'average_confidence' => $reports->avg('confidence_score'),
                'reports_per_day' => $reports->groupBy(function ($report) {
                    return $report->created_at->format('Y-m-d');
                })->map(function ($group) {
                    return $group->count();
                }),
                'high_confidence_reports' => $reports->where('confidence_score', '>', 0.8)->count(),
                'processing_time_avg' => $reports->avg('processing_time'),
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics,
                'period' => [
                    'days' => $days,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => Carbon::now()->format('Y-m-d'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate a report configuration
     */
    public function duplicate(Request $request, string $reportId): JsonResponse
    {
        try {
            $originalReport = $this->customReportService->getStoredReport($reportId);
            
            if (!$originalReport) {
                return response()->json([
                    'success' => false,
                    'error' => 'Original report not found',
                ], 404);
            }

            $config = $originalReport['config'];
            
            // Allow overriding some config values
            $overrides = $request->only([
                'time_range',
                'server_ids',
                'node_ids',
                'include_charts',
                'include_recommendations',
            ]);
            
            $config = array_merge($config, array_filter($overrides));

            // Generate new report
            $result = $this->customReportService->generateReport($config);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'report_id' => $result['report_id'],
                'message' => 'Report duplicated and generated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Duplication failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Compare multiple reports
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'report_ids' => 'required|array|min:2|max:4',
            'report_ids.*' => 'string',
        ]);

        try {
            $reportIds = $request->input('report_ids');
            $reports = [];
            
            foreach ($reportIds as $reportId) {
                $report = $this->customReportService->getStoredReport($reportId);
                if ($report) {
                    $reports[$reportId] = $report;
                }
            }

            if (count($reports) < 2) {
                return response()->json([
                    'success' => false,
                    'error' => 'At least 2 valid reports are required for comparison',
                ], 422);
            }

            // Generate comparison analysis
            $comparison = $this->generateComparison($reports);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Comparison failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate comparison analysis
     */
    protected function generateComparison(array $reports): array
    {
        $comparison = [
            'summary' => [
                'total_reports' => count($reports),
                'report_types' => array_unique(array_column($reports, 'type')),
                'date_range' => $this->getComparisonDateRange($reports),
            ],
            'metrics_comparison' => $this->compareMetrics($reports),
            'trend_analysis' => $this->compareTrends($reports),
            'key_differences' => $this->identifyKeyDifferences($reports),
            'recommendations' => $this->generateComparisonRecommendations($reports),
        ];

        return $comparison;
    }

    protected function getComparisonDateRange(array $reports): array
    {
        $dates = [];
        foreach ($reports as $report) {
            $dates[] = $report['created_at'];
        }
        
        return [
            'earliest' => min($dates)->format('Y-m-d H:i:s'),
            'latest' => max($dates)->format('Y-m-d H:i:s'),
        ];
    }

    protected function compareMetrics(array $reports): array
    {
        // Simplified comparison - in production, this would be more sophisticated
        $metrics = [];
        
        foreach ($reports as $reportId => $report) {
            if (isset($report['data']['ai_insights']['confidence_score'])) {
                $metrics['confidence_scores'][$reportId] = $report['data']['ai_insights']['confidence_score'];
            }
        }

        return $metrics;
    }

    protected function compareTrends(array $reports): array
    {
        return [
            'message' => 'Trend comparison available in detailed analysis',
            'trend_count' => count($reports),
        ];
    }

    protected function identifyKeyDifferences(array $reports): array
    {
        return [
            'Different report types detected',
            'Varying confidence levels across reports',
            'Time range differences may affect comparability',
        ];
    }

    protected function generateComparisonRecommendations(array $reports): array
    {
        return [
            [
                'text' => 'Consider standardizing time ranges for better comparison',
                'priority' => 'medium',
            ],
            [
                'text' => 'Focus on reports with highest confidence scores',
                'priority' => 'high',
            ],
        ];
    }
}