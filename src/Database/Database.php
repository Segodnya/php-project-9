<?php

/**
 * Database connection class
 *
 * Provides PDO connection to the database
 * PHP version 8.0
 *
 * @category Database
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Database class for PDO connections
 */
class Database
{
    /**
     * @var PDO|null The static PDO instance (for backward compatibility)
     */
    private static ?PDO $instance = null;

    /**
     * Get PDO database connection (static singleton method)
     *
     * @deprecated Use createPDO() or dependency injection instead
     * @return PDO Database connection
     */
    public static function getPDO(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createPDO();
        }

        return self::$instance;
    }

    /**
     * Create a new PDO database connection
     *
     * @return PDO Database connection
     */
    public static function createPDO(): PDO
    {
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
                    $sqlPath = dirname(__DIR__, 2) . '/database.sql';
                    if (file_exists($sqlPath)) {
                        $sql = file_get_contents($sqlPath);
                        if ($sql !== false) {
                            // Execute the SQL statements - PostgreSQL can handle the script as is
                            $pdo->exec($sql);
                        } else {
                            throw new RuntimeException("Failed to read database SQL file: {$sqlPath}");
                        }
                    } else {
                        throw new RuntimeException("Database SQL file not found: {$sqlPath}");
                    }
                } catch (PDOException $migrationException) {
                    // If tables already exist, we'll get an error but that's fine
                    // Log the error if in development mode
                    if (isset($_ENV['DEBUG']) && $_ENV['DEBUG'] === 'true') {
                        error_log("Migration error (can be ignored if tables exist): {$migrationException->getMessage()}");
                    }
                }
            } else {
                // Use SQLite with local database.sqlite file in development
                $dbPath = dirname(__DIR__, 2) . '/database.sqlite';

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
                    $sqlPath = dirname(__DIR__, 2) . '/database.sql';
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
                            throw new RuntimeException("Failed to read database SQL file: {$sqlPath}");
                        }
                    } else {
                        throw new RuntimeException("Database SQL file not found: {$sqlPath}");
                    }
                }
            }

            // Test connection
            $pdo->query('SELECT 1');
            return $pdo;
        } catch (PDOException $e) {
            // Simple error handling
            die("Database connection failed: {$e->getMessage()}");
        }
    }
}
