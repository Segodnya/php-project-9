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
     * @return array<string, mixed>|null URL data or null if not found
     */
    public function findById(int $id): ?array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        /** @var array<string, mixed> $result */
        $result = $fetchedResult;
        return $result;
    }

    /**
     * Find a URL by its name
     *
     * @param string $name URL name
     * @return array<string, mixed>|null URL data or null if not found
     */
    public function findByName(string $name): ?array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls WHERE name = :name');
        $stmt->execute(['name' => $name]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        /** @var array<string, mixed> $result */
        $result = $fetchedResult;
        return $result;
    }

    /**
     * Find all URLs
     *
     * @return array<int, array<string, mixed>> All URLs
     */
    public function findAll(): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Find all URLs with their latest check data
     *
     * @return array<int, array<string, mixed>> All URLs with latest check data
     */
    public function findAllWithLatestChecks(): array
    {
        $urls = $this->findAll();

        // Add the latest check data to each URL
        foreach ($urls as &$url) {
            if (isset($url['id']) && is_numeric($url['id'])) {
                $latestCheck = $this->findLatestUrlCheck((int) $url['id']);
                if ($latestCheck) {
                    $url['last_check_created_at'] = $latestCheck['created_at'];
                    $url['last_check_status_code'] = $latestCheck['status_code'];
                }
            }
        }

        return $urls;
    }

    /**
     * Create a new URL
     *
     * @param string $name URL name
     * @return int New URL ID
     */
    public function create(string $name): int
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('INSERT INTO urls (name) VALUES (:name) RETURNING id');
        $stmt->execute(['name' => $name]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new \RuntimeException('Failed to create URL');
        }

        return (int) $id;
    }

    /**
     * Find URL checks by URL ID
     *
     * @param int $urlId URL ID
     * @return array<int, array<string, mixed>> URL check records
     */
    public function findUrlChecks(int $urlId): array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC');
        $stmt->execute(['url_id' => $urlId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Find the latest URL check by URL ID
     *
     * @param int $urlId URL ID
     * @return array<string, mixed>|null URL check data or null if not found
     */
    public function findLatestUrlCheck(int $urlId): ?array
    {
        $pdo = Database::getPDO();
        $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['url_id' => $urlId]);

        $fetchedResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetchedResult === false) {
            return null;
        }

        /** @var array<string, mixed> $result */
        $result = $fetchedResult;
        return $result;
    }

    /**
     * Create a new URL check
     *
     * @param array<string, mixed> $data URL check data
     * @return int New URL check ID
     */
    public function createUrlCheck(array $data): int
    {
        $pdo = Database::getPDO();
        $sql = 'INSERT INTO url_checks (url_id, status_code, h1, title, description)
                VALUES (:url_id, :status_code, :h1, :title, :description)
                RETURNING id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $data['url_id'],
            'status_code' => $data['status_code'],
            'h1' => $data['h1'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null
        ]);

        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new \RuntimeException('Failed to create URL check');
        }

        return (int) $id;
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

        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Check URL length
        if (strlen($url) > 255) {
            throw new InvalidArgumentException('URL cannot exceed 255 characters');
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

        if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
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
