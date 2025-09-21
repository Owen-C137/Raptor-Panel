<?php

namespace Pterodactyl\Services\Updates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * System Health Service
 * 
 * Provides comprehensive real-time system health monitoring
 * including disk space, memory usage, database status, and more.
 */
class SystemHealthService extends BaseUpdateService
{
    public function getServiceName(): string
    {
        return 'System Health Service';
    }

    public function getConfigurationErrors(): array
    {
        return [];
    }

    /**
     * Get comprehensive system health overview with live stats
     */
    public function getSystemHealthOverview(): array
    {
        $this->logInfo('Getting system health overview');
        
        return [
            'timestamp' => now(),
            'overall_status' => 'healthy',
            'checks' => [
                'disk_space' => $this->getDiskSpaceStatus(),
                'memory_usage' => $this->getMemoryUsageStatus(),
                'database_connection' => $this->getDatabaseStatus(),
                'file_permissions' => $this->getFilePermissionsStatus(),
                'php_version' => $this->getPhpVersionStatus(),
                'laravel_requirements' => $this->getLaravelRequirementsStatus(),
                'update_system_integrity' => $this->getUpdateSystemIntegrityStatus(),
                'backup_status' => $this->getBackupStatus(),
            ]
        ];
    }

    /**
     * Get real-time disk space information
     */
    private function getDiskSpaceStatus(): array
    {
        try {
            $path = base_path();
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            
            if ($freeSpace === false || $totalSpace === false) {
                return [
                    'status' => 'error',
                    'message' => 'Unable to determine disk space',
                    'details' => []
                ];
            }
            
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercent = round(($usedSpace / $totalSpace) * 100, 1);
            
            $status = 'healthy';
            if ($usagePercent > 90) {
                $status = 'critical';
            } elseif ($usagePercent > 80) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'message' => "{$usagePercent}% disk space used",
                'details' => [
                    'total_space' => $this->formatBytes($totalSpace),
                    'used_space' => $this->formatBytes($usedSpace),
                    'free_space' => $this->formatBytes($freeSpace),
                    'usage_percent' => $usagePercent,
                    'path' => $path
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Disk space check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Get real-time memory usage information from system
     */
    private function getMemoryUsageStatus(): array
    {
        try {
            // Try to get system memory info from /proc/meminfo (Linux)
            $systemMemory = $this->getSystemMemoryInfo();
            
            if ($systemMemory) {
                $totalMemory = $systemMemory['total'];
                $freeMemory = $systemMemory['free'];
                $availableMemory = $systemMemory['available'] ?? $freeMemory;
                $usedMemory = $totalMemory - $availableMemory;
                $usagePercent = round(($usedMemory / $totalMemory) * 100, 1);
                
                $status = 'healthy';
                if ($usagePercent > 90) {
                    $status = 'critical';
                } elseif ($usagePercent > 80) {
                    $status = 'warning';
                }
                
                return [
                    'status' => $status,
                    'message' => "{$usagePercent}% memory used",
                    'details' => [
                        'current_usage' => $this->formatBytes($usedMemory),
                        'total_memory' => $this->formatBytes($totalMemory),
                        'available_memory' => $this->formatBytes($availableMemory),
                        'usage_percent' => $usagePercent,
                        'source' => 'system'
                    ]
                ];
            }
            
            // Fallback to PHP memory if system info unavailable
            $memoryUsage = memory_get_usage(true);
            $memoryPeak = memory_get_peak_usage(true);
            $memoryLimit = $this->parseSize(ini_get('memory_limit'));
            
            $usagePercent = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 1) : 0;
            
            $status = 'healthy';
            if ($usagePercent > 90) {
                $status = 'critical';
            } elseif ($usagePercent > 80) {
                $status = 'warning';
            }
            
            return [
                'status' => $status,
                'message' => "{$usagePercent}% PHP memory used",
                'details' => [
                    'current_usage' => $this->formatBytes($memoryUsage),
                    'peak_usage' => $this->formatBytes($memoryPeak),
                    'memory_limit' => $this->formatBytes($memoryLimit),
                    'usage_percent' => $usagePercent,
                    'source' => 'php_fallback'
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Memory usage check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check database connection status
     */
    private function getDatabaseStatus(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Test with a simple query
            $result = DB::select('SELECT 1 as test');
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection is working',
                'details' => [
                    'connection_time' => "{$connectionTime}ms",
                    'driver' => DB::connection()->getDriverName(),
                    'database' => DB::connection()->getDatabaseName()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check file permissions for critical directories
     */
    private function getFilePermissionsStatus(): array
    {
        try {
            $directories = [
                'storage' => storage_path(),
                'bootstrap/cache' => base_path('bootstrap/cache'),
                'public' => public_path(),
            ];
            
            $issues = [];
            foreach ($directories as $name => $path) {
                if (!is_writable($path)) {
                    $issues[] = $name;
                }
            }
            
            if (!empty($issues)) {
                return [
                    'status' => 'error',
                    'message' => 'Some directories are not writable: ' . implode(', ', $issues),
                    'details' => [
                        'writable_issues' => $issues,
                        'checked_directories' => array_keys($directories)
                    ]
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'All required directories are writable',
                'details' => [
                    'checked_directories' => array_keys($directories)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'File permissions check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check PHP version compatibility
     */
    private function getPhpVersionStatus(): array
    {
        try {
            $currentVersion = PHP_VERSION;
            $minVersion = '8.0.0';
            
            $status = version_compare($currentVersion, $minVersion, '>=') ? 'healthy' : 'error';
            
            return [
                'status' => $status,
                'message' => "PHP {$currentVersion} (minimum: {$minVersion})",
                'details' => [
                    'current_version' => $currentVersion,
                    'minimum_version' => $minVersion,
                    'sapi' => PHP_SAPI,
                    'extensions_loaded' => get_loaded_extensions()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'PHP version check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check Laravel requirements and extensions
     */
    private function getLaravelRequirementsStatus(): array
    {
        try {
            $requiredExtensions = [
                'openssl',
                'pdo',
                'mbstring',
                'tokenizer',
                'xml',
                'ctype',
                'json',
                'curl',
                'zip',
                'gd',
            ];
            
            $missing = [];
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missing[] = $ext;
                }
            }
            
            if (!empty($missing)) {
                return [
                    'status' => 'error',
                    'message' => 'Missing required PHP extensions: ' . implode(', ', $missing),
                    'details' => [
                        'missing_extensions' => $missing,
                        'required_extensions' => $requiredExtensions
                    ]
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'All required PHP extensions are loaded',
                'details' => [
                    'loaded_extensions' => $requiredExtensions
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Laravel requirements check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check update system integrity
     */
    private function getUpdateSystemIntegrityStatus(): array
    {
        try {
            $requiredClasses = [
                'Pterodactyl\\Services\\Updates\\GitHub\\GitHubReleaseService',
                'Pterodactyl\\Services\\Updates\\Validation\\ValidationService',
                'Pterodactyl\\Http\\Controllers\\Admin\\UpdateController',
                'Pterodactyl\\Models\\Updates\\PanelVersion',
                'Pterodactyl\\Models\\Updates\\UpdateSession',
            ];
            
            $missing = [];
            foreach ($requiredClasses as $class) {
                if (!class_exists($class)) {
                    $missing[] = $class;
                }
            }
            
            if (!empty($missing)) {
                return [
                    'status' => 'error',
                    'message' => 'Missing update system classes',
                    'details' => [
                        'missing_classes' => $missing,
                        'required_classes' => $requiredClasses
                    ]
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Update system integrity verified',
                'details' => [
                    'verified_classes' => $requiredClasses
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Update system integrity check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Check backup system status
     */
    private function getBackupStatus(): array
    {
        try {
            $backupPath = storage_path('app/backups');
            
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $isWritable = is_writable($backupPath);
            $diskSpace = disk_free_space($backupPath);
            
            if (!$isWritable) {
                return [
                    'status' => 'error',
                    'message' => 'Backup directory is not writable',
                    'details' => [
                        'backup_path' => $backupPath,
                        'is_writable' => false
                    ]
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Backup directory is ready',
                'details' => [
                    'backup_path' => $backupPath,
                    'is_writable' => $isWritable,
                    'available_space' => $this->formatBytes($diskSpace),
                    'backup_count' => count(glob($backupPath . '/*.zip'))
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Backup status check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Get system memory information from /proc/meminfo
     */
    private function getSystemMemoryInfo(): ?array
    {
        if (!file_exists('/proc/meminfo')) {
            return null;
        }
        
        try {
            $meminfo = file_get_contents('/proc/meminfo');
            if ($meminfo === false) {
                return null;
            }
            
            $lines = explode("\n", $meminfo);
            $memory = [];
            
            foreach ($lines as $line) {
                if (preg_match('/^(\w+):\s*(\d+)\s*kB/', $line, $matches)) {
                    $key = strtolower($matches[1]);
                    $value = intval($matches[2]) * 1024; // Convert KB to bytes
                    $memory[$key] = $value;
                }
            }
            
            // Extract the values we need
            if (!isset($memory['memtotal'])) {
                return null;
            }
            
            $total = $memory['memtotal'];
            $free = $memory['memfree'] ?? 0;
            $buffers = $memory['buffers'] ?? 0;
            $cached = $memory['cached'] ?? 0;
            $sreclaim = $memory['sreclaimable'] ?? 0;
            
            // Calculate available memory (more accurate than just free)
            $available = $memory['memavailable'] ?? ($free + $buffers + $cached + $sreclaim);
            
            return [
                'total' => $total,
                'free' => $free,
                'available' => $available,
                'buffers' => $buffers,
                'cached' => $cached,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize(string $size): int
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }
}