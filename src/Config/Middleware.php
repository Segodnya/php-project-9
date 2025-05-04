<?php

// renamed

namespace App\Config;

use Slim\App;
use Psr\Container\ContainerInterface;
use App\Middleware\ErrorHandlerMiddleware;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

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

        // Add Flash middleware to refresh flash messages for each request
        $app->add(function (Request $request, RequestHandler $handler) use ($container) {
            $twig = $container->get(Twig::class);
            $flash = $container->get(Messages::class);
            $twig->getEnvironment()->addGlobal('flash', $flash->getMessages());

            return $handler->handle($request);
        });

        // Add Twig Middleware using the factory
        $twigMiddlewareFactory = $container->get('twig-middleware-factory');
        $app->add($twigMiddlewareFactory($app));

        // Add other middleware here as needed
        // Example: $app->add(SomeMiddleware::class);
    }
}