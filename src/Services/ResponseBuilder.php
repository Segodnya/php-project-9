<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;
use App\Services\ViewService;

/**
 * Service for building standardized responses
 */
class ResponseBuilder
{
    private ResponseFactory $responseFactory;
    private ViewService $viewService;

    public function __construct(ResponseFactory $responseFactory, ViewService $viewService)
    {
        $this->responseFactory = $responseFactory;
        $this->viewService = $viewService;
    }

    /**
     * Create a view response
     *
     * @param string $template Template file
     * @param array $data Data to pass to the template
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function view(string $template, array $data = [], int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        return $this->viewService->render($response, $template, $data);
    }

    /**
     * Create a JSON response
     *
     * @param array $data Data to convert to JSON
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function json(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write(json_encode($data));
        
        return $response;
    }

    /**
     * Create a standardized API response
     *
     * @param bool $success Whether the request was successful
     * @param array $data The data to include in the response
     * @param string|null $message A message to include in the response
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function apiResponse(bool $success, array $data = [], ?string $message = null, int $status = 200): ResponseInterface
    {
        $responseData = [
            'success' => $success,
            'data' => $data
        ];

        if ($message !== null) {
            $responseData['message'] = $message;
        }

        return $this->json($responseData, $status);
    }

    /**
     * Create a successful API response
     *
     * @param array $data The data to include in the response
     * @param string|null $message A message to include in the response
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function apiSuccess(array $data = [], ?string $message = null, int $status = 200): ResponseInterface
    {
        return $this->apiResponse(true, $data, $message, $status);
    }

    /**
     * Create an error API response
     *
     * @param string $message Error message
     * @param array $errors Additional error details
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function apiError(string $message, array $errors = [], int $status = 400): ResponseInterface
    {
        return $this->apiResponse(false, ['errors' => $errors], $message, $status);
    }

    /**
     * Create a redirect response
     *
     * @param string $url URL to redirect to
     * @param int $status HTTP status code
     * @return ResponseInterface
     */
    public function redirect(string $url, int $status = 302): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        return $response->withHeader('Location', $url);
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param string $template Template to use for rendering the error
     * @return ResponseInterface
     */
    public function error(string $message, int $status = 500, string $template = 'errors/error.twig'): ResponseInterface
    {
        return $this->view($template, ['error' => $message], $status);
    }

    /**
     * Create a not found response
     *
     * @param string $message Not found message
     * @return ResponseInterface
     */
    public function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return $this->error($message, 404, 'errors/404.twig');
    }

    /**
     * Create a validation error response
     *
     * @param array $errors Validation errors
     * @param string $template Template to use for rendering the validation errors
     * @return ResponseInterface
     */
    public function validationError(array $errors, string $template = 'index.twig'): ResponseInterface
    {
        return $this->view($template, ['errors' => $errors], 422);
    }
} 