<?php

namespace Pterodactyl\Models\Updates;

use Pterodactyl\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Update Session Model
 * 
 * Tracks individual update processes from start to completion,
 * including progress, errors, and rollback information.
 */
class UpdateSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'update_sessions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'session_id',
        'from_version',
        'to_version',
        'status',
        'progress_percentage',
        'current_step',
        'total_steps',
        'completed_steps',
        'started_at',
        'completed_at',
        'error_message',
        'error_trace',
        'backup_id',
        'files_to_update',
        'files_updated',
        'files_failed',
        'migrations_to_run',
        'migrations_completed',
        'rollback_data',
        'initiated_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'progress_percentage' => 'integer',
        'total_steps' => 'integer',
        'completed_steps' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'files_to_update' => 'array',
        'files_updated' => 'array',
        'files_failed' => 'array',
        'migrations_to_run' => 'array',
        'migrations_completed' => 'array',
        'rollback_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Valid status values
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_DOWNLOADING = 'downloading';
    public const STATUS_BACKING_UP = 'backing_up';
    public const STATUS_EXTRACTING = 'extracting';
    public const STATUS_UPDATING_FILES = 'updating_files';
    public const STATUS_RUNNING_MIGRATIONS = 'running_migrations';
    public const STATUS_FINALIZING = 'finalizing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    /**
     * Boot method to generate UUID for session_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->session_id)) {
                $model->session_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user who initiated this update session.
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Get the backup associated with this session.
     */
    public function backup(): HasOne
    {
        return $this->hasOne(UpdateBackup::class, 'session_id', 'session_id');
    }

    /**
     * Get all file changes for this session.
     */
    public function fileChanges(): HasMany
    {
        return $this->hasMany(UpdateFileChange::class, 'session_id', 'session_id');
    }

    /**
     * Get all migrations for this session.
     */
    public function migrations(): HasMany
    {
        return $this->hasMany(UpdateMigration::class, 'session_id', 'session_id');
    }

    /**
     * Scope for active sessions (not completed, failed, or rolled back)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_ROLLED_BACK
        ]);
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed sessions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for sessions that can be rolled back
     */
    public function scopeRollbackable($query)
    {
        return $query->whereIn('status', [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED
        ])->whereNotNull('backup_id');
    }

    /**
     * Check if the session is currently active
     */
    public function isActive(): bool
    {
        return !in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_ROLLED_BACK
        ]);
    }

    /**
     * Check if the session has completed successfully
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the session has failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the session can be rolled back
     */
    public function canRollback(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]) 
               && !empty($this->backup_id);
    }

    /**
     * Update the progress of the session
     */
    public function updateProgress(int $percentage, string $currentStep = null): bool
    {
        $data = ['progress_percentage' => min(100, max(0, $percentage))];
        
        if ($currentStep !== null) {
            $data['current_step'] = $currentStep;
        }

        return $this->update($data);
    }

    /**
     * Mark the session as started
     */
    public function markStarted(): bool
    {
        return $this->update([
            'started_at' => now(),
            'status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Mark the session as completed
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percentage' => 100
        ]);
    }

    /**
     * Mark the session as failed
     */
    public function markFailed(string $errorMessage, string $errorTrace = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'error_trace' => $errorTrace
        ]);
    }

    /**
     * Get the duration of the update session
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?: now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get a human readable duration
     */
    public function getHumanDurationAttribute(): string
    {
        $duration = $this->duration;
        
        if ($duration === null) {
            return 'Not started';
        }

        if ($duration < 60) {
            return $duration . ' seconds';
        } elseif ($duration < 3600) {
            return round($duration / 60) . ' minutes';
        } else {
            return round($duration / 3600, 1) . ' hours';
        }
    }
}