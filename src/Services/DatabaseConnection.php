<?php

namespace App\Services;

// Use fully qualified name for PDO
use \PDO;
use InvalidArgumentException;

class DatabaseConnection
{
    private static ?\PDO $pdo = null;

    /**
     * Get a PDO connection instance
     *
     * @return \PDO
     * @throws InvalidArgumentException
     */
    public static function get(): \PDO
    {
        if (self::$pdo === null) {
            if (!isset($_ENV['DATABASE_URL'])) {
                throw new InvalidArgumentException('DATABASE_URL environment variable is not set');
            }
            
            self::$pdo = DatabaseFactory::createFromUrl($_ENV['DATABASE_URL']);
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