<?php

namespace App\Exceptions;

use Exception;

class HttpRequestException extends Exception
{
    private ?int $statusCode = null;
    private string $url = '';

    public function __construct(
        string $message = "HTTP request failed",
        int $code = 0,
        ?int $statusCode = null,
        string $url = '',
        Exception $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->url = $url;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
} 