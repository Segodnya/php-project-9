<?php

/**
 * Home Controller
 *
 * Handles the home page route
 * PHP version 8.0
 *
 * @category Controller
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * HomeController class
 */
class HomeController
{
    /**
     * @var Twig $view
     */
    private Twig $view;

    /**
     * Constructor
     *
     * @param Twig $view View renderer
     */
    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    /**
     * Display the home page
     *
     * @param Request  $request  The request object
     * @param Response $response The response object
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->view->render($response, 'index.twig');
    }
}
