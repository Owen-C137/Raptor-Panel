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