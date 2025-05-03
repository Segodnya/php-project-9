<?php

namespace App\Middleware;

use App\Services\ResponseBuilder;
use App\Services\LoggerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\DatabaseException;
use App\Exceptions\ExternalServiceException;
use Throwable;

/**
 * Middleware for handling errors in a standardized way
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private ResponseBuilder $responseBuilder;
    private LoggerService $logger;
    private bool $displayErrors;

    public function __construct(
        ResponseBuilder $responseBuilder,
        LoggerService $logger,
        bool $displayErrors = false
    ) {
        $this->responseBuilder = $responseBuilder;
        $this->logger = $logger;
        $this->displayErrors = $displayErrors;
    }

    /**
     * Process the request through the middleware
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            // Process the request through the application
            return $handler->handle($request);
        } catch (NotFoundException | HttpNotFoundException $exception) {
            // Log the exception with context
            $this->logger->warning('Not Found: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            // Handle 404 Not Found exceptions - from our custom exception or Slim's exception
            return $this->responseBuilder->error(
                $exception->getMessage(),
                404,
                'errors/404.twig'
            );
        } catch (UnauthorizedException $exception) {
            // Log the unauthorized attempt
            $this->logger->warning('Unauthorized access: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            // Return a 401 Unauthorized response
            return $this->responseBuilder->error(
                $exception->getMessage(),
                401,
                'errors/401.twig'
            );
        } catch (ForbiddenException $exception) {
            // Log the forbidden access attempt
            $this->logger->warning('Forbidden access: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            // Return a 403 Forbidden response
            return $this->responseBuilder->error(
                $exception->getMessage(),
                403,
                'errors/403.twig'
            );
        } catch (HttpException $exception) {
            // Log the HTTP exception
            $this->logger->warning('HTTP Exception: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'status_code' => $exception->getStatusCode(),
            ]);

            // Handle HTTP exceptions with their specific status codes
            $statusCode = $exception->getStatusCode();

            // Determine the appropriate template based on status code
            $template = 'errors/error.twig';
            if (file_exists(dirname(__DIR__, 2) . '/templates/errors/' . $statusCode . '.twig')) {
                $template = 'errors/' . $statusCode . '.twig';
            }

            return $this->responseBuilder->error(
                $exception->getMessage(),
                $statusCode,
                $template
            );
        } catch (ValidationException $exception) {
            // Log validation errors
            $this->logger->info('Validation error', [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'errors' => $exception->getErrors(),
            ]);

            // Handle validation exceptions with custom template if provided
            $template = $exception->getTemplate() ?: 'home/index.twig';
            return $this->responseBuilder->validationError($exception->getErrors(), $template);
        } catch (DatabaseException $exception) {
            // Log database exceptions with extra details
            $this->logger->error('Database error: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            // Return a standardized server error response
            $message = $this->displayErrors
                ? $exception->getMessage()
                : 'A database error occurred. Please try again later.';

            return $this->responseBuilder->error($message, 500, 'errors/500.twig');
        } catch (ExternalServiceException $exception) {
            // Log external service errors
            $this->logger->error('External service error: ' . $exception->getMessage(), [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'service' => $exception->getServiceName(),
            ]);

            // Return an appropriate error response
            $message = $this->displayErrors
                ? sprintf('Error in external service %s: %s', $exception->getServiceName(), $exception->getMessage())
                : 'An error occurred while communicating with an external service. Please try again later.';

            return $this->responseBuilder->error($message, $exception->getStatusCode(), 'errors/error.twig');
        } catch (Throwable $exception) {
            // Log the full exception details
            $this->logger->logException($exception, [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
            ]);

            // Prepare error message (detailed in dev, generic in prod)
            $message = $this->displayErrors
                ? $exception->getMessage()
                : 'An unexpected error occurred. Please try again later.';

            // Return a standardized error response
            return $this->responseBuilder->error($message, 500, 'errors/500.twig');
        }
    }
}