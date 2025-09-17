<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Analysis results from AI server analysis operations.
 * 
 * @property int $id
 * @property int $server_id
 * @property int $user_id
 * @property string $analysis_type
 * @property array $analysis_data
 * @property array $recommendations
 * @property int $score
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AiAnalysisResult extends Model
{
    protected $table = 'ai_analysis_results';

    protected $fillable = [
        'context_id',
        'context_type',
        'server_id',
        'user_id', 
        'analysis_type',
        'analysis_data',
        'recommendations',
        'score',
        'status',
    ];

    protected $casts = [
        'analysis_data' => 'array',
        'recommendations' => 'array',
        'score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Analysis status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Analysis type constants
     */
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_SECURITY = 'security';
    const TYPE_OPTIMIZATION = 'optimization';
    const TYPE_LOGS = 'logs';
    const TYPE_RESOURCES = 'resources';
    const TYPE_CONFIGURATION = 'configuration';

    /**
     * Get the server this analysis belongs to.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\Server::class);
    }

    /**
     * Get the user who requested this analysis.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\Pterodactyl\Models\User::class);
    }

    /**
     * Scope for completed analyses
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for specific analysis type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('analysis_type', $type);
    }

    /**
     * Scope for analyses above a certain score threshold
     */
    public function scopeAboveScore($query, int $threshold)
    {
        return $query->where('score', '>=', $threshold);
    }

    /**
     * Get formatted analysis results
     */
    public function getFormattedResultsAttribute(): array
    {
        return [
            'type' => $this->analysis_type,
            'score' => $this->score,
            'status' => $this->status,
            'data' => $this->analysis_data,
            'recommendations' => $this->recommendations,
            'created' => $this->created_at->format('Y-m-d H:i:s'),
            'server' => $this->server ? $this->server->name : 'Unknown',
            'user' => $this->user ? $this->user->username : 'System',
        ];
    }

    /**
     * Get the priority level based on score
     */
    public function getPriorityAttribute(): string
    {
        if ($this->score >= 90) return 'low';
        if ($this->score >= 70) return 'medium';
        if ($this->score >= 50) return 'high';
        return 'critical';
    }

    /**
     * Get the color class for UI display
     */
    public function getColorClassAttribute(): string
    {
        switch ($this->priority) {
            case 'low': return 'success';
            case 'medium': return 'warning';
            case 'high': return 'danger';
            case 'critical': return 'danger';
            default: return 'info';
        }
    }

    /**
     * Check if analysis has recommendations
     */
    public function hasRecommendations(): bool
    {
        return !empty($this->recommendations);
    }

    /**
     * Get recommendation count
     */
    public function getRecommendationCount(): int
    {
        return count($this->recommendations);
    }

    /**
     * Mark analysis as completed
     */
    public function markAsCompleted(array $data, array $recommendations, int $score): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'analysis_data' => $data,
            'recommendations' => $recommendations,
            'score' => $score,
        ]);
    }

    /**
     * Mark analysis as failed
     */
    public function markAsFailed(string $reason = ''): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'analysis_data' => ['error' => $reason],
        ]);
    }
}