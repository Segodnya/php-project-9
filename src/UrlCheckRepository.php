<?php

namespace App;

use Carbon\Carbon;
use PDO;

class UrlCheckRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $urlId, int $statusCode, ?string $h1, ?string $title, ?string $description): int
    {
        $now = Carbon::now()->toDateTimeString();
        $stmt = $this->pdo->prepare(
            'INSERT INTO url_checks (url_id, status_code, h1, title, description, created_at) 
             VALUES (:url_id, :status_code, :h1, :title, :description, :created_at) 
             RETURNING id'
        );

        $stmt->execute([
            'url_id' => $urlId,
            'status_code' => $statusCode,
            'h1' => $h1,
            'title' => $title,
            'description' => $description,
            'created_at' => $now
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findByUrlId(int $urlId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM url_checks 
             WHERE url_id = :url_id 
             ORDER BY created_at DESC'
        );

        $stmt->execute(['url_id' => $urlId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findLatestByUrlId(int $urlId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM url_checks 
             WHERE url_id = :url_id 
             ORDER BY created_at DESC 
             LIMIT 1'
        );

        $stmt->execute(['url_id' => $urlId]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);

        return $check ?: null;
    }
}
