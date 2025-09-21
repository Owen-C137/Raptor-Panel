<?php

namespace Pterodactyl\Exceptions\Updates;

use Exception;

/**
 * Base Update Exception
 * 
 * Base exception class for all update-related errors.
 */
class UpdateException extends Exception
{
    protected string $context = '';

    public function __construct(string $message = '', string $context = '', int $code = 0, \Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getFullMessage(): string
    {
        return $this->context ? "{$this->context}: {$this->getMessage()}" : $this->getMessage();
    }
}

/**
 * GitHub API Exception
 */
class GitHubApiException extends UpdateException
{
    public function __construct(string $message = '', int $statusCode = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 'GitHub API Error', $statusCode, $previous);
    }
}

/**
 * File Operation Exception
 */
class FileOperationException extends UpdateException
{
    protected string $filePath;

    public function __construct(string $message = '', string $filePath = '', \Throwable $previous = null)
    {
        $this->filePath = $filePath;
        $context = $filePath ? "File Operation ({$filePath})" : 'File Operation';
        parent::__construct($message, $context, 0, $previous);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}

/**
 * Backup Operation Exception
 */
class BackupException extends UpdateException
{
    public function __construct(string $message = '', \Throwable $previous = null)
    {
        parent::__construct($message, 'Backup Operation', 0, $previous);
    }
}

/**
 * Migration Exception
 */
class MigrationException extends UpdateException
{
    protected string $migrationFile;

    public function __construct(string $message = '', string $migrationFile = '', \Throwable $previous = null)
    {
        $this->migrationFile = $migrationFile;
        $context = $migrationFile ? "Migration ({$migrationFile})" : 'Migration';
        parent::__construct($message, $context, 0, $previous);
    }

    public function getMigrationFile(): string
    {
        return $this->migrationFile;
    }
}

/**
 * Version Conflict Exception
 */
class VersionConflictException extends UpdateException
{
    public function __construct(string $message = '', \Throwable $previous = null)
    {
        parent::__construct($message, 'Version Conflict', 0, $previous);
    }
}

/**
 * Update Session Exception
 */
class UpdateSessionException extends UpdateException
{
    protected string $sessionId;

    public function __construct(string $message = '', string $sessionId = '', \Throwable $previous = null)
    {
        $this->sessionId = $sessionId;
        $context = $sessionId ? "Update Session ({$sessionId})" : 'Update Session';
        parent::__construct($message, $context, 0, $previous);
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}

/**
 * Validation Exception
 */
class ValidationException extends UpdateException
{
    protected array $errors;

    public function __construct(array $errors = [], string $message = '', \Throwable $previous = null)
    {
        $this->errors = $errors;
        $errorMessage = $message ?: 'Validation failed: ' . implode(', ', $errors);
        parent::__construct($errorMessage, 'Validation', 0, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}