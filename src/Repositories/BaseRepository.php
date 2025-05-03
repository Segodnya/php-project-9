<?php

namespace App\Repositories;

// Import our PDO wrapper class
use App\PDO;

abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $pdo;
    protected string $table;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function findById(int $id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        
        $fields = array_map(function ($field) {
            return "{$field} = :{$field}";
        }, array_keys($data));
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($data);
    }
    
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
    
    abstract public function create(array $data): int;
} 