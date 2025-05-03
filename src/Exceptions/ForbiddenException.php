<?php

namespace App\Exceptions;

/**
 * Exception for 403 Forbidden errors
 */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = "Forbidden", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 403, $code, $previous);
    }
} 