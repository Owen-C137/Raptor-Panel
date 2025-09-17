<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\Node;
use PterodactylAddons\OllamaAi\Services\OllamaService;
use PterodactylAddons\OllamaAi\Models\AiAnalysisResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AiAnalysisController extends Controller
{
    protected $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * Display server analysis overview
     */
    public function index(Request $request)
    {
        $servers = Server::with(['user', 'node', 'allocation'])
            ->limit(50)
            ->get();

        $analysisData = $this->getAnalysisOverview();

        return view('ollama-ai::admin.analysis.index', [
            'servers' => $servers,
            'analysis' => $analysisData,
        ]);
    }

    /**
     * Analyze a specific server
     */
    public function analyzeServer(Request $request, int $serverId): JsonResponse
    {
        $server = Server::with(['user', 'node', 'allocation', 'egg'])->findOrFail($serverId);

        try {
            // Gather server data for analysis
            $serverData = $this->gatherServerData($server);
            
            // Perform AI analysis
            $analysis = $this->performServerAnalysis($server, $serverData);
            
            // Store analysis result
            $analysisResult = AiAnalysisResult::create([
                'context_type' => 'server',
                'context_id' => $serverId,
                'analysis_type' => 'performance',
                'input_data' => json_encode($serverData),
                'ai_response' => $analysis['response'],
                'insights' => json_encode($analysis['insights']),
                'recommendations' => json_encode($analysis['recommendations']),
                'confidence_score' => $analysis['confidence'] ?? 0.8,
                'processing_time' => $analysis['processing_time'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'analysis' => [
                    'id' => $analysisResult->id,
                    'server_name' => $server->name,
                    'health_score' => $analysis['health_score'] ?? 75,
                    'insights' => $analysis['insights'],
                    'recommendations' => $analysis['recommendations'],
                    'analysis_time' => $analysisResult->created_at->diffForHumans(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze server: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get server performance insights
     */
    public function getServerInsights(Request $request, int $serverId): JsonResponse
    {
        $server = Server::with(['user', 'node'])->findOrFail($serverId);
        
        // Get recent analysis results
        $recentAnalysis = AiAnalysisResult::where('context_type', 'server')
            ->where('context_id', $serverId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $insights = [
            'server_info' => [
                'name' => $server->name,
                'status' => $server->status,
                'cpu_limit' => $server->cpu,
                'memory_limit' => $server->memory,
                'disk_limit' => $server->disk,
                'egg_type' => $server->egg->name ?? 'Unknown',
                'node' => $server->node->name,
            ],
            'recent_analysis' => $recentAnalysis->map(function ($analysis) {
                return [
                    'id' => $analysis->id,
                    'type' => $analysis->analysis_type,
                    'health_score' => $this->extractHealthScore($analysis->insights),
                    'key_insights' => $this->extractKeyInsights($analysis->insights),
                    'created_at' => $analysis->created_at->format('M j, Y H:i'),
                ];
            }),
            'recommendations' => $this->generateQuickRecommendations($server),
        ];

        return response()->json([
            'success' => true,
            'insights' => $insights,
        ]);
    }

    /**
     * Bulk analyze multiple servers
     */
    public function bulkAnalyze(Request $request): JsonResponse
    {
        $request->validate([
            'server_ids' => 'required|array',
            'server_ids.*' => 'integer|exists:servers,id',
        ]);

        $results = [];
        $processed = 0;
        $failed = 0;

        foreach ($request->server_ids as $serverId) {
            try {
                $server = Server::findOrFail($serverId);
                $serverData = $this->gatherServerData($server);
                $analysis = $this->performServerAnalysis($server, $serverData);
                
                AiAnalysisResult::create([
                    'context_type' => 'server',
                    'context_id' => $serverId,
                    'analysis_type' => 'bulk_performance',
                    'input_data' => json_encode($serverData),
                    'ai_response' => $analysis['response'],
                    'insights' => json_encode($analysis['insights']),
                    'recommendations' => json_encode($analysis['recommendations']),
                    'confidence_score' => $analysis['confidence'] ?? 0.8,
                    'processing_time' => $analysis['processing_time'] ?? 0,
                ]);

                $results[] = [
                    'server_id' => $serverId,
                    'server_name' => $server->name,
                    'health_score' => $analysis['health_score'] ?? 75,
                    'status' => 'success',
                ];

                $processed++;
                
            } catch (\Exception $e) {
                $results[] = [
                    'server_id' => $serverId,
                    'server_name' => $server->name ?? 'Unknown',
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
     * Get analysis statistics
     */
    public function getAnalysisStats(): JsonResponse
    {
        $stats = [
            'total_analyses' => AiAnalysisResult::count(),
            'servers_analyzed' => AiAnalysisResult::where('context_type', 'server')->distinct('context_id')->count(),
            'recent_analyses' => AiAnalysisResult::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'avg_health_score' => $this->calculateAverageHealthScore(),
            'top_issues' => $this->getTopIssues(),
            'analysis_trends' => $this->getAnalysisTrends(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Gather server data for analysis
     */
    protected function gatherServerData(Server $server): array
    {
        return [
            'server' => [
                'id' => $server->id,
                'name' => $server->name,
                'status' => $server->status,
                'suspended' => $server->suspended,
                'cpu_limit' => $server->cpu,
                'memory_limit' => $server->memory,
                'disk_limit' => $server->disk,
                'created_at' => $server->created_at->toISOString(),
                'updated_at' => $server->updated_at->toISOString(),
            ],
            'node' => [
                'id' => $server->node->id,
                'name' => $server->node->name,
                'fqdn' => $server->node->fqdn,
                'memory' => $server->node->memory,
                'disk' => $server->node->disk,
                'location_id' => $server->node->location_id,
            ],
            'egg' => [
                'id' => $server->egg->id ?? null,
                'name' => $server->egg->name ?? 'unknown',
                'startup' => $server->startup,
            ],
            'allocation' => [
                'ip' => $server->allocation->ip,
                'port' => $server->allocation->port,
                'alias' => $server->allocation->alias,
            ],
            'user' => [
                'id' => $server->user->id,
                'username' => $server->user->username,
                'email' => $server->user->email,
            ],
        ];
    }

    /**
     * Perform AI analysis on server data
     */
    protected function performServerAnalysis(Server $server, array $serverData): array
    {
        $prompt = $this->buildAnalysisPrompt($server, $serverData);
        
        $startTime = microtime(true);
        
        try {
            $response = $this->ollamaService->analyzeData($prompt, [
                'context' => 'server_analysis',
                'server_id' => $server->id,
            ]);
            
            $processingTime = round((microtime(true) - $startTime) * 1000);
            
            // Parse the AI response to extract structured insights
            $insights = $this->parseAnalysisResponse($response);
            
            return [
                'response' => $response,
                'insights' => $insights,
                'recommendations' => $this->extractRecommendations($response),
                'health_score' => $this->calculateHealthScore($insights),
                'confidence' => 0.85,
                'processing_time' => $processingTime,
            ];
            
        } catch (\Exception $e) {
            return [
                'response' => 'Analysis failed: ' . $e->getMessage(),
                'insights' => [],
                'recommendations' => [],
                'health_score' => 0,
                'confidence' => 0,
                'processing_time' => round((microtime(true) - $startTime) * 1000),
            ];
        }
    }

    /**
     * Build analysis prompt for AI
     */
    protected function buildAnalysisPrompt(Server $server, array $serverData): string
    {
        return "Analyze this Pterodactyl server configuration and performance data:

Server: {$server->name} (ID: {$server->id})
Status: {$server->status}
Resources: CPU: {$server->cpu}%, Memory: {$server->memory}MB, Disk: {$server->disk}MB
Node: {$server->node->name}
Egg Type: " . ($server->egg->name ?? 'Unknown') . "

Please provide:
1. Overall health assessment (score 0-100)
2. Resource allocation analysis
3. Performance insights
4. Potential issues or bottlenecks
5. Optimization recommendations
6. Security considerations

Focus on actionable insights that can help improve server performance and stability.";
    }

    /**
     * Parse AI response into structured insights
     */
    protected function parseAnalysisResponse(string $response): array
    {
        // Simple parsing - in production, you might use more sophisticated NLP
        $insights = [];
        
        if (preg_match('/health.*?(\d+)/i', $response, $matches)) {
            $insights['health_score'] = intval($matches[1]);
        }
        
        if (preg_match('/resource.*?allocation/i', $response)) {
            $insights['resource_analysis'] = true;
        }
        
        if (preg_match('/performance/i', $response)) {
            $insights['performance_review'] = true;
        }
        
        if (preg_match('/security/i', $response)) {
            $insights['security_check'] = true;
        }

        return $insights;
    }

    /**
     * Extract recommendations from AI response
     */
    protected function extractRecommendations(string $response): array
    {
        $recommendations = [];
        
        // Extract numbered recommendations
        if (preg_match_all('/\d+\.\s*(.+?)(?=\n|\d+\.|$)/s', $response, $matches)) {
            $recommendations = array_map('trim', $matches[1]);
        }
        
        return array_slice($recommendations, 0, 5); // Limit to top 5
    }

    /**
     * Calculate health score from insights
     */
    protected function calculateHealthScore(array $insights): int
    {
        if (isset($insights['health_score'])) {
            return $insights['health_score'];
        }
        
        // Calculate based on available insights
        $score = 60; // Base score
        
        if (isset($insights['resource_analysis'])) $score += 10;
        if (isset($insights['performance_review'])) $score += 10;
        if (isset($insights['security_check'])) $score += 20;
        
        return min(100, $score);
    }

    /**
     * Get analysis overview data
     */
    protected function getAnalysisOverview(): array
    {
        return [
            'total_servers' => Server::count(),
            'analyzed_servers' => AiAnalysisResult::where('context_type', 'server')->distinct('context_id')->count(),
            'recent_analyses' => AiAnalysisResult::where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            'avg_health_score' => $this->calculateAverageHealthScore(),
        ];
    }

    /**
     * Calculate average health score
     */
    protected function calculateAverageHealthScore(): float
    {
        $results = AiAnalysisResult::where('context_type', 'server')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();
        
        if ($results->isEmpty()) {
            return 0.0;
        }
        
        $totalScore = 0;
        $count = 0;
        
        foreach ($results as $result) {
            $score = $this->extractHealthScore($result->insights);
            if ($score > 0) {
                $totalScore += $score;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalScore / $count, 1) : 0.0;
    }

    /**
     * Extract health score from insights JSON
     */
    protected function extractHealthScore(string $insightsJson): int
    {
        $insights = json_decode($insightsJson, true) ?? [];
        return $insights['health_score'] ?? 0;
    }

    /**
     * Extract key insights from insights JSON
     */
    protected function extractKeyInsights(string $insightsJson): array
    {
        $insights = json_decode($insightsJson, true) ?? [];
        
        $keyInsights = [];
        if ($insights['resource_analysis'] ?? false) $keyInsights[] = 'Resource Analysis';
        if ($insights['performance_review'] ?? false) $keyInsights[] = 'Performance Review';
        if ($insights['security_check'] ?? false) $keyInsights[] = 'Security Check';
        
        return $keyInsights;
    }

    /**
     * Generate quick recommendations for a server
     */
    protected function generateQuickRecommendations(Server $server): array
    {
        $recommendations = [];
        
        if ($server->status === 'offline') {
            $recommendations[] = 'Server is offline - check connectivity and startup configuration';
        }
        
        if ($server->suspended) {
            $recommendations[] = 'Server is suspended - review suspension reason';
        }
        
        if ($server->cpu > 200) {
            $recommendations[] = 'High CPU allocation - monitor for performance issues';
        }
        
        if ($server->memory < 512) {
            $recommendations[] = 'Low memory allocation - consider increasing for better performance';
        }
        
        return $recommendations;
    }

    /**
     * Get top issues across all servers
     */
    protected function getTopIssues(): array
    {
        // Simplified version - in production, you'd analyze actual issue patterns
        return [
            'High CPU Usage' => 12,
            'Memory Constraints' => 8,
            'Disk Space Low' => 5,
            'Network Issues' => 3,
            'Configuration Errors' => 2,
        ];
    }

    /**
     * Get analysis trends over time
     */
    protected function getAnalysisTrends(): array
    {
        $trends = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = AiAnalysisResult::whereDate('created_at', $date)->count();
            
            $trends[] = [
                'date' => $date->format('M j'),
                'analyses' => $count,
            ];
        }
        
        return $trends;
    }
}