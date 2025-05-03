<?php

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Controller for API endpoints
 */
class ApiController extends Controller
{
    /**
     * Get information about a URL
     */
    public function getUrl(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->getUrlRepository()->findById($id);

        if (!$url) {
            throw new NotFoundException("URL with ID {$id} not found");
        }

        // Get the latest check data for this URL
        $latestCheck = $this->getUrlCheckRepository()->findLatestByUrlId($id);

        // Prepare response data
        $responseData = [
            'id' => $url['id'],
            'name' => $url['name'],
            'created_at' => $url['created_at'],
            'latest_check' => $latestCheck ?: null
        ];

        return $this->responseBuilder->apiSuccess($responseData);
    }

    /**
     * Get all URLs
     */
    public function getUrls(Request $request, Response $response): Response
    {
        $urls = $this->getUrlRepository()->findAll();

        // Add the latest check data to each URL
        foreach ($urls as &$url) {
            $latestCheck = $this->getUrlCheckRepository()->findLatestByUrlId($url['id']);
            if ($latestCheck) {
                $url['latest_check'] = $latestCheck;
            }
        }

        return $this->responseBuilder->apiSuccess(['urls' => $urls]);
    }
} 