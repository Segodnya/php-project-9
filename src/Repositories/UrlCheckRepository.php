<?php

namespace App\Repositories;

use App\Models\AbstractModel;
use App\Models\UrlCheck;
use InvalidArgumentException;

class UrlCheckRepository extends BaseRepository
{
    protected string $table = 'url_checks';
    
    /**
     * Create a new URL check record from array data
     *
     * @param array $data
     * @return int The newly created URL check ID
     * @throws InvalidArgumentException If required data is missing
     */
    public function create(array $data): int
    {
        // Handle legacy format where parameters are passed directly
        if (!isset($data['url_id'])) {
            if (func_num_args() >= 5) {
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
            } else {
                throw new InvalidArgumentException('URL ID is required');
            }
        }
        
        // Validate required fields
        if (!isset($data['url_id']) || !isset($data['status_code'])) {
            throw new InvalidArgumentException('URL ID and status code are required');
        }
        
        $sql = "INSERT INTO {$this->table} (url_id, status_code, h1, title, description) VALUES (:url_id, :status_code, :h1, :title, :description) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $data['url_id'],
            'status_code' => $data['status_code'],
            'h1' => $data['h1'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null
        ]);
        
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Find URL checks by URL ID
     *
     * @param int $urlId
     * @return array
     */
    public function findByUrlId(int $urlId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE url_id = :url_id ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Find the latest URL check by URL ID
     *
     * @param int $urlId
     * @return array|null
     */
    public function findLatestByUrlId(int $urlId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE url_id = :url_id ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['url_id' => $urlId]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find the latest URL check for a URL ID and return as an entity
     *
     * @param int $urlId
     * @return UrlCheck|null
     */
    public function findLatestByUrlIdAsEntity(int $urlId): ?UrlCheck
    {
        $data = $this->findLatestByUrlId($urlId);
        
        if (!$data) {
            return null;
        }
        
        return UrlCheck::fromArray($data);
    }
    
    /**
     * Find a URL check by ID and return as an entity
     *
     * @param int $id
     * @return \App\Models\AbstractModel|null
     */
    public function findByIdAsEntity(int $id): ?\App\Models\AbstractModel
    {
        $data = $this->findById($id);
        
        if (!$data) {
            return null;
        }
        
        return UrlCheck::fromArray($data);
    }
    
    /**
     * Find all URL checks for a URL ID and return as entities
     *
     * @param int $urlId
     * @return UrlCheck[]
     */
    public function findByUrlIdAsEntities(int $urlId): array
    {
        $data = $this->findByUrlId($urlId);
        $checks = [];
        
        foreach ($data as $checkData) {
            $checks[] = UrlCheck::fromArray($checkData);
        }
        
        return $checks;
    }
    
    /**
     * Find all URL checks and return as entities
     *
     * @return UrlCheck[]
     */
    public function findAllAsEntities(): array
    {
        $data = $this->findAll();
        $checks = [];
        
        foreach ($data as $checkData) {
            $checks[] = UrlCheck::fromArray($checkData);
        }
        
        return $checks;
    }
} 