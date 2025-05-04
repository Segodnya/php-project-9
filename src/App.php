<?php

namespace App;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Exception;

class App
{
    private SlimApp $app;
    private Container $container;

    /**
     * Initialize the application
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     */
    public function __construct()
    {
        try {
            // Load environment variables
            $this->loadEnvironment();

            // Create container
            $this->container = $this->createContainer();

            // Create app
            $this->app = $this->createApp();

            // Register middleware
            $this->registerMiddleware();

            // Register routes
            $this->registerRoutes();
        } catch (Exception $e) {
            // Log the error and rethrow for higher level handling
            error_log('Application initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment(): void
    {
        try {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            // No .env file, continue with default environment
            error_log('No .env file found, using default environment variables');
        }

        // Set default environment variables if not already set
        if (!isset($_ENV['APP_ENV'])) {
            $_ENV['APP_ENV'] = 'development';
        }
    }

    /**
     * Create the dependency injection container
     *
     * @return Container
     * @throws Exception
     */
    private function createContainer(): Container
    {
        try {
            return require dirname(__DIR__) . '/src/Config/container.php';
        } catch (Exception $e) {
            error_log('Container creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create the Slim application instance
     *
     * @return SlimApp
     */
    private function createApp(): SlimApp
    {
        AppFactory::setContainer($this->container);
        return AppFactory::create();
    }

    /**
     * Register middleware with the application
     */
    private function registerMiddleware(): void
    {
        $middlewareClass = \App\Config\Middleware::class;
        $middlewareClass::register($this->app, $this->container);
    }

    /**
     * Register routes with the application
     */
    private function registerRoutes(): void
    {
        $routes = require dirname(__DIR__) . '/src/Routes/web.php';
        $routes($this->app);
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * Get the Slim application instance
     *
     * @return SlimApp
     */
    public function getApp(): SlimApp
    {
        return $this->app;
    }

    /**
     * Get the DI container
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}