<?php

namespace App\Config;

use Slim\App;
use Psr\Container\ContainerInterface;
use App\Middleware\ErrorHandlerMiddleware;

class Middleware
{
    public static function register(App $app, ContainerInterface $container): void
    {
        // Get settings
        $settings = $container->get('settings');

        // Add ErrorHandlerMiddleware (must be added first to catch all exceptions)
        $app->add($container->get(ErrorHandlerMiddleware::class));

        // Add Error Middleware (Slim's internal error handler)
        // This is still useful for handling Slim-specific errors
        $app->addErrorMiddleware(
            $settings['displayErrorDetails'],
            $settings['logErrors'],
            $settings['logErrorDetails']
        );

        // Add Twig Middleware using the factory
        $twigMiddlewareFactory = $container->get('twig-middleware-factory');
        $app->add($twigMiddlewareFactory($app));

        // Add other middleware here as needed
        // Example: $app->add(SomeMiddleware::class);
    }
}