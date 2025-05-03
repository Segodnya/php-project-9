<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    private array $errors = [];
    private ?string $template = null;

    public function __construct(array $errors, string $message = "Validation failed", int $code = 422, ?string $template = null)
    {
        $this->errors = $errors;
        $this->template = $template;
        parent::__construct($message, $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }
} 