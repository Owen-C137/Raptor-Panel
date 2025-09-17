<?php

namespace PterodactylAddons\OllamaAi\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PterodactylAddons\OllamaAi\Models\AiConversation;
use PterodactylAddons\OllamaAi\Models\AiCodeGeneration;
use PterodactylAddons\OllamaAi\Models\AiHelpContext;

class AiAnalyticsService
{
    /**
     * Get comprehensive analytics data for dashboard
     */
    public function getAnalytics(array $filters = []): array
    {
        $user = Auth::user();
        $dateRange = $this->getDateRange($filters);
        
        return [
            'conversations' => $this->getConversationAnalytics($user, $dateRange),
            'codeGeneration' => $this->getCodeGenerationAnalytics($user, $dateRange),
            'helpUsage' => $this->getHelpAnalytics($user, $dateRange),
            'models' => $this->getModelUsageAnalytics($user, $dateRange),
            'performance' => $this->getPerformanceMetrics($user, $dateRange),
        ];
    }

    /**
     * Get conversation analytics
     */
    public function getConversationAnalytics($user = null, array $dateRange = []): array
    {
        $query = AiConversation::query();
        
        if ($user && !$user->root_admin) {
            $query->where('user_id', $user->id);
        }
        
        $this->applyDateRange($query, $dateRange);
        
        $totalConversations = $query->count();
        $activeToday = (clone $query)->whereDate('created_at', Carbon::today())->count();
        $averageLength = $query->avg(DB::raw('char_length(messages)'));
        
        return [
            'total' => $totalConversations,
            'active_today' => $activeToday,
            'average_length' => round($averageLength ?? 0),
            'by_status' => $this->getConversationsByStatus($query),
            'by_date' => $this->getConversationsByDate($query),
        ];
    }

    /**
     * Get code generation analytics
     */
    public function getCodeGenerationAnalytics($user = null, array $dateRange = []): array
    {
        $query = AiCodeGeneration::query();
        
        if ($user && !$user->root_admin) {
            $query->where('user_id', $user->id);
        }
        
        $this->applyDateRange($query, $dateRange);
        
        $totalGenerations = $query->count();
        $successfulGenerations = $query->where('status', 'completed')->count();
        $avgExecutionTime = $query->where('status', 'completed')->avg('execution_time');
        
        return [
            'total' => $totalGenerations,
            'successful' => $successfulGenerations,
            'success_rate' => $totalGenerations > 0 ? round(($successfulGenerations / $totalGenerations) * 100, 2) : 0,
            'avg_execution_time' => round($avgExecutionTime ?? 0, 2),
            'by_type' => $this->getGenerationsByType($query),
            'by_language' => $this->getGenerationsByLanguage($query),
        ];
    }

    /**
     * Get help usage analytics
     */
    public function getHelpAnalytics($user = null, array $dateRange = []): array
    {
        $query = AiHelpContext::query();
        
        if ($user && !$user->root_admin) {
            $query->where('user_id', $user->id);
        }
        
        $this->applyDateRange($query, $dateRange);
        
        return [
            'total_requests' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count(),
            'by_context_type' => $this->getHelpByContextType($query),
            'most_common_queries' => $this->getMostCommonHelpQueries($query),
        ];
    }

    /**
     * Get model usage analytics
     */
    public function getModelUsageAnalytics($user = null, array $dateRange = []): array
    {
        $conversationQuery = AiConversation::query();
        $codeGenQuery = AiCodeGeneration::query();
        
        if ($user && !$user->root_admin) {
            $conversationQuery->where('user_id', $user->id);
            $codeGenQuery->where('user_id', $user->id);
        }
        
        $this->applyDateRange($conversationQuery, $dateRange);
        $this->applyDateRange($codeGenQuery, $dateRange);
        
        // Get model usage from conversations
        $conversationModels = $conversationQuery->select('model', DB::raw('count(*) as count'))
            ->groupBy('model')
            ->pluck('count', 'model')
            ->toArray();
            
        // Get model usage from code generations
        $codeGenModels = $codeGenQuery->select('model', DB::raw('count(*) as count'))
            ->groupBy('model')
            ->pluck('count', 'model')
            ->toArray();
        
        // Merge and calculate totals
        $allModels = array_merge_recursive($conversationModels, $codeGenModels);
        $modelUsage = [];
        
        foreach ($allModels as $model => $counts) {
            $modelUsage[$model] = is_array($counts) ? array_sum($counts) : $counts;
        }
        
        return [
            'by_model' => $modelUsage,
            'most_popular' => count($modelUsage) > 0 ? array_keys($modelUsage, max($modelUsage))[0] : null,
            'total_requests' => array_sum($modelUsage),
        ];
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics($user = null, array $dateRange = []): array
    {
        $query = AiConversation::query();
        
        if ($user && !$user->root_admin) {
            $query->where('user_id', $user->id);
        }
        
        $this->applyDateRange($query, $dateRange);
        
        return [
            'avg_response_time' => $query->avg('response_time') ?? 0,
            'median_response_time' => $this->getMedianResponseTime($query),
            'error_rate' => $this->calculateErrorRate($query),
            'peak_usage_hours' => $this->getPeakUsageHours($query),
        ];
    }

    /**
     * Record analytics event
     */
    public function recordEvent(string $event, array $data = []): void
    {
        // This could be implemented to store custom analytics events
        // For now, just log to Laravel's log system
        \Log::info("AI Analytics Event: {$event}", $data);
    }

    /**
     * Helper methods
     */
    private function getDateRange(array $filters): array
    {
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : Carbon::now()->subDays(30);
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : Carbon::now();
        
        return compact('startDate', 'endDate');
    }

    private function applyDateRange($query, array $dateRange): void
    {
        if (!empty($dateRange)) {
            $query->whereBetween('created_at', [$dateRange['startDate'], $dateRange['endDate']]);
        }
    }

    private function getConversationsByStatus($query): array
    {
        return $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function getConversationsByDate($query): array
    {
        return $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    private function getGenerationsByType($query): array
    {
        return $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    private function getGenerationsByLanguage($query): array
    {
        return $query->select('language', DB::raw('count(*) as count'))
            ->groupBy('language')
            ->pluck('count', 'language')
            ->toArray();
    }

    private function getHelpByContextType($query): array
    {
        return $query->select('context_type', DB::raw('count(*) as count'))
            ->groupBy('context_type')
            ->pluck('count', 'context_type')
            ->toArray();
    }

    private function getMostCommonHelpQueries($query): array
    {
        return $query->select('query', DB::raw('count(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'query')
            ->toArray();
    }

    private function getMedianResponseTime($query)
    {
        $count = $query->count();
        if ($count == 0) return 0;
        
        $middle = intval($count / 2);
        
        if ($count % 2 == 0) {
            $median1 = $query->orderBy('response_time')->skip($middle - 1)->first()->response_time ?? 0;
            $median2 = $query->orderBy('response_time')->skip($middle)->first()->response_time ?? 0;
            return ($median1 + $median2) / 2;
        } else {
            return $query->orderBy('response_time')->skip($middle)->first()->response_time ?? 0;
        }
    }

    private function calculateErrorRate($query): float
    {
        $total = $query->count();
        if ($total == 0) return 0;
        
        $errors = $query->where('status', 'failed')->count();
        return round(($errors / $total) * 100, 2);
    }

    private function getPeakUsageHours($query): array
    {
        return $query->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->limit(3)
            ->pluck('count', 'hour')
            ->toArray();
    }
}