<?php

namespace App\Validation;

use App\Exceptions\ValidationException;

class UrlValidator
{
    /**
     * Maximum allowed URL length
     */
    public const MAX_URL_LENGTH = 255;

    /**
     * Validate a URL string
     * 
     * @param string $url The URL to validate
     * @return bool True if the URL is valid
     * @throws ValidationException If the URL is invalid
     */
    public function validate(string $url): bool
    {
        $errors = [];

        if (empty($url)) {
            $errors[] = 'URL не может быть пустым';
        }

        if (strlen($url) > self::MAX_URL_LENGTH) {
            $errors[] = sprintf('URL превышает %d символов', self::MAX_URL_LENGTH);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return true;
    }

    /**
     * Normalize a URL by extracting only scheme, host and port
     * 
     * @param string $url The URL to normalize
     * @return string The normalized URL
     * @throws ValidationException If the URL cannot be parsed
     */
    public function normalize(string $url): string
    {
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false || !isset($parsedUrl['host'])) {
            throw new ValidationException(['Не удалось разобрать URL']);
        }

        // Construct the normalized URL with just the scheme and host
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'];

        // If port is specified, include it
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }

    /**
     * Validates and normalizes a URL in one step
     * 
     * @param string $url The URL to process
     * @return string The normalized URL
     * @throws ValidationException If the URL is invalid
     */
    public function validateAndNormalize(string $url): string
    {
        $this->validate($url);
        return $this->normalize($url);
    }
} 