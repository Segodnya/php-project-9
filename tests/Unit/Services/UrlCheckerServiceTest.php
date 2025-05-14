<?php

namespace Tests\Unit\Services;

use App\Models\UrlCheck;
use App\Services\HtmlParserService;
use App\Services\UrlCheckerService;
use App\Services\UrlService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class UrlCheckerServiceTest extends TestCase
{
    /** @var UrlService&MockObject */
    private $urlServiceMock;

    /** @var Client&MockObject */
    private $httpClientMock;

    /** @var HtmlParserService&MockObject */
    private $htmlParserMock;

    /** @var UrlCheckerService */
    private $urlChecker;

    protected function setUp(): void
    {
        $this->urlServiceMock = $this->createMock(UrlService::class);
        $this->httpClientMock = $this->createMock(Client::class);
        $this->htmlParserMock = $this->createMock(HtmlParserService::class);

        $this->urlChecker = new UrlCheckerService(
            $this->urlServiceMock,
            $this->httpClientMock,
            $this->htmlParserMock
        );
    }

    public function testSuccessfulCheck(): void
    {
        $urlId = 123;
        $url = 'https://example.com';
        $html = '<html><head><title>Example</title></head><body><h1>Example Domain</h1></body></html>';
        $parsedData = [
            'h1' => 'Example Domain',
            'title' => 'Example',
            'description' => null
        ];

        // HTTP client mock returns successful response
        $response = new Response(200, [], $html);
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($url)
            ->willReturn($response);

        // HTML parser mock returns parsed data
        $this->htmlParserMock->expects($this->once())
            ->method('parse')
            ->with($html)
            ->willReturn($parsedData);

        // Mock URL service creating a check
        $urlCheck = new UrlCheck(
            $urlId,
            200,
            'Example Domain',
            'Example',
            null,
            456 // ID of created check
        );

        $this->urlServiceMock->expects($this->once())
            ->method('createUrlCheck')
            ->willReturn($urlCheck);

        // Run the check
        $result = $this->urlChecker->check($urlId, $url);

        // Verify expected ID is returned
        $this->assertEquals(456, $result);
    }

    public function testErrorResponseCheck(): void
    {
        $urlId = 123;
        $url = 'https://example.com';

        // HTTP client mock returns 404 response
        $response = new Response(404);
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($url)
            ->willReturn($response);

        // HTML parser should not be called for error responses
        $this->htmlParserMock->expects($this->never())
            ->method('parse');

        // Mock URL service creating a check
        $urlCheck = new UrlCheck(
            $urlId,
            404,
            null,
            null,
            null,
            456 // ID of created check
        );

        $this->urlServiceMock->expects($this->once())
            ->method('createUrlCheck')
            ->willReturn($urlCheck);

        // Run the check
        $result = $this->urlChecker->check($urlId, $url);

        // Verify expected ID is returned
        $this->assertEquals(456, $result);
    }

    public function testConnectionError(): void
    {
        $urlId = 123;
        $url = 'https://example.com';

        // Mock a connection exception
        $request = new Request('GET', $url);
        $exception = new ConnectException('Connection refused', $request);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($url)
            ->willThrowException($exception);

        // HTML parser should not be called when connection fails
        $this->htmlParserMock->expects($this->never())
            ->method('parse');

        // URL service should not be called
        $this->urlServiceMock->expects($this->never())
            ->method('createUrlCheck');

        // Expect an exception to be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Не удалось подключиться к сайту');

        // Run the check
        $this->urlChecker->check($urlId, $url);
    }

    public function testRequestError(): void
    {
        $urlId = 123;
        $url = 'https://example.com';

        // Mock a request exception with a response
        $request = new Request('GET', $url);
        $response = new Response(503); // Service unavailable
        $exception = new RequestException('Service unavailable', $request, $response);

        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($url)
            ->willThrowException($exception);

        // HTML parser should not be called for error responses
        $this->htmlParserMock->expects($this->never())
            ->method('parse');

        // Mock URL service creating a check with error status code
        $urlCheck = new UrlCheck(
            $urlId,
            503,
            null,
            null,
            null,
            456 // ID of created check
        );

        $this->urlServiceMock->expects($this->once())
            ->method('createUrlCheck')
            ->willReturn($urlCheck);

        // Run the check
        $result = $this->urlChecker->check($urlId, $url);

        // Verify expected ID is returned
        $this->assertEquals(456, $result);
    }
}
