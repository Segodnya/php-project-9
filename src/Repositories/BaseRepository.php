<?php

namespace App\Repositories;

use App\Models\AbstractModel;
use App\PDO;
use InvalidArgumentException;

/**
 * Base repository implementation providing common CRUD operations
 */
abstract class BaseRepository implements RepositoryInterface
{
    protected PDO $pdo;
    protected string $table;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Find an entity by its ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find all entities
     *
     * @return array
     */
    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update an existing entity
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
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
    
    /**
     * Delete an entity
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Create a new entity from an entity object
     *
     * @param \App\Models\AbstractModel $entity
     * @return int The newly created entity ID
     * @throws InvalidArgumentException If entity validation fails
     */
    public function createFromEntity(\App\Models\AbstractModel $entity): int
    {
        if (!$entity->validate()) {
            throw new InvalidArgumentException(
                'Entity validation failed: ' . json_encode($entity->getValidationErrors())
            );
        }
        
        return $this->create($entity->toArray());
    }
    
    /**
     * Create a new entity from data
     *
     * @param array $data
     * @return int The newly created entity ID
     */
    abstract public function create(array $data): int;
    
    /**
     * Find an entity by ID and return as an entity object
     *
     * @param int $id
     * @return \App\Models\AbstractModel|null
     */
    abstract public function findByIdAsEntity(int $id): ?\App\Models\AbstractModel;
    
    /**
     * Find all entities and return as entity objects
     * 
     * @return \App\Models\AbstractModel[]
     */
    abstract public function findAllAsEntities(): array;
} 