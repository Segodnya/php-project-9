<?php

/**
 * URL Service
 *
 * Provides methods for URL operations
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Models\Url;
use App\Models\UrlCheck;
use InvalidArgumentException;
use PDO;

/**
 * UrlService class
 */
class UrlService
{
    /**
     * Find a URL by its ID
     *
     * @param int $id URL ID
     * @return Url|null URL object or null if not found
     */
    public function findById(int $id): ?Url
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = :id');
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
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls WHERE name = :name');
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
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
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
        $pdo = Database::getPDO();

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

        $stmt = $pdo->prepare($sql);
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
     * @param string $name URL name
     * @return Url New URL object
     */
    public function create(string $name): Url
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO urls (name) VALUES (:name) RETURNING id');
        $stmt->execute(['name' => $name]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new \RuntimeException('Failed to create URL');
        }

        $url = new Url($name, (int) $id);
        return $url;
    }

    /**
     * Find URL checks by URL ID
     *
     * @param int $urlId URL ID
     * @return array<int, UrlCheck> URL check objects
     */
    public function findUrlChecks(int $urlId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC');
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
    public function findLatestUrlCheck(int $urlId): ?UrlCheck
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1');
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
    public function createUrlCheck(UrlCheck $urlCheck): UrlCheck
    {
        $pdo = Database::getPDO();
        $sql = 'INSERT INTO url_checks (url_id, status_code, h1, title, description)
                VALUES (:url_id, :status_code, :h1, :title, :description)
                RETURNING id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $urlCheck->getUrlId(),
            'status_code' => $urlCheck->getStatusCode(),
            'h1' => $urlCheck->getH1(),
            'title' => $urlCheck->getTitle(),
            'description' => $urlCheck->getDescription()
        ]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new \RuntimeException('Failed to create URL check');
        }

        $urlCheck->setId((int) $id);
        return $urlCheck;
    }

    /**
     * Validate a URL
     *
     * @param string $url URL to validate
     * @throws InvalidArgumentException if URL is invalid
     * @return string Normalized URL
     */
    public function validateUrl(string $url): string
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Trim whitespace
        $url = trim($url);

        // Direct check for malformed "http//" and "https//" (without colon)
        if (
            $url === 'http//' || $url === 'https//' ||
            strpos($url, 'http//') === 0 || strpos($url, 'https//') === 0
        ) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Check URL length
        if (strlen($url) > 255) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        return $this->normalizeUrl($url);
    }

    /**
     * Normalize a URL to scheme://host format
     *
     * @param string $url URL to normalize
     * @throws InvalidArgumentException if URL is invalid
     * @return string Normalized URL
     */
    private function normalizeUrl(string $url): string
    {
        // Parse the URL
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Convert scheme to lowercase for checking
        $scheme = strtolower($parsedUrl['scheme']);

        // Check for valid scheme (only http and https are allowed)
        if (!in_array($scheme, ['http', 'https'])) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Validate the host: it must have at least one dot to separate domain and TLD
        $host = strtolower($parsedUrl['host']);
        if (strpos($host, '.') === false) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Additional validation: TLD must be at least 2 characters
        $parts = explode('.', $host);
        $tld = end($parts);
        if (strlen($tld) < 2) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Normalize to scheme://host
        $normalizedUrl = $scheme . '://' . $host;

        // Add port if specified and not default
        if (isset($parsedUrl['port'])) {
            if (
                ($scheme === 'http' && $parsedUrl['port'] !== 80) ||
                ($scheme === 'https' && $parsedUrl['port'] !== 443)
            ) {
                $normalizedUrl .= ':' . $parsedUrl['port'];
            }
        }

        return $normalizedUrl;
    }
}
