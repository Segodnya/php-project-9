<?php

namespace App\Exceptions;

use App\Exceptions\ServerErrorException;

/**
 * Exception for database-related errors
 */
class DatabaseException extends ServerErrorException
{
    public function __construct(string $message = 'Database error occurred', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 