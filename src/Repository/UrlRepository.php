<?php

/**
 * URL Repository
 *
 * Repository for URL entity operations
 * PHP version 8.0
 *
 * @category Repository
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Repository;

use App\Models\Url;
use PDO;
use RuntimeException;

/**
 * UrlRepository class
 */
class UrlRepository
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor
     *
     * @param PDO $pdo PDO database connection
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find a URL by its ID
     *
     * @param int $id URL ID
     * @return Url|null URL object or null if not found
     */
    public function findById(int $id): ?Url
    {
        $stmt = $this->pdo->prepare('SELECT * FROM urls WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        return Url::fromArray($fetchedResult);
    }

    /**
     * Find a URL by its name
     *
     * @param string $name URL name
     * @return Url|null URL object or null if not found
     */
    public function findByName(string $name): ?Url
    {
        $stmt = $this->pdo->prepare('SELECT * FROM urls WHERE name = :name');
        $stmt->execute(['name' => $name]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        return Url::fromArray($fetchedResult);
    }

    /**
     * Find all URLs
     *
     * @return array<int, Url> All URLs
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $urls = [];
        foreach ($result as $row) {
            $urls[] = Url::fromArray($row);
        }

        return $urls;
    }

    /**
     * Find all URLs with their latest check data
     *
     * @return array<int, array<string, mixed>> All URLs with latest check data
     */
    public function findAllWithLatestChecks(): array
    {
        // 1: Собрали все URL
        $urlsStmt = $this->pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
        $urlsStmt->execute();
        $urlsData = $urlsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Конвертируем в объекты Url и подготавливаем результат
        $urlsWithChecks = [];
        $urlIds = [];

        foreach ($urlsData as $urlData) {
            $url = Url::fromArray($urlData);
            $urlIds[] = $url->getId();
            $urlsWithChecks[$url->getId()] = $url->toArray();
        }

        if (empty($urlIds)) {
            return [];
        }

        // 2: Получаем последнюю проверку для каждого URL
        $placeholders = implode(',', array_fill(0, count($urlIds), '?'));
        $checksQuery = <<<SQL
        SELECT uc1.*
        FROM url_checks uc1
        INNER JOIN (
            SELECT url_id, MAX(id) as max_id
            FROM url_checks
            WHERE url_id IN ({$placeholders})
            GROUP BY url_id
        ) uc2 ON uc1.id = uc2.max_id
        SQL;

        $checksStmt = $this->pdo->prepare($checksQuery);
        $checksStmt->execute($urlIds);
        $latestChecks = $checksStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3: Мапим проверки к URL
        foreach ($latestChecks as $check) {
            $urlId = (int) $check['url_id'];
            if (isset($urlsWithChecks[$urlId])) {
                $urlsWithChecks[$urlId]['last_check_created_at'] = $check['created_at'];
                $urlsWithChecks[$urlId]['last_check_status_code'] = $check['status_code'];
            }
        }

        return array_values($urlsWithChecks);
    }

    /**
     * Create a new URL
     *
     * @param Url $url URL object
     * @return Url New URL object with ID
     */
    public function create(mixed $url): Url
    {
        $stmt = $this->pdo->prepare('INSERT INTO urls (name) VALUES (:name) RETURNING id');
        $stmt->execute(['name' => $url->getName()]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException('Failed to create URL');
        }

        $url->setId((int) $id);
        return $url;
    }
}
