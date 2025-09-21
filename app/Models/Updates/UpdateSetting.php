<?php

namespace Pterodactyl\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Update Setting Model
 * 
 * Stores configurable settings for the update system,
 * including user preferences and system configurations.
 */
class UpdateSetting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'update_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
        'is_system',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'setting_value' => 'array',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Setting key constants for commonly used settings
     */
    public const AUTO_CHECK_ENABLED = 'auto_check_enabled';
    public const CHECK_INTERVAL_HOURS = 'check_interval_hours';
    public const AUTO_BACKUP_ENABLED = 'auto_backup_enabled';
    public const BACKUP_RETENTION_DAYS = 'backup_retention_days';
    public const MAX_BACKUP_SIZE_GB = 'max_backup_size_gb';
    public const REQUIRE_CONFIRMATION = 'require_confirmation';
    public const ALLOW_BETA_UPDATES = 'allow_beta_updates';
    public const NOTIFICATION_ENABLED = 'notification_enabled';
    public const PARALLEL_FILE_UPDATES = 'parallel_file_updates';
    public const EXCLUDED_FILE_PATTERNS = 'excluded_file_patterns';
    public const CRITICAL_FILES = 'critical_files';
    public const GITHUB_CONFIG = 'github_config';
    public const TEMP_DIRECTORY = 'temp_directory';
    public const BACKUP_DIRECTORY = 'backup_directory';

    /**
     * Scope for user-configurable settings
     */
    public function scopeUserConfigurable($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for system settings
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // If setting_value is an array with a single scalar value, return just that value
        if (is_array($setting->setting_value) && count($setting->setting_value) === 1 && !is_array($setting->setting_value[0])) {
            return $setting->setting_value[0];
        }

        return $setting->setting_value;
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value, string $description = null, bool $isSystem = false): bool
    {
        // Ensure value is properly formatted for JSON storage
        if (!is_array($value) && !is_object($value)) {
            $value = $value;
        }

        return static::updateOrCreate(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'description' => $description,
                'is_system' => $isSystem
            ]
        ) !== null;
    }

    /**
     * Get multiple settings as an associative array
     */
    public static function getMultiple(array $keys): array
    {
        $settings = static::whereIn('setting_key', $keys)->get();
        $result = [];

        foreach ($keys as $key) {
            $setting = $settings->where('setting_key', $key)->first();
            $result[$key] = $setting ? $setting->setting_value : null;
        }

        return $result;
    }

    /**
     * Get all user-configurable settings
     */
    public static function getUserSettings(): array
    {
        return static::userConfigurable()
            ->get()
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    /**
     * Get all system settings
     */
    public static function getSystemSettings(): array
    {
        return static::system()
            ->get()
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }

    /**
     * Get all settings as an associative array
     */
    public static function getAllSettings(): array
    {
        return static::all()->pluck('setting_value', 'setting_key')->toArray();
    }

    /**
     * Update multiple settings at once
     */
    public static function updateMultiple(array $settings): bool
    {
        $success = true;

        foreach ($settings as $key => $value) {
            $setting = static::where('setting_key', $key)->first();
            
            if ($setting) {
                if ($setting->is_system) {
                    // Skip system settings when updating from user input
                    continue;
                }
                
                $success &= $setting->update(['setting_value' => $value]);
            }
        }

        return $success;
    }

    /**
     * Check if auto-check is enabled
     */
    public static function isAutoCheckEnabled(): bool
    {
        return (bool) static::getValue(self::AUTO_CHECK_ENABLED, true);
    }

    /**
     * Get check interval in hours
     */
    public static function getCheckInterval(): int
    {
        return (int) static::getValue(self::CHECK_INTERVAL_HOURS, 24);
    }

    /**
     * Check if auto-backup is enabled
     */
    public static function isAutoBackupEnabled(): bool
    {
        return (bool) static::getValue(self::AUTO_BACKUP_ENABLED, true);
    }

    /**
     * Get backup retention days
     */
    public static function getBackupRetentionDays(): int
    {
        return (int) static::getValue(self::BACKUP_RETENTION_DAYS, 30);
    }

    /**
     * Check if confirmation is required for updates
     */
    public static function requiresConfirmation(): bool
    {
        return (bool) static::getValue(self::REQUIRE_CONFIRMATION, true);
    }

    /**
     * Check if beta updates are allowed
     */
    public static function allowsBetaUpdates(): bool
    {
        return (bool) static::getValue(self::ALLOW_BETA_UPDATES, false);
    }

    /**
     * Get excluded file patterns
     */
    public static function getExcludedFilePatterns(): array
    {
        return (array) static::getValue(self::EXCLUDED_FILE_PATTERNS, []);
    }

    /**
     * Get critical files list
     */
    public static function getCriticalFiles(): array
    {
        return (array) static::getValue(self::CRITICAL_FILES, []);
    }

    /**
     * Get GitHub configuration
     */
    public static function getGitHubConfig(): array
    {
        return (array) static::getValue(self::GITHUB_CONFIG, []);
    }

    /**
     * Get temporary directory
     */
    public static function getTempDirectory(): string
    {
        return (string) static::getValue(self::TEMP_DIRECTORY, 'storage/app/updates/temp');
    }

    /**
     * Get backup directory
     */
    public static function getBackupDirectory(): string
    {
        return (string) static::getValue(self::BACKUP_DIRECTORY, 'storage/app/updates/backups');
    }

    /**
     * Get number of parallel file updates
     */
    public static function getParallelFileUpdates(): int
    {
        return (int) static::getValue(self::PARALLEL_FILE_UPDATES, 10);
    }

    /**
     * Reset all settings to defaults
     */
    public static function resetToDefaults(): bool
    {
        // This would typically re-run the seeder
        // For now, we'll just delete all non-system settings
        return static::userConfigurable()->delete() > 0;
    }

    /**
     * Validate a setting value
     */
    public function validateValue(mixed $value): bool
    {
        return match ($this->setting_key) {
            self::CHECK_INTERVAL_HOURS => is_numeric($value) && $value > 0,
            self::BACKUP_RETENTION_DAYS => is_numeric($value) && $value >= 0,
            self::MAX_BACKUP_SIZE_GB => is_numeric($value) && $value > 0,
            self::PARALLEL_FILE_UPDATES => is_numeric($value) && $value > 0 && $value <= 50,
            self::EXCLUDED_FILE_PATTERNS, self::CRITICAL_FILES => is_array($value),
            self::AUTO_CHECK_ENABLED, self::AUTO_BACKUP_ENABLED, 
            self::REQUIRE_CONFIRMATION, self::ALLOW_BETA_UPDATES, 
            self::NOTIFICATION_ENABLED => is_bool($value),
            default => true // Allow any value for unknown settings
        };
    }

    /**
     * Get setting with validation
     */
    public static function getValidatedValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        if (!$setting->validateValue($setting->setting_value)) {
            return $default;
        }

        return $setting->setting_value;
    }
}