<?php

namespace App\Exceptions;

use App\Exceptions\HttpException;

/**
 * Exception for forbidden access attempts
 */
class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden access', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 403, $code, $previous);
    }
}