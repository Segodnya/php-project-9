<?php

namespace App\Exceptions;

/**
 * Exception for 400 Bad Request errors
 */
class BadRequestException extends HttpException
{
    public function __construct(string $message = "Bad request", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 400, $code, $previous);
    }
} 