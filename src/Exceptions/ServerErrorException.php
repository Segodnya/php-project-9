<?php

namespace App\Exceptions;

/**
 * Exception for 500 Internal Server Error
 */
class ServerErrorException extends HttpException
{
    public function __construct(string $message = "Internal server error", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 500, $code, $previous);
    }
} 