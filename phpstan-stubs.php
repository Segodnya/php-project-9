<?php

/**
 * PHPStan stubs to resolve missing function declarations
 *
 * This file is only used for static analysis and not included in runtime
 */

declare(strict_types=1);

if (!function_exists('configureDependencies')) {
    /**
     * Configure application dependencies
     *
     * @param \Slim\App $app The Slim application instance
     * @return void
     */
    function configureDependencies(\Slim\App $app): void {}
}

if (!function_exists('setupMiddleware')) {
    /**
     * Setup application middleware
     *
     * @param \Slim\App $app Slim application instance
     * @return void
     */
    function setupMiddleware(\Slim\App $app): void {}
}
