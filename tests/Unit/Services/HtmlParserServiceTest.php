<?php

namespace Tests\Unit\Services;

use App\Services\HtmlParserService;
use App\Services\LoggerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HtmlParserServiceTest extends TestCase
{
    private HtmlParserService $parser;

    protected function setUp(): void
    {
        /** @var LoggerService&MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerService::class);
        $this->parser = new HtmlParserService($loggerMock);
    }

    public function testParseEmptyHtml(): void
    {
        $result = $this->parser->parse('');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('h1', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertNull($result['h1']);
        $this->assertNull($result['title']);
        $this->assertNull($result['description']);
    }

    public function testParseValidHtml(): void
    {
        $html = '
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Test Title</title>
                    <meta name="description" content="Test Description">
                </head>
                <body>
                    <h1>Test Header</h1>
                </body>
            </html>
        ';

        $result = $this->parser->parse($html);

        $this->assertEquals('Test Header', $result['h1']);
        $this->assertEquals('Test Title', $result['title']);
        $this->assertEquals('Test Description', $result['description']);
    }

    public function testParseHtmlWithMissingElements(): void
    {
        $html = '
            <!DOCTYPE html>
            <html>
                <head>
                    <title>Test Title</title>
                </head>
                <body>
                    <p>No h1 here</p>
                </body>
            </html>
        ';

        $result = $this->parser->parse($html);

        $this->assertNull($result['h1']);
        $this->assertEquals('Test Title', $result['title']);
        $this->assertNull($result['description']);
    }

    public function testParseInvalidHtml(): void
    {
        // Используем строку, которая не является валидным HTML, но не вызывает ошибку парсинга
        $html = '<<<invalid html>>>';

        $result = $this->parser->parse($html);

        // Даже при невалидном HTML мы должны получить структурированный результат с null значениями
        $this->assertIsArray($result);
        $this->assertArrayHasKey('h1', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
    }
}
