<?php

namespace PterodactylAddons\ModManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PterodactylAddons\ModManager\Models\HarvestSkippedItem;

class DirectHarvestLog extends Model
{
    protected $table = 'mod_direct_harvest_logs';

    protected $fillable = [
        'session_id',
        'session_name',
        'harvest_type',
        'user_id',
        'game_id',
        'status',
        'total_mods',
        'total_files',
        'processed_mods',
        'processed_files',
        'api_calls_made',
        'started_at',
        'completed_at',
        'duration_seconds',
        'mods_per_second',
        'parameters',
        'new_mods',
        'updated_mods',
        'new_files',
        'updated_files',
        'error_message',
        'error_count',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'parameters' => 'array',
        'mods_per_second' => 'decimal:2',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function skippedItems(): HasMany
    {
        return $this->hasMany(HarvestSkippedItem::class, 'harvest_log_id');
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $minutes = intval($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_mods <= 0) {
            return 0.0;
        }

        return round(($this->processed_mods / $this->total_mods) * 100, 1);
    }

    /**
     * Check if harvest is active
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'running', 'processing_files' => 'primary',
            'starting' => 'info',
            'stopping' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'stopped', 'force_stopped' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForGame($query, $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('started_at', 'desc');
    }
}