<?php

namespace PterodactylAddons\OllamaAi\Services;

use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use PterodactylAddons\OllamaAi\Models\AiInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PredictiveAnalyticsService
{
    protected $ollamaService;
    protected $forecastPeriods = [
        '1_day' => 1,
        '1_week' => 7,
        '1_month' => 30,
        '3_months' => 90,
        '6_months' => 180,
        '1_year' => 365,
    ];

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * Generate predictive analytics for a server
     */
    public function generateServerPredictions(Server $server, array $options = []): array
    {
        try {
            // Gather historical data
            $historicalData = $this->gatherHistoricalData($server, $options['period'] ?? '30_days');
            
            if (empty($historicalData)) {
                return $this->createEmptyPrediction('Insufficient historical data');
            }

            // Perform trend analysis
            $trends = $this->analyzeTrends($historicalData);
            
            // Generate AI predictions
            $predictions = $this->generateAiPredictions($server, $historicalData, $trends);
            
            // Calculate confidence levels
            $confidence = $this->calculatePredictionConfidence($historicalData, $predictions);
            
            // Store predictions
            $this->storePredictions($server, $predictions, $confidence);
            
            return [
                'server_id' => $server->id,
                'predictions' => $predictions,
                'trends' => $trends,
                'confidence' => $confidence,
                'generated_at' => now(),
                'forecast_periods' => array_keys($this->forecastPeriods),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate server predictions', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->createEmptyPrediction('Prediction generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Gather historical performance data
     */
    protected function gatherHistoricalData(Server $server, string $period = '30_days'): array
    {
        $days = $this->getPeriodInDays($period);
        $startDate = Carbon::now()->subDays($days);
        
        // Simulate historical data gathering - in real implementation, you'd collect from:
        // - Wings API metrics
        // - Database performance logs  
        // - System monitoring data
        // - Previous analysis results
        
        $historicalData = [
            'time_series' => $this->generateTimeSeriesData($server, $startDate, $days),
            'performance_metrics' => $this->gatherPerformanceMetrics($server, $startDate),
            'resource_usage' => $this->gatherResourceUsage($server, $startDate),
            'user_activity' => $this->gatherUserActivity($server, $startDate),
            'system_events' => $this->gatherSystemEvents($server, $startDate),
        ];

        return $historicalData;
    }

    /**
     * Generate time series data for analysis
     */
    protected function generateTimeSeriesData(Server $server, Carbon $startDate, int $days): array
    {
        $timeSeriesData = [];
        $baseMetrics = [
            'cpu_usage' => 35.0,
            'memory_usage' => 55.0,
            'disk_usage' => 40.0,
            'network_io' => 500.0,
            'active_users' => 8.0,
        ];

        // Generate data points (hourly for better granularity)
        for ($i = 0; $i < $days * 24; $i++) {
            $timestamp = $startDate->copy()->addHours($i);
            
            // Add realistic variations and trends
            $hourOfDay = $timestamp->hour;
            $dayOfWeek = $timestamp->dayOfWeek;
            
            // Simulate daily patterns (higher usage during peak hours)
            $peakMultiplier = $this->calculatePeakMultiplier($hourOfDay, $dayOfWeek);
            
            // Add some random variation and growth trend
            $trendFactor = 1 + ($i / ($days * 24)) * 0.1; // 10% growth over period
            $randomFactor = 1 + (rand(-15, 15) / 100); // ±15% random variation
            
            $dataPoint = [
                'timestamp' => $timestamp->toISOString(),
                'cpu_usage' => round($baseMetrics['cpu_usage'] * $peakMultiplier * $trendFactor * $randomFactor, 2),
                'memory_usage' => round($baseMetrics['memory_usage'] * $peakMultiplier * $trendFactor * $randomFactor, 2),
                'disk_usage' => round($baseMetrics['disk_usage'] * (1 + ($i / ($days * 24)) * 0.2) * $randomFactor, 2), // Steady growth
                'network_io' => round($baseMetrics['network_io'] * $peakMultiplier * $randomFactor, 0),
                'active_users' => round($baseMetrics['active_users'] * $peakMultiplier * $randomFactor, 0),
            ];
            
            $timeSeriesData[] = $dataPoint;
        }

        return $timeSeriesData;
    }

    /**
     * Calculate peak usage multiplier based on time patterns
     */
    protected function calculatePeakMultiplier(int $hour, int $dayOfWeek): float
    {
        // Simulate realistic usage patterns
        $baseMultiplier = 1.0;
        
        // Peak hours (16:00-22:00)
        if ($hour >= 16 && $hour <= 22) {
            $baseMultiplier = 1.4;
        }
        // Late night (23:00-02:00)
        elseif ($hour >= 23 || $hour <= 2) {
            $baseMultiplier = 1.2;
        }
        // Early morning (03:00-08:00)
        elseif ($hour >= 3 && $hour <= 8) {
            $baseMultiplier = 0.6;
        }
        // Business hours (09:00-17:00)
        elseif ($hour >= 9 && $hour <= 17) {
            $baseMultiplier = 1.1;
        }

        // Weekend patterns (lower usage on Sunday/Monday)
        if ($dayOfWeek == 0 || $dayOfWeek == 1) { // Sunday or Monday
            $baseMultiplier *= 0.8;
        }
        // Higher usage on Friday/Saturday  
        elseif ($dayOfWeek == 5 || $dayOfWeek == 6) {
            $baseMultiplier *= 1.2;
        }

        return $baseMultiplier;
    }

    /**
     * Gather performance metrics
     */
    protected function gatherPerformanceMetrics(Server $server, Carbon $startDate): array
    {
        // Simulate gathering performance metrics
        return [
            'avg_response_time' => rand(200, 800),
            'error_rate' => rand(0, 5) / 100,
            'throughput' => rand(100, 500),
            'uptime_percentage' => rand(95, 100),
            'peak_concurrent_users' => rand(20, 100),
        ];
    }

    /**
     * Gather resource usage patterns
     */
    protected function gatherResourceUsage(Server $server, Carbon $startDate): array
    {
        return [
            'cpu_trends' => [
                'average' => rand(30, 60),
                'peak' => rand(70, 95),
                'growth_rate' => rand(-5, 15) / 10, // -0.5% to 1.5% per week
            ],
            'memory_trends' => [
                'average' => rand(40, 70),
                'peak' => rand(80, 95),
                'growth_rate' => rand(0, 20) / 10, // 0% to 2% per week
            ],
            'disk_trends' => [
                'average' => rand(35, 65),
                'peak' => rand(75, 90),
                'growth_rate' => rand(5, 25) / 10, // 0.5% to 2.5% per week
            ],
        ];
    }

    /**
     * Gather user activity patterns
     */
    protected function gatherUserActivity(Server $server, Carbon $startDate): array
    {
        return [
            'active_users_trend' => rand(-10, 25) / 10, // -1% to 2.5% growth
            'session_duration_avg' => rand(30, 180), // minutes
            'peak_activity_hours' => [16, 17, 18, 19, 20, 21, 22],
            'weekend_activity_ratio' => rand(70, 120) / 100, // 70% to 120% of weekday
        ];
    }

    /**
     * Gather system events
     */
    protected function gatherSystemEvents(Server $server, Carbon $startDate): array
    {
        return [
            'restarts' => rand(0, 5),
            'crashes' => rand(0, 2),
            'updates' => rand(1, 3),
            'maintenance_windows' => rand(0, 4),
            'performance_alerts' => rand(2, 15),
        ];
    }

    /**
     * Analyze trends in historical data
     */
    protected function analyzeTrends(array $historicalData): array
    {
        $trends = [];
        
        if (isset($historicalData['time_series'])) {
            $timeSeriesData = $historicalData['time_series'];
            
            $trends['cpu_usage'] = $this->calculateTrend($timeSeriesData, 'cpu_usage');
            $trends['memory_usage'] = $this->calculateTrend($timeSeriesData, 'memory_usage');
            $trends['disk_usage'] = $this->calculateTrend($timeSeriesData, 'disk_usage');
            $trends['network_io'] = $this->calculateTrend($timeSeriesData, 'network_io');
            $trends['active_users'] = $this->calculateTrend($timeSeriesData, 'active_users');
        }

        return $trends;
    }

    /**
     * Calculate trend for a specific metric
     */
    protected function calculateTrend(array $timeSeriesData, string $metric): array
    {
        if (empty($timeSeriesData)) {
            return ['direction' => 'stable', 'rate' => 0, 'confidence' => 0];
        }

        $values = array_column($timeSeriesData, $metric);
        $count = count($values);
        
        if ($count < 2) {
            return ['direction' => 'stable', 'rate' => 0, 'confidence' => 0];
        }

        // Simple linear regression to determine trend
        $x = range(0, $count - 1);
        $y = $values;
        
        $slope = $this->calculateSlope($x, $y);
        $correlation = $this->calculateCorrelation($x, $y);
        
        // Determine trend direction and strength
        $direction = 'stable';
        if (abs($slope) > 0.01) { // Minimum threshold for trend detection
            $direction = $slope > 0 ? 'increasing' : 'decreasing';
        }
        
        return [
            'direction' => $direction,
            'rate' => round($slope, 4),
            'confidence' => round(abs($correlation), 2),
            'current_value' => end($values),
            'average_value' => round(array_sum($values) / $count, 2),
            'volatility' => round($this->calculateVolatility($values), 2),
        ];
    }

    /**
     * Generate AI-powered predictions
     */
    protected function generateAiPredictions(Server $server, array $historicalData, array $trends): array
    {
        $prompt = $this->buildPredictionPrompt($server, $historicalData, $trends);
        
        try {
            $response = $this->ollamaService->analyzeData($prompt, [
                'context' => 'predictive_analytics',
                'server_id' => $server->id,
            ]);
            
            // Parse AI response into structured predictions
            $predictions = $this->parseAiPredictions($response, $trends);
            
            return $predictions;
            
        } catch (\Exception $e) {
            Log::error('AI prediction generation failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to statistical predictions
            return $this->generateStatisticalPredictions($trends);
        }
    }

    /**
     * Build AI prompt for predictions
     */
    protected function buildPredictionPrompt(Server $server, array $historicalData, array $trends): string
    {
        $trendSummary = '';
        foreach ($trends as $metric => $trend) {
            $trendSummary .= "- {$metric}: {$trend['direction']} at {$trend['rate']} per hour (confidence: {$trend['confidence']})\n";
        }

        $resourceUsage = $historicalData['resource_usage'] ?? [];
        $performanceMetrics = $historicalData['performance_metrics'] ?? [];

        return "Analyze the following server performance data and generate predictions:

Server: {$server->name} (ID: {$server->id})

**Current Trends:**
{$trendSummary}

**Resource Usage Patterns:**
- CPU Growth: " . ($resourceUsage['cpu_trends']['growth_rate'] ?? 0) . "% per week
- Memory Growth: " . ($resourceUsage['memory_trends']['growth_rate'] ?? 0) . "% per week  
- Disk Growth: " . ($resourceUsage['disk_trends']['growth_rate'] ?? 0) . "% per week

**Performance Metrics:**
- Average Response Time: " . ($performanceMetrics['avg_response_time'] ?? 0) . "ms
- Error Rate: " . (($performanceMetrics['error_rate'] ?? 0) * 100) . "%
- Uptime: " . ($performanceMetrics['uptime_percentage'] ?? 0) . "%

Please provide predictions for the next:
1. **1 day**: Expected resource usage and potential issues
2. **1 week**: Resource trends and capacity planning needs  
3. **1 month**: Long-term growth patterns and infrastructure requirements
4. **3 months**: Strategic planning and optimization opportunities

For each period, include:
- Predicted resource usage (CPU, Memory, Disk)
- Potential bottlenecks or issues
- Recommended actions
- Confidence level (1-10)

Focus on actionable insights for capacity planning and performance optimization.";
    }

    /**
     * Parse AI response into structured predictions
     */
    protected function parseAiPredictions(string $response, array $trends): array
    {
        $predictions = [];
        
        foreach ($this->forecastPeriods as $period => $days) {
            $predictions[$period] = $this->generatePeriodPrediction($period, $days, $trends, $response);
        }

        return $predictions;
    }

    /**
     * Generate prediction for a specific period
     */
    protected function generatePeriodPrediction(string $period, int $days, array $trends, string $aiResponse): array
    {
        // Extract period-specific insights from AI response
        $periodInsights = $this->extractPeriodInsights($aiResponse, $period);
        
        // Generate resource predictions based on trends
        $resourcePredictions = [];
        foreach (['cpu_usage', 'memory_usage', 'disk_usage', 'network_io', 'active_users'] as $metric) {
            if (isset($trends[$metric])) {
                $trend = $trends[$metric];
                $currentValue = $trend['current_value'] ?? 50;
                $rate = $trend['rate'] ?? 0;
                
                // Project forward based on trend
                $hoursInPeriod = $days * 24;
                $predictedValue = $currentValue + ($rate * $hoursInPeriod);
                
                // Add some bounds checking
                $predictedValue = max(0, min(100, $predictedValue));
                
                $resourcePredictions[$metric] = [
                    'predicted_value' => round($predictedValue, 2),
                    'confidence' => $trend['confidence'] ?? 0.5,
                    'trend_direction' => $trend['direction'] ?? 'stable',
                    'volatility' => $trend['volatility'] ?? 0,
                ];
            }
        }

        // Generate alerts and recommendations
        $alerts = $this->generatePredictiveAlerts($resourcePredictions);
        $recommendations = $this->generatePredictiveRecommendations($resourcePredictions, $period);

        return [
            'period' => $period,
            'days_ahead' => $days,
            'resource_predictions' => $resourcePredictions,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'confidence_score' => $this->calculateOverallConfidence($resourcePredictions),
            'insights' => $periodInsights,
        ];
    }

    /**
     * Extract period-specific insights from AI response
     */
    protected function extractPeriodInsights(string $response, string $period): array
    {
        $insights = [];
        
        // Simple pattern matching for insights - in production, use more sophisticated NLP
        if (preg_match("/(?:$period|" . str_replace('_', ' ', $period) . ").*?confidence.*?(\d+)/i", $response, $matches)) {
            $insights['ai_confidence'] = intval($matches[1]);
        }
        
        if (preg_match("/bottleneck|issue|problem/i", $response)) {
            $insights['potential_issues'] = true;
        }
        
        if (preg_match("/optimiz|improv|recommend/i", $response)) {
            $insights['optimization_opportunity'] = true;
        }
        
        return $insights;
    }

    /**
     * Generate predictive alerts
     */
    protected function generatePredictiveAlerts(array $resourcePredictions): array
    {
        $alerts = [];
        
        foreach ($resourcePredictions as $metric => $prediction) {
            $value = $prediction['predicted_value'];
            $confidence = $prediction['confidence'];
            
            // Only generate alerts for high-confidence predictions
            if ($confidence < 0.6) continue;
            
            // Define thresholds for alerts
            $thresholds = [
                'cpu_usage' => ['warning' => 70, 'critical' => 85],
                'memory_usage' => ['warning' => 75, 'critical' => 90],
                'disk_usage' => ['warning' => 80, 'critical' => 95],
            ];
            
            if (isset($thresholds[$metric])) {
                $threshold = $thresholds[$metric];
                
                if ($value >= $threshold['critical']) {
                    $alerts[] = [
                        'severity' => 'critical',
                        'metric' => $metric,
                        'predicted_value' => $value,
                        'threshold' => $threshold['critical'],
                        'message' => "Critical: " . ucfirst(str_replace('_', ' ', $metric)) . " predicted to reach {$value}%",
                        'confidence' => $confidence,
                    ];
                } elseif ($value >= $threshold['warning']) {
                    $alerts[] = [
                        'severity' => 'warning',
                        'metric' => $metric,
                        'predicted_value' => $value,
                        'threshold' => $threshold['warning'],
                        'message' => "Warning: " . ucfirst(str_replace('_', ' ', $metric)) . " predicted to reach {$value}%",
                        'confidence' => $confidence,
                    ];
                }
            }
        }
        
        return $alerts;
    }

    /**
     * Generate predictive recommendations
     */
    protected function generatePredictiveRecommendations(array $resourcePredictions, string $period): array
    {
        $recommendations = [];
        
        foreach ($resourcePredictions as $metric => $prediction) {
            $value = $prediction['predicted_value'];
            $trend = $prediction['trend_direction'];
            
            if ($trend === 'increasing' && $value > 60) {
                switch ($metric) {
                    case 'cpu_usage':
                        $recommendations[] = [
                            'type' => 'resource_scaling',
                            'priority' => $value > 80 ? 'high' : 'medium',
                            'action' => 'Consider CPU upgrade or load balancing',
                            'metric' => $metric,
                            'predicted_impact' => 'High',
                        ];
                        break;
                    
                    case 'memory_usage':
                        $recommendations[] = [
                            'type' => 'resource_scaling',
                            'priority' => $value > 85 ? 'high' : 'medium',
                            'action' => 'Increase memory allocation or optimize memory usage',
                            'metric' => $metric,
                            'predicted_impact' => 'High',
                        ];
                        break;
                    
                    case 'disk_usage':
                        $recommendations[] = [
                            'type' => 'storage_management',
                            'priority' => $value > 90 ? 'high' : 'medium',
                            'action' => 'Plan disk cleanup or storage expansion',
                            'metric' => $metric,
                            'predicted_impact' => 'Critical',
                        ];
                        break;
                }
            }
        }
        
        return $recommendations;
    }

    /**
     * Calculate overall confidence score
     */
    protected function calculateOverallConfidence(array $resourcePredictions): float
    {
        if (empty($resourcePredictions)) {
            return 0.0;
        }
        
        $confidenceSum = 0;
        $count = 0;
        
        foreach ($resourcePredictions as $prediction) {
            $confidenceSum += $prediction['confidence'] ?? 0;
            $count++;
        }
        
        return round($confidenceSum / $count, 2);
    }

    /**
     * Calculate prediction confidence
     */
    protected function calculatePredictionConfidence(array $historicalData, array $predictions): array
    {
        // Calculate confidence based on data quality and trend stability
        $dataPoints = count($historicalData['time_series'] ?? []);
        $baseConfidence = min(1.0, $dataPoints / (24 * 7)); // 1 week of hourly data = full confidence
        
        return [
            'overall' => round($baseConfidence, 2),
            'data_quality' => $dataPoints > 168 ? 'high' : ($dataPoints > 72 ? 'medium' : 'low'), // 1 week / 3 days
            'trend_stability' => 'medium', // Would calculate based on actual trend analysis
            'factors' => [
                'data_points' => $dataPoints,
                'time_range' => count($historicalData['time_series'] ?? []) > 0 ? '30 days' : 'insufficient',
            ],
        ];
    }

    /**
     * Store predictions in database
     */
    protected function storePredictions(Server $server, array $predictions, array $confidence): void
    {
        try {
            AiAnalysisResult::create([
                'context_type' => 'server',
                'context_id' => $server->id,
                'analysis_type' => 'predictive_analytics',
                'input_data' => json_encode(['server_id' => $server->id]),
                'ai_response' => 'Predictive analytics generated',
                'insights' => json_encode($predictions),
                'recommendations' => json_encode($this->extractAllRecommendations($predictions)),
                'confidence_score' => $confidence['overall'] ?? 0.8,
                'processing_time' => 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store predictions', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract all recommendations from predictions
     */
    protected function extractAllRecommendations(array $predictions): array
    {
        $allRecommendations = [];
        
        foreach ($predictions as $period => $prediction) {
            if (isset($prediction['recommendations'])) {
                foreach ($prediction['recommendations'] as $rec) {
                    $rec['period'] = $period;
                    $allRecommendations[] = $rec;
                }
            }
        }
        
        return $allRecommendations;
    }

    /**
     * Generate fallback statistical predictions
     */
    protected function generateStatisticalPredictions(array $trends): array
    {
        $predictions = [];
        
        foreach ($this->forecastPeriods as $period => $days) {
            $resourcePredictions = [];
            
            foreach ($trends as $metric => $trend) {
                $currentValue = $trend['current_value'] ?? 50;
                $rate = $trend['rate'] ?? 0;
                $hoursInPeriod = $days * 24;
                
                $predictedValue = max(0, min(100, $currentValue + ($rate * $hoursInPeriod)));
                
                $resourcePredictions[$metric] = [
                    'predicted_value' => round($predictedValue, 2),
                    'confidence' => 0.6, // Lower confidence for statistical predictions
                    'trend_direction' => $trend['direction'] ?? 'stable',
                    'volatility' => $trend['volatility'] ?? 0,
                ];
            }
            
            $predictions[$period] = [
                'period' => $period,
                'days_ahead' => $days,
                'resource_predictions' => $resourcePredictions,
                'alerts' => $this->generatePredictiveAlerts($resourcePredictions),
                'recommendations' => $this->generatePredictiveRecommendations($resourcePredictions, $period),
                'confidence_score' => 0.6,
                'insights' => ['method' => 'statistical_fallback'],
            ];
        }
        
        return $predictions;
    }

    /**
     * Create empty prediction result
     */
    protected function createEmptyPrediction(string $reason): array
    {
        return [
            'predictions' => [],
            'trends' => [],
            'confidence' => ['overall' => 0],
            'error' => $reason,
            'generated_at' => now(),
        ];
    }

    /**
     * Helper functions for calculations
     */
    protected function calculateSlope(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) return 0;
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return 0;
        
        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    protected function calculateCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) return 0;
        
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        
        $numerator = 0;
        $sumSqX = 0;
        $sumSqY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $deltaX = $x[$i] - $meanX;
            $deltaY = $y[$i] - $meanY;
            $numerator += $deltaX * $deltaY;
            $sumSqX += $deltaX * $deltaX;
            $sumSqY += $deltaY * $deltaY;
        }
        
        $denominator = sqrt($sumSqX * $sumSqY);
        if ($denominator == 0) return 0;
        
        return $numerator / $denominator;
    }

    protected function calculateVolatility(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;
        
        $mean = array_sum($values) / $n;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return sqrt($variance / ($n - 1));
    }

    protected function getPeriodInDays(string $period): int
    {
        $periods = [
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            '180_days' => 180,
        ];
        
        return $periods[$period] ?? 30;
    }

    /**
     * Get cached predictions for a server
     */
    public function getCachedPredictions(Server $server): ?array
    {
        return Cache::get("ai_predictions_{$server->id}");
    }

    /**
     * Cache predictions
     */
    public function cachePredictions(Server $server, array $predictions): void
    {
        Cache::put("ai_predictions_{$server->id}", $predictions, now()->addHours(6));
    }
}