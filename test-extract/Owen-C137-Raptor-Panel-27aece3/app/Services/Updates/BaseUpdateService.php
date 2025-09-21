<?php

namespace Pterodactyl\Services\Updates;

use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Updates\UpdateSetting;

/**
 * Base Update Service
 * 
 * Abstract base class providing common functionality for all update services.
 * Handles logging, configuration management, and error handling.
 */
abstract class BaseUpdateService implements UpdateServiceInterface
{
    /**
     * Log a message with service context.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['service'] = $this->getServiceName();
        Log::log($level, "[Update Service] {$message}", $context);
    }

    /**
     * Log info message.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log warning message.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log error message.
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log debug message.
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Get update configuration.
     */
    protected function getUpdateConfig(): array
    {
        return config('updates', []);
    }

    /**
     * Get a setting value with fallback.
     */
    protected function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            return UpdateSetting::getValue($key, $default);
        } catch (\Exception $e) {
            $this->logWarning("Failed to get setting '{$key}': {$e->getMessage()}");
            return $default;
        }
    }

    /**
     * Get GitHub configuration.
     */
    protected function getGitHubConfig(): array
    {
        return $this->getSetting(UpdateSetting::GITHUB_CONFIG, [
            'owner' => 'Owen-C137',
            'repo' => 'Raptor-Panel',
            'branch' => 'main',
            'api_base' => 'https://api.github.com/repos/Owen-C137/Raptor-Panel',
            'raw_base' => 'https://raw.githubusercontent.com/Owen-C137/Raptor-Panel/main'
        ]);
    }

    /**
     * Get temporary directory path.
     */
    protected function getTempDirectory(): string
    {
        $tempDir = $this->getSetting(UpdateSetting::TEMP_DIRECTORY, 'storage/app/updates/temp');
        $fullPath = base_path($tempDir);
        
        // Ensure directory exists
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $fullPath;
    }

    /**
     * Get backup directory path.
     */
    protected function getBackupDirectory(): string
    {
        $backupDir = $this->getSetting(UpdateSetting::BACKUP_DIRECTORY, 'storage/app/updates/backups');
        $fullPath = base_path($backupDir);
        
        // Ensure directory exists
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $fullPath;
    }

    /**
     * Handle exceptions with proper logging.
     */
    protected function handleException(\Exception $e, string $context = ''): void
    {
        $message = $context ? "{$context}: {$e->getMessage()}" : $e->getMessage();
        $this->logError($message, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Validate required configuration keys.
     */
    protected function validateRequiredConfig(array $config, array $requiredKeys): array
    {
        $errors = [];
        
        foreach ($requiredKeys as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                $errors[] = "Missing required configuration: {$key}";
            }
        }
        
        return $errors;
    }

    /**
     * Check if all required configuration is present.
     */
    public function isConfigured(): bool
    {
        return empty($this->getConfigurationErrors());
    }

    /**
     * Get configuration errors - must be implemented by subclasses.
     */
    abstract public function getConfigurationErrors(): array;
}