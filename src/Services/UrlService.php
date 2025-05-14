<?php

/**
 * URL Service
 *
 * Provides methods for URL operations
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\Url;
use App\Repository\UrlRepository;
use App\Validators\UrlValidator;
use InvalidArgumentException;

/**
 * UrlService class
 */
class UrlService
{
    /**
     * @var UrlRepository
     */
    private UrlRepository $urlRepository;

    /**
     * @var UrlValidator
     */
    private UrlValidator $urlValidator;

    /**
     * Constructor
     *
     * @param UrlRepository $urlRepository URL repository
     * @param UrlValidator $urlValidator URL validator
     */
    public function __construct(
        UrlRepository $urlRepository,
        UrlValidator $urlValidator
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlValidator = $urlValidator;
    }

    /**
     * Find a URL by its ID
     *
     * @param int $id URL ID
     * @return Url|null URL object or null if not found
     */
    public function findById(int $id): ?Url
    {
        return $this->urlRepository->findById($id);
    }

    /**
     * Find a URL by its name
     *
     * @param string $name URL name
     * @return Url|null URL object or null if not found
     */
    public function findByName(string $name): ?Url
    {
        return $this->urlRepository->findByName($name);
    }

    /**
     * Find all URLs
     *
     * @return array<int, Url> All URLs
     */
    public function findAll(): array
    {
        return $this->urlRepository->findAll();
    }

    /**
     * Find all URLs with their latest check data
     *
     * @return array<int, array<string, mixed>> All URLs with latest check data
     */
    public function findAllWithLatestChecks(): array
    {
        return $this->urlRepository->findAllWithLatestChecks();
    }

    /**
     * Create a new URL
     *
     * @param string $url URL to create
     * @throws InvalidArgumentException if URL is invalid
     * @return Url New URL object
     */
    public function create(string $url): Url
    {
        // Validate and normalize the URL
        $normalizedUrl = $this->urlValidator->validateUrl($url);

        // Check if URL already exists
        $existingUrl = $this->findByName($normalizedUrl);
        if ($existingUrl !== null) {
            return $existingUrl;
        }

        // Create new URL object and persist it
        $urlModel = new Url($normalizedUrl);
        return $this->urlRepository->create($urlModel);
    }

    /**
     * Validate a URL
     *
     * @param string $url URL to validate
     * @throws InvalidArgumentException if URL is invalid
     * @return string Normalized URL
     */
    public function validateUrl(string $url): string
    {
        return $this->urlValidator->validateUrl($url);
    }
}
