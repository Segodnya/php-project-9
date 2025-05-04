<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\DatabaseConnection;

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    echo "Warning: No .env file found, using environment variables instead.\n";
}

echo "Attempting to connect to the database...\n";
echo "Database URL: " . (isset($_ENV['DATABASE_URL']) ? $_ENV['DATABASE_URL'] : 'Not set') . "\n";

try {
    // Try to get a database connection
    $pdo = DatabaseConnection::get();

    // If we get here, the connection was successful
    echo "Connection successful!\n";

    // Test executing a query
    $stmt = $pdo->query("SELECT current_timestamp as now");
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);

    echo "Current database time: " . $result['now'] . "\n";

    // Check database type
    $dbType = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    echo "Database type: " . $dbType . "\n";

    // Check if tables exist
    echo "Checking database tables...\n";

    $foundTables = [];

    if ($dbType === 'sqlite') {
        // SQLite specific query
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $tables->fetch(\PDO::FETCH_ASSOC)) {
            $foundTables[] = $row['name'];
        }
    } else {
        // PostgreSQL specific query
        $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
        while ($row = $tables->fetch(\PDO::FETCH_ASSOC)) {
            $foundTables[] = $row['table_name'];
        }
    }

    echo "Found tables: " . implode(", ", $foundTables) . "\n";

    // Check if the required tables exist
    $requiredTables = ['urls', 'url_checks'];
    $missingTables = array_diff($requiredTables, $foundTables);

    if (empty($missingTables)) {
        echo "All required tables exist!\n";
    } else {
        echo "Missing tables: " . implode(", ", $missingTables) . "\n";
        echo "Please make sure the database schema is properly initialized.\n";
    }

} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage() . "\n";

    // If there's a previous exception, show that too
    if ($e->getPrevious()) {
        echo "Caused by: " . $e->getPrevious()->getMessage() . "\n";
    }

    // Provide troubleshooting tips
    echo "\nTroubleshooting tips:\n";

    if (isset($_ENV['DATABASE_URL'])) {
        echo "1. Make sure your database server is running\n";
        echo "2. Check the DATABASE_URL environment variable is correct\n";
        echo "3. Try connecting to the database manually\n";
    } else {
        echo "1. Make sure the database.sql file exists in the project root\n";
        echo "2. Check if the database.sqlite file is writable\n";
        echo "3. Try removing the database.sqlite file and restart the application\n";
    }
}