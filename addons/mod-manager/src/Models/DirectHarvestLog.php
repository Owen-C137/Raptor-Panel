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

    /**
     * 📊 ENHANCED ANALYTICS: Get comprehensive harvest metrics
     */
    public function getDetailedMetrics(): array
    {
        $metrics = [
            // Basic metrics
            'session_id' => $this->session_id,
            'status' => $this->status,
            'harvest_type' => $this->harvest_type,
            'duration_seconds' => $this->duration_seconds,
            'formatted_duration' => $this->formatted_duration,
            
            // Processing metrics
            'total_mods' => $this->total_mods,
            'processed_mods' => $this->processed_mods,
            'new_mods' => $this->new_mods,
            'updated_mods' => $this->updated_mods,
            'progress_percentage' => $this->progress_percentage,
            
            // Performance metrics
            'mods_per_second' => $this->mods_per_second,
            'api_calls_made' => $this->api_calls_made,
            'efficiency_score' => $this->getEfficiencyScore(),
            
            // Category-specific metrics
            'category_progress' => $this->getCategoryProgress(),
            'estimated_completion' => $this->getEstimatedCompletion(),
            
            // Error tracking
            'error_count' => $this->error_count ?? 0,
            'error_rate' => $this->getErrorRate(),
        ];
        
        // Add file processing metrics if available
        if ($this->total_files > 0) {
            $metrics['file_metrics'] = [
                'total_files' => $this->total_files,
                'processed_files' => $this->processed_files,
                'new_files' => $this->new_files,
                'updated_files' => $this->updated_files,
                'file_progress_percentage' => $this->getFileProgressPercentage(),
            ];
        }
        
        return $metrics;
    }

    /**
     * 🎯 EFFICIENCY SCORING: Calculate harvest efficiency
     */
    public function getEfficiencyScore(): float
    {
        if (!$this->api_calls_made || $this->api_calls_made <= 0) {
            return 0.0;
        }
        
        // Mods per API call
        $modsPerCall = $this->processed_mods / $this->api_calls_made;
        
        // Scale to 0-100 score (50 mods per call = 100 score)
        return round(min(100, ($modsPerCall / 50) * 100), 2);
    }

    /**
     * 📈 CATEGORY PROGRESS: Get detailed category processing status
     */
    public function getCategoryProgress(): array
    {
        $parameters = $this->parameters ?? [];
        $resultData = json_decode($this->result_data ?? '[]', true);
        
        return [
            'categories_processed' => $resultData['categories_processed'] ?? 0,
            'total_categories' => $resultData['total_categories'] ?? 0,
            'current_category' => $resultData['current_category'] ?? null,
            'current_page' => $resultData['current_page'] ?? 0,
            'category_completion' => $this->getCategoryCompletionPercentage(),
        ];
    }

    /**
     * Get category completion percentage
     */
    public function getCategoryCompletionPercentage(): float
    {
        $resultData = json_decode($this->result_data ?? '[]', true);
        $processed = $resultData['categories_processed'] ?? 0;
        $total = $resultData['total_categories'] ?? 0;
        
        if ($total <= 0) return 0.0;
        
        return round(($processed / $total) * 100, 1);
    }

    /**
     * ⏱️ ESTIMATED COMPLETION: Calculate ETA based on current progress
     */
    public function getEstimatedCompletion(): ?string
    {
        if (!$this->is_active || !$this->started_at) {
            return null;
        }
        
        $elapsedSeconds = now()->diffInSeconds($this->started_at);
        
        if ($this->harvest_type === 'categories') {
            // Category-based estimation
            $categoryProgress = $this->getCategoryProgress();
            $processed = $categoryProgress['categories_processed'];
            $total = $categoryProgress['total_categories'];
            
            if ($processed <= 0 || $total <= 0) return null;
            
            $categoriesPerSecond = $processed / max($elapsedSeconds, 1);
            $remainingCategories = $total - $processed;
            $etaSeconds = $remainingCategories / max($categoriesPerSecond, 0.001);
            
        } else {
            // Mod-based estimation
            if ($this->processed_mods <= 0 || $this->total_mods <= 0) return null;
            
            $modsPerSecond = $this->processed_mods / max($elapsedSeconds, 1);
            $remainingMods = $this->total_mods - $this->processed_mods;
            $etaSeconds = $remainingMods / max($modsPerSecond, 0.001);
        }
        
        if ($etaSeconds <= 0) return 'Completing...';
        
        return $this->formatDuration($etaSeconds);
    }

    /**
     * 📉 ERROR RATE: Calculate error rate for quality monitoring
     */
    public function getErrorRate(): float
    {
        if ($this->processed_mods <= 0) return 0.0;
        
        $errorCount = $this->error_count ?? 0;
        return round(($errorCount / $this->processed_mods) * 100, 2);
    }

    /**
     * 📁 FILE PROGRESS: Get file processing progress percentage
     */
    public function getFileProgressPercentage(): float
    {
        if ($this->total_files <= 0) return 0.0;
        
        return round(($this->processed_files / $this->total_files) * 100, 1);
    }

    /**
     * 🔄 UPDATE PROGRESS: Smart progress update with analytics
     */
    public function updateProgress(array $data): void
    {
        $updateData = [];
        
        // Core progress data
        foreach (['processed_mods', 'processed_files', 'api_calls_made', 'new_mods', 'updated_mods', 'new_files', 'updated_files'] as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        // Calculate performance metrics
        if ($this->started_at && isset($data['processed_mods'])) {
            $elapsedSeconds = now()->diffInSeconds($this->started_at);
            $updateData['duration_seconds'] = $elapsedSeconds;
            
            if ($elapsedSeconds > 0) {
                $updateData['mods_per_second'] = round($data['processed_mods'] / $elapsedSeconds, 2);
            }
        }
        
        // Update result_data with category progress
        if (isset($data['result_data'])) {
            $updateData['result_data'] = json_encode($data['result_data']);
        }
        
        $this->update($updateData);
    }

    /**
     * 🏁 COMPLETE HARVEST: Mark harvest as completed with final metrics
     */
    public function completeHarvest(string $status = 'completed'): void
    {
        $now = now();
        $duration = $this->started_at ? $now->diffInSeconds($this->started_at) : 0;
        
        $this->update([
            'status' => $status,
            'completed_at' => $now,
            'duration_seconds' => $duration,
            'mods_per_second' => $duration > 0 ? round($this->processed_mods / $duration, 2) : 0,
        ]);
        
        // 📊 CACHE HARVEST ANALYTICS for dashboard
        $metrics = $this->getDetailedMetrics();
        \Illuminate\Support\Facades\Cache::put(
            "mod-manager:harvest-analytics:{$this->session_id}",
            $metrics,
            now()->addDays(30)
        );
    }

    /**
     * Helper: Format duration in human-readable format
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * 📊 STATIC: Get harvest performance summary
     */
    public static function getPerformanceSummary(int $days = 7): array
    {
        $logs = static::where('started_at', '>=', now()->subDays($days))
            ->where('status', 'completed')
            ->get();
        
        if ($logs->isEmpty()) {
            return ['no_data' => true];
        }
        
        return [
            'total_harvests' => $logs->count(),
            'avg_duration_minutes' => round($logs->avg('duration_seconds') / 60, 1),
            'avg_mods_per_harvest' => round($logs->avg('processed_mods')),
            'avg_mods_per_second' => round($logs->avg('mods_per_second'), 2),
            'avg_efficiency_score' => round($logs->avg(function ($log) {
                return $log->getEfficiencyScore();
            }), 1),
            'total_mods_harvested' => $logs->sum('processed_mods'),
            'total_api_calls' => $logs->sum('api_calls_made'),
            'best_performance' => $logs->sortByDesc('mods_per_second')->first()?->session_id,
        ];
    }
}