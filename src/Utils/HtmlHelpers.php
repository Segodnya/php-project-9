<?php

/**
 * HTML Utility Helper Functions
 *
 * This file contains various helper functions for HTML formatting and display
 * PHP version 8.0
 *
 * @category Utils
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Utils;

/**
 * HtmlHelpers class provides utility methods for HTML formatting
 */
class HtmlHelpers
{
    /**
     * HTML escape function
     *
     * @param mixed $text Text to escape
     * @return string HTML escaped text
     */
    public static function escapeHtml(mixed $text): string
    {
        // Handle null values
        if ($text === null) {
            return '';
        }

        // Convert non-string inputs to string before passing to htmlspecialchars
        if (!is_string($text)) {
            // Convert different types appropriately
            if (is_bool($text)) {
                return $text ? 'true' : 'false';
            } elseif (is_array($text) || is_object($text)) {
                // For arrays and objects, use json_encode for a safer representation
                $encodedText = json_encode($text);
                return htmlspecialchars($encodedText !== false ? $encodedText : '[Uncoded value]', ENT_QUOTES, 'UTF-8');
            } elseif (is_int($text) || is_float($text)) {
                // For numeric types, use string representation
                return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
            } else {
                // For any other type, fallback to an empty string
                return '';
            }
        }

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format a date string
     *
     * @param string $date Date string
     * @return string Formatted date
     */
    public static function formatDate(string $date): string
    {
        if (empty($date)) {
            return 'Invalid date';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return 'Invalid date';
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Get a status badge HTML for a status code
     *
     * @param int $statusCode HTTP status code
     * @return string HTML for the badge
     */
    public static function getStatusBadge(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => "<span class=\"badge bg-success\">{$statusCode}</span>",
            $statusCode >= 300 && $statusCode < 400 => "<span class=\"badge bg-info\">{$statusCode}</span>",
            $statusCode >= 400 && $statusCode < 500 => "<span class=\"badge bg-warning\">{$statusCode}</span>",
            default => "<span class=\"badge bg-danger\">{$statusCode}</span>",
        };
    }
}
