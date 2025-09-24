<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mod extends Model
{
    protected $table = 'mod_mods';

    protected $fillable = [
        'curse_mod_id',
        'game_id',
        'name',
        'slug',
        'summary',
        'description',
        'download_count',
        'popularity_rank',
        'thumbs_up_count',
        'rating',
        'logo_url',
        'screenshots',
        'authors',
        'categories',
        'website_url',
        'wiki_url',
        'issues_url',
        'source_url',
        'date_created',
        'date_modified',
        'date_released',
        'allow_mod_distribution',
        'is_available',
        'game_popularity_rank',
        'last_sync_at',
        'sync_status',
        'files_synced_at',
        'file_sync_status',
    ];

    protected $casts = [
        'curse_mod_id' => 'integer',
        'game_id' => 'integer',
        'download_count' => 'integer',
        'popularity_rank' => 'integer',
        'thumbs_up_count' => 'integer',
        'rating' => 'decimal:2',
        'screenshots' => 'array',
        'authors' => 'array',
        'categories' => 'array',
        'allow_mod_distribution' => 'boolean',
        'is_available' => 'boolean',
        'game_popularity_rank' => 'integer',
        'date_created' => 'datetime',
        'date_modified' => 'datetime',
        'date_released' => 'datetime',
        'last_sync_at' => 'datetime',
        'files_synced_at' => 'datetime',
    ];

    /**
     * Get the game this mod belongs to
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * Get the primary category this mod belongs to
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * Get all categories this mod belongs to
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'mod_category_mod', 'mod_id', 'category_id');
    }

    /**
     * Get all files/versions for this mod
     */
    public function files(): HasMany
    {
        return $this->hasMany(ModFile::class, 'mod_id');
    }

    /**
     * Get all installations of this mod
     */
    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'mod_id');
    }

    /**
     * Get the latest file/version
     */
    public function latestFile()
    {
        return $this->hasOne(ModFile::class, 'mod_id')->latest('file_date');
    }

    /**
     * Get only release files (not beta/alpha)
     */
    public function releaseFiles(): HasMany
    {
        return $this->files()->where('release_type', 1);
    }

    /**
     * Scope to get available mods only
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 4); // 4 = Available
    }

    /**
     * Scope to get featured mods
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get mods by popularity
     */
    public function scopePopular($query, int $limit = 50)
    {
        return $query->orderBy('download_count', 'desc')->limit($limit);
    }

    /**
     * Scope to get recently updated mods
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('date_modified', 'desc')->limit($limit);
    }

    /**
     * Scope to search mods by name or description
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%")
            ->orWhere('summary', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%");
    }

    /**
     * Scope to get mods in specific category
     */
    public function scopeInCategory($query, int $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    /**
     * Find mod by CurseForge ID
     */
    public static function findByCurseId(int $curseModId): ?self
    {
        return self::where('curse_mod_id', $curseModId)->first();
    }

    /**
     * Get formatted download count
     */
    public function getDownloadCountFormattedAttribute(): string
    {
        if ($this->download_count >= 1000000) {
            return round($this->download_count / 1000000, 1) . 'M';
        } elseif ($this->download_count >= 1000) {
            return round($this->download_count / 1000, 1) . 'K';
        }
        
        return number_format($this->download_count);
    }

    /**
     * Get the mod URL on CurseForge
     */
    public function getCurseForgeUrlAttribute(): string
    {
        return "https://www.curseforge.com/minecraft/mc-mods/{$this->slug}";
    }

    /**
     * Check if mod needs indexing
     */
    public function needsIndexing(): bool
    {
        if (!$this->last_indexed_at) {
            return true;
        }

        // Re-index if more than 24 hours old
        return $this->last_indexed_at->diffInHours(now()) > 24;
    }

    /**
     * Get compatible game versions from files
     */
    public function getCompatibleVersions(): array
    {
        $versions = [];
        
        foreach ($this->files as $file) {
            if ($file->game_versions) {
                $gameVersions = json_decode($file->game_versions, true) ?: [];
                $versions = array_merge($versions, $gameVersions);
            }
        }
        
        return array_unique($versions);
    }

    /**
     * Check if mod supports specific game version
     */
    public function supportsVersion(string $version): bool
    {
        $compatibleVersions = $this->getCompatibleVersions();
        return in_array($version, $compatibleVersions);
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            1 => 'New',
            2 => 'Changes Required',
            3 => 'Under Soft Review',
            4 => 'Available',
            5 => 'Deleted',
            6 => 'Rejected',
            default => 'Unknown'
        };
    }
}