<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'mod_categories';

    protected $fillable = [
        'curse_category_id',
        'game_id',
        'name',
        'slug',
        'url',
        'icon_url',
        'is_class',
        'class_id',
        'parent_category_id',
        'display_index',
        'date_modified',
    ];

    protected $casts = [
        'curse_category_id' => 'integer',
        'game_id' => 'integer',
        'is_class' => 'boolean',
        'class_id' => 'integer',
        'parent_category_id' => 'integer',
        'display_index' => 'integer',
        'date_modified' => 'datetime',
    ];

    /**
     * Get the game this category belongs to
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    /**
     * Get mods in this category (many-to-many through JSON)
     */
    public function mods(): HasMany
    {
        // Note: This is a simplified relationship
        // In reality, mods store categories as JSON array
        return $this->hasMany(Mod::class)->whereJsonContains('categories', $this->curse_category_id);
    }

    /**
     * Scope to get root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_category_id');
    }

    /**
     * Scope to get class categories
     */
    public function scopeClasses($query)
    {
        return $query->where('is_class', true);
    }

    /**
     * Scope to get categories for a specific game
     */
    public function scopeForGame($query, int $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    /**
     * Find category by CurseForge ID
     */
    public static function findByCurseId(int $curseCategoryId): ?self
    {
        return self::where('curse_category_id', $curseCategoryId)->first();
    }

    /**
     * Get mod count for this category
     */
    public function getModCount(): int
    {
        return Mod::whereJsonContains('categories', $this->curse_category_id)->count();
    }

    /**
     * 📊 ENHANCED: Get estimated mod count with intelligent caching
     */
    public function estimatedModCount(): int
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "mod-manager:category:{$this->id}:mod-count",
            now()->addHours(6),
            fn() => $this->getEstimatedModCountFromApi()
        );
    }

    /**
     * Get estimated mod count from CurseForge API (without downloading all mods)
     */
    private function getEstimatedModCountFromApi(): int
    {
        try {
            $curseForgeService = app(\PterodactylAddons\ModManager\Services\CurseForgeApiService::class);
            
            $response = $curseForgeService->searchMods([
                'gameId' => $this->game->curse_game_id,
                'categoryId' => $this->curse_category_id,
                'pageSize' => 1, // Just get count, not actual mods
                'index' => 0
            ]);
            
            return $response['pagination']['totalCount'] ?? 0;
        } catch (\Exception $e) {
            // Fallback to database count if API fails
            return $this->getModCount();
        }
    }

    /**
     * 🎯 PRIORITY SCORING: Get category priority score for harvest optimization
     */
    public function getPriorityScore(): int
    {
        $cached = \Illuminate\Support\Facades\Cache::get("mod-manager:category:{$this->id}:priority");
        if ($cached !== null) {
            return $cached;
        }
        
        $score = match (strtolower($this->name)) {
            'world generation', 'worldgen' => 100,
            'technology', 'tech', 'industrial' => 95,
            'magic', 'magical' => 90,
            'adventure and rpg', 'adventure' => 85,
            'food', 'farming' => 80,
            'storage', 'utility' => 80,
            'transportation' => 75,
            'decoration', 'decorative' => 70,
            'building' => 70,
            'miscellaneous', 'misc' => 50,
            'library and api', 'api' => 40,
            default => 60
        };
        
        // Boost score based on actual mod count
        $modCount = $this->getModCount();
        if ($modCount > 1000) $score += 10;
        if ($modCount > 5000) $score += 10;
        if ($modCount > 10000) $score += 15;
        
        \Illuminate\Support\Facades\Cache::put(
            "mod-manager:category:{$this->id}:priority",
            $score,
            now()->addDay()
        );
        
        return $score;
    }

    /**
     * 📈 HARVEST ANALYTICS: Get harvest statistics for this category
     */
    public function getHarvestStats(): array
    {
        $statsKey = "mod-manager:category:{$this->id}:stats";
        $stats = \Illuminate\Support\Facades\Cache::get($statsKey, []);
        
        if (empty($stats)) {
            return [
                'never_harvested' => true,
                'estimated_mods' => $this->estimatedModCount(),
                'priority_score' => $this->getPriorityScore()
            ];
        }
        
        return array_merge($stats, [
            'never_harvested' => false,
            'priority_score' => $this->getPriorityScore(),
            'cache_age_hours' => round(now()->diffInHours($stats['last_harvest'] ?? now()), 1)
        ]);
    }

    /**
     * 🔍 SMART SEARCH: Find categories by relevance and priority
     */
    public static function findByRelevance(string $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = strtolower(trim($query));
        
        return static::where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                  ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$query}%"]);
            })
            ->get()
            ->sortByDesc(function ($category) {
                return $category->getPriorityScore();
            })
            ->take($limit);
    }

    /**
     * 🏆 TOP CATEGORIES: Get highest priority categories for a game
     */
    public static function getTopPriorityCategories(int $gameId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('game_id', $gameId)
            ->get()
            ->sortByDesc(function ($category) {
                return $category->getPriorityScore();
            })
            ->take($limit);
    }

    /**
     * Get the full category path (parent > child)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }

    /**
     * Check if this category has child categories
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get all descendant categories (recursive)
     */
    public function descendants(): array
    {
        $descendants = [];
        
        foreach ($this->children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->descendants());
        }
        
        return $descendants;
    }

    /**
     * Get the category URL on CurseForge
     */
    public function getCurseForgeUrlAttribute(): string
    {
        return $this->url ?: "https://www.curseforge.com/minecraft/mc-mods";
    }
}