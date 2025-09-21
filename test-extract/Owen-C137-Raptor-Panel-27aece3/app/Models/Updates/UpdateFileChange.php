<?php

namespace Pterodactyl\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Update File Change Model
 * 
 * Tracks individual file changes during update processes,
 * including checksums, sizes, and processing status.
 */
class UpdateFileChange extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'update_file_changes';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'session_id',
        'file_path',
        'change_type',
        'old_checksum',
        'new_checksum',
        'old_size',
        'new_size',
        'backup_path',
        'status',
        'error_message',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_size' => 'integer',
        'new_size' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Change type constants
     */
    public const CHANGE_ADDED = 'added';
    public const CHANGE_MODIFIED = 'modified';
    public const CHANGE_DELETED = 'deleted';

    /**
     * Status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    /**
     * Get the update session this file change belongs to.
     */
    public function updateSession(): BelongsTo
    {
        return $this->belongsTo(UpdateSession::class, 'session_id', 'session_id');
    }

    /**
     * Scope for pending file changes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for completed file changes
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed file changes
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for skipped file changes
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', self::STATUS_SKIPPED);
    }

    /**
     * Scope for added files
     */
    public function scopeAdded($query)
    {
        return $query->where('change_type', self::CHANGE_ADDED);
    }

    /**
     * Scope for modified files
     */
    public function scopeModified($query)
    {
        return $query->where('change_type', self::CHANGE_MODIFIED);
    }

    /**
     * Scope for deleted files
     */
    public function scopeDeleted($query)
    {
        return $query->where('change_type', self::CHANGE_DELETED);
    }

    /**
     * Check if the file change is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the file change is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the file change failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the file change was skipped
     */
    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    /**
     * Mark the file change as completed
     */
    public function markCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now()
        ]);
    }

    /**
     * Mark the file change as failed
     */
    public function markFailed(string $errorMessage): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'processed_at' => now()
        ]);
    }

    /**
     * Mark the file change as skipped
     */
    public function markSkipped(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
            'processed_at' => now()
        ]);
    }

    /**
     * Get the full file system path
     */
    public function getFullPath(): string
    {
        return base_path($this->file_path);
    }

    /**
     * Get the full backup path
     */
    public function getFullBackupPath(): ?string
    {
        if (!$this->backup_path) {
            return null;
        }

        return storage_path('app/' . ltrim($this->backup_path, '/'));
    }

    /**
     * Check if the original file exists
     */
    public function fileExists(): bool
    {
        return file_exists($this->getFullPath());
    }

    /**
     * Check if backup exists
     */
    public function backupExists(): bool
    {
        $backupPath = $this->getFullBackupPath();
        return $backupPath && file_exists($backupPath);
    }

    /**
     * Verify file integrity using checksum
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->fileExists()) {
            return $this->change_type === self::CHANGE_DELETED;
        }

        if (!$this->new_checksum) {
            return true; // Can't verify without checksum
        }

        $actualChecksum = hash_file('sha256', $this->getFullPath());
        return $actualChecksum === $this->new_checksum;
    }

    /**
     * Get human-readable file size change
     */
    public function getSizeChangeAttribute(): string
    {
        if ($this->change_type === self::CHANGE_ADDED) {
            return '+' . $this->formatBytes($this->new_size ?: 0);
        }

        if ($this->change_type === self::CHANGE_DELETED) {
            return '-' . $this->formatBytes($this->old_size ?: 0);
        }

        if ($this->old_size === null || $this->new_size === null) {
            return 'Unknown';
        }

        $diff = $this->new_size - $this->old_size;
        $sign = $diff >= 0 ? '+' : '';
        
        return $sign . $this->formatBytes($diff);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedOldSizeAttribute(): string
    {
        return $this->old_size ? $this->formatBytes($this->old_size) : 'N/A';
    }

    /**
     * Get formatted new file size
     */
    public function getFormattedNewSizeAttribute(): string
    {
        return $this->new_size ? $this->formatBytes($this->new_size) : 'N/A';
    }

    /**
     * Get change type with icon
     */
    public function getChangeTypeWithIconAttribute(): string
    {
        return match ($this->change_type) {
            self::CHANGE_ADDED => '➕ Added',
            self::CHANGE_MODIFIED => '✏️ Modified', 
            self::CHANGE_DELETED => '🗑️ Deleted',
            default => $this->change_type
        };
    }

    /**
     * Get status with icon
     */
    public function getStatusWithIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '⏳ Pending',
            self::STATUS_COMPLETED => '✅ Completed',
            self::STATUS_FAILED => '❌ Failed',
            self::STATUS_SKIPPED => '⏭️ Skipped',
            default => $this->status
        };
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
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
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as skipped,
                SUM(CASE WHEN change_type = ? THEN 1 ELSE 0 END) as added,
                SUM(CASE WHEN change_type = ? THEN 1 ELSE 0 END) as modified,
                SUM(CASE WHEN change_type = ? THEN 1 ELSE 0 END) as deleted
            ', [
                self::STATUS_PENDING,
                self::STATUS_COMPLETED, 
                self::STATUS_FAILED,
                self::STATUS_SKIPPED,
                self::CHANGE_ADDED,
                self::CHANGE_MODIFIED,
                self::CHANGE_DELETED
            ])
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'pending' => $stats->pending ?? 0,
            'completed' => $stats->completed ?? 0,
            'failed' => $stats->failed ?? 0,
            'skipped' => $stats->skipped ?? 0,
            'added' => $stats->added ?? 0,
            'modified' => $stats->modified ?? 0,
            'deleted' => $stats->deleted ?? 0,
        ];
    }
}