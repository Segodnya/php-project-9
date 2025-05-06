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
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\PhpRenderer;

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
    if ($statusCode >= 200 && $statusCode < 300) {
        return '<span class="badge bg-success">' . $statusCode . '</span>';
    } elseif ($statusCode >= 300 && $statusCode < 400) {
        return '<span class="badge bg-info">' . $statusCode . '</span>';
    } elseif ($statusCode >= 400 && $statusCode < 500) {
        return '<span class="badge bg-warning">' . $statusCode . '</span>';
    } else {
        return '<span class="badge bg-danger">' . $statusCode . '</span>';
    }
}

/** @var Container $container */

// Register Flash messages
$container->set(Messages::class, function () {
    session_start();
    return new Messages();
});

// Register URL Service
$container->set(UrlService::class, function (Container $container) {
    return new UrlService();
});

// Register URL Checker Service
$container->set(UrlCheckerService::class, function (Container $container) {
    return new UrlCheckerService($container->get(UrlService::class));
});

// Register PHP View Renderer
$container->set(PhpRenderer::class, function (Container $container) {
    $viewsPath = dirname(__DIR__) . '/views';
    $renderer = new PhpRenderer($viewsPath);

    // Set default layout template
    $renderer->setLayout('layout.php');

    // Register global helper functions as attributes too
    $renderer->addAttribute('h', 'h');
    $renderer->addAttribute('formatDate', 'formatDate');
    $renderer->addAttribute('getStatusBadge', 'getStatusBadge');

    // Add flash messages to all views
    $renderer->addAttribute('flash', function () use ($container) {
        return $container->get(Messages::class);
    });

    return $renderer;
});

// Register Controllers
$container->set(HomeController::class, function (Container $container) {
    return new HomeController(
        $container->get(PhpRenderer::class)
    );
});

$container->set(UrlController::class, function (Container $container) {
    return new UrlController(
        $container->get(PhpRenderer::class),
        $container->get(UrlService::class),
        $container->get(UrlCheckerService::class),
        $container->get(Messages::class),
        $container->get(RouteParserInterface::class)
    );
});
