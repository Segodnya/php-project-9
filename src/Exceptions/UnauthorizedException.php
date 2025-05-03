<?php

namespace App\Exceptions;

use App\Exceptions\HttpException;

/**
 * Exception for unauthorized access attempts
 */
class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized access', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, 401, $code, $previous);
    }
}