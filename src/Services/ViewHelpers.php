<?php

namespace App\Services;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides helper functions for templates
 */
class ViewHelpers extends AbstractExtension
{
    /**
     * Returns a list of functions to add to Twig
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_current_path', [$this, 'isCurrentPath']),
            new TwigFunction('format_date', [$this, 'formatDate']),
            new TwigFunction('get_status_badge', [$this, 'getStatusBadge']),
        ];
    }

    /**
     * Check if the current path matches the given path
     *
     * @param string $currentPath The current path
     * @param string $path The path to check against
     * @return bool
     */
    public function isCurrentPath(string $currentPath, string $path): bool
    {
        // Handle exact matches
        if ($currentPath === $path) {
            return true;
        }

        // Handle home page special case
        if ($path === '/' && $currentPath === '') {
            return true;
        }

        // Handle path prefixes (e.g. /urls/1 should match /urls)
        if ($path !== '/' && strpos($currentPath, $path) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Format a date
     *
     * @param string|\DateTimeInterface $date The date to format
     * @param string $format The format string (default: 'Y-m-d H:i:s')
     * @return string
     */
    public function formatDate($date, string $format = 'Y-m-d H:i:s'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        return $date->format($format);
    }

    /**
     * Get a Bootstrap badge based on HTTP status code
     *
     * @param int|null $statusCode The HTTP status code
     * @return string HTML for the badge
     */
    public function getStatusBadge(?int $statusCode): string
    {
        if ($statusCode === null) {
            return '<span class="badge bg-secondary">Unknown</span>';
        }

        $class = match (true) {
            $statusCode >= 200 && $statusCode < 300 => 'bg-success',
            $statusCode >= 300 && $statusCode < 400 => 'bg-info',
            $statusCode >= 400 && $statusCode < 500 => 'bg-warning',
            $statusCode >= 500 => 'bg-danger',
            default => 'bg-secondary'
        };

        return sprintf('<span class="badge %s">%d</span>', $class, $statusCode);
    }
} 