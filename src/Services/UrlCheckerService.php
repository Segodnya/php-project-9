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
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * UrlCheckerService class
 */
class UrlCheckerService
{
    /**
     * @var UrlService $urlService
     */
    private UrlService $urlService;

    /**
     * @var Client $httpClient
     */
    private Client $httpClient;

    /**
     * @var HtmlParserService $htmlParser
     */
    private HtmlParserService $htmlParser;

    /**
     * Constructor
     *
     * @param UrlService $urlService URL service
     * @param Client $httpClient HTTP client for making requests
     * @param HtmlParserService $htmlParser HTML parser service
     */
    public function __construct(
        UrlService $urlService,
        Client $httpClient,
        HtmlParserService $htmlParser
    ) {
        $this->urlService = $urlService;
        $this->httpClient = $httpClient;
        $this->htmlParser = $htmlParser;
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
                $htmlData = $this->htmlParser->parse($response['body']);
            }

            $urlCheck = new UrlCheck(
                $urlId,
                $statusCode,
                $htmlData['h1'] ?? null,
                $htmlData['title'] ?? null,
                $htmlData['description'] ?? null
            );

            $createdCheck = $this->urlService->createUrlCheck($urlCheck);
            return $createdCheck->getId() ?? 0;
        } catch (Exception $e) {
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
}
