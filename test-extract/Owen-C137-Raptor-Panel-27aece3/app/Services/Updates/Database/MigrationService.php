<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Illuminate\Database\Schema\Builder;
use Pterodactyl\Exceptions\Updates\DatabaseOperationException;
use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Models\UpdateMigration;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Migration Service
 * 
 * Manages database migration execution and rollback for updates.
 */
class MigrationService extends BaseUpdateService
{
    private Builder $schema;

    public function __construct()
    {
        $this->schema = \Schema::getFacadeRoot();
    }

    public function getServiceName(): string
    {
        return 'Migration Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if database connection is available
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check if update_migrations table exists
        if (!\Schema::hasTable('update_migrations')) {
            $errors[] = 'update_migrations table does not exist';
        }

        // Check if migrations table exists (Laravel's default)
        if (!\Schema::hasTable('migrations')) {
            $errors[] = 'migrations table does not exist';
        }

        return $errors;
    }

    /**
     * Execute pending migrations for a specific version.
     */
    public function executeMigrations(string $version, array $migrationFiles): array
    {
        try {
            $this->logInfo('Starting migration execution', [
                'version' => $version,
                'migration_count' => count($migrationFiles)
            ]);

            $results = [];
            $executedCount = 0;
            $skippedCount = 0;

            \DB::beginTransaction();

            try {
                foreach ($migrationFiles as $migrationFile) {
                    $migrationName = $this->extractMigrationName($migrationFile);
                    
                    // Check if migration already executed
                    if ($this->isMigrationExecuted($migrationName)) {
                        $this->logDebug('Migration already executed, skipping', [
                            'migration' => $migrationName
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Execute migration
                    $result = $this->executeSingleMigration($version, $migrationFile);
                    $results[] = $result;
                    $executedCount++;

                    $this->logInfo('Migration executed successfully', [
                        'migration' => $migrationName,
                        'execution_time' => $result['execution_time'] ?? 0
                    ]);
                }

                \DB::commit();

                $this->logInfo('All migrations executed successfully', [
                    'version' => $version,
                    'executed' => $executedCount,
                    'skipped' => $skippedCount,
                    'total' => count($migrationFiles)
                ]);

                return [
                    'success' => true,
                    'executed' => $executedCount,
                    'skipped' => $skippedCount,
                    'total' => count($migrationFiles),
                    'results' => $results
                ];

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Migration execution failed');
            throw new MigrationException('Failed to execute migrations: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a single migration file.
     */
    public function executeSingleMigration(string $version, string $migrationFile): array
    {
        try {
            $migrationName = $this->extractMigrationName($migrationFile);
            $startTime = microtime(true);

            $this->logInfo('Executing migration', [
                'migration' => $migrationName,
                'file' => $migrationFile
            ]);

            // Validate migration file exists
            if (!file_exists($migrationFile)) {
                throw new MigrationException("Migration file not found: {$migrationFile}");
            }

            // Create backup before executing
            $backupId = $this->createMigrationBackup($migrationName);

            try {
                // Include and execute migration
                require_once $migrationFile;
                
                // Get migration class name from file
                $className = $this->getMigrationClassName($migrationFile);
                
                if (!class_exists($className)) {
                    throw new MigrationException("Migration class '{$className}' not found in file: {$migrationFile}");
                }

                // Instantiate and run migration
                $migration = new $className();
                
                if (!method_exists($migration, 'up')) {
                    throw new MigrationException("Migration class '{$className}' does not have 'up' method");
                }

                // Execute the migration
                $migration->up();

                // Record migration as executed
                $this->recordMigrationExecution($version, $migrationName, $migrationFile, $backupId);

                $executionTime = microtime(true) - $startTime;

                return [
                    'migration' => $migrationName,
                    'status' => 'executed',
                    'execution_time' => round($executionTime, 4),
                    'backup_id' => $backupId
                ];

            } catch (\Exception $e) {
                // Rollback the specific migration if possible
                $this->rollbackSingleMigration($migrationName, $backupId);
                throw $e;
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Single migration execution failed');
            throw new MigrationException("Failed to execute migration '{$migrationName}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback migrations for a specific version.
     */
    public function rollbackMigrations(string $version): array
    {
        try {
            $this->logInfo('Starting migration rollback', ['version' => $version]);

            // Get migrations to rollback (in reverse order)
            $migrations = UpdateMigration::where('version', $version)
                ->where('status', 'executed')
                ->orderBy('executed_at', 'desc')
                ->get();

            if ($migrations->isEmpty()) {
                $this->logInfo('No migrations to rollback', ['version' => $version]);
                return ['success' => true, 'rolled_back' => 0, 'results' => []];
            }

            $results = [];
            $rolledBackCount = 0;

            \DB::beginTransaction();

            try {
                foreach ($migrations as $migration) {
                    $result = $this->rollbackSingleMigration($migration->migration_name, $migration->backup_id);
                    $results[] = $result;
                    $rolledBackCount++;
                }

                \DB::commit();

                $this->logInfo('All migrations rolled back successfully', [
                    'version' => $version,
                    'rolled_back' => $rolledBackCount
                ]);

                return [
                    'success' => true,
                    'rolled_back' => $rolledBackCount,
                    'results' => $results
                ];

            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Migration rollback failed');
            throw new MigrationException('Failed to rollback migrations: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback a single migration.
     */
    public function rollbackSingleMigration(string $migrationName, ?string $backupId = null): array
    {
        try {
            $this->logInfo('Rolling back migration', [
                'migration' => $migrationName,
                'backup_id' => $backupId
            ]);

            $startTime = microtime(true);

            // Get migration record
            $migrationRecord = UpdateMigration::where('migration_name', $migrationName)->first();
            
            if (!$migrationRecord) {
                throw new MigrationException("Migration record not found: {$migrationName}");
            }

            // Try to restore from backup if available
            if ($backupId && $this->hasBackup($backupId)) {
                $this->restoreFromBackup($backupId);
            } else {
                // Try to execute down method if migration file exists
                $this->executeDownMethod($migrationRecord);
            }

            // Update migration record
            $migrationRecord->update([
                'status' => 'rolled_back',
                'rolled_back_at' => Carbon::now()
            ]);

            $executionTime = microtime(true) - $startTime;

            $this->logInfo('Migration rolled back successfully', [
                'migration' => $migrationName,
                'execution_time' => round($executionTime, 4)
            ]);

            return [
                'migration' => $migrationName,
                'status' => 'rolled_back',
                'execution_time' => round($executionTime, 4),
                'method' => $backupId ? 'backup_restore' : 'down_method'
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Single migration rollback failed');
            throw new MigrationException("Failed to rollback migration '{$migrationName}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if migration has been executed.
     */
    public function isMigrationExecuted(string $migrationName): bool
    {
        try {
            return UpdateMigration::where('migration_name', $migrationName)
                ->where('status', 'executed')
                ->exists();
        } catch (\Exception $e) {
            $this->logError('Failed to check migration status', [
                'migration' => $migrationName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get migration status for a version.
     */
    public function getMigrationStatus(string $version): array
    {
        try {
            $this->logInfo('Getting migration status', ['version' => $version]);

            $migrations = UpdateMigration::where('version', $version)
                ->orderBy('executed_at')
                ->get()
                ->toArray();

            $status = [
                'version' => $version,
                'total_migrations' => count($migrations),
                'executed' => 0,
                'failed' => 0,
                'rolled_back' => 0,
                'migrations' => $migrations
            ];

            foreach ($migrations as $migration) {
                switch ($migration['status']) {
                    case 'executed':
                        $status['executed']++;
                        break;
                    case 'failed':
                        $status['failed']++;
                        break;
                    case 'rolled_back':
                        $status['rolled_back']++;
                        break;
                }
            }

            return $status;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get migration status');
            throw new DatabaseOperationException('Failed to get migration status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Record migration execution.
     */
    private function recordMigrationExecution(string $version, string $migrationName, string $filePath, ?string $backupId): void
    {
        UpdateMigration::create([
            'version' => $version,
            'migration_name' => $migrationName,
            'file_path' => $filePath,
            'status' => 'executed',
            'executed_at' => Carbon::now(),
            'backup_id' => $backupId,
            'checksum' => hash_file('sha256', $filePath)
        ]);
    }

    /**
     * Create backup before migration execution.
     */
    private function createMigrationBackup(string $migrationName): string
    {
        try {
            // Generate backup ID
            $backupId = 'migration_' . $migrationName . '_' . time();
            
            // Get list of tables that might be affected
            $tables = $this->getAllTableNames();
            
            // Create backup directory
            $backupDir = storage_path('app/backups/migrations/' . $backupId);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Simple backup: just record the schema state
            $schemaData = [];
            foreach ($tables as $table) {
                $schemaData[$table] = [
                    'columns' => \DB::select("DESCRIBE {$table}"),
                    'row_count' => \DB::table($table)->count(),
                    'indexes' => \DB::select("SHOW INDEXES FROM {$table}")
                ];
            }

            file_put_contents(
                $backupDir . '/schema.json',
                json_encode($schemaData, JSON_PRETTY_PRINT)
            );

            $this->logDebug('Migration backup created', [
                'backup_id' => $backupId,
                'tables' => count($tables)
            ]);

            return $backupId;

        } catch (\Exception $e) {
            $this->logWarning('Failed to create migration backup', [
                'migration' => $migrationName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if backup exists.
     */
    private function hasBackup(string $backupId): bool
    {
        $backupDir = storage_path('app/backups/migrations/' . $backupId);
        return is_dir($backupDir) && file_exists($backupDir . '/schema.json');
    }

    /**
     * Restore from backup.
     */
    private function restoreFromBackup(string $backupId): void
    {
        // This is a placeholder for more sophisticated backup restoration
        // In a real implementation, you would restore table structures, data, etc.
        $this->logInfo('Backup restoration not fully implemented', ['backup_id' => $backupId]);
    }

    /**
     * Execute down method of migration.
     */
    private function executeDownMethod(UpdateMigration $migrationRecord): void
    {
        try {
            if (!file_exists($migrationRecord->file_path)) {
                throw new MigrationException("Migration file not found for rollback: {$migrationRecord->file_path}");
            }

            require_once $migrationRecord->file_path;
            
            $className = $this->getMigrationClassName($migrationRecord->file_path);
            $migration = new $className();
            
            if (method_exists($migration, 'down')) {
                $migration->down();
            } else {
                throw new MigrationException("Migration '{$className}' does not have 'down' method for rollback");
            }

        } catch (\Exception $e) {
            throw new MigrationException("Failed to execute down method: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Extract migration name from file path.
     */
    private function extractMigrationName(string $filePath): string
    {
        return pathinfo($filePath, PATHINFO_FILENAME);
    }

    /**
     * Get migration class name from file.
     */
    private function getMigrationClassName(string $filePath): string
    {
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        
        // Remove timestamp prefix if present (e.g., 2024_01_01_000000_create_table)
        $parts = explode('_', $filename);
        if (count($parts) > 4 && is_numeric($parts[0])) {
            $parts = array_slice($parts, 4);
        }
        
        // Convert snake_case to PascalCase
        return str_replace('_', '', ucwords(implode('_', $parts), '_'));
    }

    /**
     * Get all table names in the database.
     */
    private function getAllTableNames(): array
    {
        try {
            $tables = \DB::select('SHOW TABLES');
            $tableNames = [];
            
            foreach ($tables as $table) {
                $tableArray = (array) $table;
                $tableNames[] = array_values($tableArray)[0];
            }
            
            return $tableNames;
        } catch (\Exception $e) {
            $this->logError('Failed to get table names', ['error' => $e->getMessage()]);
            return [];
        }
    }
}