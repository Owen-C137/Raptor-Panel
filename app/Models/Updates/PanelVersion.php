<?php

namespace Pterodactyl\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Panel Version Model
 * 
 * Tracks all available panel versions and their metadata
 * including GitHub release information and migration requirements.
 */
class PanelVersion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'panel_versions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'version',
        'is_current',
        'release_date',
        'release_notes',
        'changelog_data',
        'github_release_id',
        'github_tag',
        'release_url',
        'download_url',
        'archive_checksum',
        'requires_migration',
        'migration_files',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_current' => 'boolean',
        'release_date' => 'datetime',
        'changelog_data' => 'array',
        'github_release_id' => 'integer',
        'requires_migration' => 'boolean',
        'migration_files' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all update sessions for this version as target version.
     */
    public function updateSessionsAsTarget(): HasMany
    {
        return $this->hasMany(UpdateSession::class, 'to_version', 'version');
    }

    /**
     * Get all update sessions for this version as source version.
     */
    public function updateSessionsAsSource(): HasMany
    {
        return $this->hasMany(UpdateSession::class, 'from_version', 'version');
    }

    /**
     * Scope to get only the current version.
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get versions that require migrations.
     */
    public function scopeRequiresMigration($query)
    {
        return $query->where('requires_migration', true);
    }

    /**
     * Scope to get versions from GitHub releases.
     */
    public function scopeFromGitHub($query)
    {
        return $query->whereNotNull('github_release_id');
    }

    /**
     * Mark this version as current and unmark all others.
     */
    public function setCurrent(): bool
    {
        // Start a transaction to ensure atomicity
        return \DB::transaction(function () {
            // Unmark all other versions as current
            static::where('is_current', true)->update(['is_current' => false]);
            
            // Mark this version as current
            return $this->update(['is_current' => true]);
        });
    }

    /**
     * Check if this version is newer than another version.
     */
    public function isNewerThan(string $otherVersion): bool
    {
        return version_compare($this->version, $otherVersion, '>');
    }

    /**
     * Check if this version is older than another version.
     */
    public function isOlderThan(string $otherVersion): bool
    {
        return version_compare($this->version, $otherVersion, '<');
    }

    /**
     * Get the current panel version.
     */
    public static function getCurrentVersion(): ?self
    {
        return static::current()->first();
    }

    /**
     * Get the latest available version.
     */
    public static function getLatestVersion(): ?self
    {
        return static::orderBy('release_date', 'desc')->first();
    }

    /**
     * Check if there's an update available.
     */
    public static function hasUpdateAvailable(): bool
    {
        $current = static::getCurrentVersion();
        $latest = static::getLatestVersion();

        if (!$current || !$latest) {
            return false;
        }

        return $latest->isNewerThan($current->version);
    }
}