<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Services\OllamaService;
use PterodactylAddons\OllamaAi\Services\AiAssistantService;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Node;

/**
 * AI Dashboard Controller for Admin Panel
 * 
 * Provides AI-powered insights and widgets for the admin dashboard
 * including system status, usage analytics, and quick actions.
 */
class AiDashboardController extends Controller
{
    protected OllamaService $ollamaService;
    protected AiAssistantService $assistantService;

    public function __construct(OllamaService $ollamaService, AiAssistantService $assistantService)
    {
        $this->ollamaService = $ollamaService;
        $this->assistantService = $assistantService;
    }

    /**
     * Show AI dashboard overview
     */
    public function index(): View
    {
        $overview = $this->getDashboardOverview();
        
        return view('ollama-ai::admin.dashboard.index', compact('overview'));
    }

    /**
     * Get dashboard overview data
     */
    public function getDashboardOverview(): array
    {
        // System status
        $systemStatus = $this->ollamaService->getSystemStatus();
        $isConnected = $this->ollamaService->testConnection();

        // Usage statistics
        $usageStats = $this->assistantService->getUsageStatistics();

        // Recent activity
        $recentConversations = AiConversation::with(['user', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->limit(5)
            ->get();

        // Server insights
        $serverInsights = $this->getServerInsights();

        // System health
        $systemHealth = $this->getSystemHealth();

        // Quick actions
        $quickActions = $this->getQuickActions();

        return [
            'system_status' => [
                'connected' => $isConnected,
                'running_models' => $systemStatus['running_models'] ?? [],
                'last_check' => $systemStatus['last_check'] ?? now(),
                'status' => $isConnected ? 'operational' : 'offline',
            ],
            'usage_stats' => $usageStats,
            'recent_activity' => $recentConversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'user' => $conversation->user->username ?? 'System',
                    'title' => $conversation->title ?: 'AI Chat',
                    'context' => $conversation->context_type,
                    'last_message' => $conversation->latestMessage?->getSummary(50),
                    'time_ago' => $conversation->last_message_at?->diffForHumans(),
                ];
            }),
            'server_insights' => $serverInsights,
            'system_health' => $systemHealth,
            'quick_actions' => $quickActions,
        ];
    }

    /**
     * Get server insights for dashboard
     */
    protected function getServerInsights(): array
    {
        $serverCount = Server::count();
        $activeServers = Server::where('status', 'running')->count();
        $suspendedServers = Server::where('suspended', true)->count();

        // Get servers that might need attention
        $serversNeedingAttention = Server::where('status', '!=', 'running')
            ->where('suspended', false)
            ->limit(5)
            ->get(['id', 'name', 'status', 'updated_at']);

        return [
            'total_servers' => $serverCount,
            'active_servers' => $activeServers,
            'suspended_servers' => $suspendedServers,
            'inactive_servers' => $serverCount - $activeServers - $suspendedServers,
            'servers_needing_attention' => $serversNeedingAttention->map(function ($server) {
                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'status' => $server->status,
                    'issue' => $this->getServerIssueDescription($server->status),
                    'last_updated' => $server->updated_at->diffForHumans(),
                ];
            }),
            'health_score' => $this->calculateServerHealthScore($activeServers, $serverCount),
        ];
    }

    /**
     * Get system health indicators
     */
    protected function getSystemHealth(): array
    {
        $userCount = User::count();
        $nodeCount = Node::count();
        $recentUsers = User::where('created_at', '>', now()->subDays(7))->count();
        $activeConversations = AiConversation::where('status', 'active')->count();

        // AI Health indicators
        $aiHealth = [
            'models_available' => count($this->ollamaService->getAvailableModels()),
            'avg_response_time' => $this->getAverageResponseTime(),
            'success_rate' => $this->getAiSuccessRate(),
            'active_conversations' => $activeConversations,
        ];

        return [
            'platform' => [
                'users' => $userCount,
                'nodes' => $nodeCount,
                'new_users_week' => $recentUsers,
                'growth_rate' => $userCount > 0 ? round(($recentUsers / $userCount) * 100, 1) : 0,
            ],
            'ai_health' => $aiHealth,
            'overall_score' => $this->calculateOverallHealthScore($aiHealth),
        ];
    }

    /**
     * Get quick action suggestions
     */
    protected function getQuickActions(): array
    {
        $actions = [];

        // Check if Ollama is connected
        if (!$this->ollamaService->testConnection()) {
            $actions[] = [
                'type' => 'error',
                'title' => 'Ollama Disconnected',
                'description' => 'AI services are not available. Check Ollama connection.',
                'action_text' => 'Test Connection',
                'action_url' => route('admin.ai.test-connection'),
                'priority' => 'high',
            ];
        }

        // Check for missing models
        $configuredModels = config('ai.models', []);
        $availableModels = collect($this->ollamaService->getAvailableModels())->pluck('name');
        
        foreach ($configuredModels as $type => $model) {
            if (!$availableModels->contains($model)) {
                $actions[] = [
                    'type' => 'warning',
                    'title' => 'Missing AI Model',
                    'description' => "Model '{$model}' for {$type} is not available.",
                    'action_text' => 'Download Model',
                    'action_url' => route('admin.ai.models.pull'),
                    'action_data' => ['model' => $model],
                    'priority' => 'medium',
                ];
            }
        }

        // Check for old conversations to archive
        $oldConversations = AiConversation::where('last_message_at', '<', now()->subDays(30))
            ->where('status', 'active')
            ->count();

        if ($oldConversations > 0) {
            $actions[] = [
                'type' => 'info',
                'title' => 'Archive Old Conversations',
                'description' => "{$oldConversations} conversations are ready for archival.",
                'action_text' => 'Archive Now',
                'action_url' => route('admin.ai.archive-old'),
                'action_data' => ['days' => 30],
                'priority' => 'low',
            ];
        }

        // Suggest performance optimization if response times are slow
        $avgResponseTime = $this->getAverageResponseTime();
        if ($avgResponseTime > 5000) { // 5 seconds
            $actions[] = [
                'type' => 'warning',
                'title' => 'Slow AI Responses',
                'description' => "Average response time is {$avgResponseTime}ms. Consider optimization.",
                'action_text' => 'Optimize',
                'action_url' => route('admin.ai.settings'),
                'priority' => 'medium',
            ];
        }

        return collect($actions)->sortByDesc('priority')->take(5)->values()->all();
    }

    /**
     * Get real-time dashboard data
     */
    public function getDashboardData(): array
    {
        return response()->json($this->getDashboardOverview());
    }

    /**
     * Get AI system metrics
     */
    public function getSystemMetrics()
    {
        $metrics = [
            'ollama_status' => $this->ollamaService->testConnection() ? 'online' : 'offline',
            'available_models' => count($this->ollamaService->getAvailableModels()),
            'active_conversations' => AiConversation::where('status', 'active')->count(),
            'messages_today' => AiMessage::whereDate('created_at', today())->count(),
            'average_response_time' => $this->getAverageResponseTime(),
            'success_rate' => $this->getAiSuccessRate(),
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($metrics);
    }

    /**
     * Get recent AI activity
     */
    public function getRecentActivity(Request $request)
    {
        $limit = $request->integer('limit', 10);
        
        $conversations = AiConversation::with(['user', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->limit($limit)
            ->get();

        $activity = $conversations->map(function ($conversation) {
            return [
                'id' => $conversation->id,
                'type' => 'conversation',
                'user' => $conversation->user->username ?? 'System',
                'user_id' => $conversation->user_id,
                'title' => $conversation->title ?: 'AI Conversation',
                'context' => $conversation->context_type,
                'preview' => $conversation->latestMessage?->getSummary(100),
                'timestamp' => $conversation->last_message_at,
                'time_ago' => $conversation->last_message_at?->diffForHumans(),
            ];
        });

        return response()->json($activity);
    }

    /**
     * Helper methods
     */
    protected function getServerIssueDescription(string $status): string
    {
        return match ($status) {
            'stopped' => 'Server is stopped',
            'stopping' => 'Server is stopping',
            'starting' => 'Server is starting',
            'crashed' => 'Server has crashed',
            default => 'Unknown status',
        };
    }

    protected function calculateServerHealthScore(int $active, int $total): int
    {
        if ($total === 0) return 100;
        return (int) round(($active / $total) * 100);
    }

    protected function getAverageResponseTime(): float
    {
        return AiMessage::where('created_at', '>', now()->subHours(24))
            ->whereNotNull('processing_time_ms')
            ->avg('processing_time_ms') ?: 0;
    }

    protected function getAiSuccessRate(): float
    {
        $total = AiMessage::where('created_at', '>', now()->subHours(24))->count();
        if ($total === 0) return 100.0;
        
        $successful = AiMessage::where('created_at', '>', now()->subHours(24))
            ->where('status', 'completed')
            ->count();
        
        return round(($successful / $total) * 100, 1);
    }

    protected function calculateOverallHealthScore(array $aiHealth): int
    {
        $score = 0;
        
        // Model availability (25%)
        $score += min(25, $aiHealth['models_available'] * 5);
        
        // Response time (25%) - under 2s = full points
        $responseScore = max(0, 25 - (($aiHealth['avg_response_time'] - 2000) / 1000 * 5));
        $score += max(0, min(25, $responseScore));
        
        // Success rate (50%)
        $score += ($aiHealth['success_rate'] / 100) * 50;
        
        return (int) round($score);
    }
}