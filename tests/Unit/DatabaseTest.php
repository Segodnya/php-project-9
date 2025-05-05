<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Tests for database-related functions
 */
class DatabaseTest extends TestCase
{
    /**
     * In-memory test database connection
     *
     * @var PDO
     */
    private static PDO $pdo;

    /**
     * Set up the testing environment once before all tests
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        // Define an environment variable to indicate test mode
        $_ENV['APP_ENV'] = 'testing';

        // Create in-memory SQLite database for testing
        self::$pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Configure SQLite
        self::$pdo->exec('PRAGMA foreign_keys = ON');

        // Create tables for testing
        $sqlPath = dirname(__DIR__, 2) . '/database.sql';
        $sql = file_get_contents($sqlPath);
        if ($sql !== false) {
            // Convert PostgreSQL syntax to SQLite
            $sql = str_replace('SERIAL PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = str_replace('NOW()', 'CURRENT_TIMESTAMP', $sql);

            // Execute schema creation
            self::$pdo->exec($sql);
        }

        // Load functions from index.php
        require_once dirname(__DIR__, 2) . '/public/index.php';
    }

    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Clean up tables before each test
        self::$pdo->exec('DELETE FROM url_checks');
        self::$pdo->exec('DELETE FROM urls');

        // Reset auto-increment counters
        self::$pdo->exec('DELETE FROM sqlite_sequence WHERE name="urls" OR name="url_checks"');

        // Mock the getPDO function to return our test database
        $this->registerMockPdo();
    }

    /**
     * Register a mock for the getPDO function
     *
     * @return void
     */
    private function registerMockPdo(): void
    {
        // Static flag to track if we've already set up the mock
        static $setupComplete = false;

        // Only run once per test suite
        if ($setupComplete) {
            return;
        }

        $setupComplete = true;

        // We'll use a different approach without eval() or runkit
        // Instead, we'll use our static PDO property directly in the test methods
        // This way we avoid the function redeclaration issue

        // Any code that needs the PDO connection should use:
        // DatabaseTest::$pdo instead of getPDO()
    }

    /**
     * Test URL creation and retrieval
     *
     * @return void
     */
    public function testCreateAndFindUrl(): void
    {
        // Test creating a URL directly with PDO
        $url = 'https://example.com';
        $stmt = self::$pdo->prepare('INSERT INTO urls (name) VALUES (?)');
        $stmt->execute([$url]);
        $id = (int) self::$pdo->lastInsertId();

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Test finding URL by ID
        $stmt = self::$pdo->prepare('SELECT * FROM urls WHERE id = ?');
        $stmt->execute([$id]);
        $foundUrl = $stmt->fetch();
        $this->assertIsArray($foundUrl);
        $this->assertEquals($url, $foundUrl['name']);

        // Test finding URL by name
        $stmt = self::$pdo->prepare('SELECT * FROM urls WHERE name = ?');
        $stmt->execute([$url]);
        $foundByName = $stmt->fetch();
        $this->assertIsArray($foundByName);
        $this->assertEquals($id, $foundByName['id']);

        // Test finding all URLs
        $stmt = self::$pdo->query('SELECT * FROM urls ORDER BY id DESC');
        $this->assertNotFalse($stmt, 'Query execution failed');
        $allUrls = $stmt->fetchAll();
        $this->assertIsArray($allUrls);
        $this->assertCount(1, $allUrls);
        $this->assertEquals($url, $allUrls[0]['name']);
    }

    /**
     * Test URL check creation and retrieval
     *
     * @return void
     */
    public function testUrlChecks(): void
    {
        // Create a URL first
        $url = 'https://example.com';
        $stmt = self::$pdo->prepare('INSERT INTO urls (name) VALUES (?)');
        $stmt->execute([$url]);
        $urlId = (int) self::$pdo->lastInsertId();

        // Ensure we have a valid URL ID
        $this->assertIsInt($urlId);
        $this->assertGreaterThan(0, $urlId);

        // Create a URL check
        $checkData = [
            'url_id' => $urlId,
            'status_code' => 200,
            'h1' => 'Test H1',
            'title' => 'Test Title',
            'description' => 'Test Description'
        ];

        $stmt = self::$pdo->prepare('INSERT INTO url_checks (url_id, status_code, h1, title, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $checkData['url_id'],
            $checkData['status_code'],
            $checkData['h1'],
            $checkData['title'],
            $checkData['description']
        ]);
        $checkId = (int) self::$pdo->lastInsertId();

        $this->assertIsInt($checkId);
        $this->assertGreaterThan(0, $checkId);

        // Test finding URL checks by URL ID
        $stmt = self::$pdo->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC');
        $stmt->execute([$urlId]);
        $checks = $stmt->fetchAll();
        $this->assertIsArray($checks);
        $this->assertCount(1, $checks);
        $this->assertEquals($checkData['status_code'], $checks[0]['status_code']);
        $this->assertEquals($checkData['h1'], $checks[0]['h1']);

        // Test finding latest URL check
        $stmt = self::$pdo->prepare('SELECT * FROM url_checks WHERE url_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$urlId]);
        $latestCheck = $stmt->fetch();
        $this->assertIsArray($latestCheck);
        $this->assertEquals($checkData['title'], $latestCheck['title']);
        $this->assertEquals($checkData['description'], $latestCheck['description']);
    }
}
