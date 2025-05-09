<?php

/**
 * Application middleware
 *
 * This file sets up all application middleware
 * PHP version 8.0
 *
 * @category Configuration
 * @package  PageAnalyzer
 */

declare(strict_types=1);

use DI\Container;
use Slim\App;
use Slim\Handlers\ErrorHandler;
use Slim\Views\Twig;

/**
 * Setup application middleware
 *
 * @param App<\DI\Container> $app Slim application instance
 * @return void
 */
function setupMiddleware(App $app): void
{
    $container = $app->getContainer();

    // Add Error Middleware
    $errorMiddleware = $app->addErrorMiddleware(
        true,
        true,
        true
    );

    // Add custom error handler
    /** @var ErrorHandler $errorHandler */
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    $errorHandler->registerErrorRenderer('text/html', function ($exception, $request) use ($container) {
        $response = new \Slim\Psr7\Response();

        // Log the error
        error_log((string)$exception);

        // Render the error template
        $statusCode = 500;

        /** @var Container $container */
        $view = $container->get(Twig::class);

        return $view->render(
            $response->withStatus($statusCode),
            'errors/500.twig',
            [
                'error' => $exception->getMessage()
            ]
        );
    });
}
