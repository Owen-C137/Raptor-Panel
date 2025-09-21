<?php

namespace Pterodactyl\Http\Requests\Admin\Updates;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin.settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'auto_check_enabled' => 'boolean',
            'check_interval_hours' => 'integer|min:1|max:168', // Max 1 week
            'auto_backup_enabled' => 'boolean',
            'backup_retention_days' => 'integer|min:1|max:365',
            'max_backup_size_gb' => 'numeric|min:0.1|max:100',
            'require_confirmation' => 'boolean',
            'allow_beta_updates' => 'boolean',
            'notification_enabled' => 'boolean',
            'parallel_file_updates' => 'integer|min:1|max:50',
            'github_token' => 'nullable|string|max:255',
            'github_api_timeout' => 'integer|min:10|max:300',
            'max_execution_time' => 'integer|min:60|max:1800', // Max 30 minutes
            'memory_limit' => 'string|regex:/^\d+(M|G)$/',
            'excluded_file_patterns' => 'array',
            'excluded_file_patterns.*' => 'string|max:255',
            'critical_files' => 'array',
            'critical_files.*' => 'string|max:255',
            'webhook_url' => 'nullable|url|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'maintenance_mode_during_update' => 'boolean',
            'cleanup_temp_files' => 'boolean',
            'verify_ssl' => 'boolean',
            'update_timeout' => 'integer|min:300|max:3600', // 5 minutes to 1 hour
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'check_interval_hours.min' => 'Check interval must be at least 1 hour.',
            'check_interval_hours.max' => 'Check interval cannot exceed 168 hours (1 week).',
            'backup_retention_days.min' => 'Backup retention must be at least 1 day.',
            'backup_retention_days.max' => 'Backup retention cannot exceed 365 days.',
            'max_backup_size_gb.min' => 'Maximum backup size must be at least 0.1 GB.',
            'max_backup_size_gb.max' => 'Maximum backup size cannot exceed 100 GB.',
            'parallel_file_updates.min' => 'Parallel file updates must be at least 1.',
            'parallel_file_updates.max' => 'Parallel file updates cannot exceed 50.',
            'github_api_timeout.min' => 'GitHub API timeout must be at least 10 seconds.',
            'github_api_timeout.max' => 'GitHub API timeout cannot exceed 300 seconds (5 minutes).',
            'max_execution_time.min' => 'Maximum execution time must be at least 60 seconds.',
            'max_execution_time.max' => 'Maximum execution time cannot exceed 1800 seconds (30 minutes).',
            'memory_limit.regex' => 'Memory limit must be in format like "512M" or "1G".',
            'excluded_file_patterns.*.max' => 'File pattern cannot exceed 255 characters.',
            'critical_files.*.max' => 'File path cannot exceed 255 characters.',
            'webhook_url.url' => 'Webhook URL must be a valid URL.',
            'update_timeout.min' => 'Update timeout must be at least 300 seconds (5 minutes).',
            'update_timeout.max' => 'Update timeout cannot exceed 3600 seconds (1 hour).',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'auto_check_enabled' => 'automatic update checking',
            'check_interval_hours' => 'check interval',
            'auto_backup_enabled' => 'automatic backups',
            'backup_retention_days' => 'backup retention period',
            'max_backup_size_gb' => 'maximum backup size',
            'require_confirmation' => 'require confirmation',
            'allow_beta_updates' => 'allow beta updates',
            'notification_enabled' => 'notifications',
            'parallel_file_updates' => 'parallel file updates',
            'github_token' => 'GitHub token',
            'github_api_timeout' => 'GitHub API timeout',
            'max_execution_time' => 'maximum execution time',
            'memory_limit' => 'memory limit',
            'excluded_file_patterns' => 'excluded file patterns',
            'critical_files' => 'critical files',
            'webhook_url' => 'webhook URL',
            'webhook_secret' => 'webhook secret',
            'maintenance_mode_during_update' => 'maintenance mode during update',
            'cleanup_temp_files' => 'cleanup temporary files',
            'verify_ssl' => 'verify SSL certificates',
            'update_timeout' => 'update timeout',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate memory limit format more strictly
            if ($this->has('memory_limit')) {
                $memoryLimit = $this->input('memory_limit');
                if (!preg_match('/^\d+(M|G)$/', $memoryLimit)) {
                    $validator->errors()->add('memory_limit', 'Memory limit must be in format like "512M" or "2G".');
                } else {
                    // Check if the value is reasonable
                    preg_match('/^(\d+)([MG])$/', $memoryLimit, $matches);
                    $value = (int) $matches[1];
                    $unit = $matches[2];
                    
                    if ($unit === 'M' && $value < 256) {
                        $validator->errors()->add('memory_limit', 'Memory limit should be at least 256M.');
                    } elseif ($unit === 'G' && $value > 8) {
                        $validator->errors()->add('memory_limit', 'Memory limit should not exceed 8G.');
                    }
                }
            }

            // Validate file patterns
            if ($this->has('excluded_file_patterns')) {
                foreach ($this->input('excluded_file_patterns', []) as $index => $pattern) {
                    if (empty(trim($pattern))) {
                        $validator->errors()->add("excluded_file_patterns.{$index}", 'File pattern cannot be empty.');
                    }
                }
            }

            // Validate critical files
            if ($this->has('critical_files')) {
                foreach ($this->input('critical_files', []) as $index => $file) {
                    if (empty(trim($file))) {
                        $validator->errors()->add("critical_files.{$index}", 'Critical file path cannot be empty.');
                    }
                }
            }

            // Ensure backup retention is reasonable if backups are enabled
            if ($this->boolean('auto_backup_enabled', true) && $this->input('backup_retention_days', 30) < 7) {
                $validator->errors()->add('backup_retention_days', 'When automatic backups are enabled, retention should be at least 7 days for safety.');
            }
        });
    }
}