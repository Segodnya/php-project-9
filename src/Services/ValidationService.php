<?php

/**
 * Validation Service
 *
 * Provides methods for data validation
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * ValidationService class
 */
class ValidationService
{
    /**
     * Validate a URL
     *
     * @param string $url URL to validate
     * @throws InvalidArgumentException if URL is invalid
     * @return string Normalized URL
     */
    public function validateUrl(string $url): string
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Trim whitespace
        $url = trim($url);

        // Direct check for malformed "http//" and "https//" (without colon)
        if (
            $url === 'http//' || $url === 'https//' ||
            strpos($url, 'http//') === 0 || strpos($url, 'https//') === 0
        ) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Check URL length
        if (strlen($url) > 255) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        return $this->normalizeUrl($url);
    }

    /**
     * Normalize a URL to scheme://host format
     *
     * @param string $url URL to normalize
     * @throws InvalidArgumentException if URL is invalid
     * @return string Normalized URL
     */
    private function normalizeUrl(string $url): string
    {
        // Parse the URL
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Convert scheme to lowercase for checking
        $scheme = strtolower($parsedUrl['scheme']);

        // Check for valid scheme (only http and https are allowed)
        if (!in_array($scheme, ['http', 'https'])) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Validate the host: it must have at least one dot to separate domain and TLD
        $host = strtolower($parsedUrl['host']);
        if (strpos($host, '.') === false) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Additional validation: TLD must be at least 2 characters
        $parts = explode('.', $host);
        $tld = end($parts);
        if (strlen($tld) < 2) {
            throw new InvalidArgumentException('Некорректный URL');
        }

        // Normalize to scheme://host
        $normalizedUrl = $scheme . '://' . $host;

        // Add port if specified and not default
        if (isset($parsedUrl['port'])) {
            if (
                ($scheme === 'http' && $parsedUrl['port'] !== 80) ||
                ($scheme === 'https' && $parsedUrl['port'] !== 443)
            ) {
                $normalizedUrl .= ':' . $parsedUrl['port'];
            }
        }

        return $normalizedUrl;
    }
}
