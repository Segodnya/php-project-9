<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use App\Services\DatabaseConnection;
use App\Services\RequestHandler;
use App\Repositories\UrlRepository;
use App\Repositories\UrlCheckRepository;
use App\Services\Analyzer;
use App\Services\ResponseBuilder;
use App\Middleware\ErrorHandlerMiddleware;
use App\Validation\UrlValidator;
use App\PDO as AppPDO;
use Slim\Psr7\Factory\ResponseFactory;

// Create Container Builder
$containerBuilder = new ContainerBuilder();

// Enable Compilation for performance in production
if ($_ENV['APP_ENV'] ?? 'development' === 'production') {
    $containerBuilder->enableCompilation(__DIR__ . '/../../var/cache');
    $containerBuilder->writeProxiesToFile(true, __DIR__ . '/../../var/cache/proxies');
}

// Load controller definitions
$controllerDefinitions = require __DIR__ . '/controllers.php';

// Define container definitions
$containerBuilder->addDefinitions(array_merge([
    // Core services
    'settings' => function () {
        return [
            'displayErrorDetails' => ($_ENV['APP_ENV'] ?? 'development') !== 'production',
            'logErrors' => true,
            'logErrorDetails' => true,
        ];
    },

    // Factory for creating responses
    ResponseFactory::class => function () {
        return new ResponseFactory();
    },

    // Response builder
    ResponseBuilder::class => function (ContainerInterface $c) {
        return new ResponseBuilder(
            $c->get(ResponseFactory::class),
            $c->get(PhpRenderer::class)
        );
    },

    // Request handler
    RequestHandler::class => function () {
        return new RequestHandler();
    },

    // Error handler middleware
    ErrorHandlerMiddleware::class => function (ContainerInterface $c) {
        return new ErrorHandlerMiddleware(
            $c->get(ResponseBuilder::class),
            $c->get('settings')['displayErrorDetails']
        );
    },

    // View renderer
    PhpRenderer::class => function () {
        $phpView = new PhpRenderer(__DIR__ . '/../../templates');
        $phpView->setLayout('layout.phtml');
        return $phpView;
    },
    'renderer' => function (ContainerInterface $c) {
        return $c->get(PhpRenderer::class);
    },

    // Flash messages
    Messages::class => function () {
        return new Messages();
    },
    'flash' => function (ContainerInterface $c) {
        return $c->get(Messages::class);
    },

    // Database connection - using fully qualified name for PDO
    'PDO' => function () {
        return DatabaseConnection::get();
    },

    \PDO::class => function (ContainerInterface $c) {
        return $c->get('PDO');
    },

    'pdo' => function (ContainerInterface $c) {
        return $c->get('PDO');
    },

    // Use our App\PDO wrapper class that proxies to the real PDO
    AppPDO::class => function (ContainerInterface $c) {
        $pdo = $c->get('PDO');
        return AppPDO::fromPdo($pdo);
    },

    // Repositories
    UrlRepository::class => function (ContainerInterface $c) {
        // Use the App\PDO class for repository constructor
        return new UrlRepository($c->get(AppPDO::class));
    },
    'url_repository' => function (ContainerInterface $c) {
        return $c->get(UrlRepository::class);
    },

    UrlCheckRepository::class => function (ContainerInterface $c) {
        // Use the App\PDO class for repository constructor
        return new UrlCheckRepository($c->get(AppPDO::class));
    },
    'url_check_repository' => function (ContainerInterface $c) {
        return $c->get(UrlCheckRepository::class);
    },

    // Services
    Analyzer::class => function () {
        return new Analyzer();
    },
    'analyzer' => function (ContainerInterface $c) {
        return $c->get(Analyzer::class);
    },

    // Validators
    UrlValidator::class => function () {
        return new UrlValidator();
    }
], $controllerDefinitions));

// Build and return the container
return $containerBuilder->build();