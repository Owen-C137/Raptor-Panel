<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModFile extends Model
{
    protected $table = 'mod_files';

    protected $fillable = [
        'curse_file_id',
        'mod_id',
        'display_name',
        'file_name',
        'release_type',
        'file_status',
        'is_available',
        'download_url',
        'file_length',
        'download_count',
        'file_size_on_disk',
        'game_versions',
        'sortable_game_versions',
        'mod_loader_types',
        'game_version_type_id',
        'dependencies',
        'hashes',
        'file_fingerprint',
        'modules',
        'file_date',
        'upload_date',
        'is_server_pack',
        'server_pack_file_id',
        'is_early_access_content',
        'early_access_end_date',
        'expose_as_alternative',
        'parent_project_file_id',
        'alternate_file_id',
    ];

    protected $casts = [
        'curse_file_id' => 'integer',
        'mod_id' => 'integer',
        'release_type' => 'integer',
        'file_status' => 'integer',
        'is_available' => 'boolean',
        'file_length' => 'integer',
        'download_count' => 'integer',
        'file_size_on_disk' => 'integer',
        'game_versions' => 'array',
        'sortable_game_versions' => 'array',
        'mod_loader_types' => 'array',
        'game_version_type_id' => 'integer',
        'dependencies' => 'array',
        'hashes' => 'array',
        'file_fingerprint' => 'integer',
        'modules' => 'array',
        'file_date' => 'datetime',
        'upload_date' => 'datetime',
        'is_server_pack' => 'boolean',
        'server_pack_file_id' => 'integer',
        'is_early_access_content' => 'boolean',
        'early_access_end_date' => 'datetime',
        'expose_as_alternative' => 'boolean',
        'parent_project_file_id' => 'integer',
        'alternate_file_id' => 'integer',
    ];

    /**
     * Get the mod this file belongs to
     */
    public function mod(): BelongsTo
    {
        return $this->belongsTo(Mod::class, 'mod_id');
    }

    /**
     * Get installations of this file
     */
    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class, 'file_id');
    }

    /**
     * Scope to get available files only
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get release files only (not beta/alpha)
     */
    public function scopeRelease($query)
    {
        return $query->where('release_type', 1);
    }

    /**
     * Scope to get beta files
     */
    public function scopeBeta($query)
    {
        return $query->where('release_type', 2);
    }

    /**
     * Scope to get alpha files
     */
    public function scopeAlpha($query)
    {
        return $query->where('release_type', 3);
    }

    /**
     * Scope to get files for specific game version
     */
    public function scopeForGameVersion($query, string $version)
    {
        return $query->whereJsonContains('game_versions', $version);
    }

    /**
     * Scope to get files supporting specific mod loader
     */
    public function scopeForModLoader($query, string $loader)
    {
        return $query->whereJsonContains('mod_loader_types', $loader);
    }

    /**
     * Find file by CurseForge ID
     */
    public static function findByCurseId(int $curseFileId): ?self
    {
        return self::where('curse_file_id', $curseFileId)->first();
    }

    /**
     * Get release type name
     */
    public function getReleaseTypeNameAttribute(): string
    {
        return match($this->release_type) {
            1 => 'Release',
            2 => 'Beta',
            3 => 'Alpha',
            default => 'Unknown'
        };
    }

    /**
     * Get formatted file size
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_length;
        
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' B';
    }

    /**
     * Get supported mod loaders as string
     */
    public function getModLoadersStringAttribute(): string
    {
        if (empty($this->mod_loader_types)) {
            return 'Unknown';
        }

        $loaders = is_array($this->mod_loader_types) ? $this->mod_loader_types : json_decode($this->mod_loader_types, true);
        return implode(', ', $loaders);
    }

    /**
     * Get supported game versions as string
     */
    public function getGameVersionsStringAttribute(): string
    {
        if (empty($this->game_versions)) {
            return 'Unknown';
        }

        $versions = is_array($this->game_versions) ? $this->game_versions : json_decode($this->game_versions, true);
        
        // Sort versions and take first few for display
        sort($versions, SORT_NATURAL);
        $displayVersions = array_slice($versions, -5); // Show latest 5 versions
        
        $result = implode(', ', $displayVersions);
        
        if (count($versions) > 5) {
            $result .= ' (+' . (count($versions) - 5) . ' more)';
        }
        
        return $result;
    }

    /**
     * Check if file supports specific game version
     */
    public function supportsGameVersion(string $version): bool
    {
        if (empty($this->game_versions)) {
            return false;
        }

        $versions = is_array($this->game_versions) ? $this->game_versions : json_decode($this->game_versions, true);
        return in_array($version, $versions);
    }

    /**
     * Check if file supports specific mod loader
     */
    public function supportsModLoader(string $loader): bool
    {
        if (empty($this->mod_loader_types)) {
            return false;
        }

        $loaders = is_array($this->mod_loader_types) ? $this->mod_loader_types : json_decode($this->mod_loader_types, true);
        return in_array($loader, $loaders);
    }

    /**
     * Get file dependencies
     */
    public function getDependenciesInfo(): array
    {
        if (empty($this->dependencies)) {
            return [];
        }

        $deps = is_array($this->dependencies) ? $this->dependencies : json_decode($this->dependencies, true);
        
        $result = [];
        foreach ($deps as $dep) {
            $result[] = [
                'mod_id' => $dep['modId'] ?? null,
                'type' => $dep['relationType'] ?? 'unknown',
                'required' => ($dep['relationType'] ?? 0) === 3, // Required dependency
            ];
        }
        
        return $result;
    }

    /**
     * Check if file has required dependencies
     */
    public function hasRequiredDependencies(): bool
    {
        $deps = $this->getDependenciesInfo();
        
        foreach ($deps as $dep) {
            if ($dep['required']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get download URL for this file
     */
    public function getDownloadUrlAttribute(): string
    {
        return $this->attributes['download_url'] ?? 
               "https://www.curseforge.com/api/v1/mods/{$this->mod->curse_mod_id}/files/{$this->curse_file_id}/download";
    }

    /**
     * Check if file is considered stable
     */
    public function isStable(): bool
    {
        return $this->release_type === 1 && $this->is_available && !$this->is_early_access_content;
    }
}