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
/** @var \Slim\App<\DI\Container> $app */
$app = \Slim\Factory\AppFactory::create();

// Configure dependencies
\App\Config\DependencyContainer::configure($app);

// Add middleware
\App\Config\Middleware::setup($app);

// Register routes
\App\Config\Routes::register($app);

// Run app
$app->run();
