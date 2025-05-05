<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Tests for URL handling functions
 */
class UrlTest extends TestCase
{
    /**
     * Set up before tests
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        // Define an environment variable to indicate test mode
        $_ENV['APP_ENV'] = 'testing';

        // Load the functions
        require_once __DIR__ . '/../../public/index.php';
    }

    /**
     * Test URL normalization
     *
     * @return void
     */
    public function testNormalizeUrl(): void
    {
        // Basic URL normalization
        $this->assertEquals(
            'https://example.com',
            normalizeUrl('https://example.com')
        );

        // URL with trailing slash
        $this->assertEquals(
            'https://example.com',
            normalizeUrl('https://example.com/')
        );

        // URL with path - should only keep hostname
        $this->assertEquals(
            'https://example.com',
            normalizeUrl('https://example.com/path/to/page')
        );

        // URL with query parameters - should only keep hostname
        $this->assertEquals(
            'https://example.com',
            normalizeUrl('https://example.com/path?query=value')
        );

        // URL with www - should keep www
        $this->assertEquals(
            'https://www.example.com',
            normalizeUrl('https://www.example.com')
        );

        // URL with port - should remove port
        $this->assertEquals(
            'https://example.com',
            normalizeUrl('https://example.com:443')
        );

        // Test invalid URL
        $this->expectException(InvalidArgumentException::class);
        normalizeUrl('not-a-valid-url');
    }
}
