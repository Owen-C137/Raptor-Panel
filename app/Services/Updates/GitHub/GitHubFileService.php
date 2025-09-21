<?php

namespace Pterodactyl\Services\Updates\GitHub;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Pterodactyl\Exceptions\Updates\FileOperationException;
use Pterodactyl\Exceptions\Updates\GitHubApiException;
use Pterodactyl\Services\Updates\BaseUpdateService;
use ZipArchive;

/**
 * GitHub File Service
 * 
 * Handles downloading files and archives from GitHub,
 * including integrity verification and extraction.
 */
class GitHubFileService extends BaseUpdateService
{
    private Client $httpClient;
    private array $config;

    public function __construct()
    {
        $this->config = $this->getGitHubConfig();
        $this->httpClient = new Client([
            'timeout' => 300, // 5 minutes for large downloads
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Raptor-Panel-Updater/1.0'
            ]
        ]);
    }

    public function getServiceName(): string
    {
        return 'GitHub File Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = $this->validateRequiredConfig($this->config, [
            'owner', 'repo', 'api_base'
        ]);

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            $errors[] = 'ZipArchive extension is not available';
        }

        // Check if temp directory is writable
        $tempDir = $this->getTempDirectory();
        if (!is_writable($tempDir)) {
            $errors[] = "Temp directory is not writable: {$tempDir}";
        }

        return $errors;
    }

    /**
     * Download a release archive from GitHub.
     */
    public function downloadReleaseArchive(string $downloadUrl, string $version): array
    {
        try {
            $this->logInfo('Starting release archive download', [
                'url' => $downloadUrl,
                'version' => $version
            ]);

            $tempDir = $this->getTempDirectory();
            $filename = "raptor-panel-{$version}.zip";
            $filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;

            // Ensure temp directory exists
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Download the archive
            $this->logInfo('Downloading archive', ['path' => $filePath]);
            
            $response = $this->httpClient->get($downloadUrl, [
                'sink' => $filePath,
                'progress' => function ($totalBytes, $downloadedBytes) use ($version) {
                    if ($totalBytes > 0) {
                        $percent = round(($downloadedBytes / $totalBytes) * 100, 1);
                        $this->logDebug("Download progress: {$percent}%", [
                            'version' => $version,
                            'downloaded' => $downloadedBytes,
                            'total' => $totalBytes
                        ]);
                    }
                }
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new GitHubApiException("Download failed with status: {$response->getStatusCode()}");
            }

            // Verify file was downloaded
            if (!file_exists($filePath)) {
                throw new FileOperationException('Archive file was not created', $filePath);
            }

            $fileSize = filesize($filePath);
            $checksum = hash_file('sha256', $filePath);

            $this->logInfo('Archive downloaded successfully', [
                'path' => $filePath,
                'size' => $fileSize,
                'checksum' => $checksum
            ]);

            return [
                'path' => $filePath,
                'filename' => $filename,
                'size' => $fileSize,
                'checksum' => $checksum,
                'version' => $version
            ];

        } catch (GuzzleException $e) {
            $this->handleException($e, 'Archive download failed');
            throw new GitHubApiException("Failed to download archive: {$e->getMessage()}", $e->getCode(), $e);
        } catch (\Exception $e) {
            $this->handleException($e, 'Archive download failed');
            throw $e;
        }
    }

    /**
     * Extract a downloaded archive.
     */
    public function extractArchive(string $archivePath, string $version): array
    {
        try {
            $this->logInfo('Starting archive extraction', [
                'archive' => $archivePath,
                'version' => $version
            ]);

            if (!file_exists($archivePath)) {
                throw new FileOperationException('Archive file does not exist', $archivePath);
            }

            $tempDir = $this->getTempDirectory();
            $extractDir = $tempDir . DIRECTORY_SEPARATOR . "extracted-{$version}";

            // Clean extraction directory if it exists
            if (is_dir($extractDir)) {
                $this->removeDirectory($extractDir);
            }

            // Create extraction directory
            if (!mkdir($extractDir, 0755, true)) {
                throw new FileOperationException('Failed to create extraction directory', $extractDir);
            }

            // Extract archive
            $zip = new ZipArchive();
            $result = $zip->open($archivePath);

            if ($result !== TRUE) {
                throw new FileOperationException("Failed to open archive: " . $this->getZipError($result), $archivePath);
            }

            $this->logInfo('Extracting archive contents');
            
            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new FileOperationException('Failed to extract archive contents', $archivePath);
            }

            $numFiles = $zip->numFiles;
            $zip->close();

            // GitHub archives typically have a single top-level directory
            // Let's find the actual content directory
            $contents = scandir($extractDir);
            $contentDir = null;

            foreach ($contents as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($extractDir . DIRECTORY_SEPARATOR . $item)) {
                    $contentDir = $extractDir . DIRECTORY_SEPARATOR . $item;
                    break;
                }
            }

            if (!$contentDir) {
                throw new FileOperationException('No content directory found in extracted archive', $extractDir);
            }

            $this->logInfo('Archive extracted successfully', [
                'extract_dir' => $extractDir,
                'content_dir' => $contentDir,
                'files_extracted' => $numFiles
            ]);

            return [
                'extract_dir' => $extractDir,
                'content_dir' => $contentDir,
                'files_extracted' => $numFiles,
                'version' => $version
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Archive extraction failed');
            throw new FileOperationException("Failed to extract archive: {$e->getMessage()}", $archivePath, $e);
        }
    }

    /**
     * Compare directory contents to find file changes.
     */
    public function compareDirectories(string $currentDir, string $newDir): array
    {
        try {
            $this->logInfo('Comparing directory contents', [
                'current' => $currentDir,
                'new' => $newDir
            ]);

            $changes = [
                'added' => [],
                'modified' => [],
                'deleted' => []
            ];

            $currentFiles = $this->getFileList($currentDir);
            $newFiles = $this->getFileList($newDir);

            // Find added and modified files
            foreach ($newFiles as $relativePath => $newFile) {
                if (!isset($currentFiles[$relativePath])) {
                    // File is new
                    $changes['added'][] = [
                        'path' => $relativePath,
                        'size' => $newFile['size'],
                        'checksum' => $newFile['checksum'],
                        'full_path' => $newFile['full_path']
                    ];
                } else {
                    // File exists, check if modified
                    $currentFile = $currentFiles[$relativePath];
                    
                    if ($newFile['checksum'] !== $currentFile['checksum']) {
                        $changes['modified'][] = [
                            'path' => $relativePath,
                            'old_size' => $currentFile['size'],
                            'new_size' => $newFile['size'],
                            'old_checksum' => $currentFile['checksum'],
                            'new_checksum' => $newFile['checksum'],
                            'full_path' => $newFile['full_path']
                        ];
                    }
                }
            }

            // Find deleted files
            foreach ($currentFiles as $relativePath => $currentFile) {
                if (!isset($newFiles[$relativePath])) {
                    $changes['deleted'][] = [
                        'path' => $relativePath,
                        'size' => $currentFile['size'],
                        'checksum' => $currentFile['checksum'],
                        'full_path' => $currentFile['full_path']
                    ];
                }
            }

            $totalChanges = count($changes['added']) + count($changes['modified']) + count($changes['deleted']);
            
            $this->logInfo('Directory comparison complete', [
                'added' => count($changes['added']),
                'modified' => count($changes['modified']),
                'deleted' => count($changes['deleted']),
                'total_changes' => $totalChanges
            ]);

            return $changes;

        } catch (\Exception $e) {
            $this->handleException($e, 'Directory comparison failed');
            throw new FileOperationException("Failed to compare directories: {$e->getMessage()}", $currentDir, $e);
        }
    }

    /**
     * Clean up temporary files and directories.
     */
    public function cleanupTempFiles(array $paths): void
    {
        foreach ($paths as $path) {
            try {
                if (is_file($path)) {
                    unlink($path);
                    $this->logDebug('Cleaned up file', ['path' => $path]);
                } elseif (is_dir($path)) {
                    $this->removeDirectory($path);
                    $this->logDebug('Cleaned up directory', ['path' => $path]);
                }
            } catch (\Exception $e) {
                $this->logWarning('Failed to cleanup temp file', [
                    'path' => $path,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Verify archive integrity using checksum.
     */
    public function verifyArchiveIntegrity(string $archivePath, ?string $expectedChecksum = null): bool
    {
        try {
            if (!file_exists($archivePath)) {
                return false;
            }

            $actualChecksum = hash_file('sha256', $archivePath);
            
            if ($expectedChecksum) {
                $isValid = $actualChecksum === $expectedChecksum;
                $this->logInfo('Archive integrity check', [
                    'path' => $archivePath,
                    'expected' => $expectedChecksum,
                    'actual' => $actualChecksum,
                    'valid' => $isValid
                ]);
                return $isValid;
            }

            // If no expected checksum, just verify file is readable
            return $actualChecksum !== false;

        } catch (\Exception $e) {
            $this->logError('Archive integrity check failed', [
                'path' => $archivePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get recursive file list with checksums.
     */
    private function getFileList(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fullPath = $file->getPathname();
                $relativePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $fullPath);
                $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
                
                $files[$relativePath] = [
                    'full_path' => $fullPath,
                    'size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $fullPath)
                ];
            }
        }

        return $files;
    }

    /**
     * Recursively remove directory and its contents.
     */
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

    /**
     * Get human-readable ZIP error message.
     */
    private function getZipError(int $code): string
    {
        return match ($code) {
            ZipArchive::ER_OK => 'No error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_REMOVE => 'Can\'t remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted',
            default => "Unknown error code: {$code}"
        };
    }
}