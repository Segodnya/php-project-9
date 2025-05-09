<?php

/**
 * Dependencies configuration
 *
 * This file configures the application container
 * PHP version 8.0
 *
 * @category Configuration
 * @package  PageAnalyzer
 */

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\UrlController;
use App\Services\UrlCheckerService;
use App\Services\UrlService;
use DI\Container;
use Slim\App;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;
use Twig\TwigFunction;

/**
 * HTML escape function
 *
 * @param mixed $text Text to escape
 * @return string HTML escaped text
 */
function h(mixed $text): string
{
    // Handle null values
    if ($text === null) {
        return '';
    }

    // Convert non-string inputs to string before passing to htmlspecialchars
    if (!is_string($text)) {
        // Convert different types appropriately
        if (is_bool($text)) {
            return $text ? 'true' : 'false';
        } elseif (is_array($text) || is_object($text)) {
            // For arrays and objects, use json_encode for a safer representation
            $encodedText = json_encode($text);
            return htmlspecialchars($encodedText !== false ? $encodedText : '[Uncoded value]', ENT_QUOTES, 'UTF-8');
        } elseif (is_int($text) || is_float($text)) {
            // For numeric types, use string representation
            return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
        } else {
            // For any other type, fallback to an empty string
            return '';
        }
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a date string
 *
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate(string $date): string
{
    if (empty($date)) {
        return 'Invalid date';
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Invalid date';
    }
    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Get a status badge HTML for a status code
 *
 * @param int $statusCode HTTP status code
 * @return string HTML for the badge
 */
function getStatusBadge(int $statusCode): string
{
    return match (true) {
        $statusCode >= 200 && $statusCode < 300 => "<span class=\"badge bg-success\">{$statusCode}</span>",
        $statusCode >= 300 && $statusCode < 400 => "<span class=\"badge bg-info\">{$statusCode}</span>",
        $statusCode >= 400 && $statusCode < 500 => "<span class=\"badge bg-warning\">{$statusCode}</span>",
        default => "<span class=\"badge bg-danger\">{$statusCode}</span>",
    };
}

/**
 * Configure application dependencies
 *
 * @param App<\DI\Container> $app The Slim application instance
 * @return void
 */
function configureDependencies(App $app): void
{
    /** @var Container $container */
    $container = $app->getContainer();

    /** @phpstan-ignore-next-line */
    if (!$container) {
        throw new \RuntimeException('Container not available');
    }

    // Register Flash messages
    $container->set(Messages::class, function () {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return new Messages();
    });

    // Register Route Parser (using the app instance passed as parameter)
    $container->set(RouteParserInterface::class, function () use ($app) {
        return $app->getRouteCollector()->getRouteParser();
    });

    // Register URL Service
    $container->set(UrlService::class, function (Container $container) {
        return new UrlService();
    });

    // Register URL Checker Service
    $container->set(UrlCheckerService::class, function (Container $container) {
        return new UrlCheckerService($container->get(UrlService::class));
    });

    // Register Twig View Renderer
    $container->set(Twig::class, function (Container $container) {
        $viewsPath = dirname(__DIR__) . '/views';

        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        $cachePath = $isProduction ? dirname(__DIR__) . '/tmp/cache' : false;

        $twig = Twig::create($viewsPath, [
            'cache' => $cachePath,
            'debug' => $isProduction ? false : true
        ]);

        // Register global helper functions
        $twig->getEnvironment()->addFunction(
            new TwigFunction('h', 'h', ['is_safe' => ['html']])
        );

        $twig->getEnvironment()->addFunction(
            new TwigFunction('formatDate', fn($date) => formatDate($date))
        );

        $twig->getEnvironment()->addFunction(
            new TwigFunction('getStatusBadge', fn($statusCode) => getStatusBadge($statusCode), ['is_safe' => ['html']])
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
            $container->get(UrlCheckerService::class),
            $container->get(Messages::class),
            $container->get(RouteParserInterface::class)
        );
    });
}
