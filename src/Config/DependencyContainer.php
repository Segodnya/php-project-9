<?php

/**
 * Dependencies configuration
 *
 * This class configures the application container
 * PHP version 8.0
 *
 * @category Configuration
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Config;

use App\Controllers\HomeController;
use App\Controllers\UrlController;
use App\Database\Database;
use App\Repository\UrlCheckRepository;
use App\Repository\UrlRepository;
use App\Services\LogService;
use App\Services\UrlCheckService;
use App\Services\UrlService;
use App\Validators\UrlValidator;
use App\Utils\HtmlHelpers;
use DI\Container;
use PDO;
use Slim\App;
use Slim\Flash\Messages;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Response;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Views\Twig;
use Twig\TwigFunction;
use GuzzleHttp\Client;

/**
 * Class DependencyContainer
 *
 * @package App\Config
 */
class DependencyContainer
{
    /**
     * Configure application dependencies
     *
     * @param App<\DI\Container> $app The Slim application instance
     * @return void
     */
    public static function configure(App $app): void
    {
        /** @var Container $container */
        $container = $app->getContainer();

        /** @phpstan-ignore-next-line */
        if (!$container) {
            throw new \RuntimeException('Container not available');
        }

        // Register PDO database connection
        $container->set(PDO::class, function () {
            return Database::createPDO();
        });

        // Register Repository classes
        $container->set(UrlRepository::class, function (Container $container) {
            return new UrlRepository($container->get(PDO::class));
        });

        $container->set(UrlCheckRepository::class, function (Container $container) {
            return new UrlCheckRepository($container->get(PDO::class));
        });

        // Register Response factory components
        $container->set(ResponseFactory::class, function () {
            return new ResponseFactory();
        });

        $container->set(StreamFactory::class, function () {
            return new StreamFactory();
        });

        $container->set(DecoratedResponseFactory::class, function (Container $container) {
            return new DecoratedResponseFactory(
                $container->get(ResponseFactory::class),
                $container->get(StreamFactory::class)
            );
        });

        // Register HTTP Response
        $container->set(Response::class, function (Container $container) {
            return $container->get(DecoratedResponseFactory::class)->createResponse();
        });

        // Register Flash messages
        $container->set(Messages::class, function () {
            return new Messages();
        });

        // Register Route Parser (using the app instance passed as parameter)
        $container->set(RouteParserInterface::class, function () use ($app) {
            return $app->getRouteCollector()->getRouteParser();
        });

        // Register URL Validator
        $container->set(UrlValidator::class, function () {
            return new UrlValidator();
        });

        // Register Logger Service
        $container->set(LogService::class, function () {
            $logPath = null;

            // Define a custom log path for production environment
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                $logDir = dirname(__DIR__, 2) . '/logs';
                // Create logs directory if it doesn't exist
                if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
                }
                $logPath = "{$logDir}/app.log";
            }

            return new LogService($logPath);
        });

        // Register URL Service
        $container->set(UrlService::class, function (Container $container) {
            return new UrlService(
                $container->get(UrlRepository::class),
                $container->get(UrlValidator::class)
            );
        });

        // Register HTTP Client
        $container->set(Client::class, function () {
            return new Client([
                'timeout' => 10,
                'verify' => false,
                'http_errors' => true,
                'allow_redirects' => true
            ]);
        });

        // Register URL Checker Service
        $container->set(UrlCheckService::class, function (Container $container) {
            return new UrlCheckService(
                $container->get(UrlCheckRepository::class),
                $container->get(Client::class),
                $container->get(LogService::class)
            );
        });

        // Register Twig View Renderer
        $container->set(Twig::class, function (Container $container) {
            $viewsPath = dirname(__DIR__, 2) . '/views';

            $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
            $cachePath = $isProduction ? dirname(__DIR__, 2) . '/tmp/cache' : false;

            $twig = Twig::create($viewsPath, [
                'cache' => $cachePath,
                'debug' => $isProduction ? false : true
            ]);

            // Register global helper functions from Utils namespace
            $twig->getEnvironment()->addFunction(
                new TwigFunction('h', [HtmlHelpers::class, 'escapeHtml'], ['is_safe' => ['html']])
            );

            $twig->getEnvironment()->addFunction(
                new TwigFunction('formatDate', [HtmlHelpers::class, 'formatDate'])
            );

            $twig->getEnvironment()->addFunction(
                new TwigFunction('getStatusBadge', [HtmlHelpers::class, 'getStatusBadge'], ['is_safe' => ['html']])
            );

            // Add flash messages to all views
            $twig->getEnvironment()->addGlobal('flash', $container->get(Messages::class));

            return $twig;
        });

        // Register Controllers
        $container->set(HomeController::class, function (Container $container) {
            return new HomeController(
                $container->get(Twig::class)
            );
        });

        $container->set(UrlController::class, function (Container $container) {
            return new UrlController(
                $container->get(Twig::class),
                $container->get(UrlService::class),
                $container->get(UrlCheckService::class),
                $container->get(Messages::class),
                $container->get(RouteParserInterface::class),
                $container->get(Response::class),
                $container->get(LogService::class)
            );
        });
    }
}
