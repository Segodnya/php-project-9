<?php

/**
 * URL Checker Service
 *
 * Provides functionality for checking URLs
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

use App\Models\UrlCheck;
use App\Repository\UrlCheckRepository;
use DiDom\Document;
use DiDom\Element;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * UrlCheckService class
 */
class UrlCheckService
{
    /**
     * @var UrlCheckRepository $urlCheckRepository
     */
    private UrlCheckRepository $urlCheckRepository;

    /**
     * @var Client $httpClient
     */
    private Client $httpClient;

    /**
     * @var LogService $logger
     */
    private LogService $logger;

    /**
     * Constructor
     *
     * @param UrlCheckRepository $urlCheckRepository URL check repository
     * @param Client $httpClient HTTP client for making requests
     * @param LogService $logger Logger service for error handling
     */
    public function __construct(
        UrlCheckRepository $urlCheckRepository,
        Client $httpClient,
        LogService $logger
    ) {
        $this->urlCheckRepository = $urlCheckRepository;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Check a URL and store the check result
     *
     * @param int    $urlId URL ID
     * @param string $url   URL to check
     * @return int The ID of the created check
     * @throws Exception on error
     */
    public function check(int $urlId, string $url): int
    {
        try {
            $response = $this->fetchUrl($url);
            $statusCode = $response['status_code'];

            $htmlData = [];
            // Only parse HTML if we have a successful response
            if ($statusCode === 200 && !empty($response['body'])) {
                $htmlData = $this->parseHtml($response['body']);
            }

            $urlCheck = new UrlCheck(
                $urlId,
                $statusCode,
                $htmlData['h1'] ?? null,
                $htmlData['title'] ?? null,
                $htmlData['description'] ?? null
            );

            $createdCheck = $this->urlCheckRepository->create($urlCheck);
            return $createdCheck->getId() ?? 0;
        } catch (Exception $e) {
            $this->logger->error('Error checking URL', $e);
            throw $e;
        }
    }

    /**
     * Fetch a URL and return the response
     *
     * @param string $url URL to fetch
     * @return array<string, mixed> Response data including status code and body
     * @throws Exception on error
     */
    private function fetchUrl(string $url): array
    {
        try {
            $response = $this->httpClient->get($url);

            return [
                'status_code' => $response->getStatusCode(),
                'body' => (string) $response->getBody()
            ];
        } catch (ConnectException $e) {
            throw new Exception('Не удалось подключиться к сайту');
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : null;

            if ($response) {
                // We got a response with an error status code
                return [
                    'status_code' => $statusCode,
                    'body' => ''
                ];
            }

            // No response available, rethrow with a clear message
            $message = "Ошибка при запросе: {$e->getMessage()}";
            throw new Exception($message, 0, $e);
        }
    }

    /**
     * Parse HTML content and extract required elements
     *
     * @param string $html HTML content to parse
     * @return array<string, string|null> Parsed elements (h1, title, description)
     */
    private function parseHtml(string $html): array
    {
        if (empty($html)) {
            return [
                'h1' => null,
                'title' => null,
                'description' => null
            ];
        }

        try {
            $document = new Document($html);

            return [
                'h1' => $this->extractH1($document),
                'title' => $this->extractTitle($document),
                'description' => $this->extractDescription($document)
            ];
        } catch (\Exception $e) {
            $this->logger->error('HTML parsing error', $e);

            // Return empty values if parsing fails
            return [
                'h1' => null,
                'title' => null,
                'description' => null
            ];
        }
    }

    /**
     * Extract H1 tag content
     *
     * @param Document $document Parsed document
     * @return string|null Extracted H1 content or null if not found
     */
    private function extractH1(Document $document): ?string
    {
        $h1Element = $document->first('h1');
        if ($h1Element instanceof Element) {
            return trim($h1Element->innerHtml());
        }

        return null;
    }

    /**
     * Extract title tag content
     *
     * @param Document $document Parsed document
     * @return string|null Extracted title content or null if not found
     */
    private function extractTitle(Document $document): ?string
    {
        $titleElement = $document->first('title');
        if ($titleElement instanceof Element) {
            return trim($titleElement->innerHtml());
        }

        return null;
    }

    /**
     * Extract meta description content
     *
     * @param Document $document Parsed document
     * @return string|null Extracted description content or null if not found
     */
    private function extractDescription(Document $document): ?string
    {
        $descElement = $document->first('meta[name="description"]');
        return $descElement ? $descElement->getAttribute('content') : null;
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
}
