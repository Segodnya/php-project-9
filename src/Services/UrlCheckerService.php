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
use DiDom\Document;
use DiDom\Element;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
     * Constructor
     *
     * @param UrlService $urlService URL service
     * @param Client $httpClient HTTP client for making requests
     */
    public function __construct(UrlService $urlService, Client $httpClient)
    {
        $this->urlService = $urlService;
        $this->httpClient = $httpClient;
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
            $checkData = $this->analyze($url);

            $urlCheck = new UrlCheck(
                $urlId,
                $checkData['status_code'],
                $checkData['h1'] ?? null,
                $checkData['title'] ?? null,
                $checkData['description'] ?? null
            );

            $createdCheck = $this->urlService->createUrlCheck($urlCheck);
            return $createdCheck->getId() ?? 0;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Analyze a URL by fetching and parsing its content
     *
     * @param string $url URL to analyze
     * @return array<string, mixed> Analysis result data
     * @throws Exception on error
     */
    private function analyze(string $url): array
    {
        try {
            $response = $this->httpClient->get($url);
            $statusCode = $response->getStatusCode();

            $result = [
                'status_code' => $statusCode
            ];

            if ($statusCode === 200) {
                $body = (string) $response->getBody();

                // Skip parsing if body is empty
                if (!empty($body)) {
                    try {
                        $document = new Document($body);

                        // Extract h1 tag content
                        $h1Element = $document->first('h1');
                        // Use a safer approach with explicit null checking
                        $result['h1'] = null;
                        if ($h1Element instanceof Element) {
                            $result['h1'] = trim($h1Element->innerHtml());
                        }

                        // Extract title tag content
                        $titleElement = $document->first('title');
                        // Use a safer approach with explicit null checking
                        $result['title'] = null;
                        if ($titleElement instanceof Element) {
                            $result['title'] = trim($titleElement->innerHtml());
                        }

                        // Extract meta description content
                        $descElement = $document->first('meta[name="description"]');
                        $result['description'] = $descElement
                            ? $descElement->getAttribute('content')
                            : null;
                    } catch (Exception $e) {
                        // Log parsing error but continue
                        error_log('HTML parsing error: ' . $e->getMessage());
                    }
                }
            }

            return $result;
        } catch (ConnectException $e) {
            throw new Exception('Не удалось подключиться к сайту');
        } catch (RequestException $e) {
            // Check if a response is available before trying to get status code
            $statusCode = null;
            $response = $e->getResponse();
            if ($response !== null) {
                $statusCode = $response->getStatusCode();
            }

            // Include status code in the message instead of as a separate parameter
            $message = 'Ошибка при запросе: ' . $e->getMessage();
            if ($statusCode) {
                $message = "Ошибка при запросе (код {$statusCode}): " . $e->getMessage();
            }
            throw new Exception($message, 0, $e);
        } catch (Exception $e) {
            throw new Exception('Произошла ошибка: ' . $e->getMessage());
        }
    }
}
