<?php

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
function getPDO()
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

                    // Replace PostgreSQL SERIAL with SQLite AUTOINCREMENT
                    $sql = str_replace('SERIAL PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

                    // Replace PostgreSQL NOW() with SQLite CURRENT_TIMESTAMP
                    $sql = str_replace('NOW()', 'CURRENT_TIMESTAMP', $sql);

                    // Execute the SQL statements
                    $pdo->exec($sql);
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
function setFlashMessage($type, $message)
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages()
{
    $messages = $_SESSION['flash_messages'] ?? [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

// URL validation
function validateUrl($url)
{
    if (empty($url)) {
        throw new InvalidArgumentException('URL cannot be empty');
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

function normalizeUrl($url)
{
    // Parse the URL
    $parsedUrl = parse_url($url);

    if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
        throw new InvalidArgumentException('Invalid URL format');
    }

    // Convert scheme to lowercase for checking
    $scheme = strtolower($parsedUrl['scheme']);

    // Check for valid scheme (only http and https are allowed)
    if (!in_array($scheme, ['http', 'https'])) {
        throw new InvalidArgumentException('URL scheme must be http or https');
    }

    // Validate the host: it must have at least one dot to separate domain and TLD
    $host = strtolower($parsedUrl['host']);
    if (strpos($host, '.') === false) {
        throw new InvalidArgumentException('Некорректный URL: hostname must include domain and TLD');
    }

    // Additional validation: TLD must be at least 2 characters
    $parts = explode('.', $host);
    $tld = end($parts);
    if (strlen($tld) < 2) {
        throw new InvalidArgumentException('Некорректный URL: invalid top-level domain');
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
function findUrlById($id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $result = $stmt->fetch();
    return $result ?: null;
}

function findUrlByName($name)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls WHERE name = :name');
    $stmt->execute(['name' => $name]);

    $result = $stmt->fetch();
    return $result ?: null;
}

function findAllUrls()
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM urls ORDER BY id DESC');
    $stmt->execute();

    return $stmt->fetchAll();
}

function createUrl($name)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO urls (name) VALUES (:name) RETURNING id');
    $stmt->execute(['name' => $name]);

    $id = $stmt->fetchColumn();
    return $id ?: null;
}

// URL check repository functions
function findUrlChecksByUrlId($urlId)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC');
    $stmt->execute(['url_id' => $urlId]);

    return $stmt->fetchAll();
}

function findLatestUrlCheckByUrlId($urlId)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM url_checks WHERE url_id = :url_id ORDER BY id DESC LIMIT 1');
    $stmt->execute(['url_id' => $urlId]);

    $result = $stmt->fetch();
    return $result ?: null;
}

function createUrlCheck($data)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO url_checks (url_id, status_code, h1, title, description) VALUES (:url_id, :status_code, :h1, :title, :description) RETURNING id');
    $stmt->execute([
        'url_id' => $data['url_id'],
        'status_code' => $data['status_code'],
        'h1' => $data['h1'] ?? null,
        'title' => $data['title'] ?? null,
        'description' => $data['description'] ?? null
    ]);

    $id = $stmt->fetchColumn();
    return $id ?: null;
}

// URL analysis function
function analyzeUrl($url)
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
                    $result['h1'] = $h1Element ? $h1Element->text() : null;

                    // Extract title tag content
                    $titleElement = $document->first('title');
                    $result['title'] = $titleElement ? $titleElement->text() : null;

                    // Extract meta description content
                    $descElement = $document->first('meta[name="description"]');
                    $result['description'] = $descElement ? $descElement->getAttribute('content') : null;
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
        $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
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
function h($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function formatDate($date)
{
    $timestamp = strtotime($date);
    return date('Y-m-d H:i:s', $timestamp);
}

function getStatusBadge($statusCode)
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
                $latestCheck = findLatestUrlCheckByUrlId($url['id']);
                if ($latestCheck) {
                    $url['last_check_created_at'] = $latestCheck['created_at'];
                    $url['last_check_status_code'] = $latestCheck['status_code'];
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
                include __DIR__ . '/views/index.php';
                exit;
            }
        }

        // Route: GET /urls/{id}
        if (preg_match('#^/urls/(\d+)$#', $requestUri, $matches) && $requestMethod === 'GET') {
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
        if (preg_match('#^/urls/(\d+)/checks$#', $requestUri, $matches) && $requestMethod === 'POST') {
            $id = (int) $matches[1];
            $url = findUrlById($id);

            if (!$url) {
                http_response_code(404);
                include __DIR__ . '/views/errors/404.php';
                exit;
            }

            try {
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
                $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;

                if ($statusCode) {
                    setFlashMessage('danger', "Ошибка при проверке: HTTP код {$statusCode}. {$e->getMessage()}");
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
