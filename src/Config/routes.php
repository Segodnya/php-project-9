<?php

namespace App\Config;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use App\Controllers\HomeController;
use App\Controllers\UrlController;
use App\Controllers\UrlCheckController;
use App\Controllers\ApiController;

class Routes
{
    public static function register(App $app): void
    {
        // Define routes with middleware
        $app->group('', function (RouteCollectorProxyInterface $group) {
            // Home routes
            $group->get('/', [HomeController::class, 'index'])->setName('home');

            // URL routes
            $group->get('/urls', [UrlController::class, 'index'])->setName('urls.index');
            $group->post('/urls', [UrlController::class, 'store'])->setName('urls.store');
            $group->get('/urls/{id:[0-9]+}', [UrlController::class, 'show'])->setName('urls.show');

            // URL Check routes
            $group->post('/urls/{id:[0-9]+}/checks', [UrlCheckController::class, 'store'])->setName('urls.checks.store');
        });

        // API routes
        $app->group('/api', function (RouteCollectorProxyInterface $group) {
            // URL API endpoints
            $group->get('/urls', [ApiController::class, 'getUrls']);
            $group->get('/urls/{id:[0-9]+}', [ApiController::class, 'getUrl']);
        });
    }
}