<?php

namespace Pterodactyl\Exceptions\Updates;

use Pterodactyl\Exceptions\PterodactylException;

/**
 * DatabaseOperationException is thrown when database operations
 * in the update system fail or encounter errors.
 */
class DatabaseOperationException extends PterodactylException
{
    /**
     * Create a new database operation exception instance.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}