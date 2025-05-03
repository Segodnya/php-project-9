<?php

namespace App\Config;

use Slim\App;
use Psr\Container\ContainerInterface;

class Middleware
{
    public static function register(App $app, ContainerInterface $container): void
    {
        // Get settings
        $settings = $container->get('settings');

        // Add Error Middleware
        $app->addErrorMiddleware(
            $settings['displayErrorDetails'],
            $settings['logErrors'],
            $settings['logErrorDetails']
        );

        // Add other middleware here as needed
        // Example: $app->add(SomeMiddleware::class);
    }
} 