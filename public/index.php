<?php

// Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use DI\Container;
use App\Services\DatabaseConnection;
use App\Repositories\UrlRepository;
use App\Repositories\UrlCheckRepository;
use App\Services\Analyzer;

session_start();

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // No .env file, continue with default environment
}

// Create Container
$container = new Container();

// Configure the renderer with the layout
$container->set('renderer', function () {
    // Create a new PhpRenderer instance with the templates directory
    $phpView = new PhpRenderer(__DIR__ . '/../templates');

    // Explicitly set the layout file to use for all templates
    $phpView->setLayout('layout.phtml');

    return $phpView;
});

$container->set('flash', function () {
    return new Messages();
});

$container->set('pdo', function () {
    return DatabaseConnection::get();
});

$container->set('url_repository', function ($c) {
    return new UrlRepository($c->get('pdo'));
});

$container->set('url_check_repository', function ($c) {
    return new UrlCheckRepository($c->get('pdo'));
});

$container->set('analyzer', function () {
    return new Analyzer();
});

// Create App with Container
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Register routes
$routes = require __DIR__ . '/../src/Routes/web.php';
$routes($app);

$app->run();
