<?php

namespace App\Middleware;

use App\Services\ResponseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;
use App\Exceptions\ValidationException;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use Throwable;

/**
 * Middleware for handling errors in a standardized way
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    private ResponseBuilder $responseBuilder;
    private bool $displayErrors;

    public function __construct(ResponseBuilder $responseBuilder, bool $displayErrors = false)
    {
        $this->responseBuilder = $responseBuilder;
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
            // Handle 404 Not Found exceptions - from our custom exception or Slim's exception
            return $this->responseBuilder->notFound($exception->getMessage());
        } catch (HttpException $exception) {
            // Handle HTTP exceptions with their specific status codes
            $statusCode = $exception->getStatusCode();
            return $this->responseBuilder->error($exception->getMessage(), $statusCode);
        } catch (ValidationException $exception) {
            // Handle validation exceptions with custom template if provided
            $template = $exception->getTemplate() ?: 'index.phtml';
            return $this->responseBuilder->validationError($exception->getErrors(), $template);
        } catch (Throwable $exception) {
            // Log the exception
            error_log(sprintf(
                "Error: %s\nFile: %s\nLine: %s\nTrace: %s",
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));

            // Prepare error message (detailed in dev, generic in prod)
            $message = $this->displayErrors
                ? $exception->getMessage()
                : 'An unexpected error occurred. Please try again later.';

            // Return a standardized error response
            return $this->responseBuilder->error($message, 500);
        }
    }
} 