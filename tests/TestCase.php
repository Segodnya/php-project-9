<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use DI\Container;
use App\PDO;
use PDO as StandardPDO;
use Mockery;

/**
 * Base test case class for all tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a container for testing
        $this->container = new Container();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Get a mock PDO instance
     */
    protected function getMockPDO(): PDO
    {
        return Mockery::mock(PDO::class);
    }

    /**
     * Create an in-memory SQLite database for testing
     */
    protected function createTestDatabase(): PDO
    {
        $standardPdo = new StandardPDO('sqlite::memory:');
        $standardPdo->setAttribute(StandardPDO::ATTR_ERRMODE, StandardPDO::ERRMODE_EXCEPTION);

        // Create tables directly with SQLite syntax
        $standardPdo->exec("
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
            
            CREATE INDEX urls_name_idx ON urls (name);
            CREATE INDEX url_checks_url_id_idx ON url_checks (url_id);
        ");

        // Wrap the standard PDO in our App\PDO wrapper
        return PDO::fromPdo($standardPdo);
    }
}