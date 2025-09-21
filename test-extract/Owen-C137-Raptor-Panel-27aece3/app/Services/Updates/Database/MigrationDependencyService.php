<?php

namespace Pterodactyl\Services\Updates\Database;

use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Migration Dependency Resolution Service
 * 
 * Analyzes migration dependencies, resolves execution order,
 * and detects circular dependencies or conflicts.
 */
class MigrationDependencyService extends BaseUpdateService
{
    private array $dependencyGraph = [];
    private array $tableCreationOrder = [];
    private array $foreignKeyConstraints = [];
    private array $circularDependencies = [];

    public function getServiceName(): string
    {
        return 'Migration Dependency Resolution Service';
    }

    public function getConfigurationErrors(): array
    {
        return []; // No configuration required
    }

    /**
     * Resolve migration execution order based on dependencies.
     */
    public function resolveMigrationOrder(array $migrations): array
    {
        try {
            $this->logInfo('Starting migration dependency resolution', [
                'migration_count' => count($migrations)
            ]);

            // Build dependency graph
            $this->buildDependencyGraph($migrations);

            // Detect circular dependencies
            $this->detectCircularDependencies();

            if (!empty($this->circularDependencies)) {
                throw new MigrationException('Circular dependencies detected: ' . implode(', ', $this->circularDependencies));
            }

            // Perform topological sort
            $orderedMigrations = $this->topologicalSort($migrations);

            // Validate the resolved order
            $this->validateExecutionOrder($orderedMigrations);

            $result = [
                'ordered_migrations' => $orderedMigrations,
                'dependency_analysis' => $this->generateDependencyAnalysis(),
                'execution_groups' => $this->groupMigrationsByDependencies($orderedMigrations),
                'warnings' => $this->generateWarnings($orderedMigrations)
            ];

            $this->logInfo('Migration dependency resolution completed', [
                'execution_groups' => count($result['execution_groups']),
                'warnings' => count($result['warnings'])
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Migration dependency resolution failed');
            throw new MigrationException('Failed to resolve migration dependencies: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Analyze specific migration for its dependencies.
     */
    public function analyzeMigrationDependencies(array $migration): array
    {
        $dependencies = [
            'requires' => [],           // Migrations this one depends on
            'blocks' => [],            // Migrations that depend on this one
            'table_dependencies' => [], // Table-level dependencies
            'constraint_dependencies' => [], // Foreign key constraints
            'data_dependencies' => [],  // Data-level dependencies
            'critical_path' => false   // Is this migration on the critical path?
        ];

        // Analyze table operations for dependencies
        $dependencies['table_dependencies'] = $this->analyzeTableDependencies($migration);
        
        // Analyze foreign key constraints
        $dependencies['constraint_dependencies'] = $this->analyzeConstraintDependencies($migration);
        
        // Analyze data dependencies
        $dependencies['data_dependencies'] = $this->analyzeDataDependencies($migration);

        // Determine if migration is on critical path
        $dependencies['critical_path'] = $this->isOnCriticalPath($migration, $dependencies);

        return $dependencies;
    }

    /**
     * Build dependency graph for all migrations.
     */
    private function buildDependencyGraph(array $migrations): void
    {
        $this->dependencyGraph = [];
        $this->tableCreationOrder = [];
        $this->foreignKeyConstraints = [];

        foreach ($migrations as $migration) {
            $migrationName = $migration['name'];
            $this->dependencyGraph[$migrationName] = [
                'migration' => $migration,
                'dependencies' => [],
                'dependents' => []
            ];

            // Track table creation order
            if (isset($migration['tables']['creates'])) {
                foreach ($migration['tables']['creates'] as $table) {
                    $this->tableCreationOrder[$table] = $migrationName;
                }
            }

            // Track foreign key constraints
            if (isset($migration['dependencies']['foreign_keys'])) {
                foreach ($migration['dependencies']['foreign_keys'] as $fk) {
                    $this->foreignKeyConstraints[] = [
                        'migration' => $migrationName,
                        'local_table' => $this->findTableForMigration($migrationName),
                        'foreign_table' => $fk['foreign_table'],
                        'constraint' => $fk
                    ];
                }
            }
        }

        // Build dependency relationships
        $this->buildDependencyRelationships();
    }

    /**
     * Build dependency relationships between migrations.
     */
    private function buildDependencyRelationships(): void
    {
        foreach ($this->dependencyGraph as $migrationName => $node) {
            $migration = $node['migration'];

            // Check for table dependencies
            if (isset($migration['tables']['modifies'])) {
                foreach ($migration['tables']['modifies'] as $table) {
                    $creatorMigration = $this->findTableCreator($table);
                    if ($creatorMigration && $creatorMigration !== $migrationName) {
                        $this->addDependency($migrationName, $creatorMigration);
                    }
                }
            }

            // Check for foreign key dependencies
            if (isset($migration['dependencies']['foreign_keys'])) {
                foreach ($migration['dependencies']['foreign_keys'] as $fk) {
                    $referencedTableCreator = $this->findTableCreator($fk['foreign_table']);
                    if ($referencedTableCreator && $referencedTableCreator !== $migrationName) {
                        $this->addDependency($migrationName, $referencedTableCreator);
                    }
                }
            }

            // Check for explicit dependencies (from migration comments or attributes)
            $explicitDeps = $this->extractExplicitDependencies($migration);
            foreach ($explicitDeps as $dep) {
                $this->addDependency($migrationName, $dep);
            }
        }
    }

    /**
     * Add a dependency relationship between two migrations.
     */
    private function addDependency(string $dependent, string $dependency): void
    {
        if (!isset($this->dependencyGraph[$dependent]) || !isset($this->dependencyGraph[$dependency])) {
            return;
        }

        if (!in_array($dependency, $this->dependencyGraph[$dependent]['dependencies'])) {
            $this->dependencyGraph[$dependent]['dependencies'][] = $dependency;
        }

        if (!in_array($dependent, $this->dependencyGraph[$dependency]['dependents'])) {
            $this->dependencyGraph[$dependency]['dependents'][] = $dependent;
        }
    }

    /**
     * Detect circular dependencies in the graph.
     */
    private function detectCircularDependencies(): void
    {
        $this->circularDependencies = [];
        $visited = [];
        $recursionStack = [];

        foreach ($this->dependencyGraph as $migrationName => $node) {
            if (!isset($visited[$migrationName])) {
                $this->detectCycle($migrationName, $visited, $recursionStack, []);
            }
        }
    }

    /**
     * Detect cycle using DFS.
     */
    private function detectCycle(string $migration, array &$visited, array &$recursionStack, array $path): void
    {
        $visited[$migration] = true;
        $recursionStack[$migration] = true;
        $path[] = $migration;

        if (isset($this->dependencyGraph[$migration])) {
            foreach ($this->dependencyGraph[$migration]['dependencies'] as $dependency) {
                if (!isset($visited[$dependency])) {
                    $this->detectCycle($dependency, $visited, $recursionStack, $path);
                } elseif (isset($recursionStack[$dependency]) && $recursionStack[$dependency]) {
                    // Found a cycle
                    $cycleStart = array_search($dependency, $path);
                    $cycle = array_slice($path, $cycleStart);
                    $cycle[] = $dependency;
                    $this->circularDependencies[] = implode(' -> ', $cycle);
                }
            }
        }

        $recursionStack[$migration] = false;
    }

    /**
     * Perform topological sort on migrations.
     */
    private function topologicalSort(array $migrations): array
    {
        $inDegree = [];
        $queue = [];
        $result = [];

        // Calculate in-degrees
        foreach ($this->dependencyGraph as $migrationName => $node) {
            $inDegree[$migrationName] = count($node['dependencies']);
        }

        // Find migrations with no dependencies
        foreach ($inDegree as $migrationName => $degree) {
            if ($degree === 0) {
                $queue[] = $migrationName;
            }
        }

        // Process queue
        while (!empty($queue)) {
            $current = array_shift($queue);
            $result[] = $this->findMigrationByName($migrations, $current);

            // Update in-degrees of dependents
            if (isset($this->dependencyGraph[$current])) {
                foreach ($this->dependencyGraph[$current]['dependents'] as $dependent) {
                    $inDegree[$dependent]--;
                    if ($inDegree[$dependent] === 0) {
                        $queue[] = $dependent;
                    }
                }
            }
        }

        // Check if all migrations were processed
        if (count($result) !== count($migrations)) {
            throw new MigrationException('Unable to resolve migration order - circular dependencies may exist');
        }

        return $result;
    }

    /**
     * Find migration by name in the array.
     */
    private function findMigrationByName(array $migrations, string $name): ?array
    {
        foreach ($migrations as $migration) {
            if ($migration['name'] === $name) {
                return $migration;
            }
        }
        return null;
    }

    /**
     * Find which migration creates a specific table.
     */
    private function findTableCreator(string $tableName): ?string
    {
        return $this->tableCreationOrder[$tableName] ?? null;
    }

    /**
     * Find table name for a migration (if it creates one).
     */
    private function findTableForMigration(string $migrationName): ?string
    {
        foreach ($this->tableCreationOrder as $table => $creator) {
            if ($creator === $migrationName) {
                return $table;
            }
        }
        return null;
    }

    /**
     * Extract explicit dependencies from migration content.
     */
    private function extractExplicitDependencies(array $migration): array
    {
        $dependencies = [];

        // Look for @depends annotations or similar
        if (isset($migration['file_path']) && file_exists($migration['file_path'])) {
            $content = file_get_contents($migration['file_path']);
            
            if (preg_match_all('/@depends\s+(\w+)/', $content, $matches)) {
                $dependencies = array_merge($dependencies, $matches[1]);
            }

            // Look for other dependency indicators
            if (preg_match_all('/\/\*\*.*?@requires\s+(\w+).*?\*\//s', $content, $matches)) {
                $dependencies = array_merge($dependencies, $matches[1]);
            }
        }

        return array_unique($dependencies);
    }

    /**
     * Analyze table-level dependencies for a migration.
     */
    private function analyzeTableDependencies(array $migration): array
    {
        $dependencies = [];

        // Tables this migration modifies (depends on creators)
        if (isset($migration['tables']['modifies'])) {
            foreach ($migration['tables']['modifies'] as $table) {
                $creator = $this->findTableCreator($table);
                if ($creator) {
                    $dependencies['modifies'][$table] = $creator;
                }
            }
        }

        // Tables this migration references
        if (isset($migration['dependencies']['table_references'])) {
            foreach ($migration['dependencies']['table_references'] as $table) {
                $creator = $this->findTableCreator($table);
                if ($creator) {
                    $dependencies['references'][$table] = $creator;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Analyze constraint dependencies for a migration.
     */
    private function analyzeConstraintDependencies(array $migration): array
    {
        $dependencies = [];

        if (isset($migration['dependencies']['foreign_keys'])) {
            foreach ($migration['dependencies']['foreign_keys'] as $fk) {
                $referencedTableCreator = $this->findTableCreator($fk['foreign_table']);
                if ($referencedTableCreator) {
                    $dependencies[] = [
                        'type' => 'foreign_key',
                        'local_column' => $fk['local_column'],
                        'foreign_table' => $fk['foreign_table'],
                        'foreign_column' => $fk['foreign_column'],
                        'depends_on_migration' => $referencedTableCreator
                    ];
                }
            }
        }

        return $dependencies;
    }

    /**
     * Analyze data-level dependencies.
     */
    private function analyzeDataDependencies(array $migration): array
    {
        $dependencies = [];

        // If this is a data migration, it likely depends on structure migrations
        if ($migration['is_data_migration']) {
            $dependencies['requires_table_structure'] = true;
            
            // Find structure migrations for referenced tables
            if (isset($migration['dependencies']['table_references'])) {
                foreach ($migration['dependencies']['table_references'] as $table) {
                    $creator = $this->findTableCreator($table);
                    if ($creator) {
                        $dependencies['structure_dependencies'][] = $creator;
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Check if migration is on the critical path.
     */
    private function isOnCriticalPath(array $migration, array $dependencies): bool
    {
        // A migration is on critical path if:
        // 1. It creates tables that other migrations depend on
        // 2. It's a destructive migration that blocks others
        // 3. It has many dependents

        $migrationName = $migration['name'];
        
        if (isset($this->dependencyGraph[$migrationName])) {
            $dependentCount = count($this->dependencyGraph[$migrationName]['dependents']);
            return $dependentCount > 2 || $migration['is_destructive'];
        }

        return false;
    }

    /**
     * Validate the resolved execution order.
     */
    private function validateExecutionOrder(array $orderedMigrations): void
    {
        $executed = [];

        foreach ($orderedMigrations as $migration) {
            $migrationName = $migration['name'];

            // Check that all dependencies have been executed
            if (isset($this->dependencyGraph[$migrationName])) {
                foreach ($this->dependencyGraph[$migrationName]['dependencies'] as $dependency) {
                    if (!in_array($dependency, $executed)) {
                        throw new MigrationException("Migration {$migrationName} scheduled before its dependency {$dependency}");
                    }
                }
            }

            $executed[] = $migrationName;
        }
    }

    /**
     * Group migrations by dependency levels for parallel execution.
     */
    private function groupMigrationsByDependencies(array $orderedMigrations): array
    {
        $groups = [];
        $processed = [];
        $currentGroup = [];

        foreach ($orderedMigrations as $migration) {
            $migrationName = $migration['name'];
            $canExecuteNow = true;

            // Check if all dependencies are satisfied
            if (isset($this->dependencyGraph[$migrationName])) {
                foreach ($this->dependencyGraph[$migrationName]['dependencies'] as $dependency) {
                    if (!in_array($dependency, $processed)) {
                        $canExecuteNow = false;
                        break;
                    }
                }
            }

            if ($canExecuteNow) {
                $currentGroup[] = $migration;
            } else {
                // Start new group
                if (!empty($currentGroup)) {
                    $groups[] = $currentGroup;
                    $processed = array_merge($processed, array_column($currentGroup, 'name'));
                    $currentGroup = [];
                }
                $currentGroup[] = $migration;
            }
        }

        if (!empty($currentGroup)) {
            $groups[] = $currentGroup;
        }

        return $groups;
    }

    /**
     * Generate dependency analysis summary.
     */
    private function generateDependencyAnalysis(): array
    {
        $analysis = [
            'total_dependencies' => 0,
            'circular_dependencies' => count($this->circularDependencies),
            'critical_path_migrations' => [],
            'independent_migrations' => [],
            'dependency_depth' => [],
            'foreign_key_constraints' => count($this->foreignKeyConstraints)
        ];

        foreach ($this->dependencyGraph as $migrationName => $node) {
            $depCount = count($node['dependencies']);
            $analysis['total_dependencies'] += $depCount;

            if ($depCount === 0) {
                $analysis['independent_migrations'][] = $migrationName;
            }

            if (count($node['dependents']) > 2) {
                $analysis['critical_path_migrations'][] = $migrationName;
            }

            $analysis['dependency_depth'][$migrationName] = $this->calculateDependencyDepth($migrationName);
        }

        return $analysis;
    }

    /**
     * Calculate dependency depth for a migration.
     */
    private function calculateDependencyDepth(string $migrationName): int
    {
        $visited = [];
        return $this->calculateDepthRecursive($migrationName, $visited);
    }

    /**
     * Recursively calculate dependency depth.
     */
    private function calculateDepthRecursive(string $migrationName, array &$visited): int
    {
        if (in_array($migrationName, $visited)) {
            return 0; // Prevent infinite recursion
        }

        $visited[] = $migrationName;

        if (!isset($this->dependencyGraph[$migrationName])) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($this->dependencyGraph[$migrationName]['dependencies'] as $dependency) {
            $depth = 1 + $this->calculateDepthRecursive($dependency, $visited);
            $maxDepth = max($maxDepth, $depth);
        }

        return $maxDepth;
    }

    /**
     * Generate warnings about the migration order.
     */
    private function generateWarnings(array $orderedMigrations): array
    {
        $warnings = [];

        // Check for potential issues
        foreach ($orderedMigrations as $index => $migration) {
            // Destructive migrations early in the process
            if ($migration['is_destructive'] && $index < count($orderedMigrations) * 0.3) {
                $warnings[] = [
                    'type' => 'early_destructive_migration',
                    'migration' => $migration['name'],
                    'message' => 'Destructive migration scheduled early in the process'
                ];
            }

            // Data migrations before structure migrations
            if ($migration['is_data_migration']) {
                $hasStructureDependencies = $this->hasUnresolvedStructureDependencies($migration, array_slice($orderedMigrations, 0, $index));
                if ($hasStructureDependencies) {
                    $warnings[] = [
                        'type' => 'data_before_structure',
                        'migration' => $migration['name'],
                        'message' => 'Data migration may be scheduled before required structure changes'
                    ];
                }
            }

            // High-risk migrations without proper dependencies
            if ($migration['risk_level'] === 'high') {
                $hasProperBackup = $this->hasProperBackupStrategy($migration);
                if (!$hasProperBackup) {
                    $warnings[] = [
                        'type' => 'high_risk_no_backup',
                        'migration' => $migration['name'],
                        'message' => 'High-risk migration without proper backup strategy'
                    ];
                }
            }
        }

        return $warnings;
    }

    /**
     * Check if migration has unresolved structure dependencies.
     */
    private function hasUnresolvedStructureDependencies(array $migration, array $previousMigrations): bool
    {
        if (!isset($migration['dependencies']['table_references'])) {
            return false;
        }

        $previousMigrationNames = array_column($previousMigrations, 'name');

        foreach ($migration['dependencies']['table_references'] as $table) {
            $creator = $this->findTableCreator($table);
            if ($creator && !in_array($creator, $previousMigrationNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if migration has proper backup strategy.
     */
    private function hasProperBackupStrategy(array $migration): bool
    {
        // For now, just check if it has a rollback method
        return $migration['has_rollback'] && $migration['rollback_complexity'] !== 'empty';
    }
}