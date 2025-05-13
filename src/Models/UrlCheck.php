<?php

/**
 * UrlCheck Model
 *
 * Represents a URL check entity
 * PHP version 8.0
 *
 * @category Model
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Models;

/**
 * UrlCheck class
 */
class UrlCheck
{
    /**
     * @var int|null
     */
    private ?int $id = null;

    /**
     * @var int
     */
    private int $urlId;

    /**
     * @var int|null
     */
    private ?int $statusCode;

    /**
     * @var string|null
     */
    private ?string $h1;

    /**
     * @var string|null
     */
    private ?string $title;

    /**
     * @var string|null
     */
    private ?string $description;

    /**
     * @var string|null
     */
    private ?string $createdAt = null;

    /**
     * Constructor
     *
     * @param int         $urlId       URL ID
     * @param int|null    $statusCode  HTTP status code
     * @param string|null $h1          H1 tag content
     * @param string|null $title       Title tag content
     * @param string|null $description Meta description content
     * @param int|null    $id          Check ID
     * @param string|null $createdAt   Created timestamp
     */
    public function __construct(
        int $urlId,
        ?int $statusCode = null,
        ?string $h1 = null,
        ?string $title = null,
        ?string $description = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->urlId = $urlId;
        $this->statusCode = $statusCode;
        $this->h1 = $h1;
        $this->title = $title;
        $this->description = $description;
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
            (int) $row['url_id'],
            isset($row['status_code']) ? (int) $row['status_code'] : null,
            $row['h1'] ?? null,
            $row['title'] ?? null,
            $row['description'] ?? null,
            isset($row['id']) ? (int) $row['id'] : null,
            $row['created_at'] ?? null
        );
    }

    /**
     * Get check ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set check ID
     *
     * @param int $id Check ID
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get URL ID
     *
     * @return int
     */
    public function getUrlId(): int
    {
        return $this->urlId;
    }

    /**
     * Set URL ID
     *
     * @param int $urlId URL ID
     * @return void
     */
    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    /**
     * Get HTTP status code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Set HTTP status code
     *
     * @param int $statusCode HTTP status code
     * @return void
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Get H1 tag content
     *
     * @return string|null
     */
    public function getH1(): ?string
    {
        return $this->h1;
    }

    /**
     * Set H1 tag content
     *
     * @param string|null $h1 H1 tag content
     * @return void
     */
    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    /**
     * Get title tag content
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set title tag content
     *
     * @param string|null $title Title tag content
     * @return void
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Get meta description content
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set meta description content
     *
     * @param string|null $description Meta description content
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
            'url_id' => $this->urlId,
            'status_code' => $this->statusCode,
            'h1' => $this->h1,
            'title' => $this->title,
            'description' => $this->description,
            'created_at' => $this->createdAt
        ];
    }
}
