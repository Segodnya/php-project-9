<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use App\Services\DatabaseConnection;
use App\Services\RequestHandler;
use App\Repositories\UrlRepository;
use App\Repositories\UrlCheckRepository;
use App\Services\Analyzer;
use App\Services\ResponseBuilder;
use App\Services\ViewService;
use App\Services\ViewHelpers;
use App\Middleware\ErrorHandlerMiddleware;
use App\Validation\UrlValidator;
use App\PDO as AppPDO;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\App;

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

    // View service and Twig setup
    Twig::class => function (ContainerInterface $c) {
        $twig = Twig::create(
            __DIR__ . '/../../templates',
            [
                'cache' => ($_ENV['APP_ENV'] ?? 'development') === 'production' 
                    ? __DIR__ . '/../../var/cache/twig' 
                    : false,
                'auto_reload' => true,
                'debug' => ($_ENV['APP_ENV'] ?? 'development') !== 'production',
            ]
        );
        
        // Add extensions
        $twig->getEnvironment()->addExtension(new ViewHelpers());
        
        // Add flash messages as a global variable
        $flash = $c->get(Messages::class);
        $twig->getEnvironment()->addGlobal('flash', $flash->getMessages());
        
        // Add current path as a global variable
        $twig->getEnvironment()->addGlobal('currentPath', $_SERVER['REQUEST_URI'] ?? '/');
        
        return $twig;
    },
    
    ViewService::class => function (ContainerInterface $c) {
        return new ViewService($c->get(Twig::class));
    },
    
    // The TwigMiddleware factory now returns a factory function that takes an App instance
    // This breaks the circular dependency
    'twig-middleware-factory' => function (ContainerInterface $c) {
        return function (App $app) use ($c) {
            return TwigMiddleware::createFromContainer($app, Twig::class);
        };
    },
    
    // Legacy PhpRenderer for backward compatibility during transition
    PhpRenderer::class => function () {
        $phpView = new PhpRenderer(__DIR__ . '/../../templates');
        $phpView->setLayout('layout.phtml');
        return $phpView;
    },
    'renderer' => function (ContainerInterface $c) {
        return $c->get(PhpRenderer::class);
    },

    // Response builder
    ResponseBuilder::class => function (ContainerInterface $c) {
        return new ResponseBuilder(
            $c->get(ResponseFactory::class),
            $c->get(ViewService::class)
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