<?php

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use DiDom\Document;

class Analyzer
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10.0]);
    }

    public function analyze(string $url): array
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            $html = (string) $response->getBody();

            $document = new Document($html);

            // Extract data from the page
            $h1 = optional($document->first('h1'))->text();
            $title = optional($document->first('title'))->text();

            // Get meta description
            $metaDescription = $document->first('meta[name="description"]');
            $description = $metaDescription ? $metaDescription->getAttribute('content') : null;

            return [
                'status_code' => $statusCode,
                'h1' => $h1,
                'title' => $title,
                'description' => $description,
            ];
        } catch (RequestException $e) {
            return [
                'status_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'h1' => null,
                'title' => null,
                'description' => null,
            ];
        }
    }
}
