<?php

// renamed
use Psr\Container\ContainerInterface;
use App\Controllers\HomeController;
use App\Controllers\UrlController;
use App\Controllers\UrlCheckController;

return [
        // Controller factory definitions
    HomeController::class => function (ContainerInterface $container) {
        return new HomeController($container);
    },

    UrlController::class => function (ContainerInterface $container) {
        return new UrlController($container);
    },

    UrlCheckController::class => function (ContainerInterface $container) {
        return new UrlCheckController($container);
    },
];