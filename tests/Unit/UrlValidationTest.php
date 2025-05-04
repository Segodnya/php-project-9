<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class UrlValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Start output buffering to capture any HTML output
        ob_start();

        // Set test environment flag
        $GLOBALS['USE_TEST_PDO'] = true;

        // Include the index.php file to load functions
        require_once dirname(__DIR__, 2) . '/public/index.php';

        // Clean the output buffer
        ob_end_clean();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Make sure to clean any output buffer that might be left
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        $GLOBALS['USE_TEST_PDO'] = false;
    }

    /**
     * @dataProvider validUrlsProvider
     */
    public function testValidUrlsAreNormalized($inputUrl, $expectedUrl)
    {
        $this->assertEquals($expectedUrl, normalizeUrl($inputUrl));
    }

    /**
     * @dataProvider invalidUrlsProvider
     */
    public function testInvalidUrlsThrowException($invalidUrl)
    {
        $this->expectException(InvalidArgumentException::class);
        normalizeUrl($invalidUrl);
    }

    public function validUrlsProvider()
    {
        return [
            'simple url' => ['https://example.com', 'https://example.com'],
            'url with uppercase' => ['HTTPS://EXAMPLE.COM', 'https://example.com'],
            'url with path' => ['https://example.com/path', 'https://example.com'],
            'url with query' => ['https://example.com?q=test', 'https://example.com'],
            'url with fragment' => ['https://example.com#section', 'https://example.com'],
        ];
    }

    public function invalidUrlsProvider()
    {
        return [
            'missing scheme' => ['example.com'],
            'invalid scheme' => ['ftp://example.com'],
            'missing host' => ['https://'],
            'incomplete domain (no dot)' => ['https://goo'],
            'incomplete domain (no tld)' => ['https://example.'],
            'invalid tld (too short)' => ['https://example.a'],
        ];
    }
}