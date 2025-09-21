<?php

namespace Pterodactyl\Exceptions\Updates;

use Exception;

/**
 * Base Update Exception
 * 
 * Base exception class for all update-related errors providing
 * common functionality for context handling and error categorization.
 */
class UpdateException extends Exception
{
    /**
     * @var string
     */
    protected string $context = '';

    /**
     * @var array
     */
    protected array $metadata = [];

    public function __construct(
        string $message = '', 
        string $context = '', 
        int $code = 0, 
        \Throwable $previous = null,
        array $metadata = []
    ) {
        $this->context = $context;
        $this->metadata = $metadata;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the error context.
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Set the error context.
     *
     * @param string $context
     * @return $this
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get metadata associated with the exception.
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata for the exception.
     *
     * @param array $metadata
     * @return $this
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Add a metadata key-value pair.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get a formatted error message including context.
     *
     * @return string
     */
    public function getFullMessage(): string
    {
        $message = $this->getMessage();
        
        if (!empty($this->context)) {
            $message = "[{$this->context}] {$message}";
        }
        
        return $message;
    }

    /**
     * Convert the exception to an array for logging or API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'metadata' => $this->metadata,
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * Get a summary of the exception suitable for user display.
     *
     * @return array
     */
    public function getSummary(): array
    {
        return [
            'message' => $this->getMessage(),
            'context' => $this->context,
            'code' => $this->getCode(),
            'metadata' => $this->metadata,
        ];
    }
}