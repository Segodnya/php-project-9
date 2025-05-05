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

// Only start a session if we're not in a test environment
if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'testing') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

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
            $port = $params['port'] ?? '5432'; // Default PostgreSQL port
            $dbName = isset($params['path']) ? ltrim($params['path'], '/') : '';

            if (empty($driver) || empty($host) || empty($dbName)) {
                throw new InvalidArgumentException('Missing required database connection parameters');
            }

            // Debug connection parameters
            if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
                error_log('Database connection parameters:');
                error_log("Driver: $driver");
                error_log("Username: $username");
                error_log("Password: " . (empty($password) ? 'Not provided' : 'Provided'));
                error_log("Host: $host");
                error_log("Port: $port");
                error_log("Database: $dbName");
            }

            // Always use 'pgsql' as the PDO driver name for PostgreSQL
            $pdoDriver = 'pgsql';

            // Build the DSN with explicit port
            $dsn = "{$pdoDriver}:host={$host};port={$port};dbname={$dbName}";

            if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
                error_log("DSN: $dsn");
            }

            // Make sure we're passing the username and password to PDO
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

            // Run migrations for PostgreSQL
            try {
                $sqlPath = dirname(__DIR__) . '/database.sql';
                if (file_exists($sqlPath)) {
                    $sql = file_get_contents($sqlPath);
                    if ($sql !== false) {
                        // Execute the SQL statements - PostgreSQL can handle the script as is
                        $pdo->exec($sql);
                    } else {
                        throw new RuntimeException('Failed to read database SQL file: ' . $sqlPath);
                    }
                } else {
                    throw new RuntimeException('Database SQL file not found: ' . $sqlPath);
                }
            } catch (PDOException $migrationException) {
                // If tables already exist, we'll get an error but that's fine
                // Log the error if in development mode
                if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
                    error_log('Migration error (can be ignored if tables exist): ' . $migrationException->getMessage());
                }
            }
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
 * @phpstan-return array<string, mixed>|null
 */
function findUrlById(int $id): ?array
{
    $pdo = getPDO();
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
 * @phpstan-return array<string, mixed>|null
 */
function findUrlByName(string $name): ?array
{
    $pdo = getPDO();
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
 * @phpstan-return array<string, mixed>|null
 */
function findLatestUrlCheckByUrlId(int $urlId): ?array
{
    $pdo = getPDO();
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

// URL analysis
/**
 * Analyze a URL for SEO metrics
 *
 * @param string $url URL to analyze
 * @return array{status_code: int, h1: ?string, title: ?string, description: ?string} Analysis results
 */
function analyzeUrl(string $url): array
{
    $client = createGuzzleClient();

    try {
        $response = $client->get($url);
        $statusCode = $response->getStatusCode();

        // Parse HTML to extract SEO data
        $h1 = null;
        $title = null;
        $description = null;

        $body = (string) $response->getBody();

        if (!empty($body)) {
            $document = new DiDom\Document();
            $document->loadHtml($body);

            // Extract h1
            $h1Elements = $document->find('h1');
            if (!empty($h1Elements)) {
                // Use DiDom's html() method
                $element = $h1Elements[0];
                if ($element instanceof DiDom\Element) {
                    $h1 = trim($element->html());
                }
            }

            // Extract title
            $titleElements = $document->find('title');
            if (!empty($titleElements)) {
                $element = $titleElements[0];
                if ($element instanceof DiDom\Element) {
                    $title = trim($element->html());
                }
            }

            // Extract description
            $metaElements = $document->find('meta[name=description]');
            if (!empty($metaElements)) {
                $element = $metaElements[0];
                if ($element instanceof DiDom\Element) {
                    $description = $element->getAttribute('content');
                }
            }
        }

        return [
            'status_code' => $statusCode,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ];
    } catch (GuzzleHttp\Exception\ClientException | GuzzleHttp\Exception\ServerException $e) {
        // Handle HTTP errors
        return [
            'status_code' => $e->getResponse()->getStatusCode(),
            'h1' => null,
            'title' => null,
            'description' => null
        ];
    } catch (Exception $e) {
        // Handle other errors
        return [
            'status_code' => 0,
            'h1' => null,
            'title' => null,
            'description' => null
        ];
    }
}

/**
 * Create a Guzzle HTTP client with default configuration
 *
 * @return GuzzleHttp\Client Configured HTTP client
 */
function createGuzzleClient(): GuzzleHttp\Client
{
    return new GuzzleHttp\Client([
        'timeout' => 10,
        'connect_timeout' => 5,
        'headers' => [
            'User-Agent' => 'PageAnalyzer/1.0',
        ]
    ]);
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
            return $text ? 'true' : 'false';
        } elseif (is_array($text) || is_object($text)) {
            // For arrays and objects, use json_encode for a safer representation
            $encodedText = json_encode($text);
            return htmlspecialchars($encodedText !== false ? $encodedText : '[Uncoded value]', ENT_QUOTES, 'UTF-8');
        } elseif (is_int($text) || is_float($text)) {
            // For numeric types, use string representation
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        } else {
            // For any other type, fallback to an empty string
            return '';
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

// Only parse request data when not in test mode
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$parsedUri = parse_url((string) $requestUri, PHP_URL_PATH);
$requestUri = $parsedUri !== false ? (string) $parsedUri : '/';

// Skip route handling in test mode
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing') {
    // Don't process routes in test mode
    return;
}

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
