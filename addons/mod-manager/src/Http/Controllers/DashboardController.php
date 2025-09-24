<?php

namespace PterodactylAddons\ModManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use PterodactylAddons\ModManager\Models\Game;
use PterodactylAddons\ModManager\Models\Category;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use PterodactylAddons\ModManager\Models\HarvestJob;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview data
     */
    public function index(): JsonResponse
    {
        // Basic stats
        $stats = [
            'games' => [
                'total' => Game::count(),
                'with_mods' => Game::has('mods')->count(),
            ],
            'categories' => [
                'total' => Category::count(),
                'with_mods' => Category::has('mods')->count(),
            ],
            'mods' => [
                'total' => Mod::count(),
                'indexed_today' => Mod::whereDate('last_indexed_at', today())->count(),
                'indexed_week' => Mod::where('last_indexed_at', '>', now()->subWeek())->count(),
                'popular' => Mod::where('download_count', '>', 100000)->count(),
            ],
            'files' => [
                'total' => ModFile::count(),
                'added_today' => ModFile::whereDate('created_at', today())->count(),
                'added_week' => ModFile::where('created_at', '>', now()->subWeek())->count(),
                'total_size' => ModFile::sum('file_length'),
            ],
            'harvest_jobs' => [
                'active' => HarvestJob::whereIn('status', ['pending', 'running'])->count(),
                'completed_today' => HarvestJob::where('status', 'completed')
                    ->whereDate('completed_at', today())->count(),
                'failed_today' => HarvestJob::where('status', 'failed')
                    ->whereDate('completed_at', today())->count(),
            ]
        ];

        // Recent activity
        $recentActivity = [
            'harvest_jobs' => HarvestJob::with('game')
                ->whereIn('status', ['completed', 'failed', 'cancelled'])
                ->orderBy('completed_at', 'desc')
                ->limit(5)
                ->get(),
            'new_mods' => Mod::with(['game', 'primaryCategory'])
                ->orderBy('date_created', 'desc')
                ->limit(5)
                ->get(),
            'updated_mods' => Mod::with(['game', 'primaryCategory'])
                ->where('date_modified', '>', now()->subHours(24))
                ->orderBy('date_modified', 'desc')
                ->limit(5)
                ->get(),
        ];

        // Top categories by mod count
        $topCategories = Category::withCount('mods')
            ->orderBy('mods_count', 'desc')
            ->limit(10)
            ->get();

        // Game statistics
        $gameStats = Game::withCount(['mods', 'categories'])
            ->orderBy('mods_count', 'desc')
            ->limit(10)
            ->get();

        // Harvest job statistics for the last 7 days
        $harvestTrends = HarvestJob::selectRaw('
                DATE(completed_at) as date,
                status,
                COUNT(*) as count,
                AVG(progress_percentage) as avg_progress,
                SUM(processed_items) as total_processed
            ')
            ->where('completed_at', '>', now()->subWeek())
            ->groupBy('date', 'status')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'recent_activity' => $recentActivity,
            'top_categories' => $topCategories,
            'game_stats' => $gameStats,
            'harvest_trends' => $harvestTrends,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get system health status
     */
    public function health(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'warnings' => [],
            'errors' => []
        ];

        // Check for stuck harvest jobs
        $stuckJobs = HarvestJob::where('status', 'running')
            ->where('started_at', '<', now()->subHours(6))
            ->count();

        if ($stuckJobs > 0) {
            $health['warnings'][] = "Found {$stuckJobs} potentially stuck harvest job(s)";
            $health['status'] = 'warning';
        }

        // Check for failed jobs in last 24 hours
        $failedJobs = HarvestJob::where('status', 'failed')
            ->where('completed_at', '>', now()->subDay())
            ->count();

        if ($failedJobs > 10) {
            $health['warnings'][] = "High number of failed harvest jobs today ({$failedJobs})";
            $health['status'] = 'warning';
        }

        // Check database connectivity
        try {
            Game::count();
            $health['checks'][] = 'Database connection: OK';
        } catch (\Exception $e) {
            $health['errors'][] = 'Database connection failed: ' . $e->getMessage();
            $health['status'] = 'error';
        }

        // Check for old mods that haven't been updated
        $staleModsCount = Mod::where('last_indexed_at', '<', now()->subWeeks(2))->count();
        
        if ($staleModsCount > 100) {
            $health['warnings'][] = "Found {$staleModsCount} mods not updated in 2+ weeks";
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        }

        // Check storage usage (if file length is tracked)
        $totalFileSize = ModFile::sum('file_length');
        if ($totalFileSize > 0) {
            $sizeGB = round($totalFileSize / (1024 * 1024 * 1024), 2);
            $health['checks'][] = "Total mod file size: {$sizeGB} GB";
            
            // Warn if over 500GB
            if ($sizeGB > 500) {
                $health['warnings'][] = "Large storage usage: {$sizeGB} GB";
                if ($health['status'] === 'healthy') {
                    $health['status'] = 'warning';
                }
            }
        }

        return response()->json([
            'success' => true,
            'health' => $health,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get performance metrics
     */
    public function metrics(): JsonResponse
    {
        // Calculate harvest performance over last 7 days
        $harvestMetrics = HarvestJob::where('completed_at', '>', now()->subWeek())
            ->where('status', 'completed')
            ->selectRaw('
                AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_duration_minutes,
                AVG(processed_items) as avg_items_processed,
                AVG(progress_percentage) as avg_completion_rate,
                COUNT(*) as total_jobs
            ')
            ->first();

        // Mod growth over time
        $modGrowth = Mod::selectRaw('
                DATE(date_created) as date,
                COUNT(*) as new_mods
            ')
            ->where('date_created', '>', now()->subMonth())
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // File size distribution
        $fileSizeDistribution = ModFile::selectRaw('
                CASE 
                    WHEN file_length < 1048576 THEN "< 1MB"
                    WHEN file_length < 10485760 THEN "1-10MB" 
                    WHEN file_length < 104857600 THEN "10-100MB"
                    WHEN file_length < 1073741824 THEN "100MB-1GB"
                    ELSE "> 1GB"
                END as size_category,
                COUNT(*) as count
            ')
            ->groupBy('size_category')
            ->get();

        // Top mod authors by download count
        $topAuthors = Mod::selectRaw('
                authors,
                COUNT(*) as mod_count,
                SUM(download_count) as total_downloads
            ')
            ->whereNotNull('authors')
            ->where('authors', '!=', '')
            ->groupBy('authors')
            ->orderBy('total_downloads', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'metrics' => [
                'harvest_performance' => $harvestMetrics,
                'mod_growth' => $modGrowth,
                'file_size_distribution' => $fileSizeDistribution,
                'top_authors' => $topAuthors
            ],
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get API usage statistics
     */
    public function apiStats(): JsonResponse
    {
        // This would typically track API calls if you have logging
        // For now, we'll return basic harvest job statistics as a proxy
        
        $jobStats = HarvestJob::selectRaw('
                job_type,
                status,
                COUNT(*) as count,
                AVG(processed_items) as avg_items,
                AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_duration_seconds
            ')
            ->where('created_at', '>', now()->subWeek())
            ->groupBy('job_type', 'status')
            ->get();

        // Calculate estimated API calls based on job data
        $estimatedAPICalls = HarvestJob::where('completed_at', '>', now()->subDay())
            ->sum('processed_items') * 2; // Rough estimate: 2 API calls per processed item

        return response()->json([
            'success' => true,
            'api_stats' => [
                'job_statistics' => $jobStats,
                'estimated_daily_api_calls' => $estimatedAPICalls,
                'last_updated' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Get basic stats for the admin dashboard
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'games' => Game::count(),
            'mods' => Mod::count(),
            'categories' => Category::count(),
            'jobs' => HarvestJob::whereIn('status', ['pending', 'running'])->count(),
        ]);
    }
}