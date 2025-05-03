<?php

use Slim\App;
use App\Controllers\HomeController;
use App\Controllers\UrlController;
use App\Controllers\UrlCheckController;

return function (App $app) {
    // Home routes
    $app->get('/', [HomeController::class, 'index'])->setName('home');

    // URL routes
    $app->get('/urls', [UrlController::class, 'index'])->setName('urls.index');
    $app->post('/urls', [UrlController::class, 'store'])->setName('urls.store');
    $app->get('/urls/{id:[0-9]+}', [UrlController::class, 'show'])->setName('urls.show');

    // URL Check routes
    $app->post('/urls/{id:[0-9]+}/checks', [UrlCheckController::class, 'store'])->setName('urls.checks.store');
}; 