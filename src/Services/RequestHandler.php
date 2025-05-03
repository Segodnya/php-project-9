<?php

namespace App\Services;

use App\Exceptions\BadRequestException;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Service for extracting and validating data from requests
 */
class RequestHandler
{
    /**
     * Get a required parameter from the request body
     *
     * @param Request $request The request object
     * @param string $key The parameter key
     * @param string $message Custom error message
     * @return mixed The parameter value
     * @throws BadRequestException If parameter is missing
     */
    public function getRequiredParam(Request $request, string $key, string $message = null): mixed
    {
        $body = $request->getParsedBody();
        
        if (!is_array($body) || !array_key_exists($key, $body)) {
            $errorMessage = $message ?: "Required parameter '$key' is missing";
            throw new BadRequestException($errorMessage);
        }
        
        return $body[$key];
    }
    
    /**
     * Get an optional parameter from the request body
     *
     * @param Request $request The request object
     * @param string $key The parameter key
     * @param mixed $default Default value if parameter is not present
     * @return mixed The parameter value or default
     */
    public function getParam(Request $request, string $key, mixed $default = null): mixed
    {
        $body = $request->getParsedBody();
        
        if (!is_array($body) || !array_key_exists($key, $body)) {
            return $default;
        }
        
        return $body[$key];
    }
    
    /**
     * Get a required query parameter
     *
     * @param Request $request The request object
     * @param string $key The parameter key
     * @param string $message Custom error message
     * @return string The query parameter value
     * @throws BadRequestException If parameter is missing
     */
    public function getRequiredQueryParam(Request $request, string $key, string $message = null): string
    {
        $params = $request->getQueryParams();
        
        if (!array_key_exists($key, $params)) {
            $errorMessage = $message ?: "Required query parameter '$key' is missing";
            throw new BadRequestException($errorMessage);
        }
        
        return $params[$key];
    }
    
    /**
     * Get an optional query parameter
     *
     * @param Request $request The request object
     * @param string $key The parameter key
     * @param mixed $default Default value if parameter is not present
     * @return mixed The query parameter value or default
     */
    public function getQueryParam(Request $request, string $key, mixed $default = null): mixed
    {
        $params = $request->getQueryParams();
        
        if (!array_key_exists($key, $params)) {
            return $default;
        }
        
        return $params[$key];
    }
    
    /**
     * Get all request data from the request body
     *
     * @param Request $request The request object
     * @return array The request body data
     */
    public function getAllRequestData(Request $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
} 