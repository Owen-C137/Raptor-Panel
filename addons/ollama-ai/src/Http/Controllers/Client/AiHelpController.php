<?php

namespace PterodactylAddons\OllamaAi\Http\Controllers\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\OllamaAi\Services\AiHelpService;
use PterodactylAddons\OllamaAi\Models\AiUserLearning;

class AiHelpController extends Controller
{
    protected $aiHelpService;

    public function __construct(AiHelpService $aiHelpService)
    {
        $this->aiHelpService = $aiHelpService;
    }

    /**
     * Get contextual help for current user
     */
    public function getContextualHelp(Request $request): JsonResponse
    {
        $context = $request->only(['route', 'server_id', 'additional_context']);
        
        try {
            $help = $this->aiHelpService->generateContextualHelp($context);
            
            return response()->json([
                'success' => true,
                'help' => $help,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate help at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get intelligent suggestions for current user
     */
    public function getIntelligentSuggestions(Request $request): JsonResponse
    {
        $context = $request->only(['route', 'server_id', 'action', 'additional_context']);
        
        try {
            $suggestions = $this->aiHelpService->getIntelligentSuggestions(auth()->user(), $context);
            
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate suggestions at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get dynamic tutorial for a topic
     */
    public function getTutorial(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $context = $request->only(['server_id', 'difficulty_preference', 'additional_context']);
        
        try {
            $tutorial = $this->aiHelpService->generateDynamicTutorial(
                $request->topic,
                auth()->user(),
                $context
            );
            
            return response()->json([
                'success' => true,
                'tutorial' => $tutorial,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate tutorial at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get smart documentation for a feature
     */
    public function getSmartDocumentation(Request $request): JsonResponse
    {
        $request->validate([
            'feature' => 'required|string|max:255',
        ]);

        $context = $request->only(['server_id', 'additional_context']);
        
        try {
            $documentation = $this->aiHelpService->getSmartDocumentation(
                $request->feature,
                $context
            );
            
            return response()->json([
                'success' => true,
                'documentation' => $documentation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get documentation at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get quick help for a specific action
     */
    public function getQuickHelp(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|max:255',
        ]);

        $context = $request->only(['server_id', 'additional_context']);
        
        try {
            $quickHelp = $this->aiHelpService->getQuickHelp($request->action, $context);
            
            return response()->json([
                'success' => true,
                'quick_help' => $quickHelp,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get quick help at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Track learning progress
     */
    public function trackProgress(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'progress_data' => 'required|array',
            'time_spent' => 'sometimes|integer|min:0',
            'completed' => 'sometimes|boolean',
        ]);

        try {
            // Add time spent if provided
            if ($request->has('time_spent')) {
                $request->merge([
                    'progress_data' => array_merge($request->progress_data, [
                        'time_spent' => $request->time_spent,
                    ])
                ]);
            }

            // Mark as completed if indicated
            if ($request->completed) {
                $request->merge([
                    'progress_data' => array_merge($request->progress_data, [
                        'completed' => true,
                        'completed_at' => now()->toISOString(),
                    ])
                ]);
            }

            $this->aiHelpService->trackLearningProgress(
                auth()->user(),
                $request->topic,
                $request->progress_data
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Progress tracked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to track progress at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get user's learning dashboard
     */
    public function learningDashboard(): View
    {
        $user = auth()->user();
        
        $learningData = AiUserLearning::where('user_id', $user->id)
            ->orderBy('last_accessed', 'desc')
            ->get();

        $skillAssessment = $this->aiHelpService->assessUserSkillLevel($user);
        
        $dashboardData = [
            'total_topics' => $learningData->count(),
            'completed_tutorials' => $learningData->where('completion_percentage', 100)->count(),
            'in_progress' => $learningData->where('completion_percentage', '>', 0)
                ->where('completion_percentage', '<', 100)->count(),
            'total_time_spent' => $learningData->sum('time_spent_seconds'),
            'current_skill_levels' => $skillAssessment,
            'recent_activity' => $learningData->take(10),
            'suggested_topics' => $this->getSuggestedLearningTopics($user, $skillAssessment),
        ];

        return view('ollama-ai::client.help.dashboard', compact('dashboardData'));
    }

    /**
     * Get user's learning progress for a specific topic
     */
    public function getTopicProgress(Request $request): JsonResponse
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $learning = AiUserLearning::where('user_id', auth()->id())
            ->where('topic', $request->topic)
            ->first();

        if (!$learning) {
            return response()->json([
                'success' => true,
                'progress' => [
                    'topic' => $request->topic,
                    'completion_percentage' => 0,
                    'time_spent_seconds' => 0,
                    'skill_level' => 'beginner',
                    'last_accessed' => null,
                    'progress_data' => [],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'progress' => [
                'topic' => $learning->topic,
                'completion_percentage' => $learning->completion_percentage,
                'time_spent_seconds' => $learning->time_spent_seconds,
                'skill_level' => $learning->skill_level,
                'last_accessed' => $learning->last_accessed,
                'progress_data' => $learning->progress_data,
                'completed_at' => $learning->completed_at,
            ],
        ]);
    }

    /**
     * Get available learning topics
     */
    public function getAvailableTopics(): JsonResponse
    {
        $user = auth()->user();
        $skillLevels = $this->aiHelpService->assessUserSkillLevel($user);
        
        $topics = $this->getTopicsForSkillLevel($skillLevels);
        $userProgress = AiUserLearning::where('user_id', $user->id)
            ->pluck('completion_percentage', 'topic')
            ->toArray();

        // Add progress information to each topic
        $topicsWithProgress = array_map(function ($topic) use ($userProgress) {
            return [
                'name' => $topic,
                'display_name' => $this->formatTopicName($topic),
                'category' => $this->categorizeTopicForDisplay($topic),
                'difficulty' => $this->getTopicDifficulty($topic),
                'progress' => $userProgress[$topic] ?? 0,
                'estimated_duration' => $this->getTopicEstimatedDuration($topic),
            ];
        }, $topics);

        return response()->json([
            'success' => true,
            'topics' => $topicsWithProgress,
            'skill_levels' => $skillLevels,
        ]);
    }

    /**
     * Get learning recommendations
     */
    public function getLearningRecommendations(): JsonResponse
    {
        $user = auth()->user();
        $skillLevels = $this->aiHelpService->assessUserSkillLevel($user);
        
        try {
            $recommendations = $this->generateLearningRecommendations($user, $skillLevels);
            
            return response()->json([
                'success' => true,
                'recommendations' => $recommendations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate recommendations at this time.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Helper methods
     */
    protected function getSuggestedLearningTopics($user, array $skillAssessment): array
    {
        $suggestions = [];
        
        foreach ($skillAssessment as $category => $level) {
            if ($level === 'beginner') {
                $suggestions[] = [
                    'category' => $category,
                    'suggested_topics' => $this->getBeginnerTopics($category),
                    'reason' => 'Build foundational knowledge in ' . ucfirst(str_replace('_', ' ', $category)),
                ];
            } elseif ($level === 'intermediate') {
                $suggestions[] = [
                    'category' => $category,
                    'suggested_topics' => $this->getIntermediateTopics($category),
                    'reason' => 'Advance your ' . ucfirst(str_replace('_', ' ', $category)) . ' skills',
                ];
            }
        }
        
        return $suggestions;
    }

    protected function getTopicsForSkillLevel(array $skillLevels): array
    {
        $topics = [];
        
        // Add topics based on user's current skill levels
        foreach ($skillLevels as $category => $level) {
            switch ($category) {
                case 'server_management':
                    $topics = array_merge($topics, [
                        'server_creation',
                        'server_configuration', 
                        'server_monitoring',
                        'backup_management',
                    ]);
                    break;
                case 'configuration':
                    $topics = array_merge($topics, [
                        'basic_configuration',
                        'advanced_settings',
                        'environment_variables',
                        'resource_limits',
                    ]);
                    break;
                case 'troubleshooting':
                    $topics = array_merge($topics, [
                        'log_analysis',
                        'performance_debugging',
                        'common_issues',
                        'diagnostic_tools',
                    ]);
                    break;
            }
        }
        
        return array_unique($topics);
    }

    protected function formatTopicName(string $topic): string
    {
        return ucfirst(str_replace('_', ' ', $topic));
    }

    protected function categorizeTopicForDisplay(string $topic): string
    {
        $categories = [
            'server_creation' => 'Server Management',
            'server_configuration' => 'Server Management',
            'server_monitoring' => 'Monitoring',
            'backup_management' => 'Data Management',
            'basic_configuration' => 'Configuration',
            'advanced_settings' => 'Configuration',
            'log_analysis' => 'Troubleshooting',
            'performance_debugging' => 'Troubleshooting',
        ];
        
        return $categories[$topic] ?? 'General';
    }

    protected function getTopicDifficulty(string $topic): string
    {
        $difficulties = [
            'server_creation' => 'beginner',
            'basic_configuration' => 'beginner',
            'server_monitoring' => 'intermediate',
            'advanced_settings' => 'advanced',
            'performance_debugging' => 'advanced',
        ];
        
        return $difficulties[$topic] ?? 'intermediate';
    }

    protected function getTopicEstimatedDuration(string $topic): string
    {
        $durations = [
            'server_creation' => '15 minutes',
            'basic_configuration' => '20 minutes',
            'server_monitoring' => '25 minutes',
            'advanced_settings' => '35 minutes',
            'performance_debugging' => '45 minutes',
        ];
        
        return $durations[$topic] ?? '20 minutes';
    }

    protected function generateLearningRecommendations($user, array $skillLevels): array
    {
        $recommendations = [];
        
        // Recommend next steps based on current progress
        $completedTopics = AiUserLearning::where('user_id', $user->id)
            ->where('completion_percentage', 100)
            ->pluck('topic')
            ->toArray();

        // Recommend progression paths
        if (in_array('server_creation', $completedTopics)) {
            $recommendations[] = [
                'type' => 'progression',
                'topic' => 'server_configuration',
                'title' => 'Next: Server Configuration',
                'reason' => 'Build on your server creation knowledge',
                'difficulty' => 'beginner',
            ];
        }

        // Recommend skill gap filling
        foreach ($skillLevels as $category => $level) {
            if ($level === 'beginner') {
                $recommendations[] = [
                    'type' => 'skill_building',
                    'category' => $category,
                    'title' => 'Strengthen ' . ucfirst(str_replace('_', ' ', $category)),
                    'reason' => 'Build foundational skills in this area',
                    'suggested_topics' => $this->getBeginnerTopics($category),
                ];
            }
        }

        return array_slice($recommendations, 0, 5); // Limit to top 5 recommendations
    }

    protected function getBeginnerTopics(string $category): array
    {
        $topics = [
            'server_management' => ['server_creation', 'basic_server_settings'],
            'configuration' => ['basic_configuration', 'environment_setup'],
            'troubleshooting' => ['reading_logs', 'common_error_fixes'],
            'optimization' => ['resource_monitoring', 'basic_performance_tips'],
        ];
        
        return $topics[$category] ?? [];
    }

    protected function getIntermediateTopics(string $category): array
    {
        $topics = [
            'server_management' => ['advanced_server_config', 'multi_server_management'],
            'configuration' => ['advanced_configuration', 'custom_environments'],
            'troubleshooting' => ['advanced_debugging', 'performance_analysis'],
            'optimization' => ['resource_optimization', 'cost_management'],
        ];
        
        return $topics[$category] ?? [];
    }
}