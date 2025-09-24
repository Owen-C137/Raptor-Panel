<?php

namespace PterodactylAddons\ModManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use PterodactylAddons\ModManager\Models\Mod;
use PterodactylAddons\ModManager\Models\ModFile;
use PterodactylAddons\ModManager\Models\Category;
use PterodactylAddons\ModManager\Models\Game;

class ModController extends Controller
{
    /**
     * Get mods with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mod::with(['game', 'primaryCategory', 'categories'])
            ->orderBy('last_indexed_at', 'desc');

        // Apply filters
        if ($request->has('game') && $request->input('game') !== 'all') {
            $query->whereHas('game', function($q) use ($request) {
                $q->where('slug', $request->input('game'));
            });
        }

        if ($request->has('category') && $request->input('category') !== 'all') {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->input('category'));
            });
        }

        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('authors', 'like', "%{$search}%");
            });
        }

        if ($request->has('downloads_min')) {
            $query->where('download_count', '>=', $request->input('downloads_min'));
        }

        if ($request->has('updated_since')) {
            $query->where('date_modified', '>=', $request->input('updated_since'));
        }

        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100); // Maximum 100 per page

        $mods = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'mods' => $mods->items(),
            'pagination' => [
                'current_page' => $mods->currentPage(),
                'per_page' => $mods->perPage(),
                'total' => $mods->total(),
                'last_page' => $mods->lastPage(),
                'has_more' => $mods->hasMorePages()
            ]
        ]);
    }

    /**
     * Get a specific mod with its files
     */
    public function show(Request $request, int $modId): JsonResponse
    {
        $mod = Mod::with(['game', 'primaryCategory', 'categories', 'files'])
            ->find($modId);

        if (!$mod) {
            return response()->json([
                'success' => false,
                'message' => 'Mod not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'mod' => $mod
        ]);
    }

    /**
     * Get mod files for a specific mod
     */
    public function files(Request $request, int $modId): JsonResponse
    {
        $mod = Mod::find($modId);

        if (!$mod) {
            return response()->json([
                'success' => false,
                'message' => 'Mod not found'
            ], 404);
        }

        $query = ModFile::where('mod_id', $modId)
            ->orderBy('file_date', 'desc');

        // Filter by release type
        if ($request->has('release_type') && $request->input('release_type') !== 'all') {
            $query->where('release_type', $request->input('release_type'));
        }

        // Filter by game version
        if ($request->has('game_version') && !empty($request->input('game_version'))) {
            $query->whereJsonContains('game_versions', $request->input('game_version'));
        }

        $files = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'mod' => $mod->only(['id', 'name', 'slug']),
            'files' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
                'last_page' => $files->lastPage(),
                'has_more' => $files->hasMorePages()
            ]
        ]);
    }

    /**
     * Get mod categories for filtering
     */
    public function categories(Request $request): JsonResponse
    {
        $gameSlug = $request->input('game');
        
        $query = Category::withCount('mods');

        if ($gameSlug && $gameSlug !== 'all') {
            $query->whereHas('game', function($q) use ($gameSlug) {
                $q->where('slug', $gameSlug);
            });
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Get available games
     */
    public function games(): JsonResponse
    {
        $games = Game::withCount('mods')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'games' => $games
        ]);
    }

    /**
     * Search mods with advanced filters
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'game' => 'nullable|string',
            'category' => 'nullable|integer',
            'sort' => 'nullable|string|in:relevance,popularity,updated,name',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $searchQuery = $request->input('query');
        $gameSlug = $request->input('game');
        $categoryId = $request->input('category');
        $sortBy = $request->input('sort', 'relevance');
        $limit = $request->input('limit', 20);

        $query = Mod::with(['game', 'primaryCategory']);

        // Apply search
        $query->where(function($q) use ($searchQuery) {
            $q->where('name', 'like', "%{$searchQuery}%")
              ->orWhere('summary', 'like', "%{$searchQuery}%")
              ->orWhere('authors', 'like', "%{$searchQuery}%");
        });

        // Apply filters
        if ($gameSlug) {
            $query->whereHas('game', function($q) use ($gameSlug) {
                $q->where('slug', $gameSlug);
            });
        }

        if ($categoryId) {
            $query->whereHas('categories', function($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // Apply sorting
        switch ($sortBy) {
            case 'popularity':
                $query->orderBy('download_count', 'desc');
                break;
            case 'updated':
                $query->orderBy('date_modified', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default: // relevance
                $query->orderByRaw("
                    CASE 
                        WHEN name LIKE '%{$searchQuery}%' THEN 1
                        WHEN summary LIKE '%{$searchQuery}%' THEN 2
                        WHEN authors LIKE '%{$searchQuery}%' THEN 3
                        ELSE 4
                    END
                ")->orderBy('download_count', 'desc');
                break;
        }

        $mods = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'query' => $searchQuery,
            'results' => $mods,
            'total' => $mods->count()
        ]);
    }

    /**
     * Get popular mods
     */
    public function popular(Request $request): JsonResponse
    {
        $gameSlug = $request->input('game');
        $limit = min($request->input('limit', 20), 50);

        $query = Mod::with(['game', 'primaryCategory'])
            ->where('download_count', '>', 0)
            ->orderBy('download_count', 'desc');

        if ($gameSlug && $gameSlug !== 'all') {
            $query->whereHas('game', function($q) use ($gameSlug) {
                $q->where('slug', $gameSlug);
            });
        }

        $mods = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'popular_mods' => $mods
        ]);
    }

    /**
     * Get recently updated mods
     */
    public function recent(Request $request): JsonResponse
    {
        $gameSlug = $request->input('game');
        $limit = min($request->input('limit', 20), 50);

        $query = Mod::with(['game', 'primaryCategory'])
            ->orderBy('date_modified', 'desc');

        if ($gameSlug && $gameSlug !== 'all') {
            $query->whereHas('game', function($q) use ($gameSlug) {
                $q->where('slug', $gameSlug);
            });
        }

        $mods = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'recent_mods' => $mods
        ]);
    }
}