<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Services\AiHelpService;
use PterodactylAddons\OllamaAi\Models\AiHelpContext;
use PterodactylAddons\OllamaAi\Models\AiUserLearning;
use Pterodactyl\Models\User;

class AiHelpSystemController extends Controller
{
    protected $aiHelpService;

    public function __construct(AiHelpService $aiHelpService)
    {
        $this->aiHelpService = $aiHelpService;
    }

    /**
     * Display the help system management dashboard
     */
    public function index(): View
    {
        $stats = [
            'total_help_contexts' => AiHelpContext::count(),
            'active_learners' => AiUserLearning::whereNotNull('last_accessed')
                ->where('last_accessed', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count(),
            'completed_tutorials' => AiUserLearning::whereNotNull('completed_at')->count(),
            'help_requests_today' => AiHelpContext::whereDate('created_at', today())->count(),
        ];

        $recentActivity = AiHelpContext::with('user')
            ->latest()
            ->limit(10)
            ->get();

        $learningProgress = AiUserLearning::with('user')
            ->where('completion_percentage', '>', 0)
            ->orderBy('last_accessed', 'desc')
            ->limit(15)
            ->get();

        return view('ollama-ai::admin.help.index', compact('stats', 'recentActivity', 'learningProgress'));
    }

    /**
     * Display help analytics and insights
     */
    public function analytics(): View
    {
        $helpAnalytics = $this->generateHelpAnalytics();
        $learningAnalytics = $this->generateLearningAnalytics();
        $usagePatterns = $this->generateUsagePatterns();

        return view('ollama-ai::admin.help.analytics', compact(
            'helpAnalytics',
            'learningAnalytics', 
            'usagePatterns'
        ));
    }

    /**
     * Manage tutorial content and learning paths
     */
    public function tutorials(): View
    {
        $tutorials = $this->getTutorialManagement();
        $learningPaths = $this->getLearningPaths();
        $skillLevelDistribution = $this->getSkillLevelDistribution();

        return view('ollama-ai::admin.help.tutorials', compact(
            'tutorials',
            'learningPaths',
            'skillLevelDistribution'
        ));
    }

    /**
     * Generate contextual help for any user/route
     */
    public function generateHelp(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'route' => 'required|string',
            'context' => 'sometimes|array',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $context = array_merge($request->context ?? [], [
                'route' => $request->route,
                'generated_by_admin' => true,
            ]);

            // Temporarily authenticate as the user for context generation
            $originalUser = auth()->user();
            auth()->login($user);

            $help = $this->aiHelpService->generateContextualHelp($context);

            // Restore original authentication
            auth()->login($originalUser);

            // Store help context for tracking
            AiHelpContext::create([
                'user_id' => $user->id,
                'route_name' => $request->route,
                'context_data' => $context,
                'help_data' => $help,
                'generated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'help' => $help,
                'message' => 'Contextual help generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate help: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get intelligent suggestions for a user
     */
    public function getIntelligentSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'context' => 'sometimes|array',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $context = $request->context ?? [];

            $suggestions = $this->aiHelpService->getIntelligentSuggestions($user, $context);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate suggestions: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate dynamic tutorial for specific topic
     */
    public function generateTutorial(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string',
            'user_id' => 'sometimes|exists:users,id',
            'skill_level' => 'sometimes|in:beginner,intermediate,advanced,expert',
            'context' => 'sometimes|array',
        ]);

        try {
            $user = $request->user_id ? User::findOrFail($request->user_id) : auth()->user();
            $context = $request->context ?? [];
            
            if ($request->skill_level) {
                $context['override_skill_level'] = $request->skill_level;
            }

            $tutorial = $this->aiHelpService->generateDynamicTutorial($request->topic, $user, $context);

            return response()->json([
                'success' => true,
                'tutorial' => $tutorial,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate tutorial: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get smart documentation for a feature
     */
    public function getSmartDocumentation(Request $request): JsonResponse
    {
        $request->validate([
            'feature' => 'required|string',
            'user_id' => 'sometimes|exists:users,id',
            'context' => 'sometimes|array',
        ]);

        try {
            $user = $request->user_id ? User::findOrFail($request->user_id) : auth()->user();
            $context = $request->context ?? [];

            // Temporarily authenticate as the target user if different
            $originalUser = auth()->user();
            if ($user->id !== $originalUser->id) {
                auth()->login($user);
            }

            $documentation = $this->aiHelpService->getSmartDocumentation($request->feature, $context);

            // Restore original authentication
            if ($user->id !== $originalUser->id) {
                auth()->login($originalUser);
            }

            return response()->json([
                'success' => true,
                'documentation' => $documentation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get documentation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * View user learning progress details
     */
    public function userLearningProgress(User $user): View
    {
        $learningRecords = AiUserLearning::where('user_id', $user->id)
            ->orderBy('last_accessed', 'desc')
            ->get();

        $skillAssessment = $this->aiHelpService->assessUserSkillLevel($user);
        $helpContexts = AiHelpContext::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $learningStats = [
            'total_topics' => $learningRecords->count(),
            'completed' => $learningRecords->where('completion_percentage', 100)->count(),
            'in_progress' => $learningRecords->where('completion_percentage', '>', 0)
                ->where('completion_percentage', '<', 100)->count(),
            'total_time' => $learningRecords->sum('time_spent_seconds'),
            'last_active' => $learningRecords->max('last_accessed'),
        ];

        return view('ollama-ai::admin.help.user-progress', compact(
            'user',
            'learningRecords',
            'skillAssessment',
            'helpContexts',
            'learningStats'
        ));
    }

    /**
     * Export help system analytics
     */
    public function exportAnalytics(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'format' => 'required|in:csv,json,pdf',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->date_from ? now()->parse($request->date_from) : now()->subMonth();
        $dateTo = $request->date_to ? now()->parse($request->date_to) : now();

        $data = $this->generateExportData($dateFrom, $dateTo);

        $filename = "help-system-analytics-{$dateFrom->format('Y-m-d')}-to-{$dateTo->format('Y-m-d')}.{$request->format}";

        return response()->streamDownload(function () use ($data, $request) {
            switch ($request->format) {
                case 'csv':
                    $this->outputCsv($data);
                    break;
                case 'json':
                    echo json_encode($data, JSON_PRETTY_PRINT);
                    break;
                case 'pdf':
                    $this->outputPdf($data);
                    break;
            }
        }, $filename);
    }

    /**
     * Generate help analytics data
     */
    protected function generateHelpAnalytics(): array
    {
        return [
            'total_requests' => AiHelpContext::count(),
            'requests_last_30_days' => AiHelpContext::where('created_at', '>=', now()->subDays(30))->count(),
            'top_routes' => AiHelpContext::selectRaw('route_name, count(*) as count')
                ->groupBy('route_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'daily_requests' => AiHelpContext::selectRaw('DATE(created_at) as date, count(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }

    /**
     * Generate learning analytics data
     */
    protected function generateLearningAnalytics(): array
    {
        return [
            'total_learners' => AiUserLearning::distinct('user_id')->count(),
            'active_learners' => AiUserLearning::where('last_accessed', '>=', now()->subDays(7))
                ->distinct('user_id')->count(),
            'completed_tutorials' => AiUserLearning::where('completion_percentage', 100)->count(),
            'average_completion_rate' => AiUserLearning::avg('completion_percentage'),
            'popular_topics' => AiUserLearning::selectRaw('topic, count(*) as learners')
                ->groupBy('topic')
                ->orderBy('learners', 'desc')
                ->limit(10)
                ->get(),
            'skill_distribution' => AiUserLearning::selectRaw('skill_level, count(*) as count')
                ->groupBy('skill_level')
                ->get(),
        ];
    }

    /**
     * Generate usage patterns data
     */
    protected function generateUsagePatterns(): array
    {
        return [
            'peak_hours' => AiHelpContext::selectRaw('HOUR(created_at) as hour, count(*) as requests')
                ->groupBy('hour')
                ->orderBy('requests', 'desc')
                ->get(),
            'weekly_patterns' => AiHelpContext::selectRaw('DAYOFWEEK(created_at) as day, count(*) as requests')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('day')
                ->get(),
            'user_engagement' => AiUserLearning::selectRaw('user_id, sum(time_spent_seconds) as total_time')
                ->groupBy('user_id')
                ->orderBy('total_time', 'desc')
                ->limit(20)
                ->with('user')
                ->get(),
        ];
    }

    /**
     * Get tutorial management data
     */
    protected function getTutorialManagement(): array
    {
        return [
            'available_topics' => AiUserLearning::distinct('topic')->pluck('topic'),
            'completion_rates' => AiUserLearning::selectRaw('topic, AVG(completion_percentage) as avg_completion')
                ->groupBy('topic')
                ->get(),
            'time_investment' => AiUserLearning::selectRaw('topic, AVG(time_spent_seconds) as avg_time')
                ->groupBy('topic')
                ->get(),
        ];
    }

    /**
     * Get learning paths data
     */
    protected function getLearningPaths(): array
    {
        // This would be expanded to include predefined learning paths
        return [
            'beginner_path' => ['server_creation', 'basic_management', 'file_management'],
            'intermediate_path' => ['advanced_configuration', 'performance_tuning', 'security_setup'],
            'advanced_path' => ['automation', 'optimization', 'troubleshooting'],
        ];
    }

    /**
     * Get skill level distribution
     */
    protected function getSkillLevelDistribution(): array
    {
        return AiUserLearning::selectRaw('skill_level, count(distinct user_id) as users')
            ->groupBy('skill_level')
            ->pluck('users', 'skill_level')
            ->toArray();
    }

    /**
     * Generate export data
     */
    protected function generateExportData($dateFrom, $dateTo): array
    {
        return [
            'period' => [
                'from' => $dateFrom->toDateString(),
                'to' => $dateTo->toDateString(),
            ],
            'help_requests' => AiHelpContext::whereBetween('created_at', [$dateFrom, $dateTo])
                ->with('user')
                ->get()
                ->toArray(),
            'learning_activity' => AiUserLearning::whereBetween('last_accessed', [$dateFrom, $dateTo])
                ->with('user')
                ->get()
                ->toArray(),
            'summary' => [
                'total_help_requests' => AiHelpContext::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'active_learners' => AiUserLearning::whereBetween('last_accessed', [$dateFrom, $dateTo])
                    ->distinct('user_id')->count(),
                'tutorials_completed' => AiUserLearning::whereBetween('completed_at', [$dateFrom, $dateTo])
                    ->whereNotNull('completed_at')->count(),
            ],
        ];
    }

    /**
     * Output CSV format
     */
    protected function outputCsv(array $data): void
    {
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, ['Type', 'Date', 'User', 'Details']);
        
        // Help requests
        foreach ($data['help_requests'] as $request) {
            fputcsv($output, [
                'Help Request',
                $request['created_at'],
                $request['user']['name'] ?? 'Unknown',
                $request['route_name'],
            ]);
        }
        
        // Learning activity
        foreach ($data['learning_activity'] as $activity) {
            fputcsv($output, [
                'Learning Activity',
                $activity['last_accessed'],
                $activity['user']['name'] ?? 'Unknown',
                $activity['topic'] . ' (' . $activity['completion_percentage'] . '%)',
            ]);
        }
        
        fclose($output);
    }

    /**
     * Output PDF format (simplified)
     */
    protected function outputPdf(array $data): void
    {
        // This would integrate with a PDF library like DOMPDF
        // For now, output simple text format
        echo "Help System Analytics Report\n";
        echo "Period: {$data['period']['from']} to {$data['period']['to']}\n\n";
        echo "Summary:\n";
        echo "- Total Help Requests: {$data['summary']['total_help_requests']}\n";
        echo "- Active Learners: {$data['summary']['active_learners']}\n";
        echo "- Tutorials Completed: {$data['summary']['tutorials_completed']}\n";
    }
}