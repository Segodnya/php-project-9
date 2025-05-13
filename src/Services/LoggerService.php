<?php

/**
 * Logger Service
 *
 * Provides centralized logging functionality
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

/**
 * LoggerService class for handling application logging
 */
class LoggerService
{
    /**
     * @var string $logFile Path to the log file
     */
    private string $logFile;

    /**
     * Constructor
     *
     * @param string|null $logFile Custom log file path (optional)
     */
    public function __construct(?string $logFile = null)
    {
        // Use the provided log file or default to the system error log
        $this->logFile = $logFile ?: '';
    }

    /**
     * Log an error message
     *
     * @param string     $message Error message
     * @param \Throwable $e       Exception object (optional)
     * @return void
     */
    public function error(string $message, ?\Throwable $e = null): void
    {
        $logMessage = "[ERROR] {$message}";

        if ($e !== null) {
            $logMessage .= "\nException: " . $e->getMessage();
            $logMessage .= "\nFile: " . $e->getFile() . " (Line: " . $e->getLine() . ")";
            $logMessage .= "\nStack Trace: " . $e->getTraceAsString();
        }

        $this->log($logMessage);
    }

    /**
     * Log an info message
     *
     * @param string $message Info message
     * @return void
     */
    public function info(string $message): void
    {
        $this->log("[INFO] {$message}");
    }

    /**
     * Log a warning message
     *
     * @param string $message Warning message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->log("[WARNING] {$message}");
    }

    /**
     * Log a message
     *
     * @param string $message Message to log
     * @return void
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[{$timestamp}] {$message}" . PHP_EOL;

        if (!empty($this->logFile)) {
            // Log to custom file if specified
            file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
        } else {
            // Otherwise use error_log
            error_log($formattedMessage);
        }
    }
}
