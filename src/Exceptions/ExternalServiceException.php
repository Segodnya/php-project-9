<?php

namespace App\Exceptions;

use App\Exceptions\HttpException;

/**
 * Exception for errors related to external service calls
 */
class ExternalServiceException extends HttpException
{
    private string $serviceName;

    public function __construct(
        string $message = 'External service error',
        string $serviceName = 'unknown',
        int $statusCode = 502,
        int $code = 0,
        \Throwable $previous = null
    ) {
        $this->serviceName = $serviceName;
        parent::__construct($message, $statusCode, $code, $previous);
    }

    /**
     * Get the name of the external service that failed
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}