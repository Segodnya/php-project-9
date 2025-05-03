<?php

namespace App\Exceptions;

/**
 * Exception for HTTP client request errors
 */
class HttpRequestException extends HttpException
{
    private string $url = '';

    public function __construct(
        string $message = "HTTP request failed",
        ?int $statusCode = null,
        string $url = '',
        int $errorCode = 0,
        \Throwable $previous = null
    ) {
        $this->url = $url;
        parent::__construct($message, $statusCode ?? 500, $errorCode, $previous);
    }

    /**
     * Get the URL that caused the exception
     */
    public function getUrl(): string
    {
        return $this->url;
    }
} 