<?php

/**
 * Database Migration Script
 *
 * This script connects to the PostgreSQL database using the DATABASE_URL
 * from the .env file and runs all migrations from the database.sql file.
 *
 * NOTE: This script is designed to run in the same environment as your application.
 * If you're trying to connect to a Render.com database from your local machine,
 * you might face connection issues due to network/firewall restrictions.
 * In that case, deploy this script or run it from your Render.com environment.
 */

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Composer's autoloader to use dotenv
require_once __DIR__ . '/vendor/autoload.php';

echo "Starting database migration process...\n";

// Load environment variables from .env file or use environment variables if available
try {
    // First try to load from .env file
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
        echo "Environment variables loaded from .env file.\n";
    } else {
        echo "No .env file found, using environment variables.\n";
    }
} catch (\Dotenv\Exception\InvalidPathException $e) {
    echo "No .env file found, using environment variables.\n";
}

// Check if DATABASE_URL is set (either in .env or environment)
$databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
if (empty($databaseUrl)) {
    die("Error: DATABASE_URL is not set in .env file or environment variables.\n");
}

echo "Using DATABASE_URL: " . $databaseUrl . "\n";

// Parse DATABASE_URL
$params = parse_url($databaseUrl);
if ($params === false) {
    die("Error: Invalid DATABASE_URL format.\n");
}

// Extract connection parameters
$username = $params['user'] ?? '';
$password = $params['pass'] ?? '';
$host = $params['host'] ?? '';
$port = $params['port'] ?? '5432';
$dbName = isset($params['path']) ? ltrim($params['path'], '/') : '';

// Validate connection parameters
if (empty($host) || empty($dbName)) {
    die("Error: Missing required database connection parameters.\n");
}

// Display connection info (without password)
echo "Connecting to PostgreSQL database:\n";
echo "- Host: $host\n";
echo "- Port: $port\n";
echo "- Database: $dbName\n";
echo "- Username: $username\n";

// Always use 'pgsql' as PDO driver for PostgreSQL
$dsn = "pgsql:host={$host};port={$port};dbname={$dbName}";

try {
    // Connect to database
    echo "Attempting to connect to database...\n";

    // Set a connection timeout
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    echo "Connection successful!\n";

    // Test basic connectivity
    $stmt = $pdo->query("SELECT 1 as test");
    if ($stmt !== false) {
        $result = $stmt->fetch();
        echo "Database connectivity test: " . ($result['test'] ?? 'failed') . "\n";
    } else {
        echo "Database connectivity test failed.\n";
    }

    // Load the SQL file
    $sqlPath = __DIR__ . '/database.sql';
    if (!file_exists($sqlPath)) {
        die("Error: database.sql file not found at $sqlPath\n");
    }

    $sql = file_get_contents($sqlPath);
    if ($sql === false) {
        die("Error: Failed to read database.sql file.\n");
    }

    echo "\nLoaded migration file: database.sql (" . strlen($sql) . " bytes)\n";

    // Split SQL into individual statements
    // This handles the fact that PDO can't execute multiple statements at once
    echo "Executing migrations...\n";

    // Split statements by semicolon
    $statements = array_filter(
        array_map(
            'trim',
            explode(';', $sql)
        ),
        function ($statement) {
            return $statement !== '';
        }
    );

    $statementCount = count($statements);
    echo "Found " . $statementCount . " SQL statement" . ($statementCount !== 1 ? 's' : '') . " to execute.\n\n";

    if ($statementCount === 0) {
        echo "No SQL statements found to execute. Exiting.\n";
        exit(0);
    }

    // Begin transaction for all migrations
    $pdo->beginTransaction();

    try {
        // Validate SQL statements before executing them
        echo "Validating SQL statements...\n";
        $invalidStatements = 0;

        foreach ($statements as $index => $statement) {
            // Basic validation: ensure statements have minimum required keywords
            $lowercaseStmt = strtolower($statement);
            if (
                !preg_match('/\b(create|alter|drop|insert|update|delete|select)\b/i', $lowercaseStmt) &&
                !preg_match('/\b(table|index|constraint|trigger|view|function|sequence)\b/i', $lowercaseStmt)
            ) {
                echo "Warning: Statement [" . ($index + 1) . "] may not be valid SQL: " . substr($statement, 0, 50) . "...\n";
                $invalidStatements++;
            }
        }

        if ($invalidStatements > 0) {
            echo "Found $invalidStatements potentially invalid SQL statements. Proceed with caution.\n";
        } else {
            echo "All statements appear to be valid SQL commands.\n";
        }

        echo "\nExecuting SQL statements...\n";
        foreach ($statements as $index => $statement) {
            // Show a preview of the statement
            $preview = substr($statement, 0, 50) . (strlen($statement) > 50 ? '...' : '');
            echo "Executing [" . ($index + 1) . "]: " . $preview . "\n";

            // Execute the statement
            $pdo->exec($statement);
        }

        // Commit all changes
        $pdo->commit();
        echo "\nAll migrations completed successfully!\n";
    } catch (PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        echo "Migration failed: " . $e->getMessage() . "\n";

        // If error contains "already exists", it might be okay
        if (stripos($e->getMessage(), 'already exists') !== false) {
            echo "This may be fine if you're running migrations on an existing database.\n";
            echo "Tables/indexes may already exist.\n";
        } else {
            die("Migration process stopped due to errors.\n");
        }
    }

    // Show existing tables in the database
    echo "\nCurrent database structure:\n";

    try {
        $tables = $pdo->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'public'
            ORDER BY table_name
        ")->fetchAll();

        if (empty($tables)) {
            echo "No tables found in the database.\n";
        } else {
            echo "Tables in database:\n";
            foreach ($tables as $table) {
                echo "- " . $table['table_name'] . "\n";

                // Show columns for each table
                try {
                    $columns = $pdo->query("
                        SELECT column_name, data_type, character_maximum_length
                        FROM information_schema.columns
                        WHERE table_name = '{$table['table_name']}'
                        ORDER BY ordinal_position
                    ")->fetchAll();

                    foreach ($columns as $column) {
                        $type = $column['data_type'];
                        if ($column['character_maximum_length']) {
                            $type .= "({$column['character_maximum_length']})";
                        }
                        echo "  • {$column['column_name']} - {$type}\n";
                    }
                } catch (PDOException $e) {
                    echo "  • Error reading columns: " . $e->getMessage() . "\n";
                }
                echo "\n";
            }
        }
    } catch (PDOException $e) {
        echo "Error reading database structure: " . $e->getMessage() . "\n";
    }

    echo "Migration process completed.\n";
} catch (PDOException $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";

    // Additional error diagnostics
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "The pgsql driver is not installed or enabled in your PHP installation.\n";
        echo "Please install the PHP PostgreSQL extension.\n";
    } elseif (strpos($e->getMessage(), 'password authentication failed') !== false) {
        echo "Check your username and password in DATABASE_URL.\n";
    } elseif (strpos($e->getMessage(), 'timeout') !== false) {
        echo "Connection timeout. Check if the database server is reachable.\n";
        echo "Note: Render.com databases may be configured to only accept connections from your Render.com app.\n";
        echo "This script should be deployed to the same environment as your application.\n";
    }
}
