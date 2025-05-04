<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PDO;

/**
 * Base test case class for all tests
 *
 * @requires extension PDO
 * @requires extension pdo_sqlite
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Create an in-memory SQLite database for testing
     *
     * @return PDO SQLite PDO connection
     */
    protected function createTestDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create tables with SQLite syntax
        $pdo->exec("
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

        return $pdo;
    }
}
