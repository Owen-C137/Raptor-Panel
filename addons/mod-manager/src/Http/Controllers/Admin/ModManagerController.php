<?php

namespace PterodactylAddons\ModManager\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Pterodactyl\Http\Controllers\Controller;
use PterodactylAddons\ModManager\Models\Game;
use PterodactylAddons\ModManager\Models\Category;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use PterodactylAddons\ModManager\Models\DirectHarvestLog;
use Carbon\Carbon;

class ModManagerController extends Controller
{
    /**
     * Display the main mod manager dashboard.
     */
    public function index(Request $request): View
    {
        // Get all available games with stats
        $games = Game::withCount(['categories', 'mods'])->get();
        
        // Get global stats
        $stats = [
            'total_games' => Game::count(),
            'total_categories' => Category::count(),
            'total_mods' => Mod::count(),
            'total_files' => ModFile::count(),
            'active_sessions' => DirectHarvestLog::where('status', 'running')->count(),
        ];
        
        return view('admin.mod-manager.index', compact('games', 'stats'));
    }
    
    /**
     * Get live statistics for real-time updates
     */
    public function liveStats(Request $request): JsonResponse
    {
        $gameId = $request->get('game_id');
        
        // Get stats for specific game or global
        if ($gameId) {
            $game = Game::find($gameId);
            if (!$game) {
                return response()->json(['error' => 'Game not found'], 404);
            }
            
            $stats = [
                "game_{$gameId}_mods" => $game->mods()->count(),
                "game_{$gameId}_categories" => $game->categories()->count(),
                "game_{$gameId}_files" => ModFile::whereHas('mod', function($query) use ($gameId) {
                    $query->where('game_id', $gameId);
                })->count(),
                "game_{$gameId}_active_sessions" => DirectHarvestLog::where('game_id', $gameId)
                    ->where('status', 'running')->count(),
            ];
        } else {
            // Global stats for all games
            $stats = [];
            $games = Game::all();
            
            foreach ($games as $game) {
                $stats["game_{$game->id}_mods"] = $game->mods()->count();
                $stats["game_{$game->id}_categories"] = $game->categories()->count();
                $stats["game_{$game->id}_files"] = ModFile::whereHas('mod', function($query) use ($game) {
                    $query->where('game_id', $game->id);
                })->count();
                $stats["game_{$game->id}_active_sessions"] = DirectHarvestLog::where('game_id', $game->id)
                    ->where('status', 'running')->count();
            }
        }
        
        return response()->json($stats);
    }
    
    /**
     * Get harvest history for a specific game
     */
    public function harvestHistory(Request $request): JsonResponse
    {
        $gameId = $request->get('game_id');
        
        if (!$gameId) {
            return response()->json(['error' => 'Game ID required'], 400);
        }
        
        $history = DirectHarvestLog::with('game')
            ->withCount('skippedItems')
            ->where('game_id', $gameId)
            ->orderBy('started_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'session_name' => $log->session_name,
                    'harvest_type' => $log->harvest_type,
                    'status' => $log->status,
                    'status_color' => $log->status_color,
                    'progress_percentage' => $log->progress_percentage,
                    'total_mods' => $log->total_mods,
                    'processed_mods' => $log->processed_mods,
                    'total_files' => $log->total_files,
                    'new_mods' => $log->new_mods,
                    'updated_mods' => $log->updated_mods,
                    'new_files' => $log->new_files,
                    'updated_files' => $log->updated_files,
                    'started_at_human' => $log->started_at->diffForHumans(),
                    'formatted_duration' => $log->formatted_duration,
                    'error_message' => $log->error_message,
                    'error_count' => $log->error_count,
                    'skipped_items' => $log->skipped_items_count ?? 0,
                ];
            });
        
        return response()->json($history);
    }
    
    /**
     * Get detailed game information
     */
    public function gameDetails(Request $request, $gameId): JsonResponse
    {
        $game = Game::with(['categories', 'mods'])->find($gameId);
        
        if (!$game) {
            return response()->json(['error' => 'Game not found'], 404);
        }
        
        $stats = [
            'total_mods' => $game->mods()->count(),
            'total_categories' => $game->categories()->count(),
            'total_files' => ModFile::whereHas('mod', function($query) use ($gameId) {
                $query->where('game_id', $gameId);
            })->count(),
            'recent_harvest_sessions' => DirectHarvestLog::where('game_id', $gameId)
                ->orderBy('started_at', 'desc')
                ->limit(5)
                ->get(),
            'popular_mods' => $game->mods()
                ->orderBy('download_count', 'desc')
                ->limit(10)
                ->select('name', 'download_count', 'logo_url')
                ->get(),
        ];
        
        return response()->json([
            'game' => $game,
            'stats' => $stats
        ]);
    }
    
    /**
     * Get system health status
     */
    public function systemHealth(): JsonResponse
    {
        $health = [
            'database_connection' => true,
            'api_service' => true,
            'active_sessions' => DirectHarvestLog::where('status', 'running')->count(),
            'last_successful_harvest' => DirectHarvestLog::where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->first()?->completed_at?->diffForHumans(),
            'total_mods_collected' => Mod::count(),
            'total_files_collected' => ModFile::count(),
            'disk_usage' => 'N/A', // Could implement disk usage checking
            'memory_usage' => 'N/A', // Could implement memory usage checking
        ];
        
        try {
            // Test database connection
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['database_connection'] = false;
        }
        
        try {
            // Test API service
            $apiService = app(\PterodactylAddons\ModManager\Services\CurseForgeApiService::class);
            $result = $apiService->testConnection();
            $health['api_service'] = $result['success'];
        } catch (\Exception $e) {
            $health['api_service'] = false;
        }
        
        return response()->json($health);
    }
    
    /**
     * API endpoint for games list
     */
    public function apiGames(): JsonResponse
    {
        $games = Game::withCount(['categories', 'mods'])->get();
        return response()->json($games);
    }
    
    /**
     * API endpoint for global stats
     */
    public function apiStats(): JsonResponse
    {
        $stats = [
            'total_games' => Game::count(),
            'total_categories' => Category::count(),
            'total_mods' => Mod::count(),
            'total_files' => ModFile::count(),
            'active_sessions' => DirectHarvestLog::where('status', 'running')->count(),
            'recent_sessions' => DirectHarvestLog::orderBy('created_at', 'desc')->limit(5)->get(),
        ];
        
        return response()->json($stats);
    }
}