<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use DiDom\Document;
use Exception;

class Analyzer
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false
        ]);
    }

    public function analyze(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            $result = [
                'status_code' => $statusCode
            ];

            if ($statusCode === 200) {
                $document = new Document($body);

                // Extract h1 tag content
                $h1Element = $document->first('h1');
                $result['h1'] = $h1Element ? $h1Element->text() : null;

                // Extract title tag content
                $titleElement = $document->first('title');
                $result['title'] = $titleElement ? $titleElement->text() : null;

                // Extract meta description content
                $metaDescription = $document->first('meta[name="description"]');
                $result['description'] = $metaDescription ? $metaDescription->getAttribute('content') : null;
            }

            return $result;

        } catch (ConnectException $e) {
            throw new Exception('Не удалось подключиться к сайту');
        } catch (RequestException $e) {
            throw new Exception('Ошибка при запросе: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Произошла ошибка: ' . $e->getMessage());
        }
    }
} 