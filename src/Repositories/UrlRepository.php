<?php

namespace App\Repositories;

use App\Models\Url;
use InvalidArgumentException;

class UrlRepository extends BaseRepository
{
    protected string $table = 'urls';

    /**
     * Create a new URL record from array data
     *
     * @param array $data
     * @return int The newly created URL ID
     * @throws InvalidArgumentException If required data is missing
     */
    public function create(array $data): int
    {
        // Ensure 'name' is provided
        if (!isset($data['name']) || empty($data['name'])) {
            throw new InvalidArgumentException('URL name is required');
        }

        $name = $data['name'];

        $sql = "INSERT INTO {$this->table} (name) VALUES (:name) RETURNING id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Find a URL by its name
     *
     * @param string $name
     * @return array|null
     */
    public function findByName(string $name): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE name = :name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['name' => $name]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a URL by its name and return as an entity
     *
     * @param string $name
     * @return Url|null
     */
    public function findByNameAsEntity(string $name): ?Url
    {
        $data = $this->findByName($name);

        if (!$data) {
            return null;
        }

        return Url::fromArray($data);
    }

    /**
     * Find a URL by ID and return as an entity
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

        return Url::fromArray($data);
    }

    /**
     * Find all URLs and return as Url entities
     *
     * @return Url[]
     */
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