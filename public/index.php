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

// Configure dependencies
$dependenciesPath = __DIR__ . '/../src/dependencies.php';
require_once $dependenciesPath;
// Import the configureDependencies function
if (!function_exists('configureDependencies')) {
    throw new \RuntimeException('configureDependencies function not found');
}
configureDependencies($app);

// Add middleware
$middlewarePath = __DIR__ . '/../src/middleware.php';
require_once $middlewarePath;
// Import the setupMiddleware function
if (!function_exists('setupMiddleware')) {
    throw new \RuntimeException('setupMiddleware function not found');
}
setupMiddleware($app);

// Register routes
require_once __DIR__ . '/../src/routes.php';

// Run app
$app->run();
