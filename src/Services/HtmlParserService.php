<?php

/**
 * HTML Parser Service
 *
 * Provides functionality for parsing HTML content
 * PHP version 8.0
 *
 * @category Service
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Services;

use DiDom\Document;
use DiDom\Element;
use App\Services\LoggerService;

/**
 * HtmlParserService class
 */
class HtmlParserService
{
    /**
     * @var LoggerService $logger
     */
    private LoggerService $logger;

    /**
     * Constructor
     *
     * @param LoggerService $logger Logger service for error handling
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Parse HTML content and extract required elements
     *
     * @param string $html HTML content to parse
     * @return array<string, string|null> Parsed elements (h1, title, description)
     */
    public function parse(string $html): array
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
}
