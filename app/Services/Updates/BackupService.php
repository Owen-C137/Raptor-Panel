<?php

namespace Pterodactyl\Services\Updates;

use Exception;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupService
{
    protected string $backupDirectory;

    public function __construct()
    {
        $this->backupDirectory = storage_path('app/backups/updates');
        $this->ensureBackupDirectoryExists();
    }

    /**
     * Create a backup of files that will be updated.
     */
    public function createBackup(array $filePaths): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupName = "raptor_update_backup_{$timestamp}";
        $backupPath = $this->backupDirectory . "/{$backupName}";
        
        try {
            // Create backup directory
            if (!mkdir($backupPath, 0755, true)) {
                throw new Exception("Failed to create backup directory: {$backupPath}");
            }

            $backedUpFiles = [];
            $failedFiles = [];

            foreach ($filePaths as $filePath) {
                $sourceFile = base_path($filePath);
                
                if (!file_exists($sourceFile)) {
                    continue; // Skip files that don't exist locally
                }

                $backupFile = $backupPath . '/' . $filePath;
                $backupDir = dirname($backupFile);

                // Create directory structure
                if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true)) {
                    $failedFiles[] = $filePath;
                    continue;
                }

                // Copy file
                if (copy($sourceFile, $backupFile)) {
                    $backedUpFiles[] = $filePath;
                } else {
                    $failedFiles[] = $filePath;
                }
            }

            // Create backup manifest
            $this->createBackupManifest($backupPath, $backedUpFiles, $failedFiles);

            // Create zip archive
            $zipPath = $this->createZipBackup($backupPath, $backupName);

            // Clean up individual files if zip was created successfully
            if ($zipPath) {
                $this->removeDirectory($backupPath);
                Log::info("Update backup created successfully: {$zipPath}");
                return $zipPath;
            }

            Log::info("Update backup created successfully: {$backupPath}");
            return $backupPath;

        } catch (Exception $e) {
            Log::error("Failed to create update backup: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a zip archive of the backup.
     */
    protected function createZipBackup(string $backupPath, string $backupName): ?string
    {
        if (!class_exists('ZipArchive')) {
            Log::warning('ZipArchive not available, keeping backup as directory');
            return null;
        }

        $zipPath = $this->backupDirectory . "/{$backupName}.zip";
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            Log::warning("Failed to create zip backup: {$zipPath}");
            return null;
        }

        $this->addDirectoryToZip($zip, $backupPath, '');
        $zip->close();

        return $zipPath;
    }

    /**
     * Recursively add directory contents to zip.
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $zipPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . substr($filePath, strlen($sourcePath) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Create backup manifest with metadata.
     */
    protected function createBackupManifest(string $backupPath, array $backedUpFiles, array $failedFiles): void
    {
        $manifest = [
            'created_at' => Carbon::now()->toISOString(),
            'panel_version' => config('app.version'),
            'backup_type' => 'update',
            'total_files' => count($backedUpFiles),
            'failed_files' => count($failedFiles),
            'files' => $backedUpFiles,
            'failed' => $failedFiles,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        file_put_contents(
            $backupPath . '/backup_manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Restore from backup.
     */
    public function restoreBackup(string $backupPath): array
    {
        try {
            $isZip = pathinfo($backupPath, PATHINFO_EXTENSION) === 'zip';
            
            if ($isZip) {
                $extractPath = $this->extractZipBackup($backupPath);
                if (!$extractPath) {
                    return [
                        'success' => false,
                        'message' => 'Failed to extract backup archive'
                    ];
                }
                $backupPath = $extractPath;
            }

            $manifestPath = $backupPath . '/backup_manifest.json';
            
            if (!file_exists($manifestPath)) {
                return [
                    'success' => false,
                    'message' => 'Backup manifest not found'
                ];
            }

            $manifest = json_decode(file_get_contents($manifestPath), true);
            $restoredFiles = [];
            $failedFiles = [];

            foreach ($manifest['files'] as $filePath) {
                $backupFile = $backupPath . '/' . $filePath;
                $targetFile = base_path($filePath);

                if (!file_exists($backupFile)) {
                    $failedFiles[] = $filePath;
                    continue;
                }

                // Ensure target directory exists
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                    $failedFiles[] = $filePath;
                    continue;
                }

                if (copy($backupFile, $targetFile)) {
                    $restoredFiles[] = $filePath;
                } else {
                    $failedFiles[] = $filePath;
                }
            }

            // Clean up extracted backup if it was a zip
            if ($isZip && isset($extractPath)) {
                $this->removeDirectory($extractPath);
            }

            return [
                'success' => empty($failedFiles),
                'message' => empty($failedFiles) 
                    ? 'Backup restored successfully'
                    : 'Backup restored with some errors',
                'restored_files' => count($restoredFiles),
                'failed_files' => count($failedFiles),
                'backup_info' => $manifest,
            ];

        } catch (Exception $e) {
            Log::error("Failed to restore backup: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to restore backup: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract zip backup to temporary directory.
     */
    protected function extractZipBackup(string $zipPath): ?string
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }

        $zip = new ZipArchive();
        
        if ($zip->open($zipPath) !== TRUE) {
            return null;
        }

        $extractPath = $this->backupDirectory . '/temp_' . uniqid();
        
        if (!mkdir($extractPath, 0755, true)) {
            $zip->close();
            return null;
        }

        $zip->extractTo($extractPath);
        $zip->close();

        return $extractPath;
    }

    /**
     * List available backups.
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupDirectory . '/raptor_update_backup_*');

        foreach ($files as $file) {
            $filename = basename($file);
            $isZip = pathinfo($file, PATHINFO_EXTENSION) === 'zip';
            
            // Extract timestamp from filename
            if (preg_match('/(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches)) {
                $timestamp = $matches[1];
                $date = Carbon::createFromFormat('Y-m-d_H-i-s', $timestamp);
                
                $backups[] = [
                    'path' => $file,
                    'name' => $filename,
                    'date' => $date->toISOString(),
                    'date_human' => $date->diffForHumans(),
                    'size' => $this->formatFileSize(is_file($file) ? filesize($file) : $this->getDirectorySize($file)),
                    'type' => $isZip ? 'zip' : 'directory',
                ];
            }
        }

        // Sort by date, newest first
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    /**
     * Clean up old backups.
     */
    public function cleanupOldBackups(int $keepCount = 5): int
    {
        $backups = $this->listBackups();
        $removedCount = 0;

        if (count($backups) <= $keepCount) {
            return 0;
        }

        $toRemove = array_slice($backups, $keepCount);

        foreach ($toRemove as $backup) {
            try {
                if ($backup['type'] === 'zip') {
                    unlink($backup['path']);
                } else {
                    $this->removeDirectory($backup['path']);
                }
                $removedCount++;
            } catch (Exception $e) {
                Log::warning("Failed to remove old backup: " . $e->getMessage());
            }
        }

        return $removedCount;
    }

    /**
     * Ensure backup directory exists.
     */
    protected function ensureBackupDirectoryExists(): void
    {
        if (!is_dir($this->backupDirectory)) {
            mkdir($this->backupDirectory, 0755, true);
        }
    }

    /**
     * Remove directory recursively.
     */
    protected function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($dir);
    }

    /**
     * Get directory size recursively.
     */
    protected function getDirectorySize(string $dir): int
    {
        $size = 0;
        
        if (is_dir($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $size;
    }

    /**
     * Format file size in human readable format.
     */
    protected function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}