<?php

namespace Pterodactyl\Services\Updates\Files;

use Pterodactyl\Exceptions\Updates\FileOperationException;
use Pterodactyl\Services\Updates\BaseUpdateService;
use ZipArchive;

/**
 * Archive Service
 * 
 * Handles creation, extraction, and management of archive files
 * for update packages and backups.
 */
class ArchiveService extends BaseUpdateService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'temp_directory' => $this->getTempDirectory(),
            'max_archive_size' => config('pterodactyl.updates.max_archive_size', '500M'),
            'allowed_formats' => ['zip', 'tar.gz'],
            'extraction_timeout' => config('pterodactyl.updates.extraction_timeout', 300),
        ];
    }

    public function getServiceName(): string
    {
        return 'Archive Service';
    }

    public function getConfigurationErrors(): array
    {
        $errors = [];

        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            $errors[] = 'ZipArchive extension is not available';
        }

        // Check temp directory
        $tempDir = $this->getTempDirectory();
        if (!is_writable($tempDir)) {
            $errors[] = "Temp directory is not writable: {$tempDir}";
        }

        return $errors;
    }

    /**
     * Create a ZIP archive from directory.
     */
    public function createArchive(string $sourceDir, string $archivePath, array $options = []): array
    {
        try {
            $this->logInfo('Creating archive', [
                'source' => $sourceDir,
                'archive' => $archivePath,
                'options' => $options
            ]);

            $startTime = microtime(true);
            
            if (!is_dir($sourceDir)) {
                throw new FileOperationException("Source directory does not exist: {$sourceDir}");
            }

            // Create archive directory if needed
            $archiveDir = dirname($archivePath);
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            if ($result !== TRUE) {
                throw new FileOperationException("Failed to create archive: " . $this->getZipError($result));
            }

            // Get files to include
            $files = $this->getFilesToArchive($sourceDir, $options);
            $fileCount = 0;
            $totalSize = 0;

            foreach ($files as $file) {
                $relativePath = $file['relative_path'];
                $fullPath = $file['full_path'];

                if (is_file($fullPath)) {
                    $zip->addFile($fullPath, $relativePath);
                    $fileCount++;
                    $totalSize += $file['size'];
                } elseif (is_dir($fullPath) && ($options['include_empty_dirs'] ?? true)) {
                    $zip->addEmptyDir($relativePath);
                }
            }

            // Add comment if provided
            if (!empty($options['comment'])) {
                $zip->setArchiveComment($options['comment']);
            }

            // Set compression level
            $compressionLevel = $options['compression_level'] ?? ZipArchive::CM_DEFAULT;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $zip->setCompressionIndex($i, $compressionLevel);
            }

            $zip->close();

            $executionTime = microtime(true) - $startTime;
            $archiveSize = file_exists($archivePath) ? filesize($archivePath) : 0;
            
            $this->logInfo('Archive created successfully', [
                'archive' => $archivePath,
                'files' => $fileCount,
                'source_size' => $this->formatBytes($totalSize),
                'archive_size' => $this->formatBytes($archiveSize),
                'compression_ratio' => $totalSize > 0 ? round((1 - $archiveSize / $totalSize) * 100, 1) . '%' : '0%',
                'execution_time' => round($executionTime, 2) . 's'
            ]);

            return [
                'archive_path' => $archivePath,
                'files_count' => $fileCount,
                'source_size' => $totalSize,
                'archive_size' => $archiveSize,
                'compression_ratio' => $totalSize > 0 ? round((1 - $archiveSize / $totalSize) * 100, 1) : 0,
                'execution_time' => $executionTime,
                'checksum' => hash_file('sha256', $archivePath)
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Archive creation failed');
            throw new FileOperationException('Failed to create archive: ' . $e->getMessage(), $archivePath, 0, $e);
        }
    }

    /**
     * Extract archive to destination directory.
     */
    public function extractArchive(string $archivePath, string $destinationDir, array $options = []): array
    {
        try {
            $this->logInfo('Extracting archive', [
                'archive' => $archivePath,
                'destination' => $destinationDir,
                'options' => $options
            ]);

            $startTime = microtime(true);

            if (!file_exists($archivePath)) {
                throw new FileOperationException("Archive file does not exist: {$archivePath}");
            }

            // Create destination directory if needed
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath);

            if ($result !== TRUE) {
                throw new FileOperationException("Failed to open archive: " . $this->getZipError($result));
            }

            $fileCount = $zip->numFiles;
            $extractedFiles = [];

            // Check if we should extract specific files only
            $filesToExtract = $options['files'] ?? null;
            
            if ($filesToExtract) {
                // Extract specific files
                foreach ($filesToExtract as $file) {
                    if ($zip->extractTo($destinationDir, $file)) {
                        $extractedFiles[] = $file;
                    } else {
                        $this->logWarning('Failed to extract file', ['file' => $file]);
                    }
                }
            } else {
                // Extract all files
                if (!$zip->extractTo($destinationDir)) {
                    $zip->close();
                    throw new FileOperationException('Failed to extract archive contents');
                }
                
                // List all extracted files
                for ($i = 0; $i < $fileCount; $i++) {
                    $extractedFiles[] = $zip->getNameIndex($i);
                }
            }

            $zip->close();

            $executionTime = microtime(true) - $startTime;

            // Calculate total size of extracted files
            $totalSize = 0;
            foreach ($extractedFiles as $file) {
                $filePath = $destinationDir . DIRECTORY_SEPARATOR . $file;
                if (is_file($filePath)) {
                    $totalSize += filesize($filePath);
                }
            }

            $this->logInfo('Archive extracted successfully', [
                'archive' => $archivePath,
                'destination' => $destinationDir,
                'files_extracted' => count($extractedFiles),
                'total_size' => $this->formatBytes($totalSize),
                'execution_time' => round($executionTime, 2) . 's'
            ]);

            return [
                'extracted_files' => $extractedFiles,
                'files_count' => count($extractedFiles),
                'total_size' => $totalSize,
                'execution_time' => $executionTime,
                'destination_dir' => $destinationDir
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Archive extraction failed');
            throw new FileOperationException('Failed to extract archive: ' . $e->getMessage(), $archivePath, 0, $e);
        }
    }

    /**
     * List contents of an archive.
     */
    public function listArchiveContents(string $archivePath): array
    {
        try {
            $this->logInfo('Listing archive contents', ['archive' => $archivePath]);

            if (!file_exists($archivePath)) {
                throw new FileOperationException("Archive file does not exist: {$archivePath}");
            }

            $zip = new ZipArchive();
            $result = $zip->open($archivePath);

            if ($result !== TRUE) {
                throw new FileOperationException("Failed to open archive: " . $this->getZipError($result));
            }

            $contents = [];
            $totalSize = 0;
            $compressedSize = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                
                if ($stat !== false) {
                    $contents[] = [
                        'name' => $stat['name'],
                        'size' => $stat['size'],
                        'compressed_size' => $stat['comp_size'],
                        'mtime' => $stat['mtime'],
                        'compression_method' => $stat['comp_method'],
                        'is_dir' => substr($stat['name'], -1) === '/'
                    ];

                    $totalSize += $stat['size'];
                    $compressedSize += $stat['comp_size'];
                }
            }

            $archiveInfo = [
                'comment' => $zip->getArchiveComment(),
                'file_count' => $zip->numFiles
            ];

            $zip->close();

            $this->logDebug('Archive contents listed', [
                'files' => count($contents),
                'total_size' => $this->formatBytes($totalSize),
                'compressed_size' => $this->formatBytes($compressedSize)
            ]);

            return [
                'files' => $contents,
                'file_count' => count($contents),
                'total_size' => $totalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => $totalSize > 0 ? round((1 - $compressedSize / $totalSize) * 100, 1) : 0,
                'archive_info' => $archiveInfo
            ];

        } catch (\Exception $e) {
            $this->handleException($e, 'Failed to list archive contents');
            throw new FileOperationException('Failed to list archive contents: ' . $e->getMessage(), $archivePath, 0, $e);
        }
    }

    /**
     * Validate archive integrity.
     */
    public function validateArchive(string $archivePath): array
    {
        try {
            $this->logInfo('Validating archive integrity', ['archive' => $archivePath]);

            if (!file_exists($archivePath)) {
                throw new FileOperationException("Archive file does not exist: {$archivePath}");
            }

            $results = [
                'is_valid' => false,
                'can_open' => false,
                'test_result' => null,
                'file_count' => 0,
                'errors' => []
            ];

            $zip = new ZipArchive();
            $result = $zip->open($archivePath, ZipArchive::CHECKCONS);

            if ($result === TRUE) {
                $results['can_open'] = true;
                $results['file_count'] = $zip->numFiles;

                // Test archive integrity
                $testResult = $zip->testArchive();
                $results['test_result'] = $testResult;
                
                if ($testResult === TRUE) {
                    $results['is_valid'] = true;
                } else {
                    $results['errors'][] = 'Archive integrity test failed';
                }

                $zip->close();
            } else {
                $results['errors'][] = 'Failed to open archive: ' . $this->getZipError($result);
            }

            $this->logInfo('Archive validation completed', [
                'archive' => $archivePath,
                'is_valid' => $results['is_valid'],
                'file_count' => $results['file_count']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->handleException($e, 'Archive validation failed');
            throw new FileOperationException('Failed to validate archive: ' . $e->getMessage(), $archivePath, 0, $e);
        }
    }

    /**
     * Compare two archives.
     */
    public function compareArchives(string $archive1, string $archive2): array
    {
        try {
            $this->logInfo('Comparing archives', [
                'archive1' => $archive1,
                'archive2' => $archive2
            ]);

            $contents1 = $this->listArchiveContents($archive1);
            $contents2 = $this->listArchiveContents($archive2);

            $files1 = collect($contents1['files'])->keyBy('name');
            $files2 = collect($contents2['files'])->keyBy('name');

            $comparison = [
                'identical' => true,
                'differences' => [
                    'added' => [],
                    'removed' => [],
                    'modified' => [],
                    'size_changed' => []
                ],
                'summary' => [
                    'archive1_files' => $files1->count(),
                    'archive2_files' => $files2->count(),
                    'common_files' => 0,
                    'differences_count' => 0
                ]
            ];

            // Find added files (in archive2 but not in archive1)
            foreach ($files2 as $name => $file) {
                if (!$files1->has($name)) {
                    $comparison['differences']['added'][] = $name;
                    $comparison['identical'] = false;
                }
            }

            // Find removed files (in archive1 but not in archive2)
            foreach ($files1 as $name => $file) {
                if (!$files2->has($name)) {
                    $comparison['differences']['removed'][] = $name;
                    $comparison['identical'] = false;
                }
            }

            // Find modified files (common files with different attributes)
            foreach ($files1 as $name => $file1) {
                if ($files2->has($name)) {
                    $comparison['summary']['common_files']++;
                    $file2 = $files2->get($name);
                    
                    // Check if file sizes differ
                    if ($file1['size'] !== $file2['size']) {
                        $comparison['differences']['size_changed'][] = [
                            'name' => $name,
                            'size1' => $file1['size'],
                            'size2' => $file2['size']
                        ];
                        $comparison['identical'] = false;
                    }
                    
                    // Check if modification times differ
                    if ($file1['mtime'] !== $file2['mtime']) {
                        $comparison['differences']['modified'][] = [
                            'name' => $name,
                            'mtime1' => $file1['mtime'],
                            'mtime2' => $file2['mtime']
                        ];
                        $comparison['identical'] = false;
                    }
                }
            }

            $comparison['summary']['differences_count'] = 
                count($comparison['differences']['added']) +
                count($comparison['differences']['removed']) +
                count($comparison['differences']['modified']) +
                count($comparison['differences']['size_changed']);

            $this->logInfo('Archive comparison completed', [
                'identical' => $comparison['identical'],
                'differences' => $comparison['summary']['differences_count']
            ]);

            return $comparison;

        } catch (\Exception $e) {
            $this->handleException($e, 'Archive comparison failed');
            throw new FileOperationException('Failed to compare archives: ' . $e->getMessage(), $archive1, 0, $e);
        }
    }

    /**
     * Get files to include in archive.
     */
    private function getFilesToArchive(string $sourceDir, array $options): array
    {
        $files = [];
        $excludePatterns = $options['exclude'] ?? [];
        $includePatterns = $options['include'] ?? ['*'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize separators

            // Check exclude patterns
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            // Check include patterns
            $shouldInclude = empty($includePatterns);
            foreach ($includePatterns as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    $shouldInclude = true;
                    break;
                }
            }

            if (!$shouldInclude) {
                continue;
            }

            $files[] = [
                'relative_path' => $relativePath,
                'full_path' => $file->getPathname(),
                'size' => $file->isFile() ? $file->getSize() : 0,
                'is_dir' => $file->isDir()
            ];
        }

        return $files;
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

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}