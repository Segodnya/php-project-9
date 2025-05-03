<?php

namespace App\Models;

use DateTime;

class Url extends AbstractModel
{
    private ?int $id = null;
    private string $name;
    private ?string $createdAt = null;
    private array $validationErrors = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getFormattedCreatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$this->createdAt) {
            return null;
        }

        $date = new DateTime($this->createdAt);
        return $date->format($format);
    }

    public static function fromArray(array $data): static
    {
        $url = new static($data['name']);

        if (isset($data['id'])) {
            $url->setId((int) $data['id']);
        }

        if (isset($data['created_at'])) {
            $url->setCreatedAt($data['created_at']);
        }

        return $url;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt
        ];
    }

    /**
     * Validate the URL
     *
     * @return bool
     */
    public function validate(): bool
    {
        $this->validationErrors = [];

        // Check if name is not empty
        if (empty($this->name)) {
            $this->validationErrors['name'] = 'URL cannot be empty';
            return false;
        }

        // Check if name is a valid URL
        if (!filter_var($this->name, FILTER_VALIDATE_URL)) {
            $this->validationErrors['name'] = 'Invalid URL format';
            return false;
        }

        // Check URL length
        if (strlen($this->name) > 255) {
            $this->validationErrors['name'] = 'URL cannot exceed 255 characters';
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}