<?php

namespace Pterodactyl\Exceptions\Updates;

use Exception;
use Throwable;

class FileOperationException extends Exception
{
    protected $context;
    
    public function __construct(string $message, $context = null, $codeOrPrevious = 0, ?Throwable $previous = null)
    {
        $this->context = $context;
        
        // Handle flexible constructor parameters
        if ($codeOrPrevious instanceof Throwable) {
            // Called as (message, context, previous)
            parent::__construct($message, 0, $codeOrPrevious);
        } else {
            // Called as (message, context, code, previous)
            parent::__construct($message, (int)$codeOrPrevious, $previous);
        }
    }
    
    public function getContext()
    {
        return $this->context;
    }
}
