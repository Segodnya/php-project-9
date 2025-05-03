<?php

namespace App\Models;

abstract class AbstractModel
{
    /**
     * Create a model instance from an array of data
     *
     * @param array $data
     * @return static
     */
    abstract public static function fromArray(array $data): static;

    /**
     * Convert the model to an array
     *
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * Validate model data
     *
     * @return bool
     */
    abstract public function validate(): bool;

    /**
     * Get validation errors
     *
     * @return array
     */
    abstract public function getValidationErrors(): array;
}