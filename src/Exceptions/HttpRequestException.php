<?php

namespace App\Exceptions;

/**
 * Exception for HTTP client request errors
 */
class HttpRequestException extends HttpException
{
    private ?string $url = null;

    public function __construct(
        string $message = "HTTP request failed",
        ?int $code = 0,
        ?int $statusCode = null,
        ?string $url = null,
        ?\Throwable $previous = null
    ) {
        $this->url = $url;
        parent::__construct($message, $statusCode ?? 500, $code ?? 0, $previous);
    }

    /**
     * Get the URL that caused the exception
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}