<?php

/**
 * Url Model
 *
 * Represents a URL entity
 * PHP version 8.0
 *
 * @category Model
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Models;

/**
 * Url class
 */
class Url
{
    /**
     * @var int|null
     */
    private ?int $id = null;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string|null
     */
    private ?string $createdAt = null;

    /**
     * Constructor
     *
     * @param string    $name      URL name
     * @param int|null  $id        URL ID
     * @param string|null $createdAt Created timestamp
     */
    public function __construct(string $name, ?int $id = null, ?string $createdAt = null)
    {
        $this->name = $name;
        $this->id = $id;
        $this->createdAt = $createdAt;
    }

    /**
     * Create from database row
     *
     * @param array<string, mixed> $row Database row
     * @return self
     */
    public static function fromArray(array $row): self
    {
        return new self(
            (string) $row['name'],
            isset($row['id']) ? (int) $row['id'] : null,
            $row['created_at'] ?? null
        );
    }

    /**
     * Get URL ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set URL ID
     *
     * @param int $id URL ID
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get URL name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set URL name
     *
     * @param string $name URL name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get created timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set created timestamp
     *
     * @param string $createdAt Created timestamp
     * @return void
     */
    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->createdAt
        ];
    }
}
