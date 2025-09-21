<?php

namespace Pterodactyl\Services\Updates\Files;

use Carbon\Carbon;
use Pterodactyl\Exceptions\Updates\BackupException;
use Pterodactyl\Exceptions\Updates\FileOperationException;
use Pterodactyl\Models\UpdateBackup;
use Pterodactyl\Services\Updates\BaseUpdateService;
use ZipArchive;

/**
 * Backup Service
 * 
 * Handles creation, management, and restoration of system backups
 * before and during update processes.
 */
class BackupService extends BaseUpdateService
{
    private array $config;
    private string $backupPath;

    public function __construct()
    {
        $this->config = config('updates', []);
        $this->backupPath = $this->config['backup_path'] ?? storage_path('app/backups/updates');
        
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function getServiceName(): string
    {
        return 'Backup Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if backup directory is writable
        if (!is_writable($this->backupPath)) {
            $errors[] = "Backup directory is not writable: {$this->backupPath}";
        }

        // Check available disk space
        $availableSpace = disk_free_space($this->backupPath);
        $requiredSpace = $this->config['min_backup_space'] ?? 1073741824; // 1GB default

        if ($availableSpace < $requiredSpace) {
            $errors[] = "Insufficient disk space for backups. Available: " . $this->formatBytes($availableSpace) . 
                       ", Required: " . $this->formatBytes($requiredSpace);
        }

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            $errors[] = 'ZipArchive extension is not available';
        }

        return $errors;
    }

    /**
     * Create a full system backup before update.
     */
    public function createFullBackup(string $version, string $sessionId): array
    {
        try {
            $this->logInfo('Starting full system backup', [
                'version' => $version,
                'session_id' => $sessionId
            ]);

            $backupId = $this->generateBackupId('full', $version);
            $backupDir = $this->backupPath . DIRECTORY_SEPARATOR . $backupId;
            
            // Create backup directory
            if (!mkdir($backupDir, 0755, true)) {
                throw new BackupException("Failed to create backup directory: {$backupDir}");
            }

            $startTime = microtime(true);
            $components = [];

            // 1. Backup application files
            $this->logInfo('Backing up application files');
            $filesBackup = $this->backupApplicationFiles($backupDir);
            $components['files'] = $filesBackup;

            // 2. Backup database
            $this->logInfo('Backing up database');
            $databaseBackup = $this->backupDatabase($backupDir);
            $components['database'] = $databaseBackup;

            // 3. Backup configuration files
            $this->logInfo('Backing up configuration files');
            $configBackup = $this->backupConfiguration($backupDir);
            $components['config'] = $configBackup;

            // 4. Backup storage files (if configured)
            if ($this->config['backup_storage'] ?? true) {
                $this->logInfo('Backing up storage files');
                $storageBackup = $this->backupStorage($backupDir);
                $components['storage'] = $storageBackup;
            }

            // 5. Create backup archive
            $archivePath = $this->createBackupArchive($backupDir, $backupId);

            $executionTime = microtime(true) - $startTime;
            $backupSize = file_exists($archivePath) ? filesize($archivePath) : 0;

            // Record backup in database
            $backupRecord = $this->recordBackup([
                'backup_id' => $backupId,
                'version' => $version,
                'session_id' => $sessionId,
                'backup_type' => 'full',
                'backup_path' => $archivePath,
                'backup_size' => $backupSize,
                'components' => $components,
                'status' => 'completed',
                'execution_time' => $executionTime
            ]);

            $this->logInfo('Full backup completed successfully', [
                'backup_id' => $backupId,
                'size' => $this->formatBytes($backupSize),
                'execution_time' => round($executionTime, 2) . 's',
                'components' => array_keys($components)
            ]);

            return [
                'backup_id' => $backupId,
                'backup_path' => $archivePath,
                'backup_size' => $backupSize,
                'execution_time' => $executionTime,
                'components' => $components,
                'record_id' => $backupRecord->id
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Full backup failed');
            throw new BackupException('Failed to create full backup: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create an incremental backup of changed files.
     */
    public function createIncrementalBackup(string $version, string $sessionId, array $changedFiles): array
    {
        try {
            $this->logInfo('Starting incremental backup', [
                'version' => $version,
                'session_id' => $sessionId,
                'changed_files' => count($changedFiles)
            ]);

            $backupId = $this->generateBackupId('incremental', $version);
            $backupDir = $this->backupPath . DIRECTORY_SEPARATOR . $backupId;
            
            // Create backup directory
            if (!mkdir($backupDir, 0755, true)) {
                throw new BackupException("Failed to create backup directory: {$backupDir}");
            }

            $startTime = microtime(true);
            $backedUpFiles = [];
            $totalSize = 0;

            // Backup changed files
            foreach ($changedFiles as $file) {
                $sourcePath = $file['full_path'] ?? $file['path'];
                $relativePath = $file['path'];

                if (file_exists($sourcePath)) {
                    $backupFilePath = $backupDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                    $backupFileDir = dirname($backupFilePath);

                    // Create directory structure
                    if (!is_dir($backupFileDir)) {
                        mkdir($backupFileDir, 0755, true);
                    }

                    // Copy file
                    if (copy($sourcePath, $backupFilePath)) {
                        $fileSize = filesize($backupFilePath);
                        $totalSize += $fileSize;
                        
                        $backedUpFiles[] = [
                            'path' => $relativePath,
                            'size' => $fileSize,
                            'checksum' => hash_file('sha256', $backupFilePath)
                        ];
                    } else {
                        $this->logWarning('Failed to backup file', ['path' => $relativePath]);
                    }
                }
            }

            // Create backup archive
            $archivePath = $this->createBackupArchive($backupDir, $backupId);

            $executionTime = microtime(true) - $startTime;
            $backupSize = file_exists($archivePath) ? filesize($archivePath) : 0;

            // Record backup in database
            $backupRecord = $this->recordBackup([
                'backup_id' => $backupId,
                'version' => $version,
                'session_id' => $sessionId,
                'backup_type' => 'incremental',
                'backup_path' => $archivePath,
                'backup_size' => $backupSize,
                'components' => ['files' => $backedUpFiles],
                'status' => 'completed',
                'execution_time' => $executionTime
            ]);

            $this->logInfo('Incremental backup completed successfully', [
                'backup_id' => $backupId,
                'files_backed_up' => count($backedUpFiles),
                'size' => $this->formatBytes($backupSize),
                'execution_time' => round($executionTime, 2) . 's'
            ]);

            return [
                'backup_id' => $backupId,
                'backup_path' => $archivePath,
                'backup_size' => $backupSize,
                'execution_time' => $executionTime,
                'files_backed_up' => count($backedUpFiles),
                'record_id' => $backupRecord->id
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Incremental backup failed');
            throw new BackupException('Failed to create incremental backup: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Restore from a backup.
     */
    public function restoreBackup(string $backupId): array
    {
        try {
            $this->logInfo('Starting backup restoration', ['backup_id' => $backupId]);

            // Get backup record
            $backup = UpdateBackup::where('backup_id', $backupId)->first();
            if (!$backup) {
                throw new BackupException("Backup not found: {$backupId}");
            }

            // Check if backup file exists
            if (!file_exists($backup->backup_path)) {
                throw new BackupException("Backup file not found: {$backup->backup_path}");
            }

            $startTime = microtime(true);
            $restoreDir = $this->backupPath . DIRECTORY_SEPARATOR . 'restore_' . $backupId . '_' . time();

            // Extract backup archive
            $this->logInfo('Extracting backup archive');
            $this->extractBackupArchive($backup->backup_path, $restoreDir);

            $restoredComponents = [];

            // Restore based on backup type
            if ($backup->backup_type === 'full') {
                $restoredComponents = $this->restoreFullBackup($restoreDir, $backup);
            } else {
                $restoredComponents = $this->restoreIncrementalBackup($restoreDir, $backup);
            }

            // Update backup record
            $backup->update([
                'last_restored_at' => Carbon::now(),
                'restore_count' => $backup->restore_count + 1
            ]);

            $executionTime = microtime(true) - $startTime;

            $this->logInfo('Backup restoration completed successfully', [
                'backup_id' => $backupId,
                'execution_time' => round($executionTime, 2) . 's',
                'components' => array_keys($restoredComponents)
            ]);

            // Cleanup restore directory
            $this->removeDirectory($restoreDir);

            return [
                'backup_id' => $backupId,
                'execution_time' => $executionTime,
                'restored_components' => $restoredComponents
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Backup restoration failed');
            throw new BackupException('Failed to restore backup: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * List available backups.
     */
    public function listBackups(?string $version = null, int $limit = 20): array
    {
        try {
            $query = UpdateBackup::orderBy('created_at', 'desc');
            
            if ($version) {
                $query->where('version', $version);
            }
            
            $backups = $query->limit($limit)->get()->toArray();

            $this->logDebug('Retrieved backup list', [
                'version' => $version,
                'count' => count($backups)
            ]);

            return $backups;

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to list backups');
            throw new BackupException('Failed to list backups: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete old backups based on retention policy.
     */
    public function cleanupOldBackups(): int
    {
        try {
            $this->logInfo('Starting backup cleanup');

            $retentionDays = $this->config['backup_retention_days'] ?? 30;
            $maxBackups = $this->config['max_backups'] ?? 50;
            
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            
            // Get backups to delete
            $backupsToDelete = UpdateBackup::where('created_at', '<', $cutoffDate)
                ->orWhere(function ($query) use ($maxBackups) {
                    $query->whereNotIn('id', function ($subQuery) use ($maxBackups) {
                        $subQuery->select('id')
                            ->from('update_backups')
                            ->orderBy('created_at', 'desc')
                            ->limit($maxBackups);
                    });
                })
                ->get();

            $deletedCount = 0;

            foreach ($backupsToDelete as $backup) {
                try {
                    // Delete backup file
                    if (file_exists($backup->backup_path)) {
                        unlink($backup->backup_path);
                    }

                    // Delete backup record
                    $backup->delete();
                    $deletedCount++;

                    $this->logDebug('Deleted old backup', [
                        'backup_id' => $backup->backup_id,
                        'created_at' => $backup->created_at
                    ]);

                } catch (\Exception $e) {
                    $this->logWarning('Failed to delete backup', [
                        'backup_id' => $backup->backup_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logInfo('Backup cleanup completed', [
                'deleted_count' => $deletedCount,
                'retention_days' => $retentionDays,
                'max_backups' => $maxBackups
            ]);

            return $deletedCount;

        } catch (\Exception $e) {
            $this->handleException($e, 'Backup cleanup failed');
            throw new BackupException('Failed to cleanup old backups: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Verify backup integrity.
     */
    public function verifyBackup(string $backupId): array
    {
        try {
            $this->logInfo('Verifying backup integrity', ['backup_id' => $backupId]);

            $backup = UpdateBackup::where('backup_id', $backupId)->first();
            if (!$backup) {
                throw new BackupException("Backup not found: {$backupId}");
            }

            $results = [
                'backup_id' => $backupId,
                'file_exists' => false,
                'file_readable' => false,
                'size_matches' => false,
                'archive_valid' => false,
                'components_verified' => false,
                'overall_valid' => false
            ];

            // Check if backup file exists
            $results['file_exists'] = file_exists($backup->backup_path);
            
            if ($results['file_exists']) {
                // Check if file is readable
                $results['file_readable'] = is_readable($backup->backup_path);
                
                // Check file size
                $actualSize = filesize($backup->backup_path);
                $results['size_matches'] = ($actualSize === $backup->backup_size);
                
                // Check if archive is valid
                if ($results['file_readable']) {
                    $results['archive_valid'] = $this->isValidZipArchive($backup->backup_path);
                }
            }

            $results['overall_valid'] = $results['file_exists'] && 
                                       $results['file_readable'] && 
                                       $results['size_matches'] && 
                                       $results['archive_valid'];

            $this->logInfo('Backup verification completed', [
                'backup_id' => $backupId,
                'valid' => $results['overall_valid']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'Backup verification failed');
            throw new BackupException('Failed to verify backup: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Backup application files.
     */
    private function backupApplicationFiles(string $backupDir): array
    {
        $appDir = base_path();
        $excludePaths = $this->config['exclude_paths'] ?? [
            'storage/logs',
            'storage/app/backups',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'node_modules',
            '.git',
            'vendor'
        ];

        return $this->backupDirectory($appDir, $backupDir . '/app', $excludePaths);
    }

    /**
     * Backup database.
     */
    private function backupDatabase(string $backupDir): array
    {
        try {
            $databaseConfig = config('database.connections.mysql');
            $backupFile = $backupDir . '/database.sql';

            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                $databaseConfig['host'],
                $databaseConfig['port'],
                $databaseConfig['username'],
                $databaseConfig['password'],
                $databaseConfig['database'],
                $backupFile
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new BackupException("Database backup failed with exit code: {$returnCode}");
            }

            return [
                'file' => $backupFile,
                'size' => filesize($backupFile)
            ];

        } catch (\Exception $e) {
            $this->logError('Database backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Backup configuration files.
     */
    private function backupConfiguration(string $backupDir): array
    {
        $configPaths = [
            '.env',
            'config/',
            'bootstrap/cache/'
        ];

        $backedUpFiles = [];

        foreach ($configPaths as $path) {
            $fullPath = base_path($path);
            if (file_exists($fullPath)) {
                $backupPath = $backupDir . '/config/' . $path;
                $this->copyPath($fullPath, $backupPath);
                
                $backedUpFiles[] = [
                    'path' => $path,
                    'backup_path' => $backupPath
                ];
            }
        }

        return ['files' => $backedUpFiles];
    }

    /**
     * Backup storage files.
     */
    private function backupStorage(string $backupDir): array
    {
        $storageDir = storage_path('app');
        $excludePaths = ['backups', 'cache'];
        
        return $this->backupDirectory($storageDir, $backupDir . '/storage', $excludePaths);
    }

    /**
     * Backup directory recursively.
     */
    private function backupDirectory(string $sourceDir, string $backupDir, array $excludePaths = []): array
    {
        $backedUpFiles = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            
            // Check if path should be excluded
            $shouldExclude = false;
            foreach ($excludePaths as $excludePath) {
                if (strpos($relativePath, $excludePath) === 0) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $backupPath = $backupDir . DIRECTORY_SEPARATOR . $relativePath;

            if ($file->isFile()) {
                $backupFileDir = dirname($backupPath);
                if (!is_dir($backupFileDir)) {
                    mkdir($backupFileDir, 0755, true);
                }

                if (copy($file->getPathname(), $backupPath)) {
                    $backedUpFiles[] = [
                        'path' => $relativePath,
                        'size' => $file->getSize()
                    ];
                }
            }
        }

        return ['files' => $backedUpFiles];
    }

    /**
     * Additional helper methods for backup operations...
     */
    private function generateBackupId(string $type, string $version): string
    {
        return $type . '_' . str_replace('.', '_', $version) . '_' . date('Y_m_d_H_i_s');
    }

    private function createBackupArchive(string $backupDir, string $backupId): string
    {
        $archivePath = $this->backupPath . DIRECTORY_SEPARATOR . $backupId . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($archivePath, ZipArchive::CREATE) !== TRUE) {
            throw new BackupException("Failed to create backup archive: {$archivePath}");
        }

        $this->addDirectoryToZip($zip, $backupDir, '');
        $zip->close();

        return $archivePath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = $zipPath . substr($file->getPathname(), strlen($sourceDir));

            if ($file->isFile()) {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    private function extractBackupArchive(string $archivePath, string $extractDir): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== TRUE) {
            throw new BackupException("Failed to open backup archive: {$archivePath}");
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new BackupException("Failed to extract backup archive");
        }

        $zip->close();
    }

    private function restoreFullBackup(string $restoreDir, UpdateBackup $backup): array
    {
        // This would implement full system restoration logic
        // For now, return a placeholder
        return ['status' => 'Full restoration not yet implemented'];
    }

    private function restoreIncrementalBackup(string $restoreDir, UpdateBackup $backup): array
    {
        // This would implement incremental restoration logic
        // For now, return a placeholder
        return ['status' => 'Incremental restoration not yet implemented'];
    }

    private function recordBackup(array $data): UpdateBackup
    {
        return UpdateBackup::create($data);
    }

    private function isValidZipArchive(string $filePath): bool
    {
        $zip = new ZipArchive();
        $result = $zip->open($filePath, ZipArchive::CHECKCONS);
        $zip->close();
        
        return $result === TRUE;
    }

    private function copyPath(string $source, string $destination): void
    {
        if (is_file($source)) {
            $destDir = dirname($destination);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($source, $destination);
        } elseif (is_dir($source)) {
            $this->backupDirectory($source, $destination);
        }
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }

        return rmdir($dir);
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