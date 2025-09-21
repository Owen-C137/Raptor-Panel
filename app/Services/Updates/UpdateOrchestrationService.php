<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Updates\UpdateSession;
use Pterodactyl\Services\Updates\Database\SessionService;
use Pterodactyl\Services\Updates\Database\VersionService;
use Pterodactyl\Services\Updates\Files\BackupService;
use Pterodactyl\Services\Updates\Files\FileUpdateService;
use Pterodactyl\Services\Updates\Files\ArchiveService;
use Pterodactyl\Services\Updates\GitHub\GitHubFileService;
use Pterodactyl\Services\Updates\GitHub\GitHubReleaseService;
use Pterodactyl\Services\Updates\Database\EnhancedMigrationService;
use Pterodactyl\Exceptions\Updates\UpdateException;

/**
 * UpdateOrchestrationService coordinates the complete update process.
 * 
 * This service orchestrates all update operations including:
 * - Session management and progress tracking
 * - File downloads and validation
 * - Backup creation and management
 * - File updates and rollbacks
 * - Migration execution
 * - Post-update verification
 */
class UpdateOrchestrationService extends BaseUpdateService
{
    private SessionService $sessionService;
    private VersionService $versionService;
    private BackupService $backupService;
    private FileUpdateService $fileUpdateService;
    private ArchiveService $archiveService;
    private GitHubFileService $githubFileService;
    private GitHubReleaseService $githubReleaseService;
    private EnhancedMigrationService $migrationService;
    private ValidationService $validationService;

    public function __construct(
        SessionService $sessionService,
        VersionService $versionService,
        BackupService $backupService,
        FileUpdateService $fileUpdateService,
        ArchiveService $archiveService,
        GitHubFileService $githubFileService,
        GitHubReleaseService $githubReleaseService,
        EnhancedMigrationService $migrationService,
        ValidationService $validationService
    ) {
        $this->sessionService = $sessionService;
        $this->versionService = $versionService;
        $this->backupService = $backupService;
        $this->fileUpdateService = $fileUpdateService;
        $this->archiveService = $archiveService;
        $this->githubFileService = $githubFileService;
        $this->githubReleaseService = $githubReleaseService;
        $this->migrationService = $migrationService;
        $this->validationService = $validationService;
    }

    /**
     * Execute complete update process.
     *
     * @param string $sessionId
     * @param array $options
     * @return array
     * @throws UpdateException
     */
    public function executeUpdate(string $sessionId, array $options = []): array
    {
        $session = $this->sessionService->findSession($sessionId);
        if (!$session) {
            throw new UpdateException("Update session not found: {$sessionId}");
        }

        try {
            Log::info("Starting update orchestration", [
                'session_id' => $sessionId,
                'target_version' => $options['target_version'] ?? 'unknown',
                'options' => $options,
            ]);

            // Update session status
            $this->sessionService->updateSession($sessionId, [
                'status' => 'running',
                'started_at' => now(),
                'current_step' => 'Initializing update process',
                'progress_percentage' => 0,
            ]);

            // Execute update phases
            $this->executePhase1($sessionId, $options); // Download & Preparation
            $this->executePhase2($sessionId, $options); // Backup Creation
            $this->executePhase3($sessionId, $options); // File Updates
            $this->executePhase4($sessionId, $options); // Migration Handling
            $this->executePhase5($sessionId, $options); // Finalization

            // Mark as completed
            $this->sessionService->updateSession($sessionId, [
                'status' => 'completed',
                'completed_at' => now(),
                'current_step' => 'Update completed successfully',
                'progress_percentage' => 100,
            ]);

            Log::info("Update orchestration completed successfully", [
                'session_id' => $sessionId,
            ]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Update completed successfully',
            ];

        } catch (Exception $e) {
            Log::error("Update orchestration failed", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark session as failed
            $this->sessionService->updateSession($sessionId, [
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
                'current_step' => 'Update failed: ' . $e->getMessage(),
            ]);

            // Attempt automatic rollback if configured
            if ($options['auto_rollback'] ?? true) {
                $this->attemptAutomaticRollback($sessionId);
            }

            throw new UpdateException("Update failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Phase 1: Download and Preparation
     */
    private function executePhase1(string $sessionId, array $options): void
    {
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Downloading update files',
            'progress_percentage' => 10,
        ]);

        $targetVersion = $options['target_version'];
        
        // Get release information
        $release = $this->githubReleaseService->getReleaseByTag($targetVersion);
        if (!$release) {
            throw new UpdateException("Release not found: {$targetVersion}");
        }

        // Download update archive
        $downloadResult = $this->githubFileService->downloadRelease($targetVersion, [
            'session_id' => $sessionId,
        ]);

        if (!$downloadResult['success']) {
            throw new UpdateException("Failed to download release: " . $downloadResult['error']);
        }

        // Validate archive integrity
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Validating download integrity',
            'progress_percentage' => 15,
        ]);

        $validationResult = $this->archiveService->validateArchive($downloadResult['file_path']);
        if (!$validationResult['valid']) {
            throw new UpdateException("Archive validation failed: " . $validationResult['error']);
        }

        // Extract archive
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Extracting update files',
            'progress_percentage' => 20,
        ]);

        $extractResult = $this->archiveService->extractArchive(
            $downloadResult['file_path'],
            $this->getUpdateTempDirectory($sessionId)
        );

        if (!$extractResult['success']) {
            throw new UpdateException("Failed to extract archive: " . $extractResult['error']);
        }
    }

    /**
     * Phase 2: Backup Creation
     */
    private function executePhase2(string $sessionId, array $options): void
    {
        if (!($options['create_backup'] ?? true)) {
            $this->sessionService->updateSession($sessionId, [
                'current_step' => 'Skipping backup creation',
                'progress_percentage' => 30,
            ]);
            return;
        }

        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Creating system backup',
            'progress_percentage' => 25,
        ]);

        $backupResult = $this->backupService->createFullBackup([
            'session_id' => $sessionId,
            'reason' => 'Pre-update backup',
            'include_database' => true,
            'include_files' => true,
        ]);

        if (!$backupResult['success']) {
            throw new UpdateException("Backup creation failed: " . $backupResult['error']);
        }

        // Store backup information in session
        $this->sessionService->updateSession($sessionId, [
            'backup_id' => $backupResult['backup_id'],
            'current_step' => 'System backup completed',
            'progress_percentage' => 35,
        ]);
    }

    /**
     * Phase 3: File Updates
     */
    private function executePhase3(string $sessionId, array $options): void
    {
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Updating application files',
            'progress_percentage' => 40,
        ]);

        $updateDirectory = $this->getUpdateTempDirectory($sessionId);
        
        // Get list of files to update
        $filesToUpdate = $this->fileUpdateService->analyzeUpdateFiles($updateDirectory);
        
        $this->sessionService->updateSession($sessionId, [
            'current_step' => "Updating {$filesToUpdate['total_files']} files",
            'progress_percentage' => 45,
        ]);

        // Update files with progress tracking
        $updateResult = $this->fileUpdateService->updateFiles($updateDirectory, [
            'session_id' => $sessionId,
            'progress_callback' => function($progress) use ($sessionId) {
                $this->sessionService->updateSession($sessionId, [
                    'progress_percentage' => 45 + ($progress * 0.25), // 25% of total progress for file updates
                ]);
            },
        ]);

        if (!$updateResult['success']) {
            throw new UpdateException("File update failed: " . $updateResult['error']);
        }

        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'File updates completed',
            'progress_percentage' => 70,
        ]);
    }

    /**
     * Phase 4: Migration Handling
     */
    private function executePhase4(string $sessionId, array $options): void
    {
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Checking for database migrations',
            'progress_percentage' => 75,
        ]);

        // Check for pending migrations
        $migrationAnalysis = $this->migrationService->detectMigrations();
        
        if ($migrationAnalysis['has_migrations']) {
            $this->sessionService->updateSession($sessionId, [
                'current_step' => "Executing {$migrationAnalysis['total_migrations']} database migrations",
                'progress_percentage' => 80,
            ]);

            // Execute migrations using the enhanced migration system
            $migrationResult = $this->migrationService->executeAdvancedWorkflow([
                'session_id' => $sessionId,
                'create_rollback_point' => true,
                'validate_after_execution' => true,
                'progress_callback' => function($progress) use ($sessionId) {
                    $this->sessionService->updateSession($sessionId, [
                        'progress_percentage' => 80 + ($progress * 0.10), // 10% of total progress for migrations
                    ]);
                },
            ]);

            if (!$migrationResult['success']) {
                throw new UpdateException("Migration execution failed: " . $migrationResult['error']);
            }
        } else {
            $this->sessionService->updateSession($sessionId, [
                'current_step' => 'No database migrations required',
                'progress_percentage' => 85,
            ]);
        }
    }

    /**
     * Phase 5: Finalization
     */
    private function executePhase5(string $sessionId, array $options): void
    {
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Finalizing update',
            'progress_percentage' => 90,
        ]);

        // Update version in database
        $targetVersion = $options['target_version'];
        $this->versionService->setCurrentVersion($targetVersion);

        // Clear caches
        $this->clearSystemCaches();

        // Post-update validation
        $this->sessionService->updateSession($sessionId, [
            'current_step' => 'Validating system integrity',
            'progress_percentage' => 95,
        ]);

        $validationResult = $this->validationService->validatePostUpdate();
        if (!$validationResult['valid']) {
            Log::warning("Post-update validation warnings", [
                'session_id' => $sessionId,
                'warnings' => $validationResult['warnings'],
            ]);
        }

        // Cleanup temporary files
        $this->cleanupTempFiles($sessionId);
    }

    /**
     * Cancel an update in progress.
     */
    public function cancelUpdate(string $sessionId): array
    {
        try {
            $session = $this->sessionService->findSession($sessionId);
            if (!$session) {
                return ['success' => false, 'error' => 'Session not found'];
            }

            // Check if cancellation is possible
            if (!in_array($session->status, ['pending', 'downloading', 'preparing'])) {
                return ['success' => false, 'error' => 'Update cannot be cancelled at this stage'];
            }

            // Mark as cancelled
            $this->sessionService->updateSession($sessionId, [
                'status' => 'cancelled',
                'completed_at' => now(),
                'current_step' => 'Update cancelled by user',
            ]);

            // Cleanup temporary files
            $this->cleanupTempFiles($sessionId);

            Log::info("Update cancelled", ['session_id' => $sessionId]);

            return ['success' => true, 'message' => 'Update cancelled successfully'];

        } catch (Exception $e) {
            Log::error("Failed to cancel update", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Rollback an update.
     */
    public function rollbackUpdate(string $sessionId): array
    {
        try {
            $session = $this->sessionService->findSession($sessionId);
            if (!$session) {
                return ['success' => false, 'error' => 'Session not found'];
            }

            if (!$session->backup_id) {
                return ['success' => false, 'error' => 'No backup available for rollback'];
            }

            Log::info("Starting update rollback", [
                'session_id' => $sessionId,
                'backup_id' => $session->backup_id,
            ]);

            // Restore from backup
            $restoreResult = $this->backupService->restoreBackup($session->backup_id);
            
            if (!$restoreResult['success']) {
                throw new UpdateException("Backup restore failed: " . $restoreResult['error']);
            }

            // Mark session as rolled back
            $this->sessionService->updateSession($sessionId, [
                'status' => 'rolled_back',
                'completed_at' => now(),
                'current_step' => 'Update rolled back successfully',
            ]);

            Log::info("Update rollback completed", ['session_id' => $sessionId]);

            return ['success' => true, 'message' => 'Rollback completed successfully'];

        } catch (Exception $e) {
            Log::error("Rollback failed", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Attempt automatic rollback on failure.
     */
    private function attemptAutomaticRollback(string $sessionId): void
    {
        try {
            Log::info("Attempting automatic rollback", ['session_id' => $sessionId]);
            
            $rollbackResult = $this->rollbackUpdate($sessionId);
            
            if ($rollbackResult['success']) {
                Log::info("Automatic rollback successful", ['session_id' => $sessionId]);
            } else {
                Log::error("Automatic rollback failed", [
                    'session_id' => $sessionId,
                    'error' => $rollbackResult['error'],
                ]);
            }
        } catch (Exception $e) {
            Log::error("Exception during automatic rollback", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get temporary directory for update files.
     */
    private function getUpdateTempDirectory(string $sessionId): string
    {
        return storage_path("app/updates/temp/{$sessionId}");
    }

    /**
     * Clear system caches.
     */
    private function clearSystemCaches(): void
    {
        try {
            // Clear Laravel caches
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            \Artisan::call('route:clear');
            
            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
        } catch (Exception $e) {
            Log::warning("Failed to clear some caches", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clean up temporary files.
     */
    private function cleanupTempFiles(string $sessionId): void
    {
        try {
            $tempDirectory = $this->getUpdateTempDirectory($sessionId);
            
            if (is_dir($tempDirectory)) {
                $this->deleteDirectory($tempDirectory);
            }
        } catch (Exception $e) {
            Log::warning("Failed to cleanup temporary files", [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = array_diff(scandir($directory), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($directory);
    }

    /**
     * Get configuration errors for this service.
     *
     * @return array
     */
    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if required services are available
        $requiredServices = [
            'session_service' => $this->sessionService,
            'version_service' => $this->versionService,
            'backup_service' => $this->backupService,
            'file_update_service' => $this->fileUpdateService,
            'github_release_service' => $this->githubReleaseService,
            'migration_service' => $this->migrationService,
            'validation_service' => $this->validationService,
        ];

        foreach ($requiredServices as $serviceName => $service) {
            if (!$service) {
                $errors[] = "Required service '{$serviceName}' is not available";
            }
        }

        return $errors;
    }

    /**
     * Get service name.
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return 'update_orchestration';
    }
}