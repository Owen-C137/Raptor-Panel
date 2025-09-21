<?php

namespace Pterodactyl\Services\Updates\Files;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\FileOperationException;
use Pterodactyl\Models\UpdateFileChange;
use Pterodactyl\Services\Updates\BaseUpdateService;

/**
 * File Update Service
 * 
 * Handles file operations during update processes including
 * copying, moving, deleting, and permission management.
 */
class FileUpdateService extends BaseUpdateService
{
    private array $config;
    private string $tempDir;

    public function __construct()
    {
        $this->config = config('pterodactyl.updates', []);
        $this->tempDir = $this->config['temp_dir'] ?? sys_get_temp_dir() . '/raptor_updates';
        
        // Ensure temp directory exists
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function getServiceName(): string
    {
        return 'File Update Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if temp directory is writable
        if (!is_writable($this->tempDir)) {
            $errors[] = "Temp directory is not writable: {$this->tempDir}";
        }

        // Check if application directory is writable
        $appPath = base_path();
        if (!is_writable($appPath)) {
            $errors[] = "Application directory is not writable: {$appPath}";
        }

        // Check available disk space
        $availableSpace = disk_free_space($this->tempDir);
        $requiredSpace = $this->config['min_update_space'] ?? 536870912; // 512MB default

        if ($availableSpace < $requiredSpace) {
            $errors[] = "Insufficient disk space for updates. Available: " . $this->formatBytes($availableSpace) . 
                       ", Required: " . $this->formatBytes($requiredSpace);
        }

        return $errors;
    }

    /**
     * Apply file changes from an update.
     */
    public function applyFileChanges(string $sessionId, array $fileChanges): array
    {
        try {
            $this->logInfo('Starting file changes application', [
                'session_id' => $sessionId,
                'changes' => count($fileChanges)
            ]);

            $results = [
                'applied' => 0,
                'failed' => 0,
                'skipped' => 0,
                'details' => []
            ];

            foreach ($fileChanges as $change) {
                try {
                    $result = $this->applyFileChange($sessionId, $change);
                    $results['details'][] = $result;
                    
                    if ($result['status'] === 'applied') {
                        $results['applied']++;
                    } elseif ($result['status'] === 'skipped') {
                        $results['skipped']++;
                    } else {
                        $results['failed']++;
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'path' => $change['path'] ?? 'unknown',
                        'type' => $change['type'] ?? 'unknown',
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];

                    $this->logError('Failed to apply file change', [
                        'path' => $change['path'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logInfo('File changes application completed', [
                'session_id' => $sessionId,
                'applied' => $results['applied'],
                'failed' => $results['failed'],
                'skipped' => $results['skipped']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'File changes application failed');
            throw new FileOperationException('Failed to apply file changes: ' . $e->getMessage(), '', 0, $e);
        }
    }

    /**
     * Apply a single file change.
     */
    public function applyFileChange(string $sessionId, array $change): array
    {
        try {
            $changeType = $change['type'] ?? 'unknown';
            $filePath = $change['path'] ?? '';
            $fullPath = base_path($filePath);

            $this->logDebug('Applying file change', [
                'type' => $changeType,
                'path' => $filePath
            ]);

            $result = [
                'path' => $filePath,
                'type' => $changeType,
                'status' => 'unknown',
                'message' => '',
                'backup_created' => false
            ];

            // Create backup of existing file if it exists
            if (file_exists($fullPath) && in_array($changeType, ['modified', 'deleted'])) {
                $backupPath = $this->createFileBackup($fullPath, $sessionId);
                $result['backup_created'] = !empty($backupPath);
                $result['backup_path'] = $backupPath;
            }

            // Apply change based on type
            switch ($changeType) {
                case 'added':
                    $result = array_merge($result, $this->addFile($change, $fullPath));
                    break;

                case 'modified':
                    $result = array_merge($result, $this->modifyFile($change, $fullPath));
                    break;

                case 'deleted':
                    $result = array_merge($result, $this->deleteFile($change, $fullPath));
                    break;

                default:
                    throw new FileOperationException("Unknown change type: {$changeType}");
            }

            // Record change in database
            $this->recordFileChange($sessionId, $result, $change);

            return $result;

        } catch (\Exception $e) {
            $this->handleException($e, 'Single file change failed');
            throw new FileOperationException("Failed to apply change to '{$filePath}': " . $e->getMessage(), $filePath, 0, $e);
        }
    }

    /**
     * Rollback file changes for a session.
     */
    public function rollbackFileChanges(string $sessionId): array
    {
        try {
            $this->logInfo('Starting file changes rollback', ['session_id' => $sessionId]);

            // Get file changes to rollback (in reverse order)
            $changes = UpdateFileChange::where('session_id', $sessionId)
                ->where('status', 'applied')
                ->orderBy('applied_at', 'desc')
                ->get();

            $results = [
                'rolled_back' => 0,
                'failed' => 0,
                'details' => []
            ];

            foreach ($changes as $change) {
                try {
                    $result = $this->rollbackFileChange($change);
                    $results['details'][] = $result;
                    
                    if ($result['status'] === 'rolled_back') {
                        $results['rolled_back']++;
                    } else {
                        $results['failed']++;
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'path' => $change->file_path,
                        'type' => $change->change_type,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];

                    $this->logError('Failed to rollback file change', [
                        'path' => $change->file_path,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logInfo('File changes rollback completed', [
                'session_id' => $sessionId,
                'rolled_back' => $results['rolled_back'],
                'failed' => $results['failed']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'File changes rollback failed');
            throw new FileOperationException('Failed to rollback file changes: ' . $e->getMessage(), '', 0, $e);
        }
    }

    /**
     * Verify file integrity after changes.
     */
    public function verifyFileChanges(string $sessionId): array
    {
        try {
            $this->logInfo('Starting file changes verification', ['session_id' => $sessionId]);

            $changes = UpdateFileChange::where('session_id', $sessionId)->get();
            
            $results = [
                'verified' => 0,
                'failed' => 0,
                'missing' => 0,
                'details' => []
            ];

            foreach ($changes as $change) {
                $verificationResult = $this->verifyFileChange($change);
                $results['details'][] = $verificationResult;

                switch ($verificationResult['status']) {
                    case 'verified':
                        $results['verified']++;
                        break;
                    case 'missing':
                        $results['missing']++;
                        break;
                    default:
                        $results['failed']++;
                        break;
                }
            }

            $this->logInfo('File changes verification completed', [
                'session_id' => $sessionId,
                'verified' => $results['verified'],
                'failed' => $results['failed'],
                'missing' => $results['missing']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'File changes verification failed');
            throw new FileOperationException('Failed to verify file changes: ' . $e->getMessage(), '', 0, $e);
        }
    }

    /**
     * Copy files from source to destination.
     */
    public function copyFiles(string $sourceDir, string $destinationDir, array $filePaths): array
    {
        try {
            $this->logInfo('Starting file copy operation', [
                'source' => $sourceDir,
                'destination' => $destinationDir,
                'files' => count($filePaths)
            ]);

            $results = [
                'copied' => 0,
                'failed' => 0,
                'total_size' => 0,
                'details' => []
            ];

            foreach ($filePaths as $filePath) {
                try {
                    $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $filePath;
                    $destPath = $destinationDir . DIRECTORY_SEPARATOR . $filePath;

                    if (!file_exists($sourcePath)) {
                        throw new FileOperationException("Source file not found: {$sourcePath}");
                    }

                    // Create destination directory if needed
                    $destDir = dirname($destPath);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }

                    // Copy file
                    if (copy($sourcePath, $destPath)) {
                        $fileSize = filesize($destPath);
                        $results['copied']++;
                        $results['total_size'] += $fileSize;
                        
                        $results['details'][] = [
                            'path' => $filePath,
                            'status' => 'copied',
                            'size' => $fileSize
                        ];

                        // Preserve file permissions
                        $permissions = fileperms($sourcePath);
                        chmod($destPath, $permissions);

                    } else {
                        throw new FileOperationException("Failed to copy file");
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'path' => $filePath,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];

                    $this->logError('File copy failed', [
                        'path' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logInfo('File copy operation completed', [
                'copied' => $results['copied'],
                'failed' => $results['failed'],
                'total_size' => $this->formatBytes($results['total_size'])
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'File copy operation failed');
            throw new FileOperationException('Failed to copy files: ' . $e->getMessage(), $sourceDir, 0, $e);
        }
    }

    /**
     * Set file permissions recursively.
     */
    public function setPermissions(string $path, int $filePermissions = 0644, int $dirPermissions = 0755): array
    {
        try {
            $this->logInfo('Setting file permissions', [
                'path' => $path,
                'file_perms' => decoct($filePermissions),
                'dir_perms' => decoct($dirPermissions)
            ]);

            $results = [
                'files_updated' => 0,
                'dirs_updated' => 0,
                'failed' => 0,
                'details' => []
            ];

            if (is_file($path)) {
                // Single file
                if (chmod($path, $filePermissions)) {
                    $results['files_updated']++;
                } else {
                    $results['failed']++;
                }
            } elseif (is_dir($path)) {
                // Directory - process recursively
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    try {
                        if ($item->isFile()) {
                            if (chmod($item->getPathname(), $filePermissions)) {
                                $results['files_updated']++;
                            } else {
                                $results['failed']++;
                            }
                        } elseif ($item->isDir()) {
                            if (chmod($item->getPathname(), $dirPermissions)) {
                                $results['dirs_updated']++;
                            } else {
                                $results['failed']++;
                            }
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $this->logError('Permission setting failed', [
                            'path' => $item->getPathname(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $this->logInfo('Permission setting completed', $results);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'Permission setting failed');
            throw new FileOperationException('Failed to set permissions: ' . $e->getMessage(), $path, 0, $e);
        }
    }

    /**
     * Add a new file.
     */
    private function addFile(array $change, string $fullPath): array
    {
        $sourcePath = $change['source_path'] ?? '';
        
        if (!file_exists($sourcePath)) {
            throw new FileOperationException("Source file not found: {$sourcePath}");
        }

        // Create directory if needed
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Copy file
        if (copy($sourcePath, $fullPath)) {
            // Set permissions
            $permissions = $change['permissions'] ?? 0644;
            chmod($fullPath, $permissions);

            return [
                'status' => 'applied',
                'message' => 'File added successfully',
                'size' => filesize($fullPath)
            ];
        } else {
            throw new FileOperationException('Failed to copy new file');
        }
    }

    /**
     * Modify an existing file.
     */
    private function modifyFile(array $change, string $fullPath): array
    {
        $sourcePath = $change['source_path'] ?? '';
        
        if (!file_exists($sourcePath)) {
            throw new FileOperationException("Source file not found: {$sourcePath}");
        }

        // Verify checksum if provided
        if (isset($change['old_checksum']) && file_exists($fullPath)) {
            $currentChecksum = hash_file('sha256', $fullPath);
            if ($currentChecksum !== $change['old_checksum']) {
                $this->logWarning('File checksum mismatch, proceeding with modification', [
                    'path' => $fullPath,
                    'expected' => $change['old_checksum'],
                    'actual' => $currentChecksum
                ]);
            }
        }

        // Replace file
        if (copy($sourcePath, $fullPath)) {
            // Set permissions
            $permissions = $change['permissions'] ?? 0644;
            chmod($fullPath, $permissions);

            return [
                'status' => 'applied',
                'message' => 'File modified successfully',
                'size' => filesize($fullPath)
            ];
        } else {
            throw new FileOperationException('Failed to replace modified file');
        }
    }

    /**
     * Delete a file.
     */
    private function deleteFile(array $change, string $fullPath): array
    {
        if (!file_exists($fullPath)) {
            return [
                'status' => 'skipped',
                'message' => 'File already deleted or does not exist'
            ];
        }

        if (unlink($fullPath)) {
            return [
                'status' => 'applied',
                'message' => 'File deleted successfully'
            ];
        } else {
            throw new FileOperationException('Failed to delete file');
        }
    }

    /**
     * Create backup of a file.
     */
    private function createFileBackup(string $filePath, string $sessionId): ?string
    {
        try {
            $backupDir = $this->tempDir . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $sessionId;
            
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);
            $backupPath = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $backupFileDir = dirname($backupPath);

            if (!is_dir($backupFileDir)) {
                mkdir($backupFileDir, 0755, true);
            }

            if (copy($filePath, $backupPath)) {
                return $backupPath;
            }

        } catch (\Exception $e) {
            $this->logError('Failed to create file backup', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Record file change in database.
     */
    private function recordFileChange(string $sessionId, array $result, array $change): void
    {
        try {
            UpdateFileChange::create([
                'session_id' => $sessionId,
                'file_path' => $result['path'],
                'change_type' => $result['type'],
                'status' => $result['status'],
                'old_checksum' => $change['old_checksum'] ?? null,
                'new_checksum' => $change['new_checksum'] ?? null,
                'file_size' => $result['size'] ?? null,
                'backup_path' => $result['backup_path'] ?? null,
                'applied_at' => Carbon::now(),
                'error_message' => $result['error'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to record file change', [
                'path' => $result['path'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rollback a single file change.
     */
    private function rollbackFileChange(UpdateFileChange $change): array
    {
        $fullPath = base_path($change->file_path);

        try {
            switch ($change->change_type) {
                case 'added':
                    // Remove added file
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                    break;

                case 'modified':
                case 'deleted':
                    // Restore from backup
                    if ($change->backup_path && file_exists($change->backup_path)) {
                        copy($change->backup_path, $fullPath);
                    }
                    break;
            }

            $change->update([
                'status' => 'rolled_back',
                'rolled_back_at' => Carbon::now()
            ]);

            return [
                'path' => $change->file_path,
                'type' => $change->change_type,
                'status' => 'rolled_back'
            ];

        } catch (\Exception $e) {
            return [
                'path' => $change->file_path,
                'type' => $change->change_type,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify a single file change.
     */
    private function verifyFileChange(UpdateFileChange $change): array
    {
        $fullPath = base_path($change->file_path);

        try {
            $result = [
                'path' => $change->file_path,
                'type' => $change->change_type,
                'status' => 'unknown'
            ];

            switch ($change->change_type) {
                case 'added':
                case 'modified':
                    if (!file_exists($fullPath)) {
                        $result['status'] = 'missing';
                        $result['message'] = 'File does not exist';
                    } elseif ($change->new_checksum) {
                        $actualChecksum = hash_file('sha256', $fullPath);
                        if ($actualChecksum === $change->new_checksum) {
                            $result['status'] = 'verified';
                            $result['message'] = 'Checksum matches';
                        } else {
                            $result['status'] = 'failed';
                            $result['message'] = 'Checksum mismatch';
                            $result['expected_checksum'] = $change->new_checksum;
                            $result['actual_checksum'] = $actualChecksum;
                        }
                    } else {
                        $result['status'] = 'verified';
                        $result['message'] = 'File exists (no checksum provided)';
                    }
                    break;

                case 'deleted':
                    if (file_exists($fullPath)) {
                        $result['status'] = 'failed';
                        $result['message'] = 'File still exists';
                    } else {
                        $result['status'] = 'verified';
                        $result['message'] = 'File successfully deleted';
                    }
                    break;
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'path' => $change->file_path,
                'type' => $change->change_type,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
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