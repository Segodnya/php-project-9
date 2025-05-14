<?php

/**
 * Routes definition
 *
 * This class defines all the application routes
 * PHP version 8.0
 *
 * @category Configuration
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Config;

use App\Controllers\HomeController;
use App\Controllers\UrlController;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Class Routes
 *
 * @package App\Config
 */
class Routes
{
    /**
     * Register application routes
     *
     * @param App<\DI\Container> $app The Slim application instance
     * @return void
     */
    public static function register(App $app): void
    {
        // Home page
        $app->get('/', HomeController::class . ':index')->setName('home');

        // URL routes
        $app->group('/urls', function (RouteCollectorProxy $group) {
            // List all URLs
            $group->get('', UrlController::class . ':index')->setName('urls.index');

            // Create new URL (form submission)
            $group->post('', UrlController::class . ':store')->setName('urls.store');

            // Show URL details
            $group->get('/{id:[0-9]+}', UrlController::class . ':show')->setName('urls.show');

            // Run URL check
            $group->post('/{id:[0-9]+}/checks', UrlController::class . ':check')->setName('urls.check');
        });
    }
}
