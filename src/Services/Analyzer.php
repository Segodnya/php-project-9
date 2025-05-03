<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;
use App\Exceptions\HttpRequestException;
use Psr\Http\Message\ResponseInterface;

class Analyzer
{
    private Client $client;
    private array $clientOptions;

    /**
     * Create a new Analyzer instance
     * 
     * @param array $clientOptions Optional Guzzle client options
     */
    public function __construct(array $clientOptions = [])
    {
        $this->clientOptions = array_merge([
            'timeout' => 10,
            'verify' => false,
            'http_errors' => true,
            'allow_redirects' => true
        ], $clientOptions);
        
        $this->client = new Client($this->clientOptions);
    }

    /**
     * Set a custom HTTP client for testing
     * 
     * @param Client $client The client to use
     * @return self
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Analyze a URL and extract key SEO data
     * 
     * @param string $url The URL to analyze
     * @return array The analysis results
     * @throws HttpRequestException If the request fails
     */
    public function analyze(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            
            $result = [
                'status_code' => $statusCode
            ];

            if ($statusCode === 200) {
                $this->parseHtmlContent($response, $result);
            }

            return $result;

        } catch (ConnectException $e) {
            throw new HttpRequestException(
                'Не удалось подключиться к сайту',
                $e->getCode(),
                null,
                $url,
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            throw new HttpRequestException(
                'Ошибка при запросе: ' . $e->getMessage(),
                $e->getCode(),
                $statusCode,
                $url,
                $e
            );
        } catch (\Exception $e) {
            throw new HttpRequestException(
                'Произошла ошибка: ' . $e->getMessage(),
                $e->getCode(),
                null,
                $url,
                $e
            );
        }
    }

    /**
     * Parse HTML content from response and extract SEO data
     * 
     * @param ResponseInterface $response The HTTP response
     * @param array &$result The result array to populate
     * @return void
     */
    private function parseHtmlContent(ResponseInterface $response, array &$result): void
    {
        $body = (string) $response->getBody();
        
        // Skip parsing if body is empty
        if (empty($body)) {
            return;
        }
        
        try {
            $document = new Document($body);
            
            // Extract h1 tag content
            $result['h1'] = $this->extractH1($document);
            
            // Extract title tag content
            $result['title'] = $this->extractTitle($document);
            
            // Extract meta description content
            $result['description'] = $this->extractDescription($document);
        } catch (\Exception $e) {
            // Log the error but don't fail the entire analysis
            error_log('HTML parsing error: ' . $e->getMessage());
        }
    }

    /**
     * Extract H1 content from the document
     * 
     * @param Document $document The DiDom document
     * @return string|null The H1 content or null
     */
    private function extractH1(Document $document): ?string
    {
        $element = $document->first('h1');
        return $element ? $element->text() : null;
    }

    /**
     * Extract title content from the document
     * 
     * @param Document $document The DiDom document
     * @return string|null The title content or null
     */
    private function extractTitle(Document $document): ?string
    {
        $element = $document->first('title');
        return $element ? $element->text() : null;
    }

    /**
     * Extract meta description from the document
     * 
     * @param Document $document The DiDom document
     * @return string|null The description content or null
     */
    private function extractDescription(Document $document): ?string
    {
        $element = $document->first('meta[name="description"]');
        return $element ? $element->getAttribute('content') : null;
    }
} 