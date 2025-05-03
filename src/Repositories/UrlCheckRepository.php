<?php

namespace App\Repositories;

use App\Models\UrlCheck;

class UrlCheckRepository extends BaseRepository
{
    protected string $table = 'url_checks';
    
    public function create(array $data): int
    {
        // Handle legacy format where parameters are passed directly
        if (!is_array($data) || !isset($data['url_id'])) {
            $urlId = func_get_arg(0);
            $statusCode = func_get_arg(1);
            $h1 = func_get_arg(2);
            $title = func_get_arg(3);
            $description = func_get_arg(4);
            
            $data = [
                'url_id' => $urlId,
                'status_code' => $statusCode,
                'h1' => $h1,
                'title' => $title,
                'description' => $description
            ];
        }
        
        $sql = "INSERT INTO {$this->table} (url_id, status_code, h1, title, description) VALUES (:url_id, :status_code, :h1, :title, :description) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $data['url_id'],
            'status_code' => $data['status_code'],
            'h1' => $data['h1'],
            'title' => $data['title'],
            'description' => $data['description']
        ]);
        
        return (int) $stmt->fetchColumn();
    }
    
    public function findByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE url_id = :url_id ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        
        return $stmt->fetchAll();
    }
    
    public function findLatestByUrlId(int $urlId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE url_id = :url_id ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        
        return $stmt->fetch();
    }
    
    // Method that returns UrlCheck entity instead of array
    public function findByIdAsEntity(int $id): ?UrlCheck
    {
        $data = $this->findById($id);
        
        if (!$data) {
            return null;
        }
        
        return UrlCheck::fromArray($data);
    }
    
    // Method that returns all checks for a URL as UrlCheck entities
    public function findByUrlIdAsEntities(int $urlId): array
    {
        $data = $this->findByUrlId($urlId);
        $checks = [];
        
        foreach ($data as $checkData) {
            $checks[] = UrlCheck::fromArray($checkData);
        }
        
        return $checks;
    }
} 