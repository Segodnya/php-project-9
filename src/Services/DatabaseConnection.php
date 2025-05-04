<?php

namespace App\Services;

// Use fully qualified name for PDO
use \PDO;
use InvalidArgumentException;
use RuntimeException;

class DatabaseConnection
{
    private static ?\PDO $pdo = null;

    /**
     * Get a PDO connection instance
     *
     * @return \PDO
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function get(): \PDO
    {
        if (self::$pdo === null) {
            try {
                // Check if we're in production (with DATABASE_URL env var)
                if (isset($_ENV['DATABASE_URL'])) {
                    self::$pdo = DatabaseFactory::createFromUrl($_ENV['DATABASE_URL']);
                } else {
                    // Use SQLite with local database.sql file in development
                    $dbPath = dirname(__DIR__, 2) . '/database.sqlite';

                    // Create the SQLite database if it doesn't exist
                    $initializeDb = !file_exists($dbPath);

                    // Create PDO connection to SQLite
                    self::$pdo = new PDO("sqlite:{$dbPath}", null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]);

                    // SQLite configuration
                    self::$pdo->exec('PRAGMA foreign_keys = ON');

                    // Initialize database schema if needed
                    if ($initializeDb) {
                        $sqlPath = dirname(__DIR__, 2) . '/database.sql';
                        if (file_exists($sqlPath)) {
                            // Convert PostgreSQL SQL to SQLite compatible syntax
                            $sql = file_get_contents($sqlPath);

                            // Replace PostgreSQL SERIAL with SQLite AUTOINCREMENT
                            $sql = str_replace('SERIAL PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);

                            // Replace PostgreSQL NOW() with SQLite CURRENT_TIMESTAMP
                            $sql = str_replace('NOW()', 'CURRENT_TIMESTAMP', $sql);

                            // Execute the SQL statements
                            self::$pdo->exec($sql);
                        } else {
                            throw new RuntimeException('Database SQL file not found: ' . $sqlPath);
                        }
                    }
                }

                // Test connection
                self::$pdo->query('SELECT 1');
            } catch (\PDOException $e) {
                $errorMessage = 'Database connection failed: ' . $e->getMessage();
                error_log($errorMessage);
                throw new RuntimeException($errorMessage, 0, $e);
            }
        }

        return self::$pdo;
    }

    /**
     * Reset the connection (for testing purposes)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}