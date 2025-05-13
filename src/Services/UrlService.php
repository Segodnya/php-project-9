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
use App\Models\UrlCheck;
use App\Repository\UrlRepository;
use App\Repository\UrlCheckRepository;
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
     * @var UrlCheckRepository
     */
    private UrlCheckRepository $urlCheckRepository;

    /**
     * @var ValidationService
     */
    private ValidationService $validationService;

    /**
     * Constructor
     *
     * @param UrlRepository|null $urlRepository URL repository
     * @param UrlCheckRepository|null $urlCheckRepository URL check repository
     * @param ValidationService|null $validationService Validation service
     */
    public function __construct(
        ?UrlRepository $urlRepository = null,
        ?UrlCheckRepository $urlCheckRepository = null,
        ?ValidationService $validationService = null
    ) {
        $this->urlRepository = $urlRepository ?? new UrlRepository();
        $this->urlCheckRepository = $urlCheckRepository ?? new UrlCheckRepository();
        $this->validationService = $validationService ?? new ValidationService();
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
        $normalizedUrl = $this->validationService->validateUrl($url);

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
     * Find URL checks by URL ID
     *
     * @param int $urlId URL ID
     * @return array<int, UrlCheck> URL check objects
     */
    public function findUrlChecks(int $urlId): array
    {
        return $this->urlCheckRepository->findByUrlId($urlId);
    }

    /**
     * Find the latest URL check by URL ID
     *
     * @param int $urlId URL ID
     * @return UrlCheck|null URL check object or null if not found
     */
    public function findLatestUrlCheck(int $urlId): ?UrlCheck
    {
        return $this->urlCheckRepository->findLatestByUrlId($urlId);
    }

    /**
     * Create a new URL check
     *
     * @param UrlCheck $urlCheck URL check object
     * @return UrlCheck Updated URL check object with ID
     */
    public function createUrlCheck(UrlCheck $urlCheck): UrlCheck
    {
        return $this->urlCheckRepository->create($urlCheck);
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
        return $this->validationService->validateUrl($url);
    }
}
