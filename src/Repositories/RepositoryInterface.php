<?php

namespace App\Repositories;

use App\Models\AbstractModel;

/**
 * Repository Interface defines the standard CRUD operations for data persistence
 */
interface RepositoryInterface
{
    /**
     * Find an entity by its ID
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;
    
    /**
     * Find all entities
     *
     * @return array
     */
    public function findAll(): array;
    
    /**
     * Create a new entity from data
     *
     * @param array $data
     * @return int The newly created entity ID
     */
    public function create(array $data): int;
    
    /**
     * Update an existing entity
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
    
    /**
     * Delete an entity
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
    
    /**
     * Find an entity by ID and return as an entity object
     *
     * @param int $id
     * @return AbstractModel|null
     */
    public function findByIdAsEntity(int $id): ?AbstractModel;
    
    /**
     * Find all entities and return as entity objects
     * 
     * @return AbstractModel[]
     */
    public function findAllAsEntities(): array;
    
    /**
     * Create a new entity from an entity object
     *
     * @param AbstractModel $entity
     * @return int The newly created entity ID
     * @throws \InvalidArgumentException If entity validation fails
     */
    public function createFromEntity(AbstractModel $entity): int;
} 