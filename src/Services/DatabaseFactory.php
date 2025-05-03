<?php

namespace App\Services;

// Use fully qualified name for PDO
use \PDO;
use InvalidArgumentException;

class DatabaseFactory
{
    /**
     * Create a new PDO instance from a DSN string or database URL
     *
     * @param string $dsn Database connection string or URL
     * @return \PDO
     * @throws InvalidArgumentException
     */
    public static function createPdo(string $dsn): \PDO
    {
        if (strpos($dsn, '://') !== false) {
            return self::createFromUrl($dsn);
        }
        
        throw new InvalidArgumentException('Invalid database connection string format');
    }
    
    /**
     * Create a PDO instance from a database URL (e.g. postgresql://user:pass@host:port/dbname)
     *
     * @param string $url Database URL
     * @return \PDO
     * @throws InvalidArgumentException
     */
    public static function createFromUrl(string $url): \PDO
    {
        $params = parse_url($url);
        
        if ($params === false) {
            throw new InvalidArgumentException('Invalid database URL format');
        }
        
        $driver = $params['scheme'] ?? '';
        $username = $params['user'] ?? '';
        $password = $params['pass'] ?? '';
        $host = $params['host'] ?? '';
        $port = $params['port'] ?? '';
        $dbName = isset($params['path']) ? ltrim($params['path'], '/') : '';
        
        if (empty($driver) || empty($host) || empty($dbName)) {
            throw new InvalidArgumentException('Missing required database connection parameters');
        }
        
        // Map URL scheme to PDO driver
        switch ($driver) {
            case 'postgresql':
            case 'postgres':
            case 'pgsql':
                $pdoDriver = 'pgsql';
                break;
            case 'mysql':
                $pdoDriver = 'mysql';
                break;
            case 'sqlite':
                $pdoDriver = 'sqlite';
                break;
            default:
                throw new InvalidArgumentException("Unsupported database driver: {$driver}");
        }
        
        // Build DSN
        if ($pdoDriver === 'sqlite') {
            $dsn = "sqlite:{$dbName}";
        } else {
            $port = $port ? ";port={$port}" : '';
            $dsn = "{$pdoDriver}:host={$host}{$port};dbname={$dbName}";
        }
        
        // Default PDO options for all connections
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new \PDO($dsn, $username, $password, $options);
    }
} 