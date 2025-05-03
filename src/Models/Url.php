<?php

namespace App\Models;

class Url
{
    private ?int $id = null;
    private string $name;
    private ?string $createdAt = null;
    
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
    
    public static function fromArray(array $data): self
    {
        $url = new self($data['name']);
        
        if (isset($data['id'])) {
            $url->setId($data['id']);
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
} 