<?php

namespace App\Validation;

class UrlValidator
{
    public function validate(string $url): array
    {
        $errors = [];

        if (empty($url)) {
            $errors[] = 'URL не может быть пустым';
        }

        if (strlen($url) > 255) {
            $errors[] = 'URL превышает 255 символов';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL';
        }

        return $errors;
    }

    public function normalize(string $url): string
    {
        $parsedUrl = parse_url($url);

        // Construct the normalized URL with just the scheme and host
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] : 'http';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';

        // If port is specified, include it
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
} 