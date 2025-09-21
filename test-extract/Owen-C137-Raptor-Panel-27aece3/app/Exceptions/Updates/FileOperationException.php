<?php

namespace Pterodactyl\Exceptions\Updates;

use Throwable;

/**
 * File Operation Exception
 */
class FileOperationException extends UpdateException
{
    protected string $filePath;

    public function __construct(string $message = '', string $filePath = '', int $code = 0, Throwable $previous = null)
    {
        $this->filePath = $filePath;
        $context = $filePath ? "File Operation ({$filePath})" : 'File Operation';
        parent::__construct($message, $context, $code, $previous);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}