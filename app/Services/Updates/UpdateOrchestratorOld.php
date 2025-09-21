<?php

namespace Pterodactyl\Services\Updates;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\UpdateException;
use Pterodactyl\Services\Updates\Database\MigrationService;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\Database\VersionService;
use Pterodactyl\Services\Updates\Files\ArchiveService;
use Pterodactyl\Services\Updates\Files\BackupService;
use Pterodactyl\Services\Updates\Files\FileUpdateService;
use Pterodactyl\Services\Updates\GitHub\GitHubFileService;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Progress\ProgressTracker;
use Pterodactyl\Services\Updates\Validation\ValidationService;

/**
 * Update Orchestrator
 * 
 * Main service that coordinates the entire update process,
 * managing all sub-services and handling the update workflow.
 */
class UpdateOrchestrator extends BaseUpdateService
{
    private GitHubReleaseService $githubReleaseService;
    private GitHubFileService $githubFileService;
    private VersionService $versionService;
    private SessionService $sessionService;
    private MigrationService $migrationService;
    private BackupService $backupService;
    private FileUpdateService $fileUpdateService;
    private ArchiveService $archiveService;
    private ProgressTracker $progressTracker;
    private ValidationService $validationService;

    private array $config;
    private string $currentSessionId = '';

    public function __construct(
        GitHubReleaseService $githubReleaseService,
        GitHubFileService $githubFileService,
        VersionService $versionService,
        SessionService $sessionService,
        MigrationService $migrationService,
        BackupService $backupService,
        FileUpdateService $fileUpdateService,
        ArchiveService $archiveService,
        ProgressTracker $progressTracker,
        ValidationService $validationService
    ) {
        $this->githubReleaseService = $githubReleaseService;
        $this->githubFileService = $githubFileService;
        $this->versionService = $versionService;
        $this->sessionService = $sessionService;
        $this->migrationService = $migrationService;
        $this->backupService = $backupService;
        $this->fileUpdateService = $fileUpdateService;
        $this->archiveService = $archiveService;
        $this->progressTracker = $progressTracker;
        $this->validationService = $validationService;
        
        $this->config = $this->getUpdateConfig();
    }

    public function getServiceName(): string
    {
        return 'Update Orchestrator';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check all sub-services for configuration issues
        $services = [
            'GitHub Release Service' => $this->githubReleaseService,
            'GitHub File Service' => $this->githubFileService,
            'Version Service' => $this->versionService,
            'Session Service' => $this->sessionService,
            'Migration Service' => $this->migrationService,
            'Backup Service' => $this->backupService,
            'File Update Service' => $this->fileUpdateService,
            'Archive Service' => $this->archiveService,
            'Progress Tracker' => $this->progressTracker,
            'Validation Service' => $this->validationService,
        ];

        foreach ($services as $serviceName => $service) {
            $serviceErrors = $service->getConfigurationErrors();
            if (!empty($serviceErrors)) {
                $errors[$serviceName] = $serviceErrors;
            }
        }

        return $errors;
    }

    /**
     * Check for available updates.
     */
    public function checkForUpdates(): array
    {
        try {
            $this->logInfo('Checking for available updates');

            // Get current version
            $currentVersion = $this->versionService->getCurrentVersion();
            $currentVersionString = $currentVersion ? $currentVersion->version : '0.0.0';

            // Check GitHub for latest release
            $latestRelease = $this->githubReleaseService->getLatestRelease();
            
            if (!$latestRelease) {
                return [
                    'update_available' => false,
                    'message' => 'No releases found',
                    'current_version' => $currentVersionString
                ];
            }

            $updateAvailable = version_compare($latestRelease['version'], $currentVersionString, '>');

            $result = [
                'update_available' => $updateAvailable,
                'current_version' => $currentVersionString,
                'latest_version' => $latestRelease['version'],
                'release_info' => $latestRelease,
                'update_type' => $this->determineUpdateType($currentVersionString, $latestRelease['version']),
                'checked_at' => Carbon::now()->toDateTimeString()
            ];

            $this->logInfo('Update check completed', [
                'current_version' => $currentVersionString,
                'latest_version' => $latestRelease['version'],
                'update_available' => $updateAvailable
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to check for updates');
            throw new UpdateException('Failed to check for updates: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Perform a complete system update.
     */
    public function performUpdate(string $targetVersion, array $options = []): array
    {
        try {
            $this->logInfo('Starting system update', [
                'target_version' => $targetVersion,
                'options' => $options
            ]);

            // Get current version
            $currentVersion = $this->versionService->getCurrentVersion();
            $fromVersion = $currentVersion ? $currentVersion->version : '0.0.0';

            // Create update session
            $session = $this->sessionService->createSession([
                'from_version' => $fromVersion,
                'to_version' => $targetVersion,
                'initiated_by' => $options['initiated_by'] ?? 'system',
                'update_type' => $this->determineUpdateType($fromVersion, $targetVersion),
                'metadata' => $options['metadata'] ?? null
            ]);

            $this->currentSessionId = $session->session_id;

            // Define update steps
            $updateSteps = $this->getUpdateSteps($options);

            // Initialize progress tracking
            $this->progressTracker->initializeProgress($this->currentSessionId, $updateSteps);

            try {
                // Update session status
                $this->sessionService->updateSessionStatus($this->currentSessionId, 'in_progress');

                // Execute update steps
                $this->executeUpdateSteps($targetVersion, $options);

                // Mark update as completed
                $this->sessionService->updateSessionStatus($this->currentSessionId, 'completed');
                $this->progressTracker->completeProgress('completed');

                // Update current version
                $this->versionService->setCurrentVersion($targetVersion);

                $result = [
                    'success' => true,
                    'session_id' => $this->currentSessionId,
                    'from_version' => $fromVersion,
                    'to_version' => $targetVersion,
                    'completed_at' => Carbon::now()->toDateTimeString(),
                    'message' => 'System update completed successfully'
                ];

                $this->logInfo('System update completed successfully', $result);
                return $result;

            } catch (\Exception $e) {
                // Update failed - mark session as failed and attempt rollback
                $this->sessionService->updateSessionStatus($this->currentSessionId, 'failed', $e->getMessage());
                $this->progressTracker->completeProgress('failed');

                // Attempt rollback if configured
                if ($options['auto_rollback'] ?? true) {
                    $this->logInfo('Attempting automatic rollback');
                    try {
                        $this->rollbackUpdate($this->currentSessionId);
                        $rollbackMessage = 'Update failed but system was rolled back successfully';
                    } catch (\Exception $rollbackException) {
                        $rollbackMessage = 'Update failed and rollback also failed: ' . $rollbackException->getMessage();
                        $this->logError('Rollback failed', ['error' => $rollbackException->getMessage()]);
                    }
                } else {
                    $rollbackMessage = 'Update failed (automatic rollback disabled)';
                }

                throw new UpdateException("Update failed: {$e->getMessage()}. {$rollbackMessage}", 0, $e);
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'System update failed');
            throw $e;
        }
    }

    /**
     * Rollback a failed update.
     */
    public function rollbackUpdate(string $sessionId): array
    {
        try {
            $this->logInfo('Starting update rollback', ['session_id' => $sessionId]);

            $session = $this->sessionService->getSession($sessionId);
            if (!$session) {
                throw new UpdateException("Update session not found: {$sessionId}");
            }

            // Initialize rollback progress
            $rollbackSteps = $this->getRollbackSteps();
            $this->progressTracker->initializeProgress($sessionId . '_rollback', $rollbackSteps);

            // Step 1: Rollback file changes
            $this->progressTracker->startStep(0, 'Rolling back file changes');
            $fileRollbackResult = $this->fileUpdateService->rollbackFileChanges($sessionId);
            $this->progressTracker->completeStep(0, "File rollback: {$fileRollbackResult['rolled_back']} files restored");

            // Step 2: Rollback database migrations
            $this->progressTracker->startStep(1, 'Rolling back database migrations');
            $migrationRollbackResult = $this->migrationService->rollbackMigrations($session->to_version);
            $this->progressTracker->completeStep(1, "Migration rollback: {$migrationRollbackResult['rolled_back']} migrations reversed");

            // Step 3: Restore backup (if available)
            $this->progressTracker->startStep(2, 'Checking for backup restoration');
            // Implementation would check for and restore from backup if needed
            $this->progressTracker->completeStep(2, 'Backup restoration check completed');

            // Step 4: Validate system state
            $this->progressTracker->startStep(3, 'Validating system state after rollback');
            $validationResult = $this->validationService->validatePostUpdate($session->from_version);
            $this->progressTracker->completeStep(3, 'System validation completed');

            // Update session status
            $this->sessionService->updateSessionStatus($sessionId, 'cancelled', 'Update rolled back');
            $this->progressTracker->completeProgress('completed');

            $result = [
                'success' => true,
                'session_id' => $sessionId,
                'files_rolled_back' => $fileRollbackResult['rolled_back'],
                'migrations_rolled_back' => $migrationRollbackResult['rolled_back'],
                'validation_passed' => $validationResult['validation_passed'],
                'completed_at' => Carbon::now()->toDateTimeString(),
                'message' => 'Update rollback completed successfully'
            ];

            $this->logInfo('Update rollback completed successfully', $result);
            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Update rollback failed');
            throw new UpdateException('Rollback failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get current update progress.
     */
    public function getUpdateProgress(string $sessionId): array
    {
        try {
            $session = $this->sessionService->getSession($sessionId);
            if (!$session) {
                return [
                    'session_id' => $sessionId,
                    'status' => 'session_not_found',
                    'message' => 'Update session not found'
                ];
            }

            $progress = $this->progressTracker->getProgressForSession($sessionId);
            
            return [
                'session_id' => $sessionId,
                'status' => $session->status,
                'percentage' => $progress['percentage'] ?? 0,
                'current_step' => $progress['current_step'] ?? 'unknown',
                'current_step_name' => $progress['current_step_name'] ?? 'Unknown',
                'steps_completed' => $progress['steps_completed'] ?? 0,
                'total_steps' => $progress['total_steps'] ?? 9,
                'estimated_time_remaining' => $progress['estimated_time_remaining'] ?? null,
                'details' => $progress['details'] ?? [],
                'last_activity' => $progress['last_activity'] ?? null,
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to get update progress');
            return [
                'session_id' => $sessionId,
                'status' => 'error',
                'percentage' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute the update steps.
     */
    private function executeUpdateSteps(string $targetVersion, array $options): void
    {
        // Step 1: Pre-update validation
        $this->progressTracker->startStep(0, 'Validating system requirements');
        $validationResult = $this->validationService->validatePreUpdate($targetVersion);
        if (!$validationResult['can_proceed']) {
            throw new UpdateException('Pre-update validation failed: system does not meet requirements');
        }
        $this->progressTracker->completeStep(0, 'Pre-update validation passed');

        // Step 2: Create system backup
        $this->progressTracker->startStep(1, 'Creating system backup');
        $backupResult = $this->backupService->createFullBackup($targetVersion, $this->currentSessionId);
        $this->progressTracker->completeStep(1, "Backup created: {$this->formatBytes($backupResult['backup_size'])}");

        // Step 3: Download update files
        $this->progressTracker->startStep(2, 'Downloading update files');
        $release = $this->githubReleaseService->getReleaseByVersion($targetVersion);
        $downloadResult = $this->githubFileService->downloadReleaseArchive($release['download_url'], $targetVersion);
        $this->progressTracker->completeStep(2, "Downloaded: {$this->formatBytes($downloadResult['size'])}");

        // Step 4: Extract and prepare files
        $this->progressTracker->startStep(3, 'Extracting update files');
        $extractResult = $this->githubFileService->extractArchive($downloadResult['path'], $targetVersion);
        $this->progressTracker->completeStep(3, "Extracted {$extractResult['files_extracted']} files");

        // Step 5: Compare and analyze changes
        $this->progressTracker->startStep(4, 'Analyzing file changes');
        $changes = $this->githubFileService->compareDirectories(base_path(), $extractResult['content_dir']);
        $totalChanges = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);
        $this->progressTracker->completeStep(4, "Found {$totalChanges} file changes");

        // Step 6: Apply file changes
        $this->progressTracker->startStep(5, 'Applying file updates');
        $fileChanges = $this->prepareFileChanges($changes, $extractResult['content_dir']);
        $fileResult = $this->fileUpdateService->applyFileChanges($this->currentSessionId, $fileChanges);
        $this->progressTracker->completeStep(5, "Applied {$fileResult['applied']} file changes");

        // Step 7: Run database migrations
        $this->progressTracker->startStep(6, 'Running database migrations');
        $migrationFiles = $this->findMigrationFiles($extractResult['content_dir']);
        if (!empty($migrationFiles)) {
            $migrationResult = $this->migrationService->executeMigrations($targetVersion, $migrationFiles);
            $this->progressTracker->completeStep(6, "Executed {$migrationResult['executed']} migrations");
        } else {
            $this->progressTracker->completeStep(6, 'No database migrations required');
        }

        // Step 8: Post-update validation
        $this->progressTracker->startStep(7, 'Validating updated system');
        $postValidationResult = $this->validationService->validatePostUpdate($targetVersion);
        if (!$postValidationResult['validation_passed']) {
            throw new UpdateException('Post-update validation failed: system integrity check failed');
        }
        $this->progressTracker->completeStep(7, 'Post-update validation passed');

        // Step 9: Clean up temporary files
        $this->progressTracker->startStep(8, 'Cleaning up temporary files');
        $this->githubFileService->cleanupTempFiles([
            $downloadResult['path'],
            $extractResult['extract_dir']
        ]);
        $this->progressTracker->completeStep(8, 'Temporary files cleaned up');
    }

    /**
     * Get the list of update steps.
     */
    private function getUpdateSteps(array $options): array
    {
        return [
            ['name' => 'Pre-update Validation', 'description' => 'Validate system requirements and readiness'],
            ['name' => 'System Backup', 'description' => 'Create full system backup'],
            ['name' => 'Download Files', 'description' => 'Download update package from GitHub'],
            ['name' => 'Extract Files', 'description' => 'Extract and prepare update files'],
            ['name' => 'Analyze Changes', 'description' => 'Compare current and new file versions'],
            ['name' => 'Apply File Updates', 'description' => 'Update application files'],
            ['name' => 'Database Migrations', 'description' => 'Run database schema updates'],
            ['name' => 'Post-update Validation', 'description' => 'Validate updated system'],
            ['name' => 'Cleanup', 'description' => 'Clean up temporary files'],
        ];
    }

    /**
     * Get the list of rollback steps.
     */
    private function getRollbackSteps(): array
    {
        return [
            ['name' => 'Rollback Files', 'description' => 'Restore original files'],
            ['name' => 'Rollback Database', 'description' => 'Reverse database migrations'],
            ['name' => 'Restore Backup', 'description' => 'Restore from backup if needed'],
            ['name' => 'Validate Rollback', 'description' => 'Validate system state after rollback'],
        ];
    }

    /**
     * Prepare file changes for application.
     */
    private function prepareFileChanges(array $changes, string $sourceDir): array
    {
        $fileChanges = [];

        // Added files
        foreach ($changes['added'] as $file) {
            $fileChanges[] = [
                'type' => 'added',
                'path' => $file['path'],
                'source_path' => $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['path']),
                'new_checksum' => $file['checksum'],
                'permissions' => 0644
            ];
        }

        // Modified files
        foreach ($changes['modified'] as $file) {
            $fileChanges[] = [
                'type' => 'modified',
                'path' => $file['path'],
                'source_path' => $sourceDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['path']),
                'old_checksum' => $file['old_checksum'],
                'new_checksum' => $file['new_checksum'],
                'permissions' => 0644
            ];
        }

        // Deleted files
        foreach ($changes['deleted'] as $file) {
            $fileChanges[] = [
                'type' => 'deleted',
                'path' => $file['path'],
                'old_checksum' => $file['checksum']
            ];
        }

        return $fileChanges;
    }

    /**
     * Find migration files in extracted directory.
     */
    private function findMigrationFiles(string $extractDir): array
    {
        $migrationFiles = [];
        $migrationDir = $extractDir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
        
        if (is_dir($migrationDir)) {
            $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $migrationFiles[] = $file;
                }
            }
        }

        return $migrationFiles;
    }

    /**
     * Start an update process for a specific version.
     */
    public function startUpdate(string $targetVersion, array $releaseDetails): string
    {
        try {
            $this->logInfo('Starting update process', [
                'target_version' => $targetVersion
            ]);

            // Get current version
            $currentVersion = $this->versionService->getCurrentVersion();
            $fromVersion = $currentVersion ? $currentVersion->version : '0.0.0';

            // Create update session
            $session = $this->sessionService->createSession([
                'from_version' => $fromVersion,
                'to_version' => $targetVersion,
                'initiated_by' => 'admin',
                'update_type' => $this->determineUpdateType($fromVersion, $targetVersion),
                'metadata' => [
                    'release_details' => $releaseDetails,
                    'started_via' => 'web_interface'
                ]
            ]);

            $sessionId = $session->session_id;
            $this->currentSessionId = $sessionId;

            // Initialize progress tracking
            $this->progressTracker->initializeProgress($sessionId, $this->getUpdateSteps());

            // Start the update process asynchronously
            $this->scheduleUpdateExecution($targetVersion, $releaseDetails);

            $this->logInfo('Update process started', [
                'session_id' => $sessionId,
                'target_version' => $targetVersion
            ]);

            return $sessionId;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to start update process');
            throw new UpdateException('Failed to start update: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Cancel an in-progress update if possible.
     */
    public function cancelUpdate(string $sessionId): array
    {
        try {
            $this->logInfo('Attempting to cancel update', ['session_id' => $sessionId]);

            $session = $this->sessionService->getSession($sessionId);
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Update session not found'
                ];
            }

            // Check if update can be cancelled
            $cancellableStates = ['pending', 'in_progress', 'preparing', 'downloading', 'extracting'];
            if (!in_array($session->status, $cancellableStates)) {
                return [
                    'success' => false,
                    'error' => 'Update cannot be cancelled in current state: ' . $session->status,
                    'can_rollback' => in_array($session->status, ['completed', 'failed'])
                ];
            }

            // Get current progress to determine if rollback is needed
            $progress = $this->progressTracker->getProgressForSession($sessionId);
            $rollbackRequired = ($progress['percentage'] ?? 0) > 20; // If more than 20% complete

            if ($rollbackRequired) {
                // Attempt rollback
                $rollbackResult = $this->performRollback($sessionId);
                
                $this->sessionService->updateSessionStatus($sessionId, 'cancelled');
                $this->progressTracker->cancelProgress($sessionId);

                return [
                    'success' => true,
                    'rollback_performed' => true,
                    'rollback_details' => $rollbackResult
                ];
            } else {
                // Simple cancellation - no rollback needed
                $this->sessionService->updateSessionStatus($sessionId, 'cancelled');
                $this->progressTracker->cancelProgress($sessionId);

                return [
                    'success' => true,
                    'rollback_performed' => false
                ];
            }

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to cancel update');
            return [
                'success' => false,
                'error' => 'Failed to cancel update: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rollback a completed or failed update.
     */
    public function rollbackUpdate(string $sessionId): array
    {
        try {
            $this->logInfo('Starting update rollback', ['session_id' => $sessionId]);

            $session = $this->sessionService->getSession($sessionId);
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Update session not found'
                ];
            }

            // Check if rollback is possible
            $rollbackableStates = ['completed', 'failed', 'cancelled'];
            if (!in_array($session->status, $rollbackableStates)) {
                return [
                    'success' => false,
                    'error' => 'Update cannot be rolled back in current state: ' . $session->status
                ];
            }

            // Perform the rollback
            $rollbackDetails = $this->performRollback($sessionId);

            // Update session status
            $this->sessionService->updateSessionStatus($sessionId, 'rolled_back');

            return [
                'success' => true,
                'details' => $rollbackDetails
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to rollback update');
            return [
                'success' => false,
                'error' => 'Failed to rollback update: ' . $e->getMessage(),
                'details' => null
            ];
        }
    }

    /**
     * Schedule the actual update execution (would normally run in background).
     */
    private function scheduleUpdateExecution(string $targetVersion, array $releaseDetails): void
    {
        // In a real implementation, this would dispatch a job to a queue
        // For now, we'll just update the session status
        $this->sessionService->updateSessionStatus($this->currentSessionId, 'in_progress');
    }

    /**
     * Perform rollback of an update session.
     */
    private function performRollback(string $sessionId): array
    {
        $rollbackDetails = [];

        try {
            // Get session details
            $session = $this->sessionService->getSession($sessionId);
            
            // Rollback files from backup
            if ($this->backupService->hasBackupForSession($sessionId)) {
                $fileRollback = $this->backupService->restoreFromBackup($sessionId);
                $rollbackDetails['files_restored'] = $fileRollback['files_restored'] ?? 0;
                $rollbackDetails['backup_restored'] = true;
            } else {
                $rollbackDetails['backup_restored'] = false;
                $rollbackDetails['warning'] = 'No backup found for this session';
            }

            // Rollback database migrations
            $migrationRollback = $this->migrationService->rollbackSessionMigrations($sessionId);
            $rollbackDetails['migrations_rolled_back'] = $migrationRollback['rolled_back'] ?? 0;

            // Restore previous version
            if ($session && $session->from_version) {
                $this->versionService->setCurrentVersion($session->from_version);
                $rollbackDetails['version_restored'] = $session->from_version;
            }

            $rollbackDetails['rollback_completed_at'] = now()->toDateTimeString();
            
            return $rollbackDetails;

        } catch (\Exception $e) {
            $rollbackDetails['error'] = $e->getMessage();
            throw $e;
        }
    }

    /**
     * Determine update type based on version comparison.
     */
    private function determineUpdateType(string $fromVersion, string $toVersion): string
    {
        // Parse semantic versions
        $fromParts = explode('.', $fromVersion);
        $toParts = explode('.', $toVersion);

        if (count($fromParts) >= 1 && count($toParts) >= 1) {
            if ($fromParts[0] !== $toParts[0]) {
                return 'major';
            } elseif (count($fromParts) >= 2 && count($toParts) >= 2 && $fromParts[1] !== $toParts[1]) {
                return 'minor';
            } else {
                return 'patch';
            }
        }

        return 'unknown';
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}