<?php

namespace PterodactylAddons\OllamaAi\Services;

use PterodactylAddons\OllamaAi\Models\AiInsight;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use Pterodactyl\Models\Server;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LogAnalysisService
{
    protected $ollamaService;
    protected $maxLogSize = 50000; // 50KB max for analysis
    protected $criticalKeywords = [
        'error', 'exception', 'failed', 'timeout', 'crash', 'fatal', 
        'panic', 'critical', 'emergency', 'out of memory', 'connection refused'
    ];
    
    protected $performanceKeywords = [
        'slow', 'lag', 'delay', 'high cpu', 'memory usage', 'disk full',
        'network', 'bandwidth', 'latency', 'bottleneck'
    ];

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * Analyze server logs using AI
     */
    public function analyzeLogs(Server $server, array $options = []): array
    {
        try {
            // Extract logs from various sources
            $logData = $this->extractServerLogs($server, $options);
            
            if (empty($logData)) {
                return $this->createAnalysisResult('No logs found for analysis', [], []);
            }

            // Pre-process logs
            $processedLogs = $this->preprocessLogs($logData);
            
            // Perform AI analysis
            $analysis = $this->performAiLogAnalysis($server, $processedLogs);
            
            // Store insights
            $this->storeLogInsights($server, $analysis);
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error('Log analysis failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->createAnalysisResult(
                'Log analysis failed: ' . $e->getMessage(),
                [],
                []
            );
        }
    }

    /**
     * Extract logs from server
     */
    protected function extractServerLogs(Server $server, array $options = []): array
    {
        $logs = [];
        $timeRange = $options['time_range'] ?? '1h'; // 1 hour by default
        
        // Simulate log extraction - in real implementation, you'd connect to Wings API
        // or read from actual log files
        $logs['console'] = $this->getConsoleLogs($server, $timeRange);
        $logs['crash'] = $this->getCrashLogs($server, $timeRange);
        $logs['system'] = $this->getSystemLogs($server, $timeRange);
        
        return array_filter($logs); // Remove empty log sources
    }

    /**
     * Get console logs (simulated)
     */
    protected function getConsoleLogs(Server $server, string $timeRange): ?string
    {
        // In real implementation, fetch from Wings API or log files
        // For now, we'll simulate some sample logs
        $sampleLogs = [
            '[INFO] Server starting up...',
            '[WARN] High memory usage detected: 85%',
            '[ERROR] Connection timeout to database after 30s',
            '[INFO] Player connected: TestUser',
            '[ERROR] Failed to load plugin: InvalidPlugin',
            '[INFO] Autosave completed in 250ms',
            '[WARN] Lag spike detected: 150ms',
            '[ERROR] Out of memory error in chunk generation',
            '[INFO] Server stopped gracefully',
        ];
        
        return implode("\n", $sampleLogs);
    }

    /**
     * Get crash logs (simulated)
     */
    protected function getCrashLogs(Server $server, string $timeRange): ?string
    {
        // Simulate crash log content
        return null; // No crashes in this simulation
    }

    /**
     * Get system logs (simulated)
     */
    protected function getSystemLogs(Server $server, string $timeRange): ?string
    {
        $sampleSystemLogs = [
            'CPU usage: 45%',
            'Memory usage: 1250MB / 2048MB',
            'Disk I/O: Read 25MB/s, Write 15MB/s',
            'Network: Sent 1.2MB, Received 850KB',
            'Active connections: 15',
        ];
        
        return implode("\n", $sampleSystemLogs);
    }

    /**
     * Preprocess logs before AI analysis
     */
    protected function preprocessLogs(array $logData): array
    {
        $processed = [];
        
        foreach ($logData as $source => $content) {
            if (!$content) continue;
            
            // Truncate if too large
            if (strlen($content) > $this->maxLogSize) {
                $content = substr($content, -$this->maxLogSize);
            }
            
            // Extract timestamps and normalize format
            $lines = explode("\n", $content);
            $normalizedLines = [];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    $normalizedLines[] = $this->normalizeLine($line);
                }
            }
            
            $processed[$source] = [
                'content' => implode("\n", $normalizedLines),
                'line_count' => count($normalizedLines),
                'errors' => $this->countKeywords($normalizedLines, $this->criticalKeywords),
                'warnings' => $this->countKeywords($normalizedLines, ['warn', 'warning']),
                'performance_issues' => $this->countKeywords($normalizedLines, $this->performanceKeywords),
            ];
        }
        
        return $processed;
    }

    /**
     * Normalize log line format
     */
    protected function normalizeLine(string $line): string
    {
        // Remove excessive whitespace
        $line = preg_replace('/\s+/', ' ', $line);
        
        // Extract timestamp if present
        if (preg_match('/^\[?(\d{4}-\d{2}-\d{2}[\sT]\d{2}:\d{2}:\d{2}.*?)\]?\s*(.*)$/', $line, $matches)) {
            return '[' . trim($matches[1], '[]') . '] ' . $matches[2];
        }
        
        return $line;
    }

    /**
     * Count keyword occurrences
     */
    protected function countKeywords(array $lines, array $keywords): int
    {
        $count = 0;
        $pattern = '/\b(' . implode('|', array_map('preg_quote', $keywords)) . ')\b/i';
        
        foreach ($lines as $line) {
            if (preg_match_all($pattern, $line)) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Perform AI analysis on processed logs
     */
    protected function performAiLogAnalysis(Server $server, array $processedLogs): array
    {
        $prompt = $this->buildLogAnalysisPrompt($server, $processedLogs);
        
        $startTime = microtime(true);
        
        try {
            $response = $this->ollamaService->analyzeData($prompt, [
                'context' => 'log_analysis',
                'server_id' => $server->id,
            ]);
            
            $processingTime = round((microtime(true) - $startTime) * 1000);
            
            // Parse the response
            $insights = $this->parseLogAnalysisResponse($response);
            $issues = $this->extractLogIssues($response);
            $recommendations = $this->extractLogRecommendations($response);
            
            return $this->createAnalysisResult(
                $response,
                $insights,
                $recommendations,
                $issues,
                $processingTime
            );
            
        } catch (\Exception $e) {
            return $this->createAnalysisResult(
                'AI analysis failed: ' . $e->getMessage(),
                [],
                []
            );
        }
    }

    /**
     * Build prompt for log analysis
     */
    protected function buildLogAnalysisPrompt(Server $server, array $processedLogs): string
    {
        $logSummary = '';
        $totalErrors = 0;
        $totalWarnings = 0;
        
        foreach ($processedLogs as $source => $data) {
            $logSummary .= "\n=== {$source} logs ===\n";
            $logSummary .= "Lines: {$data['line_count']}\n";
            $logSummary .= "Errors: {$data['errors']}\n";
            $logSummary .= "Warnings: {$data['warnings']}\n";
            $logSummary .= "Performance Issues: {$data['performance_issues']}\n";
            $logSummary .= "\nContent:\n{$data['content']}\n";
            
            $totalErrors += $data['errors'];
            $totalWarnings += $data['warnings'];
        }

        return "Analyze the following server logs for server '{$server->name}' (ID: {$server->id}):

{$logSummary}

Summary:
- Total Errors: {$totalErrors}
- Total Warnings: {$totalWarnings}

Please provide:

1. **Critical Issues** (score 1-10): List the most serious problems found
2. **Performance Analysis**: Identify performance bottlenecks and resource issues
3. **Error Patterns**: Common error types and their potential causes
4. **Security Concerns**: Any security-related issues in the logs
5. **Recommendations**: Specific actionable steps to resolve issues
6. **Health Score** (0-100): Overall server health based on log analysis

Focus on actionable insights that can help improve server stability and performance.";
    }

    /**
     * Parse AI response for log analysis
     */
    protected function parseLogAnalysisResponse(string $response): array
    {
        $insights = [
            'analysis_type' => 'log_analysis',
            'timestamp' => now()->toISOString(),
        ];
        
        // Extract health score
        if (preg_match('/health.*?score.*?(\d+)/i', $response, $matches)) {
            $insights['health_score'] = intval($matches[1]);
        }
        
        // Extract critical issues count
        if (preg_match('/critical.*?issues.*?(\d+)/i', $response, $matches)) {
            $insights['critical_issues'] = intval($matches[1]);
        }
        
        // Check for performance issues
        if (preg_match_all('/performance|memory|cpu|disk|network/i', $response, $matches)) {
            $insights['performance_mentions'] = count($matches[0]);
        }
        
        // Check for security issues
        if (preg_match_all('/security|vulnerability|breach|attack/i', $response, $matches)) {
            $insights['security_mentions'] = count($matches[0]);
        }
        
        return $insights;
    }

    /**
     * Extract specific issues from AI response
     */
    protected function extractLogIssues(string $response): array
    {
        $issues = [];
        
        // Look for numbered lists or bullet points indicating issues
        if (preg_match_all('/(?:^\d+\.|^-|^\*)\s*(.+?)(?=\n|$)/m', $response, $matches)) {
            foreach ($matches[1] as $issue) {
                $issue = trim($issue);
                if (strlen($issue) > 10) { // Filter out very short matches
                    $severity = $this->determineSeverity($issue);
                    $issues[] = [
                        'description' => $issue,
                        'severity' => $severity,
                        'category' => $this->categorizeIssue($issue),
                    ];
                }
            }
        }
        
        return array_slice($issues, 0, 10); // Limit to top 10 issues
    }

    /**
     * Extract recommendations from AI response
     */
    protected function extractLogRecommendations(string $response): array
    {
        $recommendations = [];
        
        // Look for recommendation sections
        if (preg_match('/recommendations?:?\s*(.*?)(?=\n\n|\z)/is', $response, $matches)) {
            $recommendationText = $matches[1];
            
            if (preg_match_all('/(?:^\d+\.|^-|^\*)\s*(.+?)(?=\n|$)/m', $recommendationText, $recMatches)) {
                foreach ($recMatches[1] as $rec) {
                    $rec = trim($rec);
                    if (strlen($rec) > 15) {
                        $recommendations[] = [
                            'action' => $rec,
                            'priority' => $this->determinePriority($rec),
                            'category' => $this->categorizeRecommendation($rec),
                        ];
                    }
                }
            }
        }
        
        return array_slice($recommendations, 0, 8); // Limit to top 8 recommendations
    }

    /**
     * Determine issue severity
     */
    protected function determineSeverity(string $issue): string
    {
        $issue = strtolower($issue);
        
        if (preg_match('/\b(critical|fatal|emergency|crash|down)\b/', $issue)) {
            return 'critical';
        } elseif (preg_match('/\b(error|failed|timeout|exception)\b/', $issue)) {
            return 'high';
        } elseif (preg_match('/\b(warning|slow|lag|high)\b/', $issue)) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Categorize issue type
     */
    protected function categorizeIssue(string $issue): string
    {
        $issue = strtolower($issue);
        
        if (preg_match('/\b(memory|cpu|disk|resource)\b/', $issue)) {
            return 'performance';
        } elseif (preg_match('/\b(connection|network|timeout)\b/', $issue)) {
            return 'connectivity';
        } elseif (preg_match('/\b(security|vulnerability|breach)\b/', $issue)) {
            return 'security';
        } elseif (preg_match('/\b(plugin|mod|config)\b/', $issue)) {
            return 'configuration';
        } else {
            return 'general';
        }
    }

    /**
     * Determine recommendation priority
     */
    protected function determinePriority(string $recommendation): string
    {
        $rec = strtolower($recommendation);
        
        if (preg_match('/\b(urgent|immediately|critical|fix|restart)\b/', $rec)) {
            return 'high';
        } elseif (preg_match('/\b(should|recommend|consider|update)\b/', $rec)) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Categorize recommendation type
     */
    protected function categorizeRecommendation(string $recommendation): string
    {
        $rec = strtolower($recommendation);
        
        if (preg_match('/\b(memory|cpu|resource|allocation)\b/', $rec)) {
            return 'resources';
        } elseif (preg_match('/\b(config|setting|parameter)\b/', $rec)) {
            return 'configuration';
        } elseif (preg_match('/\b(update|upgrade|install|patch)\b/', $rec)) {
            return 'maintenance';
        } elseif (preg_match('/\b(monitor|alert|watch|check)\b/', $rec)) {
            return 'monitoring';
        } else {
            return 'general';
        }
    }

    /**
     * Store insights in database
     */
    protected function storeLogInsights(Server $server, array $analysis): void
    {
        try {
            // Store main analysis result
            AiAnalysisResult::create([
                'context_type' => 'server',
                'context_id' => $server->id,
                'analysis_type' => 'log_analysis',
                'input_data' => json_encode(['server_id' => $server->id]),
                'ai_response' => $analysis['response'],
                'insights' => json_encode($analysis['insights']),
                'recommendations' => json_encode($analysis['recommendations']),
                'confidence_score' => 0.85,
                'processing_time' => $analysis['processing_time'] ?? 0,
            ]);
            
            // Store individual insights
            foreach ($analysis['issues'] as $issue) {
                AiInsight::create([
                    'context_type' => 'server',
                    'context_id' => $server->id,
                    'insight_type' => 'issue',
                    'category' => $issue['category'],
                    'title' => substr($issue['description'], 0, 100),
                    'description' => $issue['description'],
                    'severity' => $issue['severity'],
                    'confidence_score' => 0.8,
                    'metadata' => json_encode(['source' => 'log_analysis']),
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to store log analysis insights', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create standardized analysis result
     */
    protected function createAnalysisResult(
        string $response,
        array $insights,
        array $recommendations,
        array $issues = [],
        int $processingTime = 0
    ): array {
        return [
            'response' => $response,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'issues' => $issues,
            'processing_time' => $processingTime,
            'analyzed_at' => now(),
        ];
    }

    /**
     * Get log analysis history for a server
     */
    public function getAnalysisHistory(Server $server, int $limit = 10): array
    {
        $results = AiAnalysisResult::where('context_type', 'server')
            ->where('context_id', $server->id)
            ->where('analysis_type', 'log_analysis')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
            
        return $results->map(function ($result) {
            return [
                'id' => $result->id,
                'analyzed_at' => $result->created_at->format('Y-m-d H:i:s'),
                'health_score' => $this->extractHealthScoreFromInsights($result->insights),
                'issue_count' => $this->countIssuesFromInsights($result->insights),
                'processing_time' => $result->processing_time,
            ];
        })->toArray();
    }

    /**
     * Extract health score from insights JSON
     */
    protected function extractHealthScoreFromInsights(string $insightsJson): int
    {
        $insights = json_decode($insightsJson, true) ?? [];
        return $insights['health_score'] ?? 0;
    }

    /**
     * Count issues from insights JSON
     */
    protected function countIssuesFromInsights(string $insightsJson): int
    {
        $insights = json_decode($insightsJson, true) ?? [];
        return $insights['critical_issues'] ?? 0;
    }

    /**
     * Schedule regular log analysis
     */
    public function scheduleAnalysis(Server $server, string $frequency = 'hourly'): bool
    {
        // In a real implementation, you'd set up a scheduled job
        // For now, just return success
        return true;
    }

    /**
     * Get real-time log alerts
     */
    public function getLogAlerts(Server $server): array
    {
        $insights = AiInsight::where('context_type', 'server')
            ->where('context_id', $server->id)
            ->where('severity', 'critical')
            ->where('created_at', '>=', Carbon::now()->subHours(1))
            ->orderBy('created_at', 'desc')
            ->get();
            
        return $insights->map(function ($insight) {
            return [
                'id' => $insight->id,
                'title' => $insight->title,
                'description' => $insight->description,
                'severity' => $insight->severity,
                'category' => $insight->category,
                'created_at' => $insight->created_at->diffForHumans(),
            ];
        })->toArray();
    }
}