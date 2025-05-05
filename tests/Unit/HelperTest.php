<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for helper functions
 */
class HelperTest extends TestCase
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

        // Load functions from index.php
        require_once dirname(__DIR__, 2) . '/public/index.php';
    }

    /**
     * Test the HTML escaping function
     *
     * @return void
     */
    public function testHtmlEscape(): void
    {
        // Test escaping HTML special characters
        $this->assertEquals(
            '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            h('<script>alert("XSS")</script>')
        );

        // Test escaping ampersand
        $this->assertEquals(
            'foo &amp; bar',
            h('foo & bar')
        );

        // Test normal text passes through (but as a string)
        $this->assertEquals(
            'Hello World',
            h('Hello World')
        );

        // Test with numbers
        $this->assertEquals(
            '123',
            h(123)
        );

        // Test with null
        $this->assertEquals(
            '',
            h(null)
        );
    }

    /**
     * Test date formatting
     *
     * @return void
     */
    public function testFormatDate(): void
    {
        // Test formatting a date string
        $date = '2023-01-01 12:34:56';
        $formatted = formatDate($date);

        // Validate the formatted string matches expected pattern (Y-m-d H:i:s)
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $formatted
        );

        // Ensure the date is correctly parsed and formatted
        $expectedDate = date('Y-m-d H:i:s', strtotime($date));
        $this->assertEquals($expectedDate, $formatted);
    }

    /**
     * Test status badge generation
     *
     * @return void
     */
    public function testGetStatusBadge(): void
    {
        // Test success status (200-299)
        $this->assertStringContainsString(
            'success',
            getStatusBadge(200)
        );

        // Test info status (300-399)
        $this->assertStringContainsString(
            'info',
            getStatusBadge(301)
        );

        // Test status code 400-499 - should use warning class
        $this->assertStringContainsString(
            'warning',
            getStatusBadge(404)
        );

        // Test status code 500+ - should use danger class
        $this->assertStringContainsString(
            'danger',
            getStatusBadge(500)
        );

        // Verify correct status code is displayed in the badge
        $this->assertStringContainsString(
            '200',
            getStatusBadge(200)
        );
        $this->assertStringContainsString(
            '404',
            getStatusBadge(404)
        );
    }
}
