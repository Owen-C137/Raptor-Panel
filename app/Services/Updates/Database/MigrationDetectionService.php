<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Migration Detection Service
 * 
 * Advanced migration file analysis, dependency detection,
 * and execution order determination.
 */
class MigrationDetectionService extends BaseUpdateService
{
    private array $migrationCache = [];
    private array $dependencyMap = [];
    private array $tableAnalysisCache = [];

    public function getServiceName(): string
    {
        return 'Migration Detection Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if migrations directory exists
        if (!is_dir(database_path('migrations'))) {
            $errors[] = 'Database migrations directory does not exist';
        }

        // Check if we can read migration files
        if (!is_readable(database_path('migrations'))) {
            $errors[] = 'Migration directory is not readable';
        }

        return $errors;
    }

    /**
     * Detect new migrations in release archive and analyze them.
     */
    public function detectNewMigrations(string $releasePath, string $currentVersion): array
    {
        try {
            $this->logInfo('Starting migration detection', [
                'release_path' => $releasePath,
                'current_version' => $currentVersion
            ]);

            // Find migration files in release
            $newMigrationFiles = $this->findMigrationFiles($releasePath);
            
            if (empty($newMigrationFiles)) {
                return [
                    'has_new_migrations' => false,
                    'migration_count' => 0,
                    'migrations' => [],
                    'execution_plan' => []
                ];
            }

            // Analyze each migration file
            $analyzedMigrations = [];
            foreach ($newMigrationFiles as $filePath) {
                $migration = $this->analyzeMigrationFile($filePath);
                if ($migration) {
                    $analyzedMigrations[] = $migration;
                }
            }

            // Filter out already executed migrations
            $pendingMigrations = $this->filterPendingMigrations($analyzedMigrations);

            // Build dependency map and execution order
            $executionPlan = $this->buildExecutionPlan($pendingMigrations);

            $result = [
                'has_new_migrations' => count($pendingMigrations) > 0,
                'migration_count' => count($pendingMigrations),
                'migrations' => $pendingMigrations,
                'execution_plan' => $executionPlan,
                'analysis_summary' => $this->generateAnalysisSummary($pendingMigrations)
            ];

            $this->logInfo('Migration detection completed', [
                'new_migrations' => count($pendingMigrations),
                'requires_execution' => $result['has_new_migrations']
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Migration detection failed');
            throw new MigrationException('Failed to detect migrations: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Analyze a single migration file for metadata and dependencies.
     */
    public function analyzeMigrationFile(string $filePath): ?array
    {
        try {
            if (!file_exists($filePath)) {
                return null;
            }

            $fileName = basename($filePath);
            $migrationName = $this->extractMigrationName($fileName);
            
            // Check cache first
            if (isset($this->migrationCache[$migrationName])) {
                return $this->migrationCache[$migrationName];
            }

            $fileContent = file_get_contents($filePath);
            
            $migration = [
                'name' => $migrationName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'timestamp' => $this->extractTimestamp($fileName),
                'class_name' => $this->extractClassName($fileContent),
                'file_size' => filesize($filePath),
                'file_hash' => hash_file('sha256', $filePath),
                'analyzed_at' => Carbon::now()->toISOString(),
                
                // Schema analysis
                'tables' => $this->analyzeTableOperations($fileContent),
                'operations' => $this->analyzeOperations($fileContent),
                'dependencies' => $this->analyzeDependencies($fileContent),
                'complexity' => $this->assessComplexity($fileContent),
                'risk_level' => $this->assessRiskLevel($fileContent),
                
                // Migration metadata
                'is_data_migration' => $this->isDataMigration($fileContent),
                'is_destructive' => $this->isDestructive($fileContent),
                'requires_downtime' => $this->requiresDowntime($fileContent),
                'estimated_duration' => $this->estimateDuration($fileContent),
                
                // Rollback analysis
                'has_rollback' => $this->hasRollbackMethod($fileContent),
                'rollback_complexity' => $this->analyzeRollbackComplexity($fileContent)
            ];

            // Cache the result
            $this->migrationCache[$migrationName] = $migration;

            return $migration;

        } catch (\Exception $e) {
            $this->logError('Migration file analysis failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find all migration files in a directory.
     */
    private function findMigrationFiles(string $directory): array
    {
        $migrationPath = $directory . '/database/migrations';
        
        if (!is_dir($migrationPath)) {
            return [];
        }

        $files = File::files($migrationPath);
        $migrationFiles = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php' && $this->isMigrationFile($file->getFilename())) {
                $migrationFiles[] = $file->getPathname();
            }
        }

        return $migrationFiles;
    }

    /**
     * Check if a file is a valid migration file.
     */
    private function isMigrationFile(string $fileName): bool
    {
        return preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/', $fileName);
    }

    /**
     * Extract migration name from filename.
     */
    private function extractMigrationName(string $fileName): string
    {
        return str_replace('.php', '', $fileName);
    }

    /**
     * Extract timestamp from migration filename.
     */
    private function extractTimestamp(string $fileName): string
    {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $fileName, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Extract class name from migration file content.
     */
    private function extractClassName(string $content): string
    {
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Analyze table operations in migration.
     */
    private function analyzeTableOperations(string $content): array
    {
        $operations = [];

        // Schema::create
        if (preg_match_all('/Schema::create\(\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $table) {
                $operations['creates'][] = $table;
            }
        }

        // Schema::table (modifications)
        if (preg_match_all('/Schema::table\(\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $table) {
                $operations['modifies'][] = $table;
            }
        }

        // Schema::drop
        if (preg_match_all('/Schema::drop\(\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $table) {
                $operations['drops'][] = $table;
            }
        }

        // Schema::dropIfExists
        if (preg_match_all('/Schema::dropIfExists\(\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $table) {
                $operations['drops_if_exists'][] = $table;
            }
        }

        // Schema::rename
        if (preg_match_all('/Schema::rename\(\s*[\'"](\w+)[\'"],\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $operations['renames'][] = [
                    'from' => $matches[1][$i],
                    'to' => $matches[2][$i]
                ];
            }
        }

        return $operations;
    }

    /**
     * Analyze specific operations in migration.
     */
    private function analyzeOperations(string $content): array
    {
        $operations = [];

        // Column operations
        $columnOps = ['string', 'integer', 'bigInteger', 'text', 'boolean', 'timestamp', 'json', 'decimal'];
        foreach ($columnOps as $op) {
            if (preg_match_all("/\$table->{$op}\(/", $content, $matches)) {
                $operations['columns'][$op] = count($matches[0]);
            }
        }

        // Index operations
        $indexOps = ['index', 'unique', 'primary', 'foreign'];
        foreach ($indexOps as $op) {
            if (preg_match_all("/\$table->{$op}\(/", $content, $matches)) {
                $operations['indexes'][$op] = count($matches[0]);
            }
        }

        // Data operations
        if (strpos($content, 'DB::table(') !== false) {
            $operations['has_data_operations'] = true;
        }

        if (strpos($content, 'DB::statement(') !== false) {
            $operations['has_raw_sql'] = true;
        }

        return $operations;
    }

    /**
     * Analyze migration dependencies.
     */
    private function analyzeDependencies(string $content): array
    {
        $dependencies = [];

        // Foreign key dependencies
        if (preg_match_all('/foreign\(\s*[\'"](\w+)[\'"]\s*\)->references\(\s*[\'"](\w+)[\'"]\s*\)->on\(\s*[\'"](\w+)[\'"]/', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $dependencies['foreign_keys'][] = [
                    'local_column' => $matches[1][$i],
                    'foreign_column' => $matches[2][$i],
                    'foreign_table' => $matches[3][$i]
                ];
            }
        }

        // Table references
        if (preg_match_all('/[\'"](\w+)[\'"]/', $content, $matches)) {
            // Filter potential table references
            $possibleTables = array_unique($matches[1]);
            foreach ($possibleTables as $table) {
                if ($this->isLikelyTableName($table)) {
                    $dependencies['table_references'][] = $table;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Assess migration complexity.
     */
    private function assessComplexity(string $content): string
    {
        $score = 0;

        // Basic operations
        if (strpos($content, 'Schema::create') !== false) $score += 2;
        if (strpos($content, 'Schema::table') !== false) $score += 1;
        if (strpos($content, 'Schema::drop') !== false) $score += 3;

        // Advanced operations
        if (strpos($content, 'foreign(') !== false) $score += 2;
        if (strpos($content, 'DB::statement(') !== false) $score += 3;
        if (strpos($content, 'DB::table(') !== false) $score += 2;

        // Data operations
        if (preg_match('/insert|update|delete/i', $content)) $score += 3;

        if ($score >= 8) return 'high';
        if ($score >= 4) return 'medium';
        return 'low';
    }

    /**
     * Assess migration risk level.
     */
    private function assessRiskLevel(string $content): string
    {
        $risk = 0;

        // Destructive operations
        if (strpos($content, 'Schema::drop') !== false) $risk += 5;
        if (strpos($content, 'dropColumn') !== false) $risk += 4;
        if (strpos($content, 'dropIndex') !== false) $risk += 2;

        // Data modifications
        if (preg_match('/delete|truncate/i', $content)) $risk += 5;
        if (preg_match('/update.*set/i', $content)) $risk += 3;

        // Raw SQL
        if (strpos($content, 'DB::statement') !== false) $risk += 3;

        if ($risk >= 8) return 'high';
        if ($risk >= 4) return 'medium';
        return 'low';
    }

    /**
     * Check if migration is a data migration.
     */
    private function isDataMigration(string $content): bool
    {
        return strpos($content, 'DB::table(') !== false || 
               preg_match('/insert|update/i', $content);
    }

    /**
     * Check if migration is destructive.
     */
    private function isDestructive(string $content): bool
    {
        return strpos($content, 'Schema::drop') !== false ||
               strpos($content, 'dropColumn') !== false ||
               preg_match('/delete|truncate/i', $content);
    }

    /**
     * Check if migration requires downtime.
     */
    private function requiresDowntime(string $content): bool
    {
        // Large table operations, adding non-nullable columns, etc.
        return strpos($content, 'Schema::drop') !== false ||
               (strpos($content, 'addColumn') !== false && strpos($content, 'nullable()') === false);
    }

    /**
     * Estimate migration duration in seconds.
     */
    private function estimateDuration(string $content): int
    {
        $duration = 5; // Base duration

        // Add time for various operations
        if (strpos($content, 'Schema::create') !== false) $duration += 10;
        if (strpos($content, 'Schema::table') !== false) $duration += 5;
        if (strpos($content, 'foreign(') !== false) $duration += 15;
        if (strpos($content, 'DB::statement') !== false) $duration += 20;
        if (strpos($content, 'DB::table') !== false) $duration += 30;

        return $duration;
    }

    /**
     * Check if migration has rollback method.
     */
    private function hasRollbackMethod(string $content): bool
    {
        return strpos($content, 'public function down()') !== false;
    }

    /**
     * Analyze rollback complexity.
     */
    private function analyzeRollbackComplexity(string $content): string
    {
        if (!$this->hasRollbackMethod($content)) {
            return 'none';
        }

        // Extract down method content
        if (preg_match('/public function down\(\).*?\{(.*?)\}/s', $content, $matches)) {
            $downContent = $matches[1];
            
            if (trim($downContent) === '' || strpos($downContent, '//') !== false) {
                return 'empty';
            }
            
            if (strpos($downContent, 'DB::statement') !== false) {
                return 'complex';
            }
            
            return 'simple';
        }

        return 'unknown';
    }

    /**
     * Filter out already executed migrations.
     */
    private function filterPendingMigrations(array $migrations): array
    {
        $executed = $this->getExecutedMigrations();
        
        return array_filter($migrations, function ($migration) use ($executed) {
            return !in_array($migration['name'], $executed);
        });
    }

    /**
     * Get list of already executed migrations.
     */
    private function getExecutedMigrations(): array
    {
        try {
            return \DB::table('migrations')->pluck('migration')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Build execution plan with proper ordering.
     */
    private function buildExecutionPlan(array $migrations): array
    {
        // Sort by timestamp first
        usort($migrations, function ($a, $b) {
            return strcmp($a['timestamp'], $b['timestamp']);
        });

        $plan = [];
        $batches = $this->groupIntoBatches($migrations);

        foreach ($batches as $batchIndex => $batch) {
            $plan[] = [
                'batch_number' => $batchIndex + 1,
                'migrations' => $batch,
                'estimated_duration' => array_sum(array_column($batch, 'estimated_duration')),
                'risk_level' => $this->calculateBatchRisk($batch),
                'requires_downtime' => $this->batchRequiresDowntime($batch)
            ];
        }

        return $plan;
    }

    /**
     * Group migrations into logical batches.
     */
    private function groupIntoBatches(array $migrations): array
    {
        // For now, simple time-based batching
        // In future, could implement dependency-based batching
        
        $batches = [];
        $currentBatch = [];
        $batchDuration = 0;
        $maxBatchDuration = 300; // 5 minutes max per batch

        foreach ($migrations as $migration) {
            if ($batchDuration + $migration['estimated_duration'] > $maxBatchDuration && !empty($currentBatch)) {
                $batches[] = $currentBatch;
                $currentBatch = [];
                $batchDuration = 0;
            }

            $currentBatch[] = $migration;
            $batchDuration += $migration['estimated_duration'];
        }

        if (!empty($currentBatch)) {
            $batches[] = $currentBatch;
        }

        return $batches;
    }

    /**
     * Calculate risk level for a batch of migrations.
     */
    private function calculateBatchRisk(array $batch): string
    {
        $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3];
        $maxRisk = 0;

        foreach ($batch as $migration) {
            $risk = $riskLevels[$migration['risk_level']] ?? 1;
            $maxRisk = max($maxRisk, $risk);
        }

        return array_search($maxRisk, $riskLevels) ?: 'low';
    }

    /**
     * Check if batch requires downtime.
     */
    private function batchRequiresDowntime(array $batch): bool
    {
        foreach ($batch as $migration) {
            if ($migration['requires_downtime']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generate analysis summary.
     */
    private function generateAnalysisSummary(array $migrations): array
    {
        $summary = [
            'total_migrations' => count($migrations),
            'by_complexity' => ['low' => 0, 'medium' => 0, 'high' => 0],
            'by_risk' => ['low' => 0, 'medium' => 0, 'high' => 0],
            'data_migrations' => 0,
            'destructive_migrations' => 0,
            'requires_downtime' => 0,
            'total_estimated_duration' => 0,
            'tables_affected' => []
        ];

        foreach ($migrations as $migration) {
            $summary['by_complexity'][$migration['complexity']]++;
            $summary['by_risk'][$migration['risk_level']]++;
            $summary['total_estimated_duration'] += $migration['estimated_duration'];

            if ($migration['is_data_migration']) $summary['data_migrations']++;
            if ($migration['is_destructive']) $summary['destructive_migrations']++;
            if ($migration['requires_downtime']) $summary['requires_downtime']++;

            // Collect affected tables
            foreach ($migration['tables'] as $operation => $tables) {
                if (is_array($tables)) {
                    $summary['tables_affected'] = array_merge($summary['tables_affected'], $tables);
                }
            }
        }

        $summary['tables_affected'] = array_unique($summary['tables_affected']);

        return $summary;
    }

    /**
     * Check if string is likely a table name.
     */
    private function isLikelyTableName(string $name): bool
    {
        return strlen($name) > 3 && 
               preg_match('/^[a-z][a-z0-9_]*$/', $name) && 
               !in_array($name, ['true', 'false', 'null', 'this', 'that', 'with', 'from']);
    }
}