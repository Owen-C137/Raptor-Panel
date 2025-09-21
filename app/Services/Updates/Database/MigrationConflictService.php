<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Migration Conflict Detection Service
 * 
 * Detects migration conflicts, schema inconsistencies, and provides
 * resolution strategies for complex migration scenarios.
 */
class MigrationConflictService extends BaseUpdateService
{
    private array $conflictRules = [];
    private array $schemaCache = [];
    private array $detectedConflicts = [];

    public function getServiceName(): string
    {
        return 'Migration Conflict Detection Service';
    }

    public function getConfigurationErrors(): array
    {
        return []; // No specific configuration required
    }

    /**
     * Analyze migrations for potential conflicts.
     */
    public function analyzeConflicts(array $migrations): array
    {
        try {
            $this->logInfo('Starting migration conflict analysis', [
                'migration_count' => count($migrations)
            ]);

            // Initialize conflict detection
            $this->initializeConflictRules();
            $this->captureCurrentSchema();

            // Detect various types of conflicts
            $conflicts = [];
            $conflicts['table_conflicts'] = $this->detectTableConflicts($migrations);
            $conflicts['column_conflicts'] = $this->detectColumnConflicts($migrations);
            $conflicts['constraint_conflicts'] = $this->detectConstraintConflicts($migrations);
            $conflicts['data_conflicts'] = $this->detectDataConflicts($migrations);
            $conflicts['dependency_conflicts'] = $this->detectDependencyConflicts($migrations);
            $conflicts['schema_inconsistencies'] = $this->detectSchemaInconsistencies($migrations);

            // Generate conflict resolution strategies
            $resolutionStrategies = $this->generateResolutionStrategies($conflicts);

            // Calculate conflict severity
            $conflictSeverity = $this->calculateConflictSeverity($conflicts);

            $result = [
                'has_conflicts' => $this->hasAnyConflicts($conflicts),
                'conflict_severity' => $conflictSeverity,
                'conflicts_by_type' => $conflicts,
                'resolution_strategies' => $resolutionStrategies,
                'recommended_actions' => $this->generateRecommendedActions($conflicts, $conflictSeverity),
                'analysis_summary' => $this->generateConflictSummary($conflicts)
            ];

            $this->logInfo('Migration conflict analysis completed', [
                'has_conflicts' => $result['has_conflicts'],
                'conflict_severity' => $conflictSeverity,
                'total_conflicts' => $this->countTotalConflicts($conflicts)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Migration conflict analysis failed');
            throw new MigrationException('Failed to analyze migration conflicts: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Detect conflicts between two specific migrations.
     */
    public function detectMigrationPairConflict(array $migration1, array $migration2): array
    {
        $conflicts = [];

        // Table operation conflicts
        $tableConflicts = $this->detectTableOperationConflicts($migration1, $migration2);
        if (!empty($tableConflicts)) {
            $conflicts['table_operations'] = $tableConflicts;
        }

        // Column operation conflicts
        $columnConflicts = $this->detectColumnOperationConflicts($migration1, $migration2);
        if (!empty($columnConflicts)) {
            $conflicts['column_operations'] = $columnConflicts;
        }

        // Constraint conflicts
        $constraintConflicts = $this->detectConstraintOperationConflicts($migration1, $migration2);
        if (!empty($constraintConflicts)) {
            $conflicts['constraint_operations'] = $constraintConflicts;
        }

        // Data operation conflicts
        $dataConflicts = $this->detectDataOperationConflicts($migration1, $migration2);
        if (!empty($dataConflicts)) {
            $conflicts['data_operations'] = $dataConflicts;
        }

        return [
            'migration_1' => $migration1['name'],
            'migration_2' => $migration2['name'],
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts,
            'conflict_severity' => $this->calculatePairConflictSeverity($conflicts)
        ];
    }

    /**
     * Initialize conflict detection rules.
     */
    private function initializeConflictRules(): void
    {
        $this->conflictRules = [
            'table_operations' => [
                'drop_then_create' => 'high',      // Dropping and creating same table
                'create_duplicate' => 'critical',   // Creating table that already exists
                'modify_dropped' => 'critical',     // Modifying a dropped table
                'concurrent_modify' => 'medium'     // Multiple migrations modifying same table
            ],
            'column_operations' => [
                'drop_then_add' => 'medium',        // Dropping and adding same column
                'add_duplicate' => 'high',          // Adding column that exists
                'modify_dropped' => 'critical',     // Modifying dropped column
                'conflicting_types' => 'high'      // Same column, different types
            ],
            'constraint_operations' => [
                'duplicate_foreign_key' => 'medium', // Same foreign key added twice
                'circular_reference' => 'critical',  // Circular foreign key references
                'orphaned_constraint' => 'high'      // Constraint on non-existent column
            ],
            'data_operations' => [
                'data_on_dropped_table' => 'critical', // Data operations on dropped table
                'conflicting_data' => 'medium',         // Conflicting data modifications
                'dependency_violation' => 'high'        // Data operations violating constraints
            ]
        ];
    }

    /**
     * Capture current database schema for comparison.
     */
    private function captureCurrentSchema(): void
    {
        try {
            $this->schemaCache = [
                'tables' => $this->getCurrentTables(),
                'columns' => $this->getCurrentColumns(),
                'constraints' => $this->getCurrentConstraints(),
                'indexes' => $this->getCurrentIndexes()
            ];
        } catch (\Exception $e) {
            $this->logWarning('Failed to capture current schema', [
                'error' => $e->getMessage()
            ]);
            $this->schemaCache = [];
        }
    }

    /**
     * Detect table operation conflicts.
     */
    private function detectTableConflicts(array $migrations): array
    {
        $conflicts = [];
        $tableOperations = [];

        // Map all table operations by migration
        foreach ($migrations as $migration) {
            $migrationName = $migration['name'];
            
            if (isset($migration['tables']['creates'])) {
                foreach ($migration['tables']['creates'] as $table) {
                    $tableOperations[$table]['creates'][] = $migrationName;
                }
            }
            
            if (isset($migration['tables']['drops'])) {
                foreach ($migration['tables']['drops'] as $table) {
                    $tableOperations[$table]['drops'][] = $migrationName;
                }
            }
            
            if (isset($migration['tables']['modifies'])) {
                foreach ($migration['tables']['modifies'] as $table) {
                    $tableOperations[$table]['modifies'][] = $migrationName;
                }
            }
            
            if (isset($migration['tables']['renames'])) {
                foreach ($migration['tables']['renames'] as $rename) {
                    $tableOperations[$rename['from']]['renames_from'][] = $migrationName;
                    $tableOperations[$rename['to']]['renames_to'][] = $migrationName;
                }
            }
        }

        // Analyze conflicts for each table
        foreach ($tableOperations as $tableName => $operations) {
            $tableConflicts = $this->analyzeTableOperationConflicts($tableName, $operations);
            if (!empty($tableConflicts)) {
                $conflicts[$tableName] = $tableConflicts;
            }
        }

        return $conflicts;
    }

    /**
     * Analyze conflicts for a specific table.
     */
    private function analyzeTableOperationConflicts(string $tableName, array $operations): array
    {
        $conflicts = [];

        // Multiple creates
        if (isset($operations['creates']) && count($operations['creates']) > 1) {
            $conflicts[] = [
                'type' => 'multiple_creates',
                'severity' => 'critical',
                'migrations' => $operations['creates'],
                'message' => "Table '{$tableName}' is created by multiple migrations"
            ];
        }

        // Drop then create
        if (isset($operations['drops']) && isset($operations['creates'])) {
            $conflicts[] = [
                'type' => 'drop_then_create',
                'severity' => 'high',
                'drop_migrations' => $operations['drops'],
                'create_migrations' => $operations['creates'],
                'message' => "Table '{$tableName}' is dropped and then created"
            ];
        }

        // Modify dropped table
        if (isset($operations['drops']) && isset($operations['modifies'])) {
            $conflicts[] = [
                'type' => 'modify_dropped',
                'severity' => 'critical',
                'drop_migrations' => $operations['drops'],
                'modify_migrations' => $operations['modifies'],
                'message' => "Table '{$tableName}' is modified after being dropped"
            ];
        }

        // Multiple concurrent modifications
        if (isset($operations['modifies']) && count($operations['modifies']) > 2) {
            $conflicts[] = [
                'type' => 'concurrent_modifications',
                'severity' => 'medium',
                'migrations' => $operations['modifies'],
                'message' => "Table '{$tableName}' is modified by multiple migrations simultaneously"
            ];
        }

        // Rename conflicts
        if (isset($operations['renames_from']) && isset($operations['modifies'])) {
            $conflicts[] = [
                'type' => 'rename_then_modify',
                'severity' => 'medium',
                'rename_migrations' => $operations['renames_from'],
                'modify_migrations' => $operations['modifies'],
                'message' => "Table '{$tableName}' is renamed and modified"
            ];
        }

        return $conflicts;
    }

    /**
     * Detect column operation conflicts.
     */
    private function detectColumnConflicts(array $migrations): array
    {
        $conflicts = [];
        $columnOperations = [];

        // Extract column operations from migration content analysis
        foreach ($migrations as $migration) {
            $migrationName = $migration['name'];
            $columnOps = $this->extractColumnOperations($migration);

            foreach ($columnOps as $table => $columns) {
                foreach ($columns as $column => $operations) {
                    $key = "{$table}.{$column}";
                    if (!isset($columnOperations[$key])) {
                        $columnOperations[$key] = [];
                    }
                    $columnOperations[$key][$migrationName] = $operations;
                }
            }
        }

        // Analyze column conflicts
        foreach ($columnOperations as $columnKey => $operations) {
            list($table, $column) = explode('.', $columnKey);
            $columnConflicts = $this->analyzeColumnOperationConflicts($table, $column, $operations);
            if (!empty($columnConflicts)) {
                $conflicts[$columnKey] = $columnConflicts;
            }
        }

        return $conflicts;
    }

    /**
     * Extract column operations from migration.
     */
    private function extractColumnOperations(array $migration): array
    {
        $operations = [];

        // This would normally parse the actual migration file content
        // For now, return basic structure based on available data
        if (isset($migration['operations']['columns'])) {
            foreach ($migration['operations']['columns'] as $columnType => $count) {
                // Estimate operations based on table operations
                if (isset($migration['tables']['creates'])) {
                    foreach ($migration['tables']['creates'] as $table) {
                        $operations[$table]["column_$columnType"] = ['add' => $count];
                    }
                }
            }
        }

        return $operations;
    }

    /**
     * Analyze column operation conflicts.
     */
    private function analyzeColumnOperationConflicts(string $table, string $column, array $operations): array
    {
        $conflicts = [];

        $adds = [];
        $drops = [];
        $modifies = [];

        foreach ($operations as $migration => $ops) {
            if (isset($ops['add'])) $adds[] = $migration;
            if (isset($ops['drop'])) $drops[] = $migration;
            if (isset($ops['modify'])) $modifies[] = $migration;
        }

        // Multiple adds
        if (count($adds) > 1) {
            $conflicts[] = [
                'type' => 'multiple_adds',
                'severity' => 'high',
                'migrations' => $adds,
                'message' => "Column '{$table}.{$column}' is added by multiple migrations"
            ];
        }

        // Drop then add
        if (!empty($drops) && !empty($adds)) {
            $conflicts[] = [
                'type' => 'drop_then_add',
                'severity' => 'medium',
                'drop_migrations' => $drops,
                'add_migrations' => $adds,
                'message' => "Column '{$table}.{$column}' is dropped and then added"
            ];
        }

        // Modify dropped column
        if (!empty($drops) && !empty($modifies)) {
            $conflicts[] = [
                'type' => 'modify_dropped',
                'severity' => 'critical',
                'drop_migrations' => $drops,
                'modify_migrations' => $modifies,
                'message' => "Column '{$table}.{$column}' is modified after being dropped"
            ];
        }

        return $conflicts;
    }

    /**
     * Detect constraint conflicts.
     */
    private function detectConstraintConflicts(array $migrations): array
    {
        $conflicts = [];
        $constraints = [];

        // Collect all constraint operations
        foreach ($migrations as $migration) {
            $migrationName = $migration['name'];
            
            if (isset($migration['dependencies']['foreign_keys'])) {
                foreach ($migration['dependencies']['foreign_keys'] as $fk) {
                    $constraintKey = "{$fk['foreign_table']}.{$fk['foreign_column']}";
                    $constraints[$constraintKey][] = [
                        'migration' => $migrationName,
                        'type' => 'foreign_key',
                        'details' => $fk
                    ];
                }
            }
        }

        // Analyze constraint conflicts
        foreach ($constraints as $constraintKey => $constraintList) {
            if (count($constraintList) > 1) {
                $conflicts[$constraintKey] = [
                    'type' => 'duplicate_constraint',
                    'severity' => 'medium',
                    'constraints' => $constraintList,
                    'message' => "Multiple migrations define constraints on '{$constraintKey}'"
                ];
            }
        }

        // Check for circular references
        $circularRefs = $this->detectCircularReferences($constraints);
        if (!empty($circularRefs)) {
            $conflicts['circular_references'] = [
                'type' => 'circular_references',
                'severity' => 'critical',
                'references' => $circularRefs,
                'message' => 'Circular foreign key references detected'
            ];
        }

        return $conflicts;
    }

    /**
     * Detect circular foreign key references.
     */
    private function detectCircularReferences(array $constraints): array
    {
        $references = [];
        
        // Build reference map
        foreach ($constraints as $constraintKey => $constraintList) {
            foreach ($constraintList as $constraint) {
                if ($constraint['type'] === 'foreign_key') {
                    $fk = $constraint['details'];
                    // Find local table from table operations
                    $localTable = $this->findLocalTableForMigration($constraint['migration']);
                    if ($localTable) {
                        $references[$localTable][] = $fk['foreign_table'];
                    }
                }
            }
        }

        // Detect cycles using DFS
        $visited = [];
        $recursionStack = [];
        $cycles = [];

        foreach ($references as $table => $deps) {
            if (!isset($visited[$table])) {
                $this->detectCircularRef($table, $references, $visited, $recursionStack, [], $cycles);
            }
        }

        return $cycles;
    }

    /**
     * DFS to detect circular references.
     */
    private function detectCircularRef(string $table, array $references, array &$visited, array &$recursionStack, array $path, array &$cycles): void
    {
        $visited[$table] = true;
        $recursionStack[$table] = true;
        $path[] = $table;

        if (isset($references[$table])) {
            foreach ($references[$table] as $referencedTable) {
                if (!isset($visited[$referencedTable])) {
                    $this->detectCircularRef($referencedTable, $references, $visited, $recursionStack, $path, $cycles);
                } elseif (isset($recursionStack[$referencedTable]) && $recursionStack[$referencedTable]) {
                    // Found cycle
                    $cycleStart = array_search($referencedTable, $path);
                    $cycle = array_slice($path, $cycleStart);
                    $cycle[] = $referencedTable;
                    $cycles[] = $cycle;
                }
            }
        }

        $recursionStack[$table] = false;
    }

    /**
     * Detect data operation conflicts.
     */
    private function detectDataConflicts(array $migrations): array
    {
        $conflicts = [];
        $dataOperations = [];

        foreach ($migrations as $migration) {
            if ($migration['is_data_migration']) {
                $migrationName = $migration['name'];
                
                // Find tables this migration affects
                $affectedTables = array_merge(
                    $migration['dependencies']['table_references'] ?? [],
                    $migration['tables']['modifies'] ?? []
                );

                foreach ($affectedTables as $table) {
                    $dataOperations[$table][] = $migrationName;
                }
            }
        }

        // Check for conflicts
        foreach ($dataOperations as $table => $migrations) {
            if (count($migrations) > 1) {
                $conflicts[$table] = [
                    'type' => 'concurrent_data_operations',
                    'severity' => 'medium',
                    'migrations' => $migrations,
                    'message' => "Multiple data migrations affect table '{$table}'"
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Detect dependency conflicts.
     */
    private function detectDependencyConflicts(array $migrations): array
    {
        // This would integrate with MigrationDependencyService
        // For now, return basic structure
        return [];
    }

    /**
     * Detect schema inconsistencies.
     */
    private function detectSchemaInconsistencies(array $migrations): array
    {
        $inconsistencies = [];

        // Check against current schema
        foreach ($migrations as $migration) {
            if (isset($migration['tables']['creates'])) {
                foreach ($migration['tables']['creates'] as $table) {
                    if ($this->tableExistsInCurrentSchema($table)) {
                        $inconsistencies[] = [
                            'type' => 'table_already_exists',
                            'severity' => 'high',
                            'migration' => $migration['name'],
                            'table' => $table,
                            'message' => "Migration attempts to create table '{$table}' that already exists"
                        ];
                    }
                }
            }

            if (isset($migration['tables']['modifies'])) {
                foreach ($migration['tables']['modifies'] as $table) {
                    if (!$this->tableExistsInCurrentSchema($table)) {
                        $inconsistencies[] = [
                            'type' => 'table_does_not_exist',
                            'severity' => 'critical',
                            'migration' => $migration['name'],
                            'table' => $table,
                            'message' => "Migration attempts to modify table '{$table}' that does not exist"
                        ];
                    }
                }
            }
        }

        return $inconsistencies;
    }

    /**
     * Generate resolution strategies for conflicts.
     */
    private function generateResolutionStrategies(array $conflicts): array
    {
        $strategies = [];

        foreach ($conflicts as $conflictType => $conflictData) {
            $strategies[$conflictType] = $this->generateResolutionStrategy($conflictType, $conflictData);
        }

        return $strategies;
    }

    /**
     * Generate resolution strategy for specific conflict type.
     */
    private function generateResolutionStrategy(string $conflictType, array $conflictData): array
    {
        $strategy = [
            'conflict_type' => $conflictType,
            'recommended_actions' => [],
            'alternative_actions' => [],
            'risk_assessment' => 'medium'
        ];

        switch ($conflictType) {
            case 'table_conflicts':
                $strategy['recommended_actions'][] = 'Reorder migrations to resolve table operation conflicts';
                $strategy['recommended_actions'][] = 'Merge conflicting migrations where possible';
                $strategy['alternative_actions'][] = 'Create intermediate migrations to handle transitions';
                break;

            case 'column_conflicts':
                $strategy['recommended_actions'][] = 'Review column operations for compatibility';
                $strategy['recommended_actions'][] = 'Use conditional column operations';
                $strategy['alternative_actions'][] = 'Split migrations to handle column changes separately';
                break;

            case 'constraint_conflicts':
                $strategy['recommended_actions'][] = 'Remove duplicate constraint definitions';
                $strategy['recommended_actions'][] = 'Resolve circular foreign key references';
                $strategy['risk_assessment'] = 'high';
                break;

            case 'schema_inconsistencies':
                $strategy['recommended_actions'][] = 'Verify current database state before migration';
                $strategy['recommended_actions'][] = 'Add conditional checks in migrations';
                $strategy['risk_assessment'] = 'high';
                break;
        }

        return $strategy;
    }

    /**
     * Helper methods for schema checking and conflict analysis.
     */
    private function tableExistsInCurrentSchema(string $tableName): bool
    {
        return isset($this->schemaCache['tables']) && 
               in_array($tableName, $this->schemaCache['tables']);
    }

    private function findLocalTableForMigration(string $migrationName): ?string
    {
        // This would require more detailed analysis of migration content
        return null;
    }

    private function getCurrentTables(): array
    {
        try {
            return DB::connection()->getDoctrineSchemaManager()->listTableNames();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getCurrentColumns(): array
    {
        // Implementation would get all columns from all tables
        return [];
    }

    private function getCurrentConstraints(): array
    {
        // Implementation would get all constraints
        return [];
    }

    private function getCurrentIndexes(): array
    {
        // Implementation would get all indexes
        return [];
    }

    private function hasAnyConflicts(array $conflicts): bool
    {
        foreach ($conflicts as $conflictType => $conflictData) {
            if (!empty($conflictData)) {
                return true;
            }
        }
        return false;
    }

    private function calculateConflictSeverity(array $conflicts): string
    {
        $severity = 'low';
        
        foreach ($conflicts as $conflictType => $conflictData) {
            if (empty($conflictData)) continue;
            
            foreach ($conflictData as $conflict) {
                if (is_array($conflict) && isset($conflict['severity'])) {
                    if ($conflict['severity'] === 'critical') return 'critical';
                    if ($conflict['severity'] === 'high') $severity = 'high';
                    if ($conflict['severity'] === 'medium' && $severity === 'low') $severity = 'medium';
                }
            }
        }
        
        return $severity;
    }

    private function generateRecommendedActions(array $conflicts, string $severity): array
    {
        $actions = [];

        if ($severity === 'critical') {
            $actions[] = 'STOP: Critical conflicts detected - manual resolution required';
            $actions[] = 'Review migration order and dependencies';
            $actions[] = 'Consider splitting problematic migrations';
        } elseif ($severity === 'high') {
            $actions[] = 'Proceed with caution - resolve high-priority conflicts first';
            $actions[] = 'Create backup before execution';
            $actions[] = 'Test in staging environment';
        } else {
            $actions[] = 'Minor conflicts detected - can proceed with monitoring';
            $actions[] = 'Review warnings and plan resolution';
        }

        return $actions;
    }

    private function generateConflictSummary(array $conflicts): array
    {
        $summary = [
            'total_conflict_types' => 0,
            'total_conflicts' => 0,
            'by_severity' => ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0]
        ];

        foreach ($conflicts as $conflictType => $conflictData) {
            if (!empty($conflictData)) {
                $summary['total_conflict_types']++;
                
                if (is_array($conflictData)) {
                    $summary['total_conflicts'] += count($conflictData);
                    
                    foreach ($conflictData as $conflict) {
                        if (is_array($conflict) && isset($conflict['severity'])) {
                            $summary['by_severity'][$conflict['severity']]++;
                        }
                    }
                }
            }
        }

        return $summary;
    }

    private function countTotalConflicts(array $conflicts): int
    {
        $total = 0;
        foreach ($conflicts as $conflictData) {
            if (is_array($conflictData)) {
                $total += count($conflictData);
            }
        }
        return $total;
    }

    private function calculatePairConflictSeverity(array $conflicts): string
    {
        $maxSeverity = 'low';
        $severityLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

        foreach ($conflicts as $conflictType => $conflictData) {
            if (is_array($conflictData)) {
                foreach ($conflictData as $conflict) {
                    if (isset($conflict['severity'])) {
                        $level = $severityLevels[$conflict['severity']] ?? 1;
                        if ($level > ($severityLevels[$maxSeverity] ?? 1)) {
                            $maxSeverity = $conflict['severity'];
                        }
                    }
                }
            }
        }

        return $maxSeverity;
    }

    private function detectTableOperationConflicts(array $migration1, array $migration2): array
    {
        // Implementation for specific table operation conflict detection between two migrations
        return [];
    }

    private function detectColumnOperationConflicts(array $migration1, array $migration2): array
    {
        // Implementation for specific column operation conflict detection
        return [];
    }

    private function detectConstraintOperationConflicts(array $migration1, array $migration2): array
    {
        // Implementation for specific constraint operation conflict detection
        return [];
    }

    private function detectDataOperationConflicts(array $migration1, array $migration2): array
    {
        // Implementation for specific data operation conflict detection
        return [];
    }
}