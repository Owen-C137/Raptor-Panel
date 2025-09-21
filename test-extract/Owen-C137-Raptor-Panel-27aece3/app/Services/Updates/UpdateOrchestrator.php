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
     * Get real-time progress for an update session.
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
     * Schedule the actual update execution (would normally run in background).
     */
    private function scheduleUpdateExecution(string $targetVersion, array $releaseDetails): void
    {
        // In a real implementation, this would dispatch a job to a queue
        // For now, we'll just update the session status
        $this->sessionService->updateSessionStatus($this->currentSessionId, 'in_progress');
        
        // Start the execution pipeline
        try {
            $this->executeUpdatePipeline($targetVersion, $releaseDetails);
        } catch (\Exception $e) {
            $this->logError('Update execution pipeline failed', [
                'session_id' => $this->currentSessionId,
                'error' => $e->getMessage()
            ]);
            $this->sessionService->updateSessionStatus($this->currentSessionId, 'failed', $e->getMessage());
        }
    }

    /**
     * Execute the complete update pipeline.
     */
    private function executeUpdatePipeline(string $targetVersion, array $releaseDetails): void
    {
        $sessionId = $this->currentSessionId;
        
        try {
            // Step 1: Pre-update validation (5%)
            $this->progressTracker->startStep(0, 'Validating system requirements');
            $validationResult = $this->validationService->validatePreUpdate($targetVersion);
            if (!$validationResult['can_proceed']) {
                throw new UpdateException('Pre-update validation failed: system does not meet requirements');
            }
            $this->progressTracker->completeStep(0, 'Pre-update validation passed');
            $this->progressTracker->updateProgress(5);

            // Step 2: Create system backup (15%)
            $this->progressTracker->startStep(1, 'Creating system backup');
            $backupResult = $this->backupService->createFullBackup($targetVersion, $sessionId);
            $this->progressTracker->completeStep(1, "Backup created: {$this->formatBytes($backupResult['backup_size'])}");
            $this->progressTracker->updateProgress(20);

            // Step 3: Download update files (35%)
            $this->progressTracker->startStep(2, 'Downloading update files');
            $downloadResult = $this->githubFileService->downloadReleaseArchive($releaseDetails['download_url'], $targetVersion);
            $this->progressTracker->completeStep(2, "Downloaded: {$this->formatBytes($downloadResult['size'])}");
            $this->progressTracker->updateProgress(35);

            // Step 4: Extract and prepare files (45%)
            $this->progressTracker->startStep(3, 'Extracting update files');
            $extractResult = $this->githubFileService->extractArchive($downloadResult['path'], $targetVersion);
            $this->progressTracker->completeStep(3, "Extracted {$extractResult['files_extracted']} files");
            $this->progressTracker->updateProgress(45);

            // Step 5: Analyze file changes (55%)
            $this->progressTracker->startStep(4, 'Analyzing file changes');
            $changes = $this->githubFileService->compareDirectories(base_path(), $extractResult['content_dir']);
            $totalChanges = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);
            $this->progressTracker->completeStep(4, "Found {$totalChanges} file changes");
            $this->progressTracker->updateProgress(55);

            // Step 6: Apply file changes (75%)
            $this->progressTracker->startStep(5, 'Applying file updates');
            $fileChanges = $this->prepareFileChanges($changes, $extractResult['content_dir']);
            $fileResult = $this->fileUpdateService->applyFileChanges($sessionId, $fileChanges);
            $this->progressTracker->completeStep(5, "Applied {$fileResult['applied']} file changes");
            $this->progressTracker->updateProgress(75);

            // Step 7: Run database migrations (85%)
            $this->progressTracker->startStep(6, 'Running database migrations');
            $migrationFiles = $this->findMigrationFiles($extractResult['content_dir']);
            if (!empty($migrationFiles)) {
                $migrationResult = $this->migrationService->executeMigrations($targetVersion, $migrationFiles);
                $this->progressTracker->completeStep(6, "Executed {$migrationResult['executed']} migrations");
            } else {
                $this->progressTracker->completeStep(6, 'No database migrations required');
            }
            $this->progressTracker->updateProgress(85);

            // Step 8: Post-update validation (95%)
            $this->progressTracker->startStep(7, 'Validating updated system');
            $postValidationResult = $this->validationService->validatePostUpdate($targetVersion);
            if (!$postValidationResult['validation_passed']) {
                throw new UpdateException('Post-update validation failed: system integrity check failed');
            }
            $this->progressTracker->completeStep(7, 'Post-update validation passed');
            $this->progressTracker->updateProgress(95);

            // Step 9: Finalize and cleanup (100%)
            $this->progressTracker->startStep(8, 'Finalizing update');
            $this->finalizeUpdate($targetVersion, [
                $downloadResult['path'],
                $extractResult['content_dir']
            ]);
            $this->progressTracker->completeStep(8, 'Update completed successfully');
            $this->progressTracker->updateProgress(100);

            // Mark update as completed
            $this->sessionService->updateSessionStatus($sessionId, 'completed');
            $this->versionService->setCurrentVersion($targetVersion);

            $this->logInfo('Update pipeline completed successfully', [
                'session_id' => $sessionId,
                'target_version' => $targetVersion
            ]);

        } catch (\Exception $e) {
            $this->logError('Update pipeline failed', [
                'session_id' => $sessionId,
                'step' => $this->progressTracker->getCurrentStep(),
                'error' => $e->getMessage()
            ]);
            
            // Attempt automatic rollback if configured
            if ($this->config['auto_rollback_on_failure'] ?? true) {
                try {
                    $this->performRollback($sessionId);
                    $this->sessionService->updateSessionStatus($sessionId, 'failed_rolled_back', $e->getMessage());
                } catch (\Exception $rollbackException) {
                    $this->sessionService->updateSessionStatus($sessionId, 'failed', $e->getMessage());
                }
            } else {
                $this->sessionService->updateSessionStatus($sessionId, 'failed', $e->getMessage());
            }
            
            throw $e;
        }
    }

    /**
     * Prepare file changes for application.
     */
    private function prepareFileChanges(array $changes, string $sourceDir): array
    {
        $fileChanges = [];
        
        foreach ($changes['added'] as $file) {
            $fileChanges[] = [
                'action' => 'add',
                'source' => $sourceDir . '/' . $file,
                'target' => base_path($file),
                'relative_path' => $file
            ];
        }
        
        foreach ($changes['modified'] as $file) {
            $fileChanges[] = [
                'action' => 'modify',
                'source' => $sourceDir . '/' . $file,
                'target' => base_path($file),
                'relative_path' => $file
            ];
        }
        
        foreach ($changes['deleted'] as $file) {
            $fileChanges[] = [
                'action' => 'delete',
                'target' => base_path($file),
                'relative_path' => $file
            ];
        }
        
        return $fileChanges;
    }

    /**
     * Find migration files in the extracted content.
     */
    private function findMigrationFiles(string $contentDir): array
    {
        $migrationDir = $contentDir . '/database/migrations';
        
        if (!is_dir($migrationDir)) {
            return [];
        }
        
        $migrationFiles = [];
        $files = scandir($migrationDir);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && str_ends_with($file, '.php')) {
                $migrationFiles[] = $migrationDir . '/' . $file;
            }
        }
        
        sort($migrationFiles); // Ensure proper order
        return $migrationFiles;
    }

    /**
     * Finalize the update and clean up.
     */
    private function finalizeUpdate(string $targetVersion, array $tempFiles): void
    {
        try {
            // Clean up temporary files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    if (is_dir($tempFile)) {
                        $this->removeDirectory($tempFile);
                    } else {
                        unlink($tempFile);
                    }
                }
            }
            
            // Clear application caches
            $this->clearApplicationCaches();
            
            // Update panel version record
            $this->versionService->recordVersionUpdate($targetVersion, [
                'updated_at' => now(),
                'update_method' => 'automatic',
                'session_id' => $this->currentSessionId
            ]);
            
        } catch (\Exception $e) {
            $this->logWarning('Finalization warning', [
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire update for finalization issues
        }
    }

    /**
     * Clear application caches after update.
     */
    private function clearApplicationCaches(): void
    {
        $cachesToClear = [
            'config' => 'config:clear',
            'route' => 'route:clear',
            'view' => 'view:clear',
            'cache' => 'cache:clear'
        ];
        
        foreach ($cachesToClear as $cache => $command) {
            try {
                \Artisan::call($command);
                $this->logInfo("Cleared {$cache} cache");
            } catch (\Exception $e) {
                $this->logWarning("Failed to clear {$cache} cache", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
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
     * Get the update steps for progress tracking.
     */
    private function getUpdateSteps(): array
    {
        return [
            'Pre-update validation',
            'Creating system backup',
            'Downloading update files',
            'Extracting update files',
            'Analyzing file changes',
            'Applying file updates',
            'Running database migrations',
            'Post-update validation',
            'Cleaning up temporary files'
        ];
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