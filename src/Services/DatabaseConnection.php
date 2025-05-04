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
            if (!isset($_ENV['DATABASE_URL'])) {
                $errorMessage = 'DATABASE_URL environment variable is not set';
                error_log($errorMessage);
                throw new InvalidArgumentException($errorMessage);
            }

            try {
                self::$pdo = DatabaseFactory::createFromUrl($_ENV['DATABASE_URL']);
                
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