<?php

namespace App;

use Carbon\Carbon;
use PDO;

class UrlRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM urls WHERE name = :name');
        $stmt->execute(['name' => $name]);
        $url = $stmt->fetch(PDO::FETCH_ASSOC);

        return $url ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM urls WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $url = $stmt->fetch(PDO::FETCH_ASSOC);

        return $url ?: null;
    }

    public function create(string $name): int
    {
        $now = Carbon::now()->toDateTimeString();
        $stmt = $this->pdo->prepare('INSERT INTO urls (name, created_at) VALUES (:name, :created_at) RETURNING id');
        $stmt->execute([
            'name' => $name,
            'created_at' => $now
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM urls ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
