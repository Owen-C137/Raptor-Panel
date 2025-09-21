<?php

namespace Pterodactyl\Models\Updates;

use Pterodactyl\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Update Backup Model
 * 
 * Tracks backup files created during update processes,
 * including metadata for restoration and cleanup.
 */
class UpdateBackup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'update_backups';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'backup_id',
        'session_id',
        'version',
        'backup_path',
        'backup_size',
        'compressed_size',
        'checksum',
        'description',
        'includes_database',
        'database_dump_path',
        'files_backed_up',
        'created_by',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'backup_size' => 'integer',
        'compressed_size' => 'integer',
        'includes_database' => 'boolean',
        'files_backed_up' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to generate UUID for backup_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->backup_id)) {
                $model->backup_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the update session that created this backup.
     */
    public function updateSession(): BelongsTo
    {
        return $this->belongsTo(UpdateSession::class, 'session_id', 'session_id');
    }

    /**
     * Get the user who created this backup.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for expired backups
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for unexpired backups
     */
    public function scopeValid($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for backups that include database dumps
     */
    public function scopeWithDatabase($query)
    {
        return $query->where('includes_database', true);
    }

    /**
     * Check if the backup file exists on disk
     */
    public function exists(): bool
    {
        return file_exists($this->getFullPath());
    }

    /**
     * Get the full file system path to the backup
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . ltrim($this->backup_path, '/'));
    }

    /**
     * Get the full file system path to the database dump
     */
    public function getDatabaseDumpFullPath(): ?string
    {
        if (!$this->includes_database || !$this->database_dump_path) {
            return null;
        }

        return storage_path('app/' . ltrim($this->database_dump_path, '/'));
    }

    /**
     * Check if backup has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if backup is valid (exists and not expired)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && $this->exists();
    }

    /**
     * Verify backup integrity using checksum
     */
    public function verifyIntegrity(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $actualChecksum = hash_file('sha256', $this->getFullPath());
        return $actualChecksum === $this->checksum;
    }

    /**
     * Get human-readable backup size
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->backup_size);
    }

    /**
     * Get human-readable compressed size
     */
    public function getFormattedCompressedSizeAttribute(): string
    {
        if (!$this->compressed_size) {
            return 'N/A';
        }

        return $this->formatBytes($this->compressed_size);
    }

    /**
     * Get compression ratio as percentage
     */
    public function getCompressionRatioAttribute(): ?float
    {
        if (!$this->compressed_size || !$this->backup_size) {
            return null;
        }

        return round((1 - ($this->compressed_size / $this->backup_size)) * 100, 1);
    }

    /**
     * Delete the backup file from disk
     */
    public function deleteFile(): bool
    {
        $deleted = true;

        // Delete main backup file
        if ($this->exists()) {
            $deleted &= unlink($this->getFullPath());
        }

        // Delete database dump if exists
        if ($this->includes_database && $this->database_dump_path) {
            $dumpPath = $this->getDatabaseDumpFullPath();
            if ($dumpPath && file_exists($dumpPath)) {
                $deleted &= unlink($dumpPath);
            }
        }

        return $deleted;
    }

    /**
     * Set expiration date based on retention policy
     */
    public function setExpirationFromRetentionDays(int $retentionDays): bool
    {
        return $this->update([
            'expires_at' => now()->addDays($retentionDays)
        ]);
    }

    /**
     * Format bytes into human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clean up expired backups
     */
    public static function cleanupExpired(): int
    {
        $expiredBackups = static::expired()->get();
        $deletedCount = 0;

        foreach ($expiredBackups as $backup) {
            if ($backup->deleteFile()) {
                $backup->delete();
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}