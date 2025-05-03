<?php

namespace App\Models;

class UrlCheck
{
    private ?int $id = null;
    private int $urlId;
    private int $statusCode;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdAt = null;
    
    public function __construct(
        int $urlId, 
        int $statusCode, 
        ?string $h1 = null, 
        ?string $title = null, 
        ?string $description = null
    ) {
        $this->urlId = $urlId;
        $this->statusCode = $statusCode;
        $this->h1 = $h1;
        $this->title = $title;
        $this->description = $description;
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
    
    public function getUrlId(): int
    {
        return $this->urlId;
    }
    
    public function setUrlId(int $urlId): self
    {
        $this->urlId = $urlId;
        return $this;
    }
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }
    
    public function getH1(): ?string
    {
        return $this->h1;
    }
    
    public function setH1(?string $h1): self
    {
        $this->h1 = $h1;
        return $this;
    }
    
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }
    
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    public function setDescription(?string $description): self
    {
        $this->description = $description;
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
    
    public static function fromArray(array $data): self
    {
        $urlCheck = new self(
            $data['url_id'],
            $data['status_code'],
            $data['h1'] ?? null,
            $data['title'] ?? null,
            $data['description'] ?? null
        );
        
        if (isset($data['id'])) {
            $urlCheck->setId($data['id']);
        }
        
        if (isset($data['created_at'])) {
            $urlCheck->setCreatedAt($data['created_at']);
        }
        
        return $urlCheck;
    }
    
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