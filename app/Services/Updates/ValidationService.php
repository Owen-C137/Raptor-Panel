<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

/**
 * ValidationService handles system validation and integrity checks.
 * 
 * This service provides comprehensive validation capabilities including:
 * - Pre-update system checks
 * - Post-update integrity validation
 * - Configuration validation
 * - Database connectivity and structure checks
 * - File system permission validation
 * - Service dependency checks
 */
class ValidationService extends BaseUpdateService
{
    /**
     * Validate system before update.
     *
     * @return array
     */
    public function validatePreUpdate(): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'checks' => [],
        ];

        try {
            // Database connectivity check
            $dbCheck = $this->checkDatabaseConnectivity();
            $results['checks']['database_connectivity'] = $dbCheck;
            if (!$dbCheck['passed']) {
                $results['errors'][] = $dbCheck['error'];
                $results['valid'] = false;
            }

            // File system permissions
            $permissionCheck = $this->checkFileSystemPermissions();
            $results['checks']['file_permissions'] = $permissionCheck;
            if (!$permissionCheck['passed']) {
                $results['errors'][] = $permissionCheck['error'];
                $results['valid'] = false;
            }

            // Disk space check
            $diskCheck = $this->checkDiskSpace();
            $results['checks']['disk_space'] = $diskCheck;
            if (!$diskCheck['passed']) {
                if ($diskCheck['critical']) {
                    $results['errors'][] = $diskCheck['error'];
                    $results['valid'] = false;
                } else {
                    $results['warnings'][] = $diskCheck['error'];
                }
            }

            // PHP requirements
            $phpCheck = $this->checkPhpRequirements();
            $results['checks']['php_requirements'] = $phpCheck;
            if (!$phpCheck['passed']) {
                $results['errors'][] = $phpCheck['error'];
                $results['valid'] = false;
            }

            // Configuration validation
            $configCheck = $this->validateConfiguration();
            $results['checks']['configuration'] = $configCheck;
            if (!$configCheck['passed']) {
                $results['warnings'][] = $configCheck['error'];
            }

            // Service dependencies
            $serviceCheck = $this->checkServiceDependencies();
            $results['checks']['service_dependencies'] = $serviceCheck;
            if (!$serviceCheck['passed']) {
                $results['warnings'][] = $serviceCheck['error'];
            }

            Log::info("Pre-update validation completed", [
                'valid' => $results['valid'],
                'errors' => count($results['errors']),
                'warnings' => count($results['warnings']),
            ]);

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = "Validation failed: " . $e->getMessage();
            
            Log::error("Pre-update validation exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Validate system after update.
     *
     * @return array
     */
    public function validatePostUpdate(): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'checks' => [],
        ];

        try {
            // Application bootstrap check
            $bootstrapCheck = $this->checkApplicationBootstrap();
            $results['checks']['application_bootstrap'] = $bootstrapCheck;
            if (!$bootstrapCheck['passed']) {
                $results['errors'][] = $bootstrapCheck['error'];
                $results['valid'] = false;
            }

            // Database migrations check
            $migrationCheck = $this->checkDatabaseMigrations();
            $results['checks']['database_migrations'] = $migrationCheck;
            if (!$migrationCheck['passed']) {
                $results['warnings'][] = $migrationCheck['error'];
            }

            // Critical files check
            $filesCheck = $this->checkCriticalFiles();
            $results['checks']['critical_files'] = $filesCheck;
            if (!$filesCheck['passed']) {
                $results['errors'][] = $filesCheck['error'];
                $results['valid'] = false;
            }

            // Configuration integrity
            $configIntegrityCheck = $this->checkConfigurationIntegrity();
            $results['checks']['config_integrity'] = $configIntegrityCheck;
            if (!$configIntegrityCheck['passed']) {
                $results['warnings'][] = $configIntegrityCheck['error'];
            }

            // Service functionality
            $functionalityCheck = $this->checkServiceFunctionality();
            $results['checks']['service_functionality'] = $functionalityCheck;
            if (!$functionalityCheck['passed']) {
                $results['warnings'][] = $functionalityCheck['error'];
            }

            // Version consistency
            $versionCheck = $this->checkVersionConsistency();
            $results['checks']['version_consistency'] = $versionCheck;
            if (!$versionCheck['passed']) {
                $results['warnings'][] = $versionCheck['error'];
            }

            Log::info("Post-update validation completed", [
                'valid' => $results['valid'],
                'errors' => count($results['errors']),
                'warnings' => count($results['warnings']),
            ]);

        } catch (Exception $e) {
            $results['valid'] = false;
            $results['errors'][] = "Post-update validation failed: " . $e->getMessage();
            
            Log::error("Post-update validation exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabaseConnectivity(): array
    {
        try {
            DB::connection()->getPdo();
            
            // Test basic query
            $result = DB::select('SELECT 1 as test');
            
            if (empty($result) || $result[0]->test != 1) {
                return [
                    'passed' => false,
                    'error' => 'Database query test failed',
                ];
            }

            return ['passed' => true];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => "Database connectivity failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check file system permissions.
     */
    private function checkFileSystemPermissions(): array
    {
        $criticalPaths = [
            storage_path(),
            storage_path('app'),
            storage_path('logs'),
            storage_path('framework'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            bootstrap_path('cache'),
        ];

        $issues = [];

        foreach ($criticalPaths as $path) {
            if (!File::exists($path)) {
                $issues[] = "Path does not exist: {$path}";
                continue;
            }

            if (!is_writable($path)) {
                $issues[] = "Path is not writable: {$path}";
            }

            if (!is_readable($path)) {
                $issues[] = "Path is not readable: {$path}";
            }
        }

        if (!empty($issues)) {
            return [
                'passed' => false,
                'error' => 'File permission issues: ' . implode(', ', $issues),
                'details' => $issues,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check available disk space.
     */
    private function checkDiskSpace(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);

            if ($freeBytes === false || $totalBytes === false) {
                return [
                    'passed' => false,
                    'critical' => true,
                    'error' => 'Unable to determine disk space',
                ];
            }

            $freeGB = $freeBytes / (1024 * 1024 * 1024);
            $usagePercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

            // Critical: Less than 1GB free or more than 95% used
            if ($freeGB < 1 || $usagePercent > 95) {
                return [
                    'passed' => false,
                    'critical' => true,
                    'error' => sprintf(
                        'Critical disk space: %.2f GB free (%.1f%% used)',
                        $freeGB,
                        $usagePercent
                    ),
                ];
            }

            // Warning: Less than 5GB free or more than 90% used
            if ($freeGB < 5 || $usagePercent > 90) {
                return [
                    'passed' => false,
                    'critical' => false,
                    'error' => sprintf(
                        'Low disk space: %.2f GB free (%.1f%% used)',
                        $freeGB,
                        $usagePercent
                    ),
                ];
            }

            return [
                'passed' => true,
                'free_space_gb' => $freeGB,
                'usage_percent' => $usagePercent,
            ];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'critical' => true,
                'error' => "Disk space check failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check PHP requirements.
     */
    private function checkPhpRequirements(): array
    {
        $issues = [];

        // PHP version check
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $issues[] = "PHP 8.0+ required, current: " . PHP_VERSION;
        }

        // Required extensions
        $requiredExtensions = [
            'bcmath', 'curl', 'gd', 'mbstring', 'mysql', 'openssl',
            'pdo', 'tokenizer', 'xml', 'zip', 'fpm', 'json'
        ];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $issues[] = "Required PHP extension missing: {$extension}";
            }
        }

        // Memory limit check
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1') {
            $memoryBytes = $this->parseMemoryLimit($memoryLimit);
            $requiredBytes = 256 * 1024 * 1024; // 256MB

            if ($memoryBytes < $requiredBytes) {
                $issues[] = "PHP memory_limit too low: {$memoryLimit} (256M+ recommended)";
            }
        }

        if (!empty($issues)) {
            return [
                'passed' => false,
                'error' => 'PHP requirements not met: ' . implode(', ', $issues),
                'details' => $issues,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Validate configuration.
     */
    private function validateConfiguration(): array
    {
        $issues = [];

        try {
            // Check critical config values
            $criticalConfigs = [
                'app.key' => 'Application key not set',
                'database.default' => 'Database connection not configured',
                'app.url' => 'Application URL not set',
            ];

            foreach ($criticalConfigs as $config => $error) {
                if (empty(config($config))) {
                    $issues[] = $error;
                }
            }

            // Check APP_KEY format
            $appKey = config('app.key');
            if ($appKey && !str_starts_with($appKey, 'base64:')) {
                $issues[] = 'Application key format invalid';
            }

        } catch (Exception $e) {
            $issues[] = "Configuration validation error: " . $e->getMessage();
        }

        if (!empty($issues)) {
            return [
                'passed' => false,
                'error' => 'Configuration issues: ' . implode(', ', $issues),
                'details' => $issues,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check service dependencies.
     */
    private function checkServiceDependencies(): array
    {
        $issues = [];

        try {
            // Check Redis if configured
            if (config('cache.default') === 'redis' || config('queue.default') === 'redis') {
                try {
                    \Illuminate\Support\Facades\Redis::ping();
                } catch (Exception $e) {
                    $issues[] = "Redis connection failed: " . $e->getMessage();
                }
            }

            // Check queue worker if using database queues
            if (config('queue.default') === 'database') {
                // Just verify the connection works
                try {
                    DB::table(config('queue.connections.database.table', 'jobs'))->limit(1)->get();
                } catch (Exception $e) {
                    $issues[] = "Queue database table not accessible";
                }
            }

        } catch (Exception $e) {
            $issues[] = "Service dependency check error: " . $e->getMessage();
        }

        if (!empty($issues)) {
            return [
                'passed' => false,
                'error' => 'Service dependency issues: ' . implode(', ', $issues),
                'details' => $issues,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check application bootstrap.
     */
    private function checkApplicationBootstrap(): array
    {
        try {
            // Test basic Laravel functionality
            $app = app();
            
            if (!$app instanceof \Illuminate\Foundation\Application) {
                return [
                    'passed' => false,
                    'error' => 'Laravel application not properly bootstrapped',
                ];
            }

            // Test service container
            $config = $app->make('config');
            if (!$config instanceof \Illuminate\Config\Repository) {
                return [
                    'passed' => false,
                    'error' => 'Service container not working properly',
                ];
            }

            return ['passed' => true];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => "Application bootstrap failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check database migrations.
     */
    private function checkDatabaseMigrations(): array
    {
        try {
            // Check if migrations table exists
            if (!DB::getSchemaBuilder()->hasTable('migrations')) {
                return [
                    'passed' => false,
                    'error' => 'Migrations table does not exist',
                ];
            }

            // Get pending migrations
            $migrator = app('migrator');
            $pendingMigrations = $migrator->pendingMigrations();

            if (!empty($pendingMigrations)) {
                return [
                    'passed' => false,
                    'error' => 'Pending migrations detected: ' . count($pendingMigrations),
                    'pending_count' => count($pendingMigrations),
                ];
            }

            return ['passed' => true];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => "Migration check failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check critical files.
     */
    private function checkCriticalFiles(): array
    {
        $criticalFiles = [
            base_path('composer.json'),
            base_path('artisan'),
            app_path('Http/Kernel.php'),
            config_path('app.php'),
            config_path('database.php'),
        ];

        $missing = [];

        foreach ($criticalFiles as $file) {
            if (!File::exists($file)) {
                $missing[] = basename($file);
            }
        }

        if (!empty($missing)) {
            return [
                'passed' => false,
                'error' => 'Critical files missing: ' . implode(', ', $missing),
                'missing_files' => $missing,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check configuration integrity.
     */
    private function checkConfigurationIntegrity(): array
    {
        try {
            $issues = [];

            // Test config loading
            $appName = config('app.name');
            if (empty($appName)) {
                $issues[] = 'app.name not configured';
            }

            // Test database config
            $dbConfig = config('database.connections.' . config('database.default'));
            if (empty($dbConfig)) {
                $issues[] = 'Database configuration missing';
            }

            if (!empty($issues)) {
                return [
                    'passed' => false,
                    'error' => 'Configuration integrity issues: ' . implode(', ', $issues),
                ];
            }

            return ['passed' => true];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => "Configuration integrity check failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Check service functionality.
     */
    private function checkServiceFunctionality(): array
    {
        $issues = [];

        try {
            // Test cache functionality
            try {
                \Illuminate\Support\Facades\Cache::put('test_key', 'test_value', 60);
                $retrieved = \Illuminate\Support\Facades\Cache::get('test_key');
                
                if ($retrieved !== 'test_value') {
                    $issues[] = 'Cache functionality not working';
                }
                
                \Illuminate\Support\Facades\Cache::forget('test_key');
            } catch (Exception $e) {
                $issues[] = 'Cache system error: ' . $e->getMessage();
            }

            // Test logging
            try {
                Log::info('Post-update validation test log');
            } catch (Exception $e) {
                $issues[] = 'Logging system error: ' . $e->getMessage();
            }

        } catch (Exception $e) {
            $issues[] = "Service functionality check error: " . $e->getMessage();
        }

        if (!empty($issues)) {
            return [
                'passed' => false,
                'error' => 'Service functionality issues: ' . implode(', ', $issues),
                'details' => $issues,
            ];
        }

        return ['passed' => true];
    }

    /**
     * Check version consistency.
     */
    private function checkVersionConsistency(): array
    {
        try {
            // This would check if the version in the database matches
            // the version in composer.json or other version files
            
            // For now, just check if version info is available
            $composerPath = base_path('composer.json');
            if (!File::exists($composerPath)) {
                return [
                    'passed' => false,
                    'error' => 'composer.json not found for version verification',
                ];
            }

            $composerData = json_decode(File::get($composerPath), true);
            if (!isset($composerData['version']) && !isset($composerData['name'])) {
                return [
                    'passed' => false,
                    'error' => 'Invalid composer.json format',
                ];
            }

            return ['passed' => true];

        } catch (Exception $e) {
            return [
                'passed' => false,
                'error' => "Version consistency check failed: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
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
     * Get configuration errors specific to validation.
     *
     * @return array
     */
    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check if required PHP extensions are loaded
        $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $errors[] = "Required PHP extension missing: {$extension}";
            }
        }

        // Check file permissions
        $criticalPaths = [
            storage_path(),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($criticalPaths as $path) {
            if (!is_writable($path)) {
                $errors[] = "Directory not writable: {$path}";
            }
        }

        // Check PHP version requirements
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $errors[] = 'PHP version 8.0.0 or higher is required';
        }

        return $errors;
    }

    /**
     * Get the service name for identification purposes.
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return 'ValidationService';
    }

    /**
     * Run comprehensive system tests.
     *
     * @return array
     */
    public function runSystemTests(): array
    {
        $tests = [
            'database_connectivity' => $this->testDatabaseConnectivity(),
            'file_permissions' => $this->testFilePermissions(),
            'php_extensions' => $this->testPhpExtensions(),
            'system_resources' => $this->testSystemResources(),
            'update_requirements' => $this->testUpdateRequirements(),
            'backup_capability' => $this->testBackupCapability(),
        ];

        return $tests;
    }

    /**
     * Test database connectivity.
     *
     * @return array
     */
    private function testDatabaseConnectivity(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'name' => 'Database Connectivity',
                'status' => 'passed',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database Connectivity',
                'status' => 'failed',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test file permissions.
     *
     * @return array
     */
    private function testFilePermissions(): array
    {
        $requiredPaths = [
            storage_path(),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $issues = [];
        foreach ($requiredPaths as $path) {
            if (!is_writable($path)) {
                $issues[] = $path;
            }
        }

        return [
            'name' => 'File Permissions',
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 
                'All required paths are writable' : 
                'Non-writable paths: ' . implode(', ', $issues),
        ];
    }

    /**
     * Test PHP extensions.
     *
     * @return array
     */
    private function testPhpExtensions(): array
    {
        $requiredExtensions = ['openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json'];
        $missing = [];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return [
            'name' => 'PHP Extensions',
            'status' => empty($missing) ? 'passed' : 'failed',
            'message' => empty($missing) ? 
                'All required extensions are loaded' : 
                'Missing extensions: ' . implode(', ', $missing),
        ];
    }

    /**
     * Test system resources.
     *
     * @return array
     */
    private function testSystemResources(): array
    {
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));
        $memoryUsage = memory_get_usage(true);
        $memoryAvailable = $memoryLimit - $memoryUsage;
        
        $diskFree = disk_free_space('/');
        $requiredDisk = 500 * 1024 * 1024; // 500MB minimum

        $issues = [];
        if ($memoryAvailable < 128 * 1024 * 1024) { // 128MB minimum
            $issues[] = 'Low memory available';
        }
        if ($diskFree < $requiredDisk) {
            $issues[] = 'Insufficient disk space';
        }

        return [
            'name' => 'System Resources',
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) ? 
                'System resources are adequate' : 
                implode(', ', $issues),
        ];
    }

    /**
     * Test update requirements.
     *
     * @return array
     */
    private function testUpdateRequirements(): array
    {
        $issues = [];

        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $issues[] = 'PHP 8.0+ required';
        }

        // Check if we can reach GitHub
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                ],
            ]);
            $response = @file_get_contents('https://api.github.com/zen', false, $context);
            if ($response === false) {
                $issues[] = 'Cannot reach GitHub API';
            }
        } catch (\Exception $e) {
            $issues[] = 'GitHub connectivity issue';
        }

        return [
            'name' => 'Update Requirements',
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 
                'All update requirements are met' : 
                implode(', ', $issues),
        ];
    }

    /**
     * Test backup capability.
     *
     * @return array
     */
    private function testBackupCapability(): array
    {
        $backupPath = storage_path('app/backups');
        
        $issues = [];
        if (!is_dir($backupPath) && !mkdir($backupPath, 0755, true)) {
            $issues[] = 'Cannot create backup directory';
        } elseif (!is_writable($backupPath)) {
            $issues[] = 'Backup directory not writable';
        }

        return [
            'name' => 'Backup Capability',
            'status' => empty($issues) ? 'passed' : 'failed',
            'message' => empty($issues) ? 
                'Backup system is ready' : 
                implode(', ', $issues),
        ];
    }
}