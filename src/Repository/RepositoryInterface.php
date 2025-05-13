<?php

/**
 * Repository Interface
 *
 * Defines standard methods for entity repositories
 * PHP version 8.0
 *
 * @category Repository
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Repository;

/**
 * Repository Interface
 */
interface RepositoryInterface
{
    /**
     * Find an entity by its ID
     *
     * @param int $id Entity ID
     * @return mixed Entity or null if not found
     */
    public function findById(int $id): mixed;

    /**
     * Find all entities
     *
     * @return array All entities
     */
    public function findAll(): array;

    /**
     * Create a new entity
     *
     * @param mixed $entity Entity to create
     * @return mixed Created entity with ID
     */
    public function create(mixed $entity): mixed;
}
