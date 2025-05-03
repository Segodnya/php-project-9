<?php

// Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use App\App;
use DI\NotFoundException;
use DI\DependencyException;

// Start session
session_start();

try {
    // Create and run the application
    $app = new App();
    $app->run();
} catch (NotFoundException $e) {
    // Handle container resolution errors (most likely due to missing classes/dependencies)
    http_response_code(500);
    echo "<h1>Dependency Injection Error</h1>";
    echo "<p>An error occurred while resolving a dependency:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Check your container configuration.</p>";

    // Log the error
    error_log('Container Resolution Error: ' . $e->getMessage());
} catch (DependencyException $e) {
    // Handle dependency errors
    http_response_code(500);
    echo "<h1>Dependency Injection Error</h1>";
    echo "<p>An error occurred with dependency injection:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";

    // Log the error
    error_log('Dependency Injection Error: ' . $e->getMessage());
} catch (Exception $e) {
    // Handle all other exceptions
    http_response_code(500);
    echo "<h1>Application Error</h1>";
    echo "<p>An unexpected error occurred:</p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";

    // Log the error
    error_log('Unexpected Application Error: ' . $e->getMessage());
}
