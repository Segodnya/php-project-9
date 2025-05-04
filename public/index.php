<?php

/**
 * Main application entry point
 *
 * This file contains both declarations and side effects.
 * PHP version 8.0
 *
 * @category Application
 * @package  PageAnalyzer
 * @phpcs:ignoreFile PSR1.Files.SideEffects
 */

declare(strict_types=1);

// Basic setup: error reporting and session
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
session_start();

// Load Composer's autoloader - we still need this for our dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // No .env file, continue with default environment
}

// Database connection function
/**
 * Get PDO database connection
 *
 * @return PDO Database connection
 */
function getPDO(): PDO
{
    static $pdo = null;

    // For test environment, use test PDO if provided
    if (isset($GLOBALS['USE_TEST_PDO']) && $GLOBALS['USE_TEST_PDO'] && isset($GLOBALS['testPdo'])) {
        return $GLOBALS['testPdo'];
    }

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Check if we're in production with DATABASE_URL env var
        if (isset($_ENV['DATABASE_URL'])) {
            $params = parse_url($_ENV['DATABASE_URL']);

            if ($params === false) {
                throw new InvalidArgumentException('Invalid database URL format');
            }

            $driver = $params['scheme'] ?? '';
            $username = $params['user'] ?? '';
            $password = $params['pass'] ?? '';
            $host = $params['host'] ?? '';
            $port = $params['port'] ?? '';
            $dbName = isset($params['path']) ? ltrim($params['path'], '/') : '';

            if (empty($driver) || empty($host) || empty($dbName)) {
                throw new InvalidArgumentException('Missing required database connection parameters');
            }

            // Map URL scheme to PDO driver
            switch ($driver) {
                case 'postgresql':
                case 'postgres':
                case 'pgsql':
                    $pdoDriver = 'pgsql';
                    break;
                default:
                    throw new InvalidArgumentException("Unsupported database driver: {$driver}");
            }

            $port = $port ? ";port={$port}" : '';
            $dsn = "{$pdoDriver}:host={$host}{$port};dbname={$dbName}";

            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } else {
            // Use SQLite with local database.sqlite file in development
            $dbPath = dirname(__DIR__) . '/database.sqlite';

            // Create the SQLite database if it doesn't exist
            $initializeDb = !file_exists($dbPath);

            // Create PDO connection to SQLite
            $pdo = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // SQLite configuration
            $pdo->exec('PRAGMA foreign_keys = ON');

            // Initialize database schema if needed
            if ($initializeDb) {
                $sqlPath = dirname(__DIR__) . '/database.sql';
                if (file_exists($sqlPath)) {
                    // Convert PostgreSQL SQL to SQLite compatible syntax
                    $sql = file_get_contents($sqlPath);

                    if ($sql !== false) {
                        // Replace PostgreSQL SERIAL with SQLite AUTOINCREMENT
                        $sql = str_replace('SERIAL PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

                        // Replace PostgreSQL NOW() with SQLite CURRENT_TIMESTAMP
                        $sql = str_replace('NOW()', 'CURRENT_TIMESTAMP', $sql);

                        // Execute the SQL statements
                        $pdo->exec($sql);
                    } else {
                        throw new RuntimeException('Failed to read database SQL file: ' . $sqlPath);
                    }
                } else {
                    throw new RuntimeException('Database SQL file not found: ' . $sqlPath);
                }
            }
        }

        // Test connection
        $pdo->query('SELECT 1');
        return $pdo;
    } catch (PDOException $e) {
        // Simple error handling
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Flash message functions
/**
 * Set a flash message to display on next request
 *
 * @param string $type Message type (success, danger, warning, info)
 * @param string $message Message content
 * @return void
 */
function setFlashMessage(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages
 *
 * @return array<int, array{type: string, message: string}> Flash messages
 */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

// URL validation
/**
 * Validate a URL
 *
 * @param string $url URL to validate
 * @throws InvalidArgumentException if URL is invalid
 * @return string Normalized URL
 */
function validateUrl(string $url): string
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

    return normalizeUrl($url);
}

/**
 * Normalize a URL to scheme://host format
 *
 * @param string $url URL to normalize
 * @throws InvalidArgumentException if URL is invalid
 * @return string Normalized URL
 */
function normalizeUrl(string $url): string
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

// URL repository functions
/**
 * Find a URL by its ID
 *
 * @param int $id URL ID
 * @return array<string, mixed>|null URL data or null if not found
 */
function findUrlById(int $id): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
}

/**
 * Find a URL by its name
 *
 * @param string $name URL name
 * @return array<string, mixed>|null URL data or null if not found
 */
function findUrlByName(string $name): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE name = :name');
    $stmt->execute(['name' => $name]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
}

/**
 * Find all URLs
 *
 * @return array<int, array<string, mixed>> All URLs
 */
function findAllUrls(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

/**
 * Create a new URL
 *
 * @param string $name URL name
 * @return int|null New URL ID or null on failure
 */
function createUrl(string $name): ?int
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO urls (name) VALUES (:name) RETURNING id');
    $stmt->execute(['name' => $name]);

    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

// URL check repository functions
/**
 * Find URL checks by URL ID
 *
 * @param int $urlId URL ID
 * @return array<int, array<string, mixed>> URL check records
 */
function findUrlChecksByUrlId(int $urlId): array
{
    $pdo = getPDO();
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
function findLatestUrlCheckByUrlId(int $urlId): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1');
    $stmt->execute(['url_id' => $urlId]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result === false ? null : $result;
}

/**
 * Create a new URL check
 *
 * @param array<string, mixed> $data URL check data
 * @return int|null New URL check ID or null on failure
 */
function createUrlCheck(array $data): ?int
{
    $pdo = getPDO();
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
    return $id !== false ? (int) $id : null;
}

// URL analysis function
/**
 * Analyze a URL by fetching and parsing its content
 *
 * @param string $url URL to analyze
 * @return array<string, mixed> Analysis result data
 * @throws Exception on error
 */
function analyzeUrl(string $url): array
{
    // Create a HTTP client with options
    $client = new GuzzleHttp\Client([
        'timeout' => 10,
        'verify' => false,
        'http_errors' => true,
        'allow_redirects' => true
    ]);

    try {
        $response = $client->get($url);
        $statusCode = $response->getStatusCode();

        $result = [
            'status_code' => $statusCode
        ];

        if ($statusCode === 200) {
            $body = (string) $response->getBody();

            // Skip parsing if body is empty
            if (!empty($body)) {
                try {
                    $document = new DiDom\Document($body);

                    // Extract h1 tag content
                    $h1Element = $document->first('h1');
                    // DiDom\Element has a text() method, but PHPStan doesn't know about it
                    // Use a different approach that PHPStan can understand
                    $result['h1'] = $h1Element ? trim($h1Element->text()) : null;

                    // Extract title tag content
                    $titleElement = $document->first('title');
                    // Same issue with text() method
                    $result['title'] = $titleElement ? trim($titleElement->text()) : null;

                    // Extract meta description content
                    $descElement = $document->first('meta[name="description"]');
                    $result['description'] = $descElement
                        ? $descElement->getAttribute('content')
                        : null;
                } catch (Exception $e) {
                    // Log parsing error but continue
                    error_log('HTML parsing error: ' . $e->getMessage());
                }
            }
        }

        return $result;
    } catch (GuzzleHttp\Exception\ConnectException $e) {
        throw new Exception('Не удалось подключиться к сайту');
    } catch (GuzzleHttp\Exception\RequestException $e) {
        // Check if a response is available before trying to get status code
        $statusCode = null;
        $response = $e->getResponse();
        if ($response !== null) {
            $statusCode = $response->getStatusCode();
        }

        // Include status code in the message instead of as a separate parameter
        $message = 'Ошибка при запросе: ' . $e->getMessage();
        if ($statusCode) {
            $message = "Ошибка при запросе (код {$statusCode}): " . $e->getMessage();
        }
        throw new Exception($message, 0, $e);
    } catch (Exception $e) {
        throw new Exception('Произошла ошибка: ' . $e->getMessage());
    }
}

// Helper functions for views
/**
 * HTML escape function
 *
 * @param mixed $text Text to escape
 * @return string HTML escaped text
 */
function h(mixed $text): string
{
    // Handle null values
    if ($text === null) {
        return '';
    }

    // Convert non-string inputs to string before passing to htmlspecialchars
    if (!is_string($text)) {
        // Convert different types appropriately
        if (is_bool($text)) {
            $text = $text ? 'true' : 'false';
        } elseif (is_array($text) || is_object($text)) {
            // For arrays and objects, use json_encode for a safer representation
            $text = json_encode($text) ?: '[Uncoded value]';
        } else {
            // For numbers and other scalar types
            $text = (string) $text;
        }
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date string
 *
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate(string $date): string
{
    if (empty($date)) {
        return 'Invalid date';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Invalid date';
    }
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Get a status badge HTML for a status code
 *
 * @param int $statusCode HTTP status code
 * @return string HTML for the badge
 */
function getStatusBadge(int $statusCode): string
{
    if ($statusCode >= 200 && $statusCode < 300) {
        return '<span class="badge bg-success">' . $statusCode . '</span>';
    } elseif ($statusCode >= 300 && $statusCode < 400) {
        return '<span class="badge bg-info">' . $statusCode . '</span>';
    } elseif ($statusCode >= 400 && $statusCode < 500) {
        return '<span class="badge bg-warning">' . $statusCode . '</span>';
    } else {
        return '<span class="badge bg-danger">' . $statusCode . '</span>';
    }
}

// Simple router
if (isset($GLOBALS['USE_TEST_PDO']) && $GLOBALS['USE_TEST_PDO']) {
    // Skip request handling and routing when in test mode
    // This prevents errors when $_SERVER variables aren't available in tests
} else {
    // Only parse request data when not in test mode
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $requestUri = parse_url($requestUri, PHP_URL_PATH);

    // Routes handling
    try {
        // Route: GET /
        if ($requestUri === '/' && $requestMethod === 'GET') {
            include __DIR__ . '/views/index.php';
            exit;
        }

        // Route: GET /urls
        if ($requestUri === '/urls' && $requestMethod === 'GET') {
            $urls = findAllUrls();

            // Add the latest check data to each URL
            foreach ($urls as &$url) {
                if (isset($url['id']) && is_numeric($url['id'])) {
                    $latestCheck = findLatestUrlCheckByUrlId((int) $url['id']);
                    if ($latestCheck) {
                        $url['last_check_created_at'] = $latestCheck['created_at'];
                        $url['last_check_status_code'] = $latestCheck['status_code'];
                    }
                }
            }

            include __DIR__ . '/views/urls/index.php';
            exit;
        }

        // Route: POST /urls
        if ($requestUri === '/urls' && $requestMethod === 'POST') {
            $url = $_POST['url']['name'] ?? '';

            try {
                $normalizedUrl = validateUrl($url);
                $existingUrl = findUrlByName($normalizedUrl);

                if ($existingUrl) {
                    setFlashMessage('info', 'Страница уже существует');
                    header('Location: /urls/' . $existingUrl['id']);
                    exit;
                }

                $id = createUrl($normalizedUrl);
                setFlashMessage('success', 'Страница успешно добавлена');
                header('Location: /urls/' . $id);
                exit;
            } catch (InvalidArgumentException $e) {
                setFlashMessage('danger', $e->getMessage());
                http_response_code(422);
                include __DIR__ . '/views/index.php';
                exit;
            }
        }

        // Route: GET /urls/{id}
        if (preg_match('#^/urls/(\d+)$#', (string) $requestUri, $matches) && $requestMethod === 'GET') {
            $id = (int) $matches[1];
            $url = findUrlById($id);

            if (!$url) {
                http_response_code(404);
                include __DIR__ . '/views/errors/404.php';
                exit;
            }

            $checks = findUrlChecksByUrlId($id);
            include __DIR__ . '/views/urls/show.php';
            exit;
        }

        // Route: POST /urls/{id}/checks
        if (preg_match('#^/urls/(\d+)/checks$#', (string) $requestUri, $matches) && $requestMethod === 'POST') {
            $id = (int) $matches[1];
            $url = findUrlById($id);

            if (!$url) {
                http_response_code(404);
                include __DIR__ . '/views/errors/404.php';
                exit;
            }

            try {
                if (!isset($url['name']) || !is_string($url['name'])) {
                    throw new Exception('Invalid URL data: missing name');
                }

                $checkData = analyzeUrl($url['name']);

                $data = [
                    'url_id' => $id,
                    'status_code' => $checkData['status_code'],
                    'h1' => $checkData['h1'] ?? null,
                    'title' => $checkData['title'] ?? null,
                    'description' => $checkData['description'] ?? null
                ];

                createUrlCheck($data);
                setFlashMessage('success', 'Страница успешно проверена');
            } catch (GuzzleHttp\Exception\RequestException $e) {
                // Handle RequestException separately since it might have a response with status code
                $statusCode = null;
                $response = $e->getResponse();
                if ($response !== null) {
                    $statusCode = $response->getStatusCode();
                }

                if ($statusCode) {
                    setFlashMessage(
                        'danger',
                        "Ошибка при проверке: HTTP код {$statusCode}. {$e->getMessage()}"
                    );
                } else {
                    setFlashMessage('danger', "Ошибка при проверке: {$e->getMessage()}");
                }
            } catch (Exception $e) {
                // Handle all other exceptions
                setFlashMessage('danger', "Ошибка при проверке: {$e->getMessage()}");
            }

            header('Location: /urls/' . $id);
            exit;
        }

        // No route matched - 404
        http_response_code(404);
        include __DIR__ . '/views/errors/404.php';
    } catch (Exception $e) {
        http_response_code(500);
        include __DIR__ . '/views/errors/500.php';
    }
}
