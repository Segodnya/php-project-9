<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Analyzer;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use App\Exceptions\HttpRequestException;
use Mockery;

class AnalyzerTest extends TestCase
{
    private Analyzer $analyzer;
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
        $this->analyzer = new Analyzer();
        $this->analyzer->setClient($this->mockClient);
    }

    public function testSuccessfulAnalyze(): void
    {
        // Sample HTML response
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
            <meta name="description" content="This is a test page for SEO analysis">
        </head>
        <body>
            <h1>Welcome to Test Page</h1>
            <p>Some content here</p>
        </body>
        </html>
        HTML;

        // Configure mock
        $this->mockClient->shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andReturn(new Response(200, [], $html));

        // Run the analysis
        $result = $this->analyzer->analyze('https://example.com');

        // Check the results
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('Welcome to Test Page', $result['h1']);
        $this->assertEquals('Test Page', $result['title']);
        $this->assertEquals('This is a test page for SEO analysis', $result['description']);
    }

    public function testAnalyzeWithNoH1(): void
    {
        // HTML without h1 tag
        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test Page</title>
            <meta name="description" content="Description">
        </head>
        <body>
            <p>Some content here</p>
        </body>
        </html>
        HTML;

        $this->mockClient->shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andReturn(new Response(200, [], $html));

        $result = $this->analyzer->analyze('https://example.com');

        $this->assertEquals(200, $result['status_code']);
        $this->assertNull($result['h1']);
        $this->assertEquals('Test Page', $result['title']);
        $this->assertEquals('Description', $result['description']);
    }

    public function testConnectionError(): void
    {
        $this->mockClient->shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andThrow(new \GuzzleHttp\Exception\ConnectException('Connection refused', new Request('GET', 'https://example.com')));

        $this->expectException(HttpRequestException::class);
        $this->expectExceptionMessage('Не удалось подключиться к сайту');

        $this->analyzer->analyze('https://example.com');
    }

    public function testRequestError(): void
    {
        $response = new Response(404);
        
        $this->mockClient->shouldReceive('get')
            ->once()
            ->with('https://example.com')
            ->andThrow(new RequestException('Not Found', new Request('GET', 'https://example.com'), $response));

        $this->expectException(HttpRequestException::class);
        
        $this->analyzer->analyze('https://example.com');
    }
} 