<?php

use Slim\App;
use App\Config\Routes;

return function (App $app) {
    // Register all application routes
    Routes::register($app);
};