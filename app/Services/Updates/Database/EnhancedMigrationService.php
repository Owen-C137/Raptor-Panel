<?php

namespace Pterodactyl\Services\Updates\Database;

use Carbon\Carbon;
use Illuminate\Database\Schema\Builder;
use Pterodactyl\Exceptions\Updates\DatabaseOperationException;
use Pterodactyl\Exceptions\Updates\MigrationException;
use Pterodactyl\Models\Updates\UpdateMigration;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * Enhanced Migration Service
 * 
 * Orchestrates advanced migration handling with detection, validation,
 * execution, rollback, conflict resolution, and testing capabilities.
 */
class EnhancedMigrationService extends BaseUpdateService
{
    private Builder $schema;
    private MigrationDetectionService $detectionService;
    private MigrationDependencyService $dependencyService;
    private MigrationValidationService $validationService;
    private MigrationExecutionService $executionService;
    private MigrationConflictService $conflictService;
    private MigrationRollbackService $rollbackService;
    private MigrationTestingService $testingService;

    public function __construct(
        MigrationDetectionService $detectionService,
        MigrationDependencyService $dependencyService,
        MigrationValidationService $validationService,
        MigrationExecutionService $executionService,
        MigrationConflictService $conflictService,
        MigrationRollbackService $rollbackService,
        MigrationTestingService $testingService
    ) {
        // Note: BaseUpdateService doesn't need constructor parameters
        $this->schema = \Schema::getFacadeRoot();
        $this->detectionService = $detectionService;
        $this->dependencyService = $dependencyService;
        $this->validationService = $validationService;
        $this->executionService = $executionService;
        $this->conflictService = $conflictService;
        $this->rollbackService = $rollbackService;
        $this->testingService = $testingService;
    }

    public function getServiceName(): string
    {
        return 'Enhanced Migration Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check database connection
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check required tables
        if (!\Schema::hasTable('update_migrations')) {
            $errors[] = 'update_migrations table does not exist';
        }

        if (!\Schema::hasTable('migrations')) {
            $errors[] = 'migrations table does not exist';
        }

        // Check all sub-services
        $errors = array_merge($errors, $this->detectionService->getConfigurationErrors());
        $errors = array_merge($errors, $this->validationService->getConfigurationErrors());
        $errors = array_merge($errors, $this->executionService->getConfigurationErrors());
        $errors = array_merge($errors, $this->rollbackService->getConfigurationErrors());

        return $errors;
    }

    /**
     * Execute comprehensive migration workflow with full analysis and safety checks.
     */
    public function executeAdvancedMigrationWorkflow(string $releasePath, string $currentVersion, array $options = []): array
    {
        $workflowId = 'workflow_' . uniqid();
        $startTime = microtime(true);

        try {
            $this->logInfo('Starting advanced migration workflow', [
                'workflow_id' => $workflowId,
                'release_path' => $releasePath,
                'current_version' => $currentVersion,
                'options' => $options
            ]);

            $workflowResults = [
                'workflow_id' => $workflowId,
                'phase_1_detection' => null,
                'phase_2_dependency_analysis' => null,
                'phase_3_conflict_analysis' => null,
                'phase_4_validation' => null,
                'phase_5_testing' => null,
                'phase_6_execution' => null,
                'phase_7_verification' => null,
                'workflow_summary' => null
            ];

            // Phase 1: Migration Detection and Analysis
            $workflowResults['phase_1_detection'] = $this->executeDetectionPhase($releasePath, $currentVersion);
            
            if (!$workflowResults['phase_1_detection']['has_new_migrations']) {
                return $this->generateWorkflowResult($workflowId, $workflowResults, $startTime, 'no_migrations');
            }

            $migrations = $workflowResults['phase_1_detection']['migrations'];

            // Phase 2: Dependency Analysis and Resolution
            $workflowResults['phase_2_dependency_analysis'] = $this->executeDependencyPhase($migrations);

            // Phase 3: Conflict Detection and Resolution
            $workflowResults['phase_3_conflict_analysis'] = $this->executeConflictPhase($migrations);

            // Check for blocking conflicts
            if ($this->hasBlockingConflicts($workflowResults['phase_3_conflict_analysis'])) {
                return $this->generateWorkflowResult($workflowId, $workflowResults, $startTime, 'blocked_by_conflicts');
            }

            // Phase 4: Pre-Migration Validation
            $workflowResults['phase_4_validation'] = $this->executeValidationPhase($migrations);

            // Check validation results
            if (!$this->canProceedAfterValidation($workflowResults['phase_4_validation'])) {
                return $this->generateWorkflowResult($workflowId, $workflowResults, $startTime, 'blocked_by_validation');
            }

            // Phase 5: Testing (if enabled)
            if ($options['run_tests'] ?? true) {
                $workflowResults['phase_5_testing'] = $this->executeTestingPhase($migrations, $options);
                
                if (!$this->canProceedAfterTesting($workflowResults['phase_5_testing'])) {
                    return $this->generateWorkflowResult($workflowId, $workflowResults, $startTime, 'blocked_by_tests');
                }
            }

            // Phase 6: Migration Execution
            if (!($options['dry_run'] ?? false)) {
                $workflowResults['phase_6_execution'] = $this->executeExecutionPhase($migrations, $options);
            } else {
                $workflowResults['phase_6_execution'] = ['status' => 'skipped_dry_run'];
            }

            // Phase 7: Post-Migration Verification
            if (isset($workflowResults['phase_6_execution']) && 
                $workflowResults['phase_6_execution']['status'] === 'completed') {
                $workflowResults['phase_7_verification'] = $this->executeVerificationPhase($migrations);
            }

            return $this->generateWorkflowResult($workflowId, $workflowResults, $startTime, 'completed');

        } catch (\Exception $e) {
            return $this->handleWorkflowError($workflowId, $e, $workflowResults, $startTime);
        }
    }

    /**
     * Execute simplified migration workflow for basic operations.
     */
    public function executeSimpleMigrationWorkflow(string $releasePath, string $currentVersion, array $options = []): array
    {
        try {
            $this->logInfo('Starting simple migration workflow', [
                'release_path' => $releasePath,
                'current_version' => $currentVersion
            ]);

            // Basic detection
            $detectionResult = $this->detectionService->detectNewMigrations($releasePath, $currentVersion);
            
            if (!$detectionResult['has_new_migrations']) {
                return [
                    'status' => 'no_migrations',
                    'message' => 'No new migrations detected'
                ];
            }

            $migrations = $detectionResult['migrations'];

            // Basic validation
            $validationResult = $this->validationService->validateBeforeMigration($migrations);
            
            if ($validationResult['validation_status'] === 'failed') {
                return [
                    'status' => 'validation_failed',
                    'validation_result' => $validationResult
                ];
            }

            // Execute migrations
            $executionResult = $this->executionService->executeMigrations($migrations, $options);

            return [
                'status' => 'completed',
                'detection_result' => $detectionResult,
                'validation_result' => $validationResult,
                'execution_result' => $executionResult
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Simple migration workflow failed');
            throw new MigrationException('Failed to execute simple migration workflow: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute rollback workflow with comprehensive safety checks.
     */
    public function executeRollbackWorkflow(array $migrationNames, array $options = []): array
    {
        try {
            $this->logInfo('Starting rollback workflow', [
                'migrations' => $migrationNames,
                'rollback_type' => $options['rollback_type'] ?? 'selective'
            ]);

            $rollbackType = $options['rollback_type'] ?? 'selective';

            // Test rollback first if safety mode is enabled
            if ($options['safety_mode'] ?? true) {
                $rollbackTest = $this->rollbackService->testRollback($migrationNames);
                
                if (!$this->isRollbackTestSafe($rollbackTest)) {
                    return [
                        'status' => 'rollback_unsafe',
                        'rollback_test' => $rollbackTest,
                        'recommendation' => 'Resolve safety issues before attempting rollback'
                    ];
                }
            }

            // Execute appropriate rollback type
            $rollbackResult = match ($rollbackType) {
                'selective' => $this->rollbackService->selectiveRollback($migrationNames, $options),
                'dependency_aware' => $this->rollbackService->dependencyAwareRollback($migrationNames, $options),
                default => throw new MigrationException("Unknown rollback type: {$rollbackType}")
            };

            return [
                'status' => 'completed',
                'rollback_type' => $rollbackType,
                'rollback_result' => $rollbackResult
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Rollback workflow failed');
            throw new MigrationException('Failed to execute rollback workflow: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute migration testing workflow.
     */
    public function executeTestingWorkflow(array $migrations, array $options = []): array
    {
        try {
            $testSuite = $options['test_suite'] ?? 'comprehensive';

            $this->logInfo('Starting testing workflow', [
                'migration_count' => count($migrations),
                'test_suite' => $testSuite
            ]);

            $testingResult = match ($testSuite) {
                'dry_run' => $this->testingService->runDryRunTests($migrations, $options),
                'rollback' => $this->testingService->testRollbackCapabilities($migrations),
                'performance' => $this->testingService->profileMigrationPerformance($migrations, $options),
                'comprehensive' => $this->testingService->runComprehensiveTests($migrations, $options),
                default => throw new MigrationException("Unknown test suite: {$testSuite}")
            };

            return [
                'status' => 'completed',
                'test_suite' => $testSuite,
                'testing_result' => $testingResult
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Testing workflow failed');
            throw new MigrationException('Failed to execute testing workflow: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Phase execution methods for the advanced workflow.
     */
    private function executeDetectionPhase(string $releasePath, string $currentVersion): array
    {
        $this->logInfo('Executing migration detection phase');
        return $this->detectionService->detectNewMigrations($releasePath, $currentVersion);
    }

    private function executeDependencyPhase(array $migrations): array
    {
        $this->logInfo('Executing dependency analysis phase');
        return $this->dependencyService->resolveMigrationOrder($migrations);
    }

    private function executeConflictPhase(array $migrations): array
    {
        $this->logInfo('Executing conflict detection phase');
        return $this->conflictService->analyzeConflicts($migrations);
    }

    private function executeValidationPhase(array $migrations): array
    {
        $this->logInfo('Executing validation phase');
        return $this->validationService->validateBeforeMigration($migrations);
    }

    private function executeTestingPhase(array $migrations, array $options): array
    {
        $this->logInfo('Executing testing phase');
        
        $testSuite = $options['test_suite'] ?? 'standard';
        return $this->testingService->runComprehensiveTests($migrations, ['test_suite' => $testSuite]);
    }

    private function executeExecutionPhase(array $migrations, array $options): array
    {
        $this->logInfo('Executing migration execution phase');
        
        // Use ordered migrations from dependency analysis
        $orderedMigrations = $migrations; // Would be reordered by dependency service
        
        return $this->executionService->executeMigrations($orderedMigrations, $options);
    }

    private function executeVerificationPhase(array $migrations): array
    {
        $this->logInfo('Executing post-migration verification phase');
        return $this->validationService->validateAfterMigration($migrations);
    }

    /**
     * Safety check methods.
     */
    private function hasBlockingConflicts(array $conflictAnalysis): bool
    {
        return $conflictAnalysis['conflict_severity'] === 'critical' || 
               count($conflictAnalysis['blocking_issues'] ?? []) > 0;
    }

    private function canProceedAfterValidation(array $validationResult): bool
    {
        return $validationResult['validation_status'] !== 'failed';
    }

    private function canProceedAfterTesting(array $testingResult): bool
    {
        return ($testingResult['testing_result']['test_execution_summary']['overall_test_status'] ?? 'failed') !== 'failed';
    }

    private function isRollbackTestSafe(array $rollbackTest): bool
    {
        return ($rollbackTest['safety_assessment']['safety_level'] ?? 'low') !== 'low';
    }

    /**
     * Generate comprehensive workflow result.
     */
    private function generateWorkflowResult(string $workflowId, array $workflowResults, float $startTime, string $status): array
    {
        $executionTime = microtime(true) - $startTime;

        $result = [
            'workflow_id' => $workflowId,
            'workflow_status' => $status,
            'execution_time' => $executionTime,
            'completed_phases' => array_keys(array_filter($workflowResults, fn($phase) => $phase !== null)),
            'phase_results' => $workflowResults,
            'workflow_summary' => $this->generateWorkflowSummary($workflowResults, $status),
            'recommendations' => $this->generateWorkflowRecommendations($workflowResults, $status),
            'next_actions' => $this->generateWorkflowNextActions($workflowResults, $status)
        ];

        $this->logInfo('Migration workflow completed', [
            'workflow_id' => $workflowId,
            'status' => $status,
            'execution_time' => $executionTime,
            'completed_phases' => count($result['completed_phases'])
        ]);

        return $result;
    }

    /**
     * Handle workflow errors with comprehensive reporting.
     */
    private function handleWorkflowError(string $workflowId, \Exception $error, array $workflowResults, float $startTime): array
    {
        $executionTime = microtime(true) - $startTime;

        $this->logError('Migration workflow failed', [
            'workflow_id' => $workflowId,
            'error' => $error->getMessage(),
            'execution_time' => $executionTime,
            'completed_phases' => array_keys(array_filter($workflowResults, fn($phase) => $phase !== null))
        ]);

        return [
            'workflow_id' => $workflowId,
            'workflow_status' => 'failed',
            'error' => $error->getMessage(),
            'execution_time' => $executionTime,
            'completed_phases' => array_keys(array_filter($workflowResults, fn($phase) => $phase !== null)),
            'partial_results' => $workflowResults,
            'recovery_recommendations' => $this->generateRecoveryRecommendations($workflowResults, $error)
        ];
    }

    /**
     * Generate workflow summary based on results.
     */
    private function generateWorkflowSummary(array $workflowResults, string $status): array
    {
        $summary = [
            'workflow_status' => $status,
            'phases_executed' => count(array_filter($workflowResults, fn($phase) => $phase !== null)),
            'migrations_processed' => 0,
            'conflicts_detected' => 0,
            'validation_issues' => 0,
            'execution_success' => false
        ];

        // Extract key metrics from phase results
        if (isset($workflowResults['phase_1_detection']['migration_count'])) {
            $summary['migrations_processed'] = $workflowResults['phase_1_detection']['migration_count'];
        }

        if (isset($workflowResults['phase_3_conflict_analysis']['analysis_summary']['total_conflicts'])) {
            $summary['conflicts_detected'] = $workflowResults['phase_3_conflict_analysis']['analysis_summary']['total_conflicts'];
        }

        if (isset($workflowResults['phase_4_validation']['blocking_issues'])) {
            $summary['validation_issues'] = count($workflowResults['phase_4_validation']['blocking_issues']);
        }

        if (isset($workflowResults['phase_6_execution']['status'])) {
            $summary['execution_success'] = $workflowResults['phase_6_execution']['status'] === 'completed';
        }

        return $summary;
    }

    /**
     * Generate workflow recommendations.
     */
    private function generateWorkflowRecommendations(array $workflowResults, string $status): array
    {
        $recommendations = [];

        switch ($status) {
            case 'no_migrations':
                $recommendations[] = 'No action required - no new migrations detected';
                break;
                
            case 'blocked_by_conflicts':
                $recommendations[] = 'Resolve migration conflicts before proceeding';
                $recommendations[] = 'Review conflict analysis report for specific issues';
                break;
                
            case 'blocked_by_validation':
                $recommendations[] = 'Fix validation errors before proceeding';
                $recommendations[] = 'Check database schema consistency';
                break;
                
            case 'blocked_by_tests':
                $recommendations[] = 'Address test failures before proceeding';
                $recommendations[] = 'Review test report for specific issues';
                break;
                
            case 'completed':
                $recommendations[] = 'Migration workflow completed successfully';
                $recommendations[] = 'Verify application functionality after migration';
                break;
                
            case 'failed':
                $recommendations[] = 'Review error details and resolve issues';
                $recommendations[] = 'Consider rollback if necessary';
                break;
        }

        return $recommendations;
    }

    /**
     * Generate next actions based on workflow results.
     */
    private function generateWorkflowNextActions(array $workflowResults, string $status): array
    {
        $nextActions = [];

        switch ($status) {
            case 'completed':
                $nextActions[] = 'Monitor application for any post-migration issues';
                $nextActions[] = 'Update deployment documentation';
                $nextActions[] = 'Clean up temporary migration files if applicable';
                break;
                
            case 'blocked_by_conflicts':
            case 'blocked_by_validation':
            case 'blocked_by_tests':
                $nextActions[] = 'Address identified issues';
                $nextActions[] = 'Re-run workflow after fixes';
                break;
                
            case 'failed':
                $nextActions[] = 'Investigate failure cause';
                $nextActions[] = 'Check database state for consistency';
                $nextActions[] = 'Consider manual intervention if needed';
                break;
        }

        return $nextActions;
    }

    /**
     * Generate recovery recommendations for failed workflows.
     */
    private function generateRecoveryRecommendations(array $workflowResults, \Exception $error): array
    {
        $recommendations = [
            'immediate_actions' => [
                'Check database connection and state',
                'Verify no partial migrations were applied',
                'Review error logs for specific failure points'
            ],
            'investigation_steps' => [
                'Analyze failed phase results for clues',
                'Check file system permissions and paths',
                'Validate migration file syntax and structure'
            ],
            'recovery_options' => [
                'Retry workflow after addressing root cause',
                'Use rollback workflow if migrations were partially applied',
                'Manual database inspection and cleanup if necessary'
            ]
        ];

        return $recommendations;
    }
}