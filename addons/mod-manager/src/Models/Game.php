<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $table = 'mod_games';

    protected $fillable = [
        'curse_game_id',
        'name',
        'slug',
        'logo_url',
        'tile_url',
        'cover_url',
        'status',
        'api_status',
        'date_modified',
        'is_active',
    ];

    protected $casts = [
        'curse_game_id' => 'integer',
        'status' => 'integer',
        'api_status' => 'integer',
        'is_active' => 'boolean',
        'date_modified' => 'datetime',
    ];

    /**
     * Get all categories for this game
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'game_id');
    }

    /**
     * Get all mods for this game
     */
    public function mods(): HasMany
    {
        return $this->hasMany(Mod::class, 'game_id');
    }

    /**
     * Get all collections for this game
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'game_id');
    }

    /**
     * Get active categories only
     */
    public function activeCategories(): HasMany
    {
        return $this->categories()->where('is_active', true);
    }

    /**
     * Get available mods only
     */
    public function availableMods(): HasMany
    {
        return $this->mods()->where('is_available', true);
    }

    /**
     * Scope to get only active games
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find game by CurseForge ID
     */
    public static function findByCurseId(int $curseGameId): ?self
    {
        return self::where('curse_game_id', $curseGameId)->first();
    }

    /**
     * Get mod statistics for this game
     */
    public function getModStats(): array
    {
        return [
            'total_mods' => $this->mods()->count(),
            'available_mods' => $this->availableMods()->count(),
            'total_downloads' => $this->mods()->sum('download_count'),
            'total_categories' => $this->categories()->count(),
        ];
    }

    /**
     * Check if this is Minecraft
     */
    public function isMinecraft(): bool
    {
        return $this->curse_game_id === 432;
    }

    /**
     * Get the display name for the game
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the game URL on CurseForge
     */
    public function getCurseForgeUrlAttribute(): string
    {
        return "https://www.curseforge.com/games/{$this->slug}";
    }
}