<?php

namespace Pterodactyl\Services\Updates;

use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Pterodactyl\Models\UpdateSession;
use Carbon\Carbon;

class HealthService
{
    /**
     * @var \Pterodactyl\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * HealthService constructor.
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get overall system health status.
     *
     * @return array
     */
    public function getOverallHealth(): array
    {
        $checks = $this->performHealthChecks();
        
        $totalChecks = count($checks);
        $passedChecks = count(array_filter($checks, function($check) {
            return $check['status'] === 'passed';
        }));
        
        $healthScore = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0;
        
        return [
            'score' => $healthScore,
            'status' => $this->determineHealthStatus($healthScore),
            'checks' => $checks,
            'last_check' => now(),
            'total_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'checks_passed' => $passedChecks, // Alias for template compatibility
            'failed_checks' => $totalChecks - $passedChecks,
            'uptime' => $this->getSystemUptime()['formatted'] ?? 'N/A',
            'uptime_status' => 'excellent', // Default status
        ];
    }

    /**
     * Perform individual health checks.
     *
     * @return array
     */
    public function performHealthChecks(): array
    {
        return [
            'disk_space' => $this->checkDiskSpace(),
            'memory_usage' => $this->checkMemoryUsage(),
            'database_connection' => $this->checkDatabaseConnection(),
            'file_permissions' => $this->checkFilePermissions(),
            'php_version' => $this->checkPhpVersion(),
            'laravel_requirements' => $this->checkLaravelRequirements(),
            'update_system_integrity' => $this->checkUpdateSystemIntegrity(),
            'backup_status' => $this->checkBackupStatus(),
        ];
    }

    /**
     * Check available disk space.
     *
     * @return array
     */
    private function checkDiskSpace(): array
    {
        $freeBytes = disk_free_space('/');
        $totalBytes = disk_total_space('/');
        $usedBytes = $totalBytes - $freeBytes;
        $usagePercentage = ($usedBytes / $totalBytes) * 100;

        return [
            'name' => 'Disk Space',
            'status' => $usagePercentage < 90 ? 'passed' : 'failed',
            'message' => sprintf('%.1f%% disk space used', $usagePercentage),
            'details' => [
                'free' => $this->formatBytes($freeBytes),
                'used' => $this->formatBytes($usedBytes),
                'total' => $this->formatBytes($totalBytes),
                'percentage' => round($usagePercentage, 1),
            ],
        ];
    }

    /**
     * Check memory usage.
     *
     * @return array
     */
    private function checkMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $usagePercentage = ($memoryUsage / $memoryLimit) * 100;

        return [
            'name' => 'Memory Usage',
            'status' => $usagePercentage < 80 ? 'passed' : 'warning',
            'message' => sprintf('%.1f%% memory used', $usagePercentage),
            'details' => [
                'current' => $this->formatBytes($memoryUsage),
                'limit' => $this->formatBytes($memoryLimit),
                'percentage' => round($usagePercentage, 1),
            ],
        ];
    }

    /**
     * Check database connection.
     *
     * @return array
     */
    private function checkDatabaseConnection(): array
    {
        try {
            \DB::connection()->getPdo();
            
            return [
                'name' => 'Database Connection',
                'status' => 'passed',
                'message' => 'Database connection is working',
                'details' => [
                    'driver' => config('database.default'),
                    'connected' => true,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database Connection',
                'status' => 'failed',
                'message' => 'Database connection failed',
                'details' => [
                    'error' => $e->getMessage(),
                    'connected' => false,
                ],
            ];
        }
    }

    /**
     * Check file permissions.
     *
     * @return array
     */
    private function checkFilePermissions(): array
    {
        $paths = [
            storage_path(),
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        $issues = [];
        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $issues[] = $path;
            }
        }

        return [
            'name' => 'File Permissions',
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 'All required directories are writable' : 'Some directories are not writable',
            'details' => [
                'writable_paths' => count($paths) - count($issues),
                'total_paths' => count($paths),
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Check PHP version.
     *
     * @return array
     */
    private function checkPhpVersion(): array
    {
        $currentVersion = PHP_VERSION;
        $minimumVersion = '8.0.0';
        $isSupported = version_compare($currentVersion, $minimumVersion, '>=');

        return [
            'name' => 'PHP Version',
            'status' => $isSupported ? 'passed' : 'failed',
            'message' => sprintf('PHP %s (minimum: %s)', $currentVersion, $minimumVersion),
            'details' => [
                'current' => $currentVersion,
                'minimum' => $minimumVersion,
                'supported' => $isSupported,
            ],
        ];
    }

    /**
     * Check Laravel requirements.
     *
     * @return array
     */
    private function checkLaravelRequirements(): array
    {
        $extensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath'];
        $missing = [];

        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return [
            'name' => 'Laravel Requirements',
            'status' => empty($missing) ? 'passed' : 'failed',
            'message' => empty($missing) ? 'All required PHP extensions are loaded' : 'Missing required PHP extensions',
            'details' => [
                'loaded' => count($extensions) - count($missing),
                'total' => count($extensions),
                'missing' => $missing,
            ],
        ];
    }

    /**
     * Check update system integrity.
     *
     * @return array
     */
    private function checkUpdateSystemIntegrity(): array
    {
        $requiredClasses = [
            \Pterodactyl\Services\Updates\UpdateOrchestrationService::class,
            \Pterodactyl\Services\Updates\UpdateDownloadService::class,
            \Pterodactyl\Services\Updates\UpdateValidationService::class,
            \Pterodactyl\Services\Updates\BackupService::class,
            \Pterodactyl\Services\Updates\RollbackService::class,
        ];

        $missing = [];
        foreach ($requiredClasses as $class) {
            if (!class_exists($class)) {
                $missing[] = $class;
            }
        }

        return [
            'name' => 'Update System Integrity',
            'status' => empty($missing) ? 'passed' : 'failed',
            'message' => empty($missing) ? 'All update system classes are available' : 'Missing update system classes',
            'details' => [
                'available' => count($requiredClasses) - count($missing),
                'total' => count($requiredClasses),
                'missing' => $missing,
            ],
        ];
    }

    /**
     * Check backup status.
     *
     * @return array
     */
    private function checkBackupStatus(): array
    {
        $backupPath = storage_path('app/backups');
        $backupExists = is_dir($backupPath);
        $backupWritable = $backupExists && is_writable($backupPath);

        return [
            'name' => 'Backup System',
            'status' => $backupWritable ? 'passed' : 'warning',
            'message' => $backupWritable ? 'Backup directory is ready' : 'Backup directory issues detected',
            'details' => [
                'directory_exists' => $backupExists,
                'directory_writable' => $backupWritable,
                'path' => $backupPath,
            ],
        ];
    }

    /**
     * Determine overall health status based on score.
     *
     * @param int $score
     * @return string
     */
    private function determineHealthStatus(int $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 75) {
            return 'good';
        } elseif ($score >= 50) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Parse size string to bytes.
     *
     * @param string $size
     * @return int
     */
    private function parseSize(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get system performance metrics.
     *
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'uptime' => $this->getSystemUptime(),
            'load_average' => $this->getLoadAverage(),
            'process_count' => $this->getProcessCount(),
            'memory_info' => $this->getMemoryInfo(),
            'disk_io' => $this->getDiskIOStats(),
        ];
    }

    /**
     * Get system uptime.
     *
     * @return array
     */
    private function getSystemUptime(): array
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = file_get_contents('/proc/uptime');
            $uptimeSeconds = (float) explode(' ', $uptime)[0];
            
            return [
                'seconds' => $uptimeSeconds,
                'formatted' => gmdate('H:i:s', $uptimeSeconds),
                'days' => floor($uptimeSeconds / 86400),
            ];
        }

        return ['seconds' => 0, 'formatted' => 'N/A', 'days' => 0];
    }

    /**
     * Get load average.
     *
     * @return array
     */
    private function getLoadAverage(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        }

        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    /**
     * Get process count.
     *
     * @return int
     */
    private function getProcessCount(): int
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return (int) exec('ps aux | wc -l');
        }

        return 0;
    }

    /**
     * Get memory information.
     *
     * @return array
     */
    private function getMemoryInfo(): array
    {
        $info = [
            'php_memory_usage' => memory_get_usage(true),
            'php_memory_peak' => memory_get_peak_usage(true),
            'php_memory_limit' => $this->parseSize(ini_get('memory_limit')),
        ];

        if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $matches);
            $info['system_memory_total'] = isset($matches[1]) ? $matches[1] * 1024 : 0;
            
            preg_match('/MemFree:\s+(\d+) kB/', $meminfo, $matches);
            $info['system_memory_free'] = isset($matches[1]) ? $matches[1] * 1024 : 0;
        }

        return $info;
    }

    /**
     * Get disk I/O statistics.
     *
     * @return array
     */
    private function getDiskIOStats(): array
    {
        // Basic implementation - could be expanded with more detailed stats
        return [
            'free_space' => disk_free_space('/'),
            'total_space' => disk_total_space('/'),
            'used_space' => disk_total_space('/') - disk_free_space('/'),
        ];
    }
}