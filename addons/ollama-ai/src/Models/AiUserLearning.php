<?php

namespace PterodactylAddons\OllamaAi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pterodactyl\Models\User;

class AiUserLearning extends Model
{
    protected $table = 'ai_user_learning';
    
    protected $fillable = [
        'user_id',
        'topic',
        'skill_level',
        'progress_data',
        'learning_style',
        'completion_percentage',
        'time_spent_seconds',
        'last_accessed',
        'completed_at',
    ];
    
    protected $casts = [
        'progress_data' => 'array',
        'completion_percentage' => 'integer',
        'time_spent_seconds' => 'integer',
        'last_accessed' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at) && $this->completion_percentage >= 100;
    }
    
    public function getProgressPercentage(): int
    {
        return $this->completion_percentage ?? 0;
    }
    
    public function getTotalTimeSpent(): string
    {
        $seconds = $this->time_spent_seconds ?? 0;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }
}