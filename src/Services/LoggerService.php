<?php

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Level;
use Throwable;

/**
 * Service for handling application logging
 */
class LoggerService
{
    private Logger $logger;

    /**
     * Initialize the logger
     */
    public function __construct(string $name = 'app')
    {
        $this->logger = new Logger($name);

        // Configure logger based on environment
        $this->configureLogger();
    }

    /**
     * Configure the logger with appropriate handlers and processors
     */
    private function configureLogger(): void
    {
        $logDir = dirname(__DIR__, 2) . '/var/logs';

        // Make sure log directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Create the line formatter
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat);

        // Add rotating file handler for errors (7 days of logs, only errors and above)
        $errorHandler = new RotatingFileHandler(
            $logDir . '/error.log',
            7,
            Level::Error
        );
        $errorHandler->setFormatter($formatter);

        // Add stream handler for all logs in development
        $debugHandler = new StreamHandler(
            $logDir . '/debug.log',
            $_ENV['APP_ENV'] === 'development' ? Level::Debug : Level::Info
        );
        $debugHandler->setFormatter($formatter);

        // Add processors for additional context
        $this->logger->pushProcessor(new IntrospectionProcessor());
        $this->logger->pushProcessor(new WebProcessor());

        // Add handlers
        $this->logger->pushHandler($errorHandler);
        $this->logger->pushHandler($debugHandler);
    }

    /**
     * Log an emergency message
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * Log an alert message
     */
    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * Log a critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Log an error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * Log a warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Log a notice message
     */
    public function notice(string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * Log an info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Log a debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * Log an exception with full details
     */
    public function logException(Throwable $exception, array $context = []): void
    {
        $context = array_merge($context, [
            'exception' => [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        ]);

        $this->error('Exception: ' . $exception->getMessage(), $context);
    }
}