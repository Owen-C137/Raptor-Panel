<?php

namespace Pterodactyl\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Update Migration Model
 * 
 * Tracks database migrations during update processes,
 * including rollback SQL and execution status.
 */
class UpdateMigration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'update_migrations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'session_id',
        'migration_file',
        'batch_number',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'rollback_sql',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'batch_number' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    /**
     * Get the update session this migration belongs to.
     */
    public function updateSession(): BelongsTo
    {
        return $this->belongsTo(UpdateSession::class, 'session_id', 'session_id');
    }

    /**
     * Scope for pending migrations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for running migrations
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope for completed migrations
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed migrations
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for rolled back migrations
     */
    public function scopeRolledBack($query)
    {
        return $query->where('status', self::STATUS_ROLLED_BACK);
    }

    /**
     * Check if the migration is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the migration is running
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * Check if the migration is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the migration failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the migration was rolled back
     */
    public function isRolledBack(): bool
    {
        return $this->status === self::STATUS_ROLLED_BACK;
    }

    /**
     * Check if the migration can be rolled back
     */
    public function canRollback(): bool
    {
        return $this->isCompleted() && !empty($this->rollback_sql);
    }

    /**
     * Mark the migration as running
     */
    public function markRunning(): bool
    {
        return $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now()
        ]);
    }

    /**
     * Mark the migration as completed
     */
    public function markCompleted(string $rollbackSql = null): bool
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ];

        if ($rollbackSql !== null) {
            $data['rollback_sql'] = $rollbackSql;
        }

        return $this->update($data);
    }

    /**
     * Mark the migration as failed
     */
    public function markFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage
        ]);
    }

    /**
     * Mark the migration as rolled back
     */
    public function markRolledBack(): bool
    {
        return $this->update([
            'status' => self::STATUS_ROLLED_BACK,
            'completed_at' => now()
        ]);
    }

    /**
     * Get the migration class name from file name
     */
    public function getMigrationClassAttribute(): string
    {
        // Convert migration filename to class name
        // e.g., "2024_01_01_000001_create_users_table.php" -> "CreateUsersTable"
        $filename = basename($this->migration_file, '.php');
        $parts = explode('_', $filename);
        
        // Skip the date and time parts (first 4 parts)
        $classNameParts = array_slice($parts, 4);
        
        return str_replace(' ', '', ucwords(implode(' ', $classNameParts)));
    }

    /**
     * Get the migration name without timestamp
     */
    public function getMigrationNameAttribute(): string
    {
        $filename = basename($this->migration_file, '.php');
        $parts = explode('_', $filename);
        
        // Skip the date and time parts (first 4 parts)
        $nameParts = array_slice($parts, 4);
        
        return implode(' ', $nameParts);
    }

    /**
     * Get the duration of the migration execution
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

        if ($duration < 1) {
            return '< 1 second';
        } elseif ($duration < 60) {
            return $duration . ' seconds';
        } elseif ($duration < 3600) {
            return round($duration / 60) . ' minutes';
        } else {
            return round($duration / 3600, 1) . ' hours';
        }
    }

    /**
     * Get status with icon
     */
    public function getStatusWithIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '⏳ Pending',
            self::STATUS_RUNNING => '🔄 Running',
            self::STATUS_COMPLETED => '✅ Completed',
            self::STATUS_FAILED => '❌ Failed',
            self::STATUS_ROLLED_BACK => '↩️ Rolled Back',
            default => $this->status
        };
    }

    /**
     * Get the full path to the migration file
     */
    public function getFullPathAttribute(): string
    {
        return database_path('migrations/' . $this->migration_file);
    }

    /**
     * Check if the migration file exists
     */
    public function migrationFileExists(): bool
    {
        return file_exists($this->full_path);
    }

    /**
     * Get statistics for a session
     */
    public static function getSessionStats(string $sessionId): array
    {
        $stats = static::where('session_id', $sessionId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as running,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rolled_back,
                AVG(CASE 
                    WHEN started_at IS NOT NULL AND completed_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, started_at, completed_at) 
                    ELSE NULL 
                END) as avg_duration
            ', [
                self::STATUS_PENDING,
                self::STATUS_RUNNING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_ROLLED_BACK
            ])
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'pending' => $stats->pending ?? 0,
            'running' => $stats->running ?? 0,
            'completed' => $stats->completed ?? 0,
            'failed' => $stats->failed ?? 0,
            'rolled_back' => $stats->rolled_back ?? 0,
            'avg_duration' => $stats->avg_duration ? round($stats->avg_duration, 1) : null,
        ];
    }

    /**
     * Order migrations by batch number and filename
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('batch_number')->orderBy('migration_file');
    }

    /**
     * Get next batch number for a session
     */
    public static function getNextBatchNumber(string $sessionId): int
    {
        $maxBatch = static::where('session_id', $sessionId)->max('batch_number');
        return ($maxBatch ?? 0) + 1;
    }
}