<?php

namespace Tests\Feature;

use Tests\TestCase;
use PDO;

/**
 * @requires extension PDO
 * @requires extension pdo_sqlite
 */
class UrlTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables with SQLite syntax
        $this->pdo->exec("
            CREATE TABLE urls (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE url_checks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url_id INTEGER REFERENCES urls(id),
                status_code INTEGER,
                h1 TEXT,
                title TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // Set the global test PDO to our database for this test
        global $testPdo;
        $testPdo = $this->pdo;

        // Set test environment flag
        $GLOBALS['USE_TEST_PDO'] = true;

        // Start output buffering to prevent HTML output during tests
        ob_start();

        // Include index.php to load functions
        include_once dirname(__DIR__, 2) . '/public/index.php';

        // Clean the output buffer
        ob_end_clean();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Make sure to clean any output buffer
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $GLOBALS['USE_TEST_PDO'] = false;
    }

    public function testCreateAndFindUrl(): void
    {
        // Test creating a URL
        $urlName = 'https://example.com';

        $id = createUrl($urlName);

        // Verify the URL was created with an ID
        $this->assertIsNumeric($id);

        // Find the URL by ID and ensure we pass an int to the function
        $url = null;
        if (is_numeric($id)) {
            $url = findUrlById((int) $id);
        }

        // Verify the URL was found and has the correct name
        $this->assertNotNull($url);
        $this->assertEquals($urlName, $url['name'] ?? null);
    }
}
