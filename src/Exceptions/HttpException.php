<?php

namespace App\Exceptions;

use Exception;

/**
 * Base class for HTTP exceptions
 */
class HttpException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message = "HTTP Error", int $statusCode = 500, int $code = 0, \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code for this exception
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}