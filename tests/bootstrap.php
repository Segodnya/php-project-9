<?php

// Set up autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.testing');
try {
    $dotenv->load();
} catch (Exception $e) {
    // No .env.testing file, continue with default settings
}

// Set up test environment
$_ENV['APP_ENV'] = 'testing';

// Create shared SQLite in-memory database for tests
$GLOBALS['testPdo'] = new PDO('sqlite::memory:');
$GLOBALS['testPdo']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables with SQLite syntax
$GLOBALS['testPdo']->exec("
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

// Create a function that returns the test PDO object
function test_getPDO()
{
    return $GLOBALS['testPdo'];
}

// Initialize test helpers and utilities
require_once __DIR__ . '/TestCase.php';