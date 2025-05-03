<?php

namespace App;

use PDO;

class Connection
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $databaseUrl = parse_url($_ENV['DATABASE_URL']);
            $username = $databaseUrl['user'];
            $password = $databaseUrl['pass'];
            $host = $databaseUrl['host'];
            $port = $databaseUrl['port'];
            $dbName = ltrim($databaseUrl['path'], '/');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$pdo = new PDO($dsn, $username, $password, $options);
        }

        return self::$pdo;
    }
}
