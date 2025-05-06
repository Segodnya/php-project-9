<?php

/**
 * Main application entry point
 *
 * This file contains both declarations and side effects.
 * PHP version 8.0
 *
 * @category Application
 * @package  PageAnalyzer
 * @phpcs:ignoreFile PSR1.Files.SideEffects
 */

declare(strict_types=1);

// Start session
session_start();

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Create Container
$container = new \DI\Container();

// Set container to create App with
\Slim\Factory\AppFactory::setContainer($container);

// Create App
$app = \Slim\Factory\AppFactory::create();

// Set base path if needed (useful for subdirectory installations)
$basePath = getenv('APP_BASE_PATH') ?: '';
if (!empty($basePath)) {
    $app->setBasePath($basePath);
}

// Configure dependencies
require_once __DIR__ . '/../src/dependencies.php';

// Add middleware
require_once __DIR__ . '/../src/middleware.php';
setupMiddleware($app);

// Register routes
require_once __DIR__ . '/../src/routes.php';

// Run app
$app->run();
