<?php

namespace Tests\Unit\Services;

use App\Models\UrlCheck;
use App\Repository\UrlCheckRepository;
use App\Services\LogService;
use App\Services\UrlCheckService;
use DiDom\Document;
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
    /** @var UrlCheckRepository&MockObject */
    private $urlCheckRepositoryMock;

    /** @var Client&MockObject */
    private $httpClientMock;

    /** @var LogService&MockObject */
    private $loggerMock;

    /** @var UrlCheckService */
    private $urlChecker;

    protected function setUp(): void
    {
        $this->urlCheckRepositoryMock = $this->createMock(UrlCheckRepository::class);
        $this->httpClientMock = $this->createMock(Client::class);
        $this->loggerMock = $this->createMock(LogService::class);

        $this->urlChecker = new UrlCheckService(
            $this->urlCheckRepositoryMock,
            $this->httpClientMock,
            $this->loggerMock
        );
    }

    public function testSuccessfulCheck(): void
    {
        $urlId = 123;
        $url = 'https://example.com';
        $html = '<html><head><title>Example</title></head><body><h1>Example Domain</h1></body></html>';

        // HTTP client mock returns successful response
        $response = new Response(200, [], $html);
        $this->httpClientMock->expects($this->once())
            ->method('get')
            ->with($url)
            ->willReturn($response);

        // Expect creating a check in repository
        $this->urlCheckRepositoryMock->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (UrlCheck $urlCheck) use ($urlId) {
                $this->assertEquals($urlId, $urlCheck->getUrlId());
                $this->assertEquals(200, $urlCheck->getStatusCode());
                $this->assertEquals('Example Domain', $urlCheck->getH1());
                $this->assertEquals('Example', $urlCheck->getTitle());
                $this->assertNull($urlCheck->getDescription());

                // Set ID and return the check
                $urlCheck->setId(456);
                return $urlCheck;
            });

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

        // Expect creating a check in repository with error status
        $this->urlCheckRepositoryMock->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (UrlCheck $urlCheck) use ($urlId) {
                $this->assertEquals($urlId, $urlCheck->getUrlId());
                $this->assertEquals(404, $urlCheck->getStatusCode());
                $this->assertNull($urlCheck->getH1());
                $this->assertNull($urlCheck->getTitle());
                $this->assertNull($urlCheck->getDescription());

                // Set ID and return the check
                $urlCheck->setId(456);
                return $urlCheck;
            });

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

        // Logger should record the error
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error checking URL'), $this->isInstanceOf(\Exception::class));

        // Repository should not be called
        $this->urlCheckRepositoryMock->expects($this->never())
            ->method('create');

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

        // Expect creating a check in repository with error status
        $this->urlCheckRepositoryMock->expects($this->once())
            ->method('create')
            ->willReturnCallback(function (UrlCheck $urlCheck) use ($urlId) {
                $this->assertEquals($urlId, $urlCheck->getUrlId());
                $this->assertEquals(503, $urlCheck->getStatusCode());
                $this->assertNull($urlCheck->getH1());
                $this->assertNull($urlCheck->getTitle());
                $this->assertNull($urlCheck->getDescription());

                // Set ID and return the check
                $urlCheck->setId(456);
                return $urlCheck;
            });

        // Run the check
        $result = $this->urlChecker->check($urlId, $url);

        // Verify expected ID is returned
        $this->assertEquals(456, $result);
    }

    public function testFindUrlChecks(): void
    {
        $urlId = 123;
        $expectedChecks = [
            new UrlCheck($urlId, 200, 'Test H1', 'Test Title', 'Test Description', 1),
            new UrlCheck($urlId, 200, 'Test H1 2', 'Test Title 2', 'Test Description 2', 2)
        ];

        $this->urlCheckRepositoryMock->expects($this->once())
            ->method('findByUrlId')
            ->with($urlId)
            ->willReturn($expectedChecks);

        $result = $this->urlChecker->findUrlChecks($urlId);

        $this->assertSame($expectedChecks, $result);
    }

    public function testFindLatestUrlCheck(): void
    {
        $urlId = 123;
        $expectedCheck = new UrlCheck($urlId, 200, 'Test H1', 'Test Title', 'Test Description', 1);

        $this->urlCheckRepositoryMock->expects($this->once())
            ->method('findLatestByUrlId')
            ->with($urlId)
            ->willReturn($expectedCheck);

        $result = $this->urlChecker->findLatestUrlCheck($urlId);

        $this->assertSame($expectedCheck, $result);
    }
}
