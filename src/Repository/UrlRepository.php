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
class UrlRepository implements RepositoryInterface
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
        // This query joins URLs with a subquery that selects only the latest check for each URL
        $sql = <<<SQL
        SELECT
            urls.*,
            checks.id AS check_id,
            checks.status_code AS last_check_status_code,
            checks.created_at AS last_check_created_at
        FROM
            urls
        LEFT JOIN (
            SELECT
                url_id,
                id,
                status_code,
                created_at
            FROM
                url_checks uc1
            WHERE
                id = (SELECT MAX(id) FROM url_checks uc2 WHERE uc2.url_id = uc1.url_id)
        ) AS checks
        ON
            urls.id = checks.url_id
        ORDER BY
            urls.id DESC
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $urlsWithChecks = [];
        foreach ($results as $row) {
            $url = Url::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'created_at' => $row['created_at']
            ]);

            $urlData = $url->toArray();

            // Add check data if it exists
            if ($row['check_id'] !== null) {
                $urlData['last_check_created_at'] = $row['last_check_created_at'];
                $urlData['last_check_status_code'] = $row['last_check_status_code'];
            }

            $urlsWithChecks[] = $urlData;
        }

        return $urlsWithChecks;
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
