<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Services\AiPerformanceOptimizationService;
use PterodactylAddons\OllamaAi\Services\AiUiUxOptimizationService;
use PterodactylAddons\OllamaAi\Services\AiTestingQualityAssuranceService;

class AiOptimizationController extends Controller
{
    protected $performanceService;
    protected $uiUxService;
    protected $testingService;

    public function __construct(
        AiPerformanceOptimizationService $performanceService,
        AiUiUxOptimizationService $uiUxService,
        AiTestingQualityAssuranceService $testingService
    ) {
        $this->performanceService = $performanceService;
        $this->uiUxService = $uiUxService;
        $this->testingService = $testingService;
    }

    /**
     * Show the optimization dashboard
     */
    public function index(): View
    {
        return view('ollama-ai::admin.optimization.index', [
            'performance_metrics' => $this->performanceService->monitorPerformanceMetrics(),
            'ui_ux_data' => $this->uiUxService->optimizeUserInterface(),
            'quality_metrics' => $this->testingService->generateQualityMetrics(),
            'optimization_recommendations' => $this->getOptimizationRecommendations(),
        ]);
    }

    /**
     * Run performance optimizations
     */
    public function optimizePerformance(Request $request): JsonResponse
    {
        try {
            $results = $this->performanceService->optimizeAllServices();

            return response()->json([
                'success' => true,
                'message' => 'Performance optimizations applied successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Performance optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run UI/UX optimizations
     */
    public function optimizeUiUx(Request $request): JsonResponse
    {
        try {
            $results = $this->uiUxService->optimizeUserInterface();

            return response()->json([
                'success' => true,
                'message' => 'UI/UX optimizations applied successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'UI/UX optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run comprehensive testing
     */
    public function runTests(Request $request): JsonResponse
    {
        try {
            $results = $this->testingService->runComprehensiveTests();

            return response()->json([
                'success' => true,
                'message' => 'Testing suite completed successfully',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Testing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        try {
            $metrics = $this->performanceService->monitorPerformanceMetrics();
            $improvements = $this->performanceService->measurePerformanceImprovements();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_metrics' => $metrics,
                    'improvements' => $improvements,
                    'recommendations' => $this->performanceService->generateOptimizationRecommendations(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get UI/UX metrics
     */
    public function uiUxMetrics(Request $request): JsonResponse
    {
        try {
            $metrics = [
                'ui_improvements' => $this->uiUxService->measureUiImprovements(),
                'accessibility_score' => $this->uiUxService->calculateAccessibilityScore(),
                'user_satisfaction' => $this->uiUxService->getUserSatisfactionMetrics(),
                'style_guide' => $this->uiUxService->generateStyleGuide(),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve UI/UX metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get testing and quality metrics
     */
    public function testingMetrics(Request $request): JsonResponse
    {
        try {
            $diagnostics = $this->testingService->generateDebugDiagnostics();
            $qualityMetrics = $this->testingService->generateQualityMetrics();

            return response()->json([
                'success' => true,
                'data' => [
                    'diagnostics' => $diagnostics,
                    'quality_metrics' => $qualityMetrics,
                    'health_status' => $diagnostics['health_check'] ?? [],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve testing metrics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show performance optimization dashboard
     */
    public function performanceDashboard(): View
    {
        return view('ollama-ai::admin.optimization.performance', [
            'metrics' => $this->performanceService->monitorPerformanceMetrics(),
            'improvements' => $this->performanceService->measurePerformanceImprovements(),
            'recommendations' => $this->performanceService->generateOptimizationRecommendations(),
        ]);
    }

    /**
     * Show UI/UX optimization dashboard
     */
    public function uiUxDashboard(): View
    {
        return view('ollama-ai::admin.optimization.ui-ux', [
            'improvements' => $this->uiUxService->measureUiImprovements(),
            'accessibility_score' => $this->uiUxService->calculateAccessibilityScore(),
            'user_satisfaction' => $this->uiUxService->getUserSatisfactionMetrics(),
            'style_guide' => $this->uiUxService->generateStyleGuide(),
        ]);
    }

    /**
     * Show testing and QA dashboard
     */
    public function testingDashboard(): View
    {
        return view('ollama-ai::admin.optimization.testing', [
            'diagnostics' => $this->testingService->generateDebugDiagnostics(),
            'quality_metrics' => $this->testingService->generateQualityMetrics(),
        ]);
    }

    /**
     * Generate comprehensive optimization report
     */
    public function generateOptimizationReport(Request $request): JsonResponse
    {
        try {
            $report = [
                'report_id' => uniqid('optimization_report_'),
                'generated_at' => now()->toISOString(),
                'performance' => [
                    'current_metrics' => $this->performanceService->monitorPerformanceMetrics(),
                    'optimizations_applied' => $this->performanceService->optimizeAllServices(),
                    'recommendations' => $this->performanceService->generateOptimizationRecommendations(),
                ],
                'ui_ux' => [
                    'improvements' => $this->uiUxService->measureUiImprovements(),
                    'accessibility_score' => $this->uiUxService->calculateAccessibilityScore(),
                    'optimizations_applied' => $this->uiUxService->optimizeUserInterface(),
                ],
                'testing_qa' => [
                    'test_results' => $this->testingService->runComprehensiveTests(),
                    'diagnostics' => $this->testingService->generateDebugDiagnostics(),
                    'quality_metrics' => $this->testingService->generateQualityMetrics(),
                ],
                'overall_health' => $this->calculateOverallHealth(),
                'recommendations' => $this->getOptimizationRecommendations(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Optimization report generated successfully',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate optimization report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export optimization data
     */
    public function exportOptimizationData(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:json,csv,pdf',
            'sections' => 'array',
            'sections.*' => 'in:performance,ui_ux,testing,recommendations',
        ]);

        try {
            $data = $this->gatherOptimizationData($request->get('sections', ['performance', 'ui_ux', 'testing']));
            $format = $request->get('format', 'json');

            switch ($format) {
                case 'json':
                    return response()->json([
                        'success' => true,
                        'data' => $data,
                        'export_format' => 'json',
                    ]);

                case 'csv':
                    // Convert data to CSV format
                    $csv = $this->convertToCsv($data);
                    return response($csv, 200, [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="ai_optimization_report.csv"',
                    ]);

                case 'pdf':
                    // Generate PDF report (would require PDF library)
                    return response()->json([
                        'success' => true,
                        'message' => 'PDF export functionality would be implemented here',
                        'data' => $data,
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export optimization data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset optimization baselines
     */
    public function resetBaselines(Request $request): JsonResponse
    {
        try {
            // Reset performance baselines
            cache()->forget('ai_perf_baseline_metrics');
            
            // Establish new baselines
            $newBaselines = [
                'performance' => $this->performanceService->monitorPerformanceMetrics(),
                'ui_ux' => $this->uiUxService->measureUiImprovements(),
                'quality' => $this->testingService->generateQualityMetrics(),
                'reset_at' => now()->toISOString(),
            ];

            cache()->put('ai_optimization_baselines', $newBaselines, 86400); // 24 hours

            return response()->json([
                'success' => true,
                'message' => 'Optimization baselines reset successfully',
                'data' => $newBaselines,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset baselines: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Protected helper methods
     */
    protected function getOptimizationRecommendations(): array
    {
        $performanceRecs = $this->performanceService->generateOptimizationRecommendations();
        
        return [
            'high_priority' => array_filter($performanceRecs, function($rec) {
                return ($rec['priority'] ?? '') === 'high';
            }),
            'medium_priority' => array_filter($performanceRecs, function($rec) {
                return ($rec['priority'] ?? '') === 'medium';
            }),
            'low_priority' => array_filter($performanceRecs, function($rec) {
                return ($rec['priority'] ?? '') === 'low';
            }),
        ];
    }

    protected function calculateOverallHealth(): array
    {
        $performanceMetrics = $this->performanceService->monitorPerformanceMetrics();
        $uiMetrics = $this->uiUxService->measureUiImprovements();
        $qualityMetrics = $this->testingService->generateQualityMetrics();

        // Calculate weighted health score
        $healthScore = (
            ($performanceMetrics['response_times']['average'] < 1000 ? 25 : 0) +
            ($uiMetrics['accessibility_score'] > 95 ? 25 : 0) +
            ($qualityMetrics['test_coverage'] > 90 ? 25 : 0) +
            ($qualityMetrics['security_score'] > 95 ? 25 : 0)
        );

        return [
            'overall_score' => $healthScore,
            'status' => $this->getHealthStatus($healthScore),
            'performance_health' => $performanceMetrics['response_times']['average'] < 1000 ? 'excellent' : 'needs_improvement',
            'ui_ux_health' => $uiMetrics['accessibility_score'] > 95 ? 'excellent' : 'good',
            'quality_health' => $qualityMetrics['test_coverage'] > 90 ? 'excellent' : 'good',
            'security_health' => $qualityMetrics['security_score'] > 95 ? 'excellent' : 'good',
        ];
    }

    protected function getHealthStatus(int $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        return 'needs_improvement';
    }

    protected function gatherOptimizationData(array $sections): array
    {
        $data = [];

        if (in_array('performance', $sections)) {
            $data['performance'] = [
                'metrics' => $this->performanceService->monitorPerformanceMetrics(),
                'improvements' => $this->performanceService->measurePerformanceImprovements(),
                'recommendations' => $this->performanceService->generateOptimizationRecommendations(),
            ];
        }

        if (in_array('ui_ux', $sections)) {
            $data['ui_ux'] = [
                'improvements' => $this->uiUxService->measureUiImprovements(),
                'accessibility' => $this->uiUxService->calculateAccessibilityScore(),
                'satisfaction' => $this->uiUxService->getUserSatisfactionMetrics(),
            ];
        }

        if (in_array('testing', $sections)) {
            $data['testing'] = [
                'quality_metrics' => $this->testingService->generateQualityMetrics(),
                'diagnostics' => $this->testingService->generateDebugDiagnostics(),
            ];
        }

        if (in_array('recommendations', $sections)) {
            $data['recommendations'] = $this->getOptimizationRecommendations();
        }

        return $data;
    }

    protected function convertToCsv(array $data): string
    {
        $csv = "Section,Metric,Value,Status\n";

        foreach ($data as $section => $sectionData) {
            foreach ($sectionData as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        if (!is_array($subValue)) {
                            $csv .= sprintf("%s,%s.%s,%s,\n", 
                                ucfirst($section), 
                                $key, 
                                $subKey, 
                                $subValue
                            );
                        }
                    }
                } else {
                    $csv .= sprintf("%s,%s,%s,\n", 
                        ucfirst($section), 
                        $key, 
                        $value
                    );
                }
            }
        }

        return $csv;
    }
}