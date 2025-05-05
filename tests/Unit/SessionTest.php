<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests for session and flash message functions
 */
class SessionTest extends TestCase
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

        // Start session if not already started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Load functions from index.php
        require_once dirname(__DIR__, 2) . '/public/index.php';
    }

    /**
     * Set up before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // Clear session data before each test
        $_SESSION = [];
    }

    /**
     * Test setting and retrieving flash messages
     *
     * @return void
     */
    public function testFlashMessages(): void
    {
        // Initially there should be no flash messages
        $messages = getFlashMessages();
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);

        // Set a flash message
        setFlashMessage('success', 'Test success message');

        // Check that the message is in the session
        $this->assertArrayHasKey('flash_messages', $_SESSION);
        $this->assertCount(1, $_SESSION['flash_messages']);
        $this->assertEquals('success', $_SESSION['flash_messages'][0]['type']);
        $this->assertEquals('Test success message', $_SESSION['flash_messages'][0]['message']);

        // Get the flash message - should clear after retrieval
        $messages = getFlashMessages();
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('success', $messages[0]['type']);
        $this->assertEquals('Test success message', $messages[0]['message']);

        // Verify the session is now cleared
        $this->assertEmpty($_SESSION['flash_messages']);

        // Multiple flash messages
        setFlashMessage('success', 'First message');
        setFlashMessage('danger', 'Second message');

        $messages = getFlashMessages();
        $this->assertCount(2, $messages);
        $this->assertEquals('success', $messages[0]['type']);
        $this->assertEquals('First message', $messages[0]['message']);
        $this->assertEquals('danger', $messages[1]['type']);
        $this->assertEquals('Second message', $messages[1]['message']);
    }
}
