<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env.testing if it exists
if (file_exists(__DIR__ . '/../.env.testing')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.testing');
    $dotenv->load();
}

// Set default environment variables for testing
$_ENV['APP_ENV'] = 'testing';
if (!isset($_ENV['DATABASE_URL'])) {
    $_ENV['DATABASE_URL'] = 'sqlite::memory:';
}

// Create a test database schema
// This is for SQLite in-memory tests
// If you need to create a temporary PostgreSQL schema, you would do that here

// Initialize test helpers and utilities
require_once __DIR__ . '/TestCase.php';