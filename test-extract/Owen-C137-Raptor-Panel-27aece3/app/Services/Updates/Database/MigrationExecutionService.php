<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Builder;
use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Services\Updates\BaseUpdateService;
use Pterodactyl\Models\Updates\UpdateMigration;

/**
 * Safe Migration Execution Engine
 * 
 * Provides atomic migration execution with transaction isolation,
 * rollback points, progress tracking, and comprehensive error recovery.
 */
class MigrationExecutionService extends BaseUpdateService
{
    private array $executionState = [];
    private array $rollbackPoints = [];
    private array $activeTransactions = [];
    private bool $dryRunMode = false;
    private ?string $currentExecutionId = null;

    public function getServiceName(): string
    {
        return 'Migration Execution Engine';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection not available: ' . $e->getMessage();
        }

        // Check transaction support
        if (!DB::connection()->getDriverName() === 'sqlite') {
            try {
                DB::connection()->statement('SELECT 1');
            } catch (\Exception $e) {
                $errors[] = 'Database does not support transactions properly';
            }
        }

        return $errors;
    }

    /**
     * Execute migrations safely with full transaction management.
     */
    public function executeMigrations(array $migrations, array $options = []): array
    {
        $this->currentExecutionId = 'exec_' . uniqid();
        $this->dryRunMode = $options['dry_run'] ?? false;
        
        $startTime = microtime(true);

        try {
            $this->logInfo('Starting migration execution', [
                'execution_id' => $this->currentExecutionId,
                'migration_count' => count($migrations),
                'dry_run' => $this->dryRunMode
            ]);

            // Initialize execution state
            $this->initializeExecutionState($migrations, $options);

            // Create master rollback point
            $masterRollbackPoint = $this->createRollbackPoint('master_execution');

            // Execute migrations in groups
            $results = [];
            foreach ($this->executionState['execution_groups'] as $groupIndex => $group) {
                $groupResult = $this->executeGroup($group, $groupIndex);
                $results[] = $groupResult;

                // Check for critical errors
                if ($groupResult['status'] === 'failed' && $groupResult['critical']) {
                    throw new MigrationException('Critical migration failure in group ' . ($groupIndex + 1));
                }
            }

            // Verify final state
            $this->verifyExecutionState();

            // Cleanup rollback points on success
            if (!$this->dryRunMode) {
                $this->cleanupRollbackPoints();
            }

            $executionTime = microtime(true) - $startTime;

            $result = [
                'execution_id' => $this->currentExecutionId,
                'status' => 'completed',
                'total_migrations' => count($migrations),
                'successful_migrations' => $this->countSuccessfulMigrations($results),
                'failed_migrations' => $this->countFailedMigrations($results),
                'execution_time' => $executionTime,
                'dry_run' => $this->dryRunMode,
                'group_results' => $results,
                'rollback_points' => count($this->rollbackPoints),
                'final_state' => $this->generateExecutionSummary()
            ];

            $this->logInfo('Migration execution completed', [
                'execution_id' => $this->currentExecutionId,
                'status' => 'success',
                'execution_time' => $executionTime,
                'successful_migrations' => $result['successful_migrations']
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleExecutionError($e, $startTime);
            throw new MigrationException('Migration execution failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a single migration with full safety measures.
     */
    public function executeSingleMigration(array $migration, array $options = []): array
    {
        $migrationName = $migration['name'];
        $startTime = microtime(true);

        try {
            $this->logInfo('Executing single migration', [
                'migration' => $migrationName,
                'dry_run' => $this->dryRunMode
            ]);

            // Create rollback point for this migration
            $rollbackPoint = $this->createRollbackPoint($migrationName);

            // Pre-execution validation
            $this->validateMigrationBeforeExecution($migration);

            // Execute in transaction
            $result = DB::transaction(function () use ($migration, $options) {
                return $this->performMigrationExecution($migration, $options);
            });

            // Post-execution validation
            $this->validateMigrationAfterExecution($migration, $result);

            // Record successful execution
            if (!$this->dryRunMode) {
                $this->recordMigrationExecution($migration, $result);
            }

            $executionTime = microtime(true) - $startTime;

            return [
                'migration' => $migrationName,
                'status' => 'success',
                'execution_time' => $executionTime,
                'rollback_point' => $rollbackPoint,
                'result_details' => $result,
                'dry_run' => $this->dryRunMode
            ];

        } catch (\Exception $e) {
            return $this->handleMigrationError($migration, $e, $startTime);
        }
    }

    /**
     * Initialize execution state and planning.
     */
    private function initializeExecutionState(array $migrations, array $options): void
    {
        $this->executionState = [
            'execution_id' => $this->currentExecutionId,
            'total_migrations' => count($migrations),
            'start_time' => Carbon::now(),
            'options' => $options,
            'migrations' => $migrations,
            'execution_groups' => $this->groupMigrationsForExecution($migrations),
            'progress' => [
                'completed' => 0,
                'failed' => 0,
                'skipped' => 0,
                'current_group' => 0
            ]
        ];

        // Initialize rollback points array
        $this->rollbackPoints = [];
        $this->activeTransactions = [];
    }

    /**
     * Group migrations for safe parallel or sequential execution.
     */
    private function groupMigrationsForExecution(array $migrations): array
    {
        $groups = [];
        $currentGroup = [];
        $maxGroupSize = 5; // Limit concurrent migrations
        $maxGroupRisk = 'medium'; // Don't mix high-risk migrations

        foreach ($migrations as $migration) {
            // Check if migration can be added to current group
            if ($this->canAddToGroup($migration, $currentGroup, $maxGroupSize, $maxGroupRisk)) {
                $currentGroup[] = $migration;
            } else {
                // Start new group
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                }
                $currentGroup = [$migration];
            }
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    /**
     * Check if migration can be added to current group.
     */
    private function canAddToGroup(array $migration, array $currentGroup, int $maxSize, string $maxRisk): bool
    {
        // Group size limit
        if (count($currentGroup) >= $maxSize) {
            return false;
        }

        // Risk level considerations
        if ($migration['risk_level'] === 'high' && !empty($currentGroup)) {
            return false; // High-risk migrations run alone
        }

        if ($migration['requires_downtime'] && !empty($currentGroup)) {
            return false; // Downtime migrations run alone
        }

        // Check for conflicts with existing group members
        foreach ($currentGroup as $existing) {
            if ($this->migrationsConflict($migration, $existing)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if two migrations conflict.
     */
    private function migrationsConflict(array $migration1, array $migration2): bool
    {
        // Check table conflicts
        $tables1 = array_merge(
            $migration1['tables']['creates'] ?? [],
            $migration1['tables']['modifies'] ?? [],
            $migration1['tables']['drops'] ?? []
        );

        $tables2 = array_merge(
            $migration2['tables']['creates'] ?? [],
            $migration2['tables']['modifies'] ?? [],
            $migration2['tables']['drops'] ?? []
        );

        return !empty(array_intersect($tables1, $tables2));
    }

    /**
     * Execute a group of migrations.
     */
    private function executeGroup(array $group, int $groupIndex): array
    {
        $groupStartTime = microtime(true);
        
        try {
            $this->logInfo('Executing migration group', [
                'group_index' => $groupIndex,
                'migration_count' => count($group)
            ]);

            $this->executionState['progress']['current_group'] = $groupIndex;

            $groupRollbackPoint = $this->createRollbackPoint("group_{$groupIndex}");

            $groupResults = [];
            $criticalFailure = false;

            // Execute migrations in the group
            foreach ($group as $migration) {
                $migrationResult = $this->executeSingleMigration($migration);
                $groupResults[] = $migrationResult;

                // Update progress
                if ($migrationResult['status'] === 'success') {
                    $this->executionState['progress']['completed']++;
                } else {
                    $this->executionState['progress']['failed']++;
                    
                    // Check if this is a critical failure
                    if ($this->isCriticalMigration($migration)) {
                        $criticalFailure = true;
                        break;
                    }
                }

                // Progress callback
                $this->reportProgress();
            }

            $groupExecutionTime = microtime(true) - $groupStartTime;

            return [
                'group_index' => $groupIndex,
                'status' => $criticalFailure ? 'failed' : 'completed',
                'critical' => $criticalFailure,
                'migration_count' => count($group),
                'successful_migrations' => count(array_filter($groupResults, fn($r) => $r['status'] === 'success')),
                'failed_migrations' => count(array_filter($groupResults, fn($r) => $r['status'] !== 'success')),
                'execution_time' => $groupExecutionTime,
                'rollback_point' => $groupRollbackPoint,
                'migration_results' => $groupResults
            ];

        } catch (\Exception $e) {
            return [
                'group_index' => $groupIndex,
                'status' => 'failed',
                'critical' => true,
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $groupStartTime
            ];
        }
    }

    /**
     * Perform the actual migration execution.
     */
    private function performMigrationExecution(array $migration, array $options): array
    {
        $migrationName = $migration['name'];

        if ($this->dryRunMode) {
            return $this->simulateMigrationExecution($migration);
        }

        // Load migration class
        $migrationClass = $this->loadMigrationClass($migration);
        
        // Create schema builder
        $schema = DB::connection()->getSchemaBuilder();

        $result = [
            'migration' => $migrationName,
            'operations' => [],
            'affected_rows' => 0,
            'execution_details' => []
        ];

        try {
            // Execute up method
            $beforeState = $this->captureSchemaState();
            
            $migrationInstance = new $migrationClass();
            $migrationInstance->up();

            $afterState = $this->captureSchemaState();
            
            $result['operations'] = $this->compareSchemaStates($beforeState, $afterState);
            $result['execution_details'] = [
                'before_state' => $beforeState,
                'after_state' => $afterState,
                'schema_changes' => count($result['operations'])
            ];

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            $result['failed'] = true;
            throw $e;
        }

        return $result;
    }

    /**
     * Simulate migration execution for dry run.
     */
    private function simulateMigrationExecution(array $migration): array
    {
        $this->logInfo('Simulating migration execution (dry run)', [
            'migration' => $migration['name']
        ]);

        // Analyze what the migration would do
        $simulatedOperations = [];

        if (isset($migration['tables']['creates'])) {
            foreach ($migration['tables']['creates'] as $table) {
                $simulatedOperations[] = [
                    'type' => 'create_table',
                    'table' => $table,
                    'estimated_time' => 5
                ];
            }
        }

        if (isset($migration['tables']['modifies'])) {
            foreach ($migration['tables']['modifies'] as $table) {
                $simulatedOperations[] = [
                    'type' => 'modify_table',
                    'table' => $table,
                    'estimated_time' => 3
                ];
            }
        }

        return [
            'migration' => $migration['name'],
            'simulated' => true,
            'operations' => $simulatedOperations,
            'estimated_time' => array_sum(array_column($simulatedOperations, 'estimated_time')),
            'would_succeed' => $this->wouldMigrationSucceed($migration)
        ];
    }

    /**
     * Create a rollback point for recovery.
     */
    private function createRollbackPoint(string $name): string
    {
        $rollbackId = $name . '_' . uniqid();
        
        $rollbackPoint = [
            'id' => $rollbackId,
            'name' => $name,
            'created_at' => Carbon::now(),
            'schema_state' => $this->captureSchemaState(),
            'execution_state' => $this->executionState,
            'transaction_level' => DB::transactionLevel()
        ];

        if (!$this->dryRunMode) {
            // Create database savepoint if supported
            try {
                DB::statement("SAVEPOINT {$rollbackId}");
                $rollbackPoint['has_savepoint'] = true;
            } catch (\Exception $e) {
                $rollbackPoint['has_savepoint'] = false;
                $this->logWarning('Could not create database savepoint', [
                    'rollback_id' => $rollbackId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->rollbackPoints[$rollbackId] = $rollbackPoint;

        $this->logInfo('Created rollback point', [
            'rollback_id' => $rollbackId,
            'name' => $name
        ]);

        return $rollbackId;
    }

    /**
     * Rollback to a specific point.
     */
    public function rollbackToPoint(string $rollbackId): array
    {
        if (!isset($this->rollbackPoints[$rollbackId])) {
            throw new MigrationException("Rollback point {$rollbackId} not found");
        }

        $rollbackPoint = $this->rollbackPoints[$rollbackId];

        try {
            $this->logInfo('Rolling back to point', [
                'rollback_id' => $rollbackId,
                'name' => $rollbackPoint['name']
            ]);

            // Rollback database transaction
            if ($rollbackPoint['has_savepoint'] ?? false) {
                DB::statement("ROLLBACK TO SAVEPOINT {$rollbackId}");
            } else {
                // Manual rollback by reversing operations
                $this->performManualRollback($rollbackPoint);
            }

            // Restore execution state
            $this->executionState = $rollbackPoint['execution_state'];

            return [
                'status' => 'success',
                'rollback_id' => $rollbackId,
                'rolled_back_to' => $rollbackPoint['created_at']->toISOString()
            ];

        } catch (\Exception $e) {
            $this->logError('Rollback failed', [
                'rollback_id' => $rollbackId,
                'error' => $e->getMessage()
            ]);
            
            throw new MigrationException("Rollback to {$rollbackId} failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate migration before execution.
     */
    private function validateMigrationBeforeExecution(array $migration): void
    {
        // Check if migration file exists
        if (isset($migration['file_path']) && !file_exists($migration['file_path'])) {
            throw new MigrationException("Migration file not found: {$migration['file_path']}");
        }

        // Check if migration class can be loaded
        if (!$this->dryRunMode) {
            $this->loadMigrationClass($migration);
        }

        // Validate dependencies are met
        $this->validateMigrationDependencies($migration);

        // Check database state
        $this->validateDatabaseState($migration);
    }

    /**
     * Validate migration after execution.
     */
    private function validateMigrationAfterExecution(array $migration, array $result): void
    {
        if ($this->dryRunMode) {
            return; // Skip validation in dry run mode
        }

        // Check if expected schema changes occurred
        $this->validateSchemaChanges($migration, $result);

        // Validate data integrity
        $this->validateDataIntegrity($migration);

        // Check foreign key constraints
        $this->validateConstraints($migration);
    }

    /**
     * Load migration class.
     */
    private function loadMigrationClass(array $migration): string
    {
        if (!isset($migration['file_path'])) {
            throw new MigrationException("Migration file path not provided for {$migration['name']}");
        }

        require_once $migration['file_path'];

        $className = $migration['class_name'] ?? null;
        if (!$className) {
            throw new MigrationException("Could not determine class name for migration {$migration['name']}");
        }

        if (!class_exists($className)) {
            throw new MigrationException("Migration class {$className} not found");
        }

        return $className;
    }

    /**
     * Capture current schema state.
     */
    private function captureSchemaState(): array
    {
        if ($this->dryRunMode) {
            return ['simulated' => true];
        }

        try {
            $schema = DB::connection()->getSchemaBuilder();
            $tables = [];

            $tableNames = $this->getTableNames();
            foreach ($tableNames as $tableName) {
                $tables[$tableName] = [
                    'exists' => $schema->hasTable($tableName),
                    'columns' => $this->getTableColumns($tableName)
                ];
            }

            return [
                'timestamp' => Carbon::now()->toISOString(),
                'tables' => $tables,
                'database' => DB::connection()->getDatabaseName()
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toISOString()
            ];
        }
    }

    /**
     * Get table names from database.
     */
    private function getTableNames(): array
    {
        try {
            return DB::connection()->getDoctrineSchemaManager()->listTableNames();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table columns.
     */
    private function getTableColumns(string $tableName): array
    {
        try {
            return array_keys(DB::connection()->getSchemaBuilder()->getColumnListing($tableName));
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Compare two schema states.
     */
    private function compareSchemaStates(array $beforeState, array $afterState): array
    {
        $changes = [];

        if ($this->dryRunMode) {
            return ['simulated_changes' => true];
        }

        // Compare tables
        $beforeTables = $beforeState['tables'] ?? [];
        $afterTables = $afterState['tables'] ?? [];

        foreach ($afterTables as $tableName => $tableInfo) {
            if (!isset($beforeTables[$tableName])) {
                $changes[] = [
                    'type' => 'table_created',
                    'table' => $tableName
                ];
            } elseif ($beforeTables[$tableName] !== $tableInfo) {
                $changes[] = [
                    'type' => 'table_modified',
                    'table' => $tableName,
                    'changes' => $this->compareTableStates($beforeTables[$tableName], $tableInfo)
                ];
            }
        }

        foreach ($beforeTables as $tableName => $tableInfo) {
            if (!isset($afterTables[$tableName])) {
                $changes[] = [
                    'type' => 'table_dropped',
                    'table' => $tableName
                ];
            }
        }

        return $changes;
    }

    /**
     * Compare two table states.
     */
    private function compareTableStates(array $beforeTable, array $afterTable): array
    {
        $changes = [];

        $beforeColumns = $beforeTable['columns'] ?? [];
        $afterColumns = $afterTable['columns'] ?? [];

        $addedColumns = array_diff($afterColumns, $beforeColumns);
        $removedColumns = array_diff($beforeColumns, $afterColumns);

        if (!empty($addedColumns)) {
            $changes['added_columns'] = $addedColumns;
        }

        if (!empty($removedColumns)) {
            $changes['removed_columns'] = $removedColumns;
        }

        return $changes;
    }

    /**
     * Handle migration execution error.
     */
    private function handleMigrationError(array $migration, \Exception $error, float $startTime): array
    {
        $executionTime = microtime(true) - $startTime;

        $this->logError('Migration execution failed', [
            'migration' => $migration['name'],
            'error' => $error->getMessage(),
            'execution_time' => $executionTime
        ]);

        // Attempt rollback
        $rollbackResult = null;
        if (!empty($this->rollbackPoints)) {
            try {
                $lastRollbackPoint = array_key_last($this->rollbackPoints);
                $rollbackResult = $this->rollbackToPoint($lastRollbackPoint);
            } catch (\Exception $rollbackError) {
                $this->logError('Rollback also failed', [
                    'migration' => $migration['name'],
                    'rollback_error' => $rollbackError->getMessage()
                ]);
            }
        }

        return [
            'migration' => $migration['name'],
            'status' => 'failed',
            'error' => $error->getMessage(),
            'execution_time' => $executionTime,
            'rollback_attempted' => $rollbackResult !== null,
            'rollback_result' => $rollbackResult
        ];
    }

    /**
     * Handle overall execution error.
     */
    private function handleExecutionError(\Exception $error, float $startTime): void
    {
        $executionTime = microtime(true) - $startTime;

        $this->logError('Migration execution failed completely', [
            'execution_id' => $this->currentExecutionId,
            'error' => $error->getMessage(),
            'execution_time' => $executionTime,
            'rollback_points' => count($this->rollbackPoints)
        ]);

        // Attempt complete rollback
        if (!empty($this->rollbackPoints) && !$this->dryRunMode) {
            try {
                $masterRollbackPoint = $this->rollbackPoints[array_key_first($this->rollbackPoints)]['id'] ?? null;
                if ($masterRollbackPoint) {
                    $this->rollbackToPoint($masterRollbackPoint);
                }
            } catch (\Exception $rollbackError) {
                $this->logError('Complete rollback failed', [
                    'execution_id' => $this->currentExecutionId,
                    'rollback_error' => $rollbackError->getMessage()
                ]);
            }
        }
    }

    /**
     * Additional helper methods would continue here...
     * Including: validateMigrationDependencies, validateDatabaseState, 
     * validateSchemaChanges, validateDataIntegrity, validateConstraints,
     * performManualRollback, wouldMigrationSucceed, isCriticalMigration,
     * reportProgress, verifyExecutionState, cleanupRollbackPoints,
     * countSuccessfulMigrations, countFailedMigrations, generateExecutionSummary,
     * recordMigrationExecution
     */

    /**
     * Report execution progress.
     */
    private function reportProgress(): void
    {
        $progress = $this->executionState['progress'];
        $total = $this->executionState['total_migrations'];
        
        $this->logInfo('Migration progress update', [
            'execution_id' => $this->currentExecutionId,
            'completed' => $progress['completed'],
            'failed' => $progress['failed'],
            'total' => $total,
            'progress_percentage' => round(($progress['completed'] + $progress['failed']) / $total * 100, 2)
        ]);
    }

    /**
     * Count successful migrations from results.
     */
    private function countSuccessfulMigrations(array $results): int
    {
        $count = 0;
        foreach ($results as $groupResult) {
            $count += $groupResult['successful_migrations'] ?? 0;
        }
        return $count;
    }

    /**
     * Count failed migrations from results.
     */
    private function countFailedMigrations(array $results): int
    {
        $count = 0;
        foreach ($results as $groupResult) {
            $count += $groupResult['failed_migrations'] ?? 0;
        }
        return $count;
    }
}