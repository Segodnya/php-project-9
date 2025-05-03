<?php

namespace App\Exceptions;

/**
 * Exception for 404 Not Found errors
 */
class NotFoundException extends HttpException
{
    public function __construct(string $message = "Resource not found", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 404, $code, $previous);
    }
} 