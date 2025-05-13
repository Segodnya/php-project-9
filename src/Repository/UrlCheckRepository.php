<?php

/**
 * URL Check Repository
 *
 * Repository for URL Check entity operations
 * PHP version 8.0
 *
 * @category Repository
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Models\UrlCheck;
use PDO;
use RuntimeException;

/**
 * UrlCheckRepository class
 */
class UrlCheckRepository implements RepositoryInterface
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pdo = Database::getPDO();
    }

    /**
     * Find a URL check by its ID
     *
     * @param int $id URL check ID
     * @return UrlCheck|null URL check object or null if not found
     */
    public function findById(int $id): ?UrlCheck
    {
        $stmt = $this->pdo->prepare('SELECT * FROM url_checks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        return UrlCheck::fromArray($fetchedResult);
    }

    /**
     * Find all URL checks
     *
     * @return array<int, UrlCheck> All URL checks
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM url_checks ORDER BY id DESC');
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $checks = [];
        foreach ($result as $row) {
            $checks[] = UrlCheck::fromArray($row);
        }

        return $checks;
    }

    /**
     * Find URL checks by URL ID
     *
     * @param int $urlId URL ID
     * @return array<int, UrlCheck> URL check objects
     */
    public function findByUrlId(int $urlId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC');
        $stmt->execute(['url_id' => $urlId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $checks = [];
        foreach ($result as $row) {
            $checks[] = UrlCheck::fromArray($row);
        }

        return $checks;
    }

    /**
     * Find the latest URL check by URL ID
     *
     * @param int $urlId URL ID
     * @return UrlCheck|null URL check object or null if not found
     */
    public function findLatestByUrlId(int $urlId): ?UrlCheck
    {
        $stmt = $this->pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['url_id' => $urlId]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        return UrlCheck::fromArray($fetchedResult);
    }

    /**
     * Create a new URL check
     *
     * @param UrlCheck $urlCheck URL check object
     * @return UrlCheck Updated URL check object with ID
     */
    public function create(mixed $urlCheck): UrlCheck
    {
        if (!$urlCheck instanceof UrlCheck) {
            throw new \InvalidArgumentException('Entity must be of type UrlCheck');
        }

        $sql = 'INSERT INTO url_checks (url_id, status_code, h1, title, description)
                VALUES (:url_id, :status_code, :h1, :title, :description)
                RETURNING id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $urlCheck->getUrlId(),
            'status_code' => $urlCheck->getStatusCode(),
            'h1' => $urlCheck->getH1(),
            'title' => $urlCheck->getTitle(),
            'description' => $urlCheck->getDescription()
        ]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException('Failed to create URL check');
        }

        $urlCheck->setId((int) $id);
        return $urlCheck;
    }
}
