<?php

namespace PterodactylAddons\OllamaAi\Services;

use Pterodactyl\Models\Server;
use PterodactylAddons\OllamaAi\Models\AiInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RealTimeMonitoringService
{
    protected $alertThresholds = [
        'cpu_usage' => 80,
        'memory_usage' => 85,
        'disk_usage' => 90,
        'response_time' => 5000, // 5 seconds
        'error_rate' => 10, // errors per minute
    ];

    protected $monitoringInterval = 300; // 5 minutes

    /**
     * Start real-time monitoring for a server
     */
    public function startMonitoring(Server $server): bool
    {
        try {
            // Store monitoring state
            Cache::put("ai_monitoring_{$server->id}", [
                'status' => 'active',
                'started_at' => now(),
                'last_check' => now(),
                'alert_count' => 0,
            ], now()->addDays(7));

            Log::info("Started AI monitoring for server {$server->name}", [
                'server_id' => $server->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to start monitoring for server {$server->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stop real-time monitoring for a server
     */
    public function stopMonitoring(Server $server): bool
    {
        try {
            Cache::forget("ai_monitoring_{$server->id}");
            
            Log::info("Stopped AI monitoring for server {$server->name}", [
                'server_id' => $server->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to stop monitoring for server {$server->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check server metrics and trigger alerts if needed
     */
    public function checkServerMetrics(Server $server): array
    {
        $metrics = $this->gatherServerMetrics($server);
        $alerts = [];

        // Check each metric against thresholds
        foreach ($metrics as $metric => $value) {
            if ($this->shouldTriggerAlert($metric, $value)) {
                $alert = $this->createAlert($server, $metric, $value);
                $alerts[] = $alert;
                $this->storeAlert($alert);
            }
        }

        // Update monitoring state
        $this->updateMonitoringState($server, $metrics, $alerts);

        return [
            'server_id' => $server->id,
            'metrics' => $metrics,
            'alerts' => $alerts,
            'checked_at' => now(),
        ];
    }

    /**
     * Gather server metrics (simulated)
     */
    protected function gatherServerMetrics(Server $server): array
    {
        // In a real implementation, you'd fetch actual metrics from Wings API
        // For now, we'll simulate some realistic metrics
        
        $baselineHealthy = [
            'cpu_usage' => rand(20, 45),
            'memory_usage' => rand(40, 65),
            'disk_usage' => rand(30, 55),
            'response_time' => rand(100, 500),
            'error_rate' => rand(0, 2),
            'network_in' => rand(100, 1000),
            'network_out' => rand(50, 800),
            'active_connections' => rand(5, 25),
        ];

        // Sometimes simulate issues for testing
        if (rand(1, 10) === 1) { // 10% chance of issues
            $issueType = array_rand($this->alertThresholds);
            switch ($issueType) {
                case 'cpu_usage':
                    $baselineHealthy['cpu_usage'] = rand(85, 98);
                    break;
                case 'memory_usage':
                    $baselineHealthy['memory_usage'] = rand(88, 95);
                    break;
                case 'disk_usage':
                    $baselineHealthy['disk_usage'] = rand(92, 98);
                    break;
                case 'response_time':
                    $baselineHealthy['response_time'] = rand(6000, 15000);
                    break;
                case 'error_rate':
                    $baselineHealthy['error_rate'] = rand(12, 25);
                    break;
            }
        }

        return $baselineHealthy;
    }

    /**
     * Check if an alert should be triggered
     */
    protected function shouldTriggerAlert(string $metric, $value): bool
    {
        if (!isset($this->alertThresholds[$metric])) {
            return false;
        }

        $threshold = $this->alertThresholds[$metric];
        
        // Check if value exceeds threshold
        if ($value > $threshold) {
            // Additional check: has this alert been triggered recently?
            $recentAlerts = $this->getRecentAlerts($metric);
            
            // Don't spam alerts - only trigger if no similar alert in last 10 minutes
            if ($recentAlerts < 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create an alert object
     */
    protected function createAlert(Server $server, string $metric, $value): array
    {
        $severity = $this->determineSeverity($metric, $value);
        $message = $this->generateAlertMessage($metric, $value);

        return [
            'server_id' => $server->id,
            'server_name' => $server->name,
            'metric' => $metric,
            'value' => $value,
            'threshold' => $this->alertThresholds[$metric] ?? 0,
            'severity' => $severity,
            'message' => $message,
            'timestamp' => now(),
        ];
    }

    /**
     * Determine alert severity based on how much threshold is exceeded
     */
    protected function determineSeverity(string $metric, $value): string
    {
        $threshold = $this->alertThresholds[$metric];
        $exceedPercentage = (($value - $threshold) / $threshold) * 100;

        if ($exceedPercentage >= 50) {
            return 'critical';
        } elseif ($exceedPercentage >= 20) {
            return 'high';
        } elseif ($exceedPercentage >= 10) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Generate human-readable alert message
     */
    protected function generateAlertMessage(string $metric, $value): string
    {
        $threshold = $this->alertThresholds[$metric];

        switch ($metric) {
            case 'cpu_usage':
                return "High CPU usage detected: {$value}% (threshold: {$threshold}%)";
            case 'memory_usage':
                return "High memory usage detected: {$value}% (threshold: {$threshold}%)";
            case 'disk_usage':
                return "High disk usage detected: {$value}% (threshold: {$threshold}%)";
            case 'response_time':
                return "Slow response time detected: {$value}ms (threshold: {$threshold}ms)";
            case 'error_rate':
                return "High error rate detected: {$value} errors/min (threshold: {$threshold}/min)";
            default:
                return "Alert triggered for {$metric}: {$value}";
        }
    }

    /**
     * Store alert in database
     */
    protected function storeAlert(array $alert): void
    {
        try {
            AiInsight::create([
                'context_type' => 'server',
                'context_id' => $alert['server_id'],
                'insight_type' => 'alert',
                'category' => 'performance',
                'title' => "Real-time Alert: " . ucfirst(str_replace('_', ' ', $alert['metric'])),
                'description' => $alert['message'],
                'severity' => $alert['severity'],
                'confidence_score' => 0.95,
                'metadata' => json_encode([
                    'metric' => $alert['metric'],
                    'value' => $alert['value'],
                    'threshold' => $alert['threshold'],
                    'source' => 'real_time_monitoring',
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to store real-time alert", [
                'server_id' => $alert['server_id'],
                'metric' => $alert['metric'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update monitoring state in cache
     */
    protected function updateMonitoringState(Server $server, array $metrics, array $alerts): void
    {
        $currentState = Cache::get("ai_monitoring_{$server->id}", []);
        
        $updatedState = array_merge($currentState, [
            'last_check' => now(),
            'last_metrics' => $metrics,
            'alert_count' => ($currentState['alert_count'] ?? 0) + count($alerts),
            'health_score' => $this->calculateHealthScore($metrics),
        ]);

        Cache::put("ai_monitoring_{$server->id}", $updatedState, now()->addDays(7));
    }

    /**
     * Calculate health score based on metrics
     */
    protected function calculateHealthScore(array $metrics): int
    {
        $score = 100;
        
        // Deduct points based on metrics
        if (isset($metrics['cpu_usage']) && $metrics['cpu_usage'] > 70) {
            $score -= ($metrics['cpu_usage'] - 70) * 0.5;
        }
        
        if (isset($metrics['memory_usage']) && $metrics['memory_usage'] > 75) {
            $score -= ($metrics['memory_usage'] - 75) * 0.4;
        }
        
        if (isset($metrics['disk_usage']) && $metrics['disk_usage'] > 80) {
            $score -= ($metrics['disk_usage'] - 80) * 0.3;
        }
        
        if (isset($metrics['response_time']) && $metrics['response_time'] > 1000) {
            $score -= ($metrics['response_time'] - 1000) / 100;
        }
        
        if (isset($metrics['error_rate']) && $metrics['error_rate'] > 5) {
            $score -= ($metrics['error_rate'] - 5) * 2;
        }

        return max(0, min(100, intval($score)));
    }

    /**
     * Get recent alerts for a metric
     */
    protected function getRecentAlerts(string $metric): int
    {
        return AiInsight::where('insight_type', 'alert')
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->whereJsonContains('metadata->metric', $metric)
            ->count();
    }

    /**
     * Get monitoring status for a server
     */
    public function getMonitoringStatus(Server $server): array
    {
        $state = Cache::get("ai_monitoring_{$server->id}");
        
        if (!$state) {
            return [
                'status' => 'inactive',
                'message' => 'Monitoring not active',
            ];
        }

        return [
            'status' => $state['status'] ?? 'unknown',
            'started_at' => $state['started_at'] ?? null,
            'last_check' => $state['last_check'] ?? null,
            'alert_count' => $state['alert_count'] ?? 0,
            'health_score' => $state['health_score'] ?? 0,
            'last_metrics' => $state['last_metrics'] ?? [],
        ];
    }

    /**
     * Get active monitoring for all servers
     */
    public function getAllMonitoringStatuses(): array
    {
        // In a real implementation, you'd store monitoring configs in database
        // For now, we'll check cache for active monitoring
        $servers = Server::all();
        $statuses = [];

        foreach ($servers as $server) {
            $status = $this->getMonitoringStatus($server);
            if ($status['status'] === 'active') {
                $statuses[] = [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'status' => $status,
                ];
            }
        }

        return $statuses;
    }

    /**
     * Get real-time alerts for dashboard
     */
    public function getDashboardAlerts(int $limit = 10): array
    {
        return AiInsight::where('insight_type', 'alert')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($alert) {
                $metadata = json_decode($alert->metadata, true) ?? [];
                
                return [
                    'id' => $alert->id,
                    'server_id' => $alert->context_id,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'severity' => $alert->severity,
                    'metric' => $metadata['metric'] ?? 'unknown',
                    'value' => $metadata['value'] ?? 0,
                    'threshold' => $metadata['threshold'] ?? 0,
                    'created_at' => $alert->created_at->diffForHumans(),
                ];
            })->toArray();
    }

    /**
     * Configure alert thresholds
     */
    public function setAlertThresholds(array $thresholds): bool
    {
        foreach ($thresholds as $metric => $threshold) {
            if (isset($this->alertThresholds[$metric])) {
                $this->alertThresholds[$metric] = $threshold;
            }
        }

        // In a real implementation, you'd store these in database/config
        Cache::put('ai_monitoring_thresholds', $this->alertThresholds, now()->addDays(30));

        return true;
    }

    /**
     * Get current alert thresholds
     */
    public function getAlertThresholds(): array
    {
        return Cache::get('ai_monitoring_thresholds', $this->alertThresholds);
    }

    /**
     * Simulate running monitoring checks (called by scheduler)
     */
    public function runMonitoringChecks(): array
    {
        $results = [];
        $activeMonitoring = $this->getAllMonitoringStatuses();

        foreach ($activeMonitoring as $monitoring) {
            try {
                $server = Server::find($monitoring['server_id']);
                if ($server) {
                    $checkResult = $this->checkServerMetrics($server);
                    $results[] = $checkResult;
                }
            } catch (\Exception $e) {
                Log::error("Monitoring check failed for server {$monitoring['server_id']}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get monitoring statistics
     */
    public function getMonitoringStats(): array
    {
        $totalActiveMonitoring = count($this->getAllMonitoringStatuses());
        $alertsLast24h = AiInsight::where('insight_type', 'alert')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
        $criticalAlerts = AiInsight::where('insight_type', 'alert')
            ->where('severity', 'critical')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();

        return [
            'active_monitoring' => $totalActiveMonitoring,
            'alerts_24h' => $alertsLast24h,
            'critical_alerts_24h' => $criticalAlerts,
            'avg_health_score' => $this->getAverageHealthScore(),
            'alert_breakdown' => $this->getAlertBreakdown(),
        ];
    }

    /**
     * Get average health score across monitored servers
     */
    protected function getAverageHealthScore(): float
    {
        $statuses = $this->getAllMonitoringStatuses();
        
        if (empty($statuses)) {
            return 0.0;
        }

        $totalScore = 0;
        $count = 0;

        foreach ($statuses as $status) {
            if (isset($status['status']['health_score'])) {
                $totalScore += $status['status']['health_score'];
                $count++;
            }
        }

        return $count > 0 ? round($totalScore / $count, 1) : 0.0;
    }

    /**
     * Get alert breakdown by severity
     */
    protected function getAlertBreakdown(): array
    {
        return AiInsight::where('insight_type', 'alert')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }
}