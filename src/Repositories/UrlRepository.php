<?php

namespace App\Repositories;

use App\Models\Url;

class UrlRepository extends BaseRepository
{
    protected string $table = 'urls';
    
    public function create(array $data): int
    {
        // Expecting an array with 'name' key
        $name = $data['name'] ?? '';
        
        $sql = "INSERT INTO {$this->table} (name) VALUES (:name) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function findByName(string $name)
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);
        
        return $stmt->fetch();
    }
    
    // Method that returns Url entity instead of array
    public function findByIdAsEntity(int $id): ?Url
    {
        $data = $this->findById($id);
        
        if (!$data) {
            return null;
        }
        
        return Url::fromArray($data);
    }
    
    // Method that returns all URLs as Url entities
    public function findAllAsEntities(): array
    {
        $data = $this->findAll();
        $urls = [];
        
        foreach ($data as $urlData) {
            $urls[] = Url::fromArray($urlData);
        }
        
        return $urls;
    }
} 