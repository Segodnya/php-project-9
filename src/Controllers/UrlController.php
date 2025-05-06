<?php

/**
 * URL Controller
 *
 * Handles URL-related routes
 * PHP version 8.0
 *
 * @category Controller
 * @package  PageAnalyzer
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UrlService;
use App\Services\UrlCheckerService;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\Twig;

/**
 * UrlController class
 */
class UrlController
{
    /**
     * @var Twig $view
     */
    private Twig $view;

    /**
     * @var UrlService $urlService
     */
    private UrlService $urlService;

    /**
     * @var UrlCheckerService $urlCheckerService
     */
    private UrlCheckerService $urlCheckerService;

    /**
     * @var Messages $flash
     */
    private Messages $flash;

    /**
     * @var RouteParserInterface $routeParser
     */
    private RouteParserInterface $routeParser;

    /**
     * Constructor
     *
     * @param Twig                 $view              View renderer
     * @param UrlService           $urlService        URL service
     * @param UrlCheckerService    $urlCheckerService URL checker service
     * @param Messages             $flash             Flash messages
     * @param RouteParserInterface $routeParser       Route parser
     */
    public function __construct(
        Twig $view,
        UrlService $urlService,
        UrlCheckerService $urlCheckerService,
        Messages $flash,
        RouteParserInterface $routeParser
    ) {
        $this->view = $view;
        $this->urlService = $urlService;
        $this->urlCheckerService = $urlCheckerService;
        $this->flash = $flash;
        $this->routeParser = $routeParser;
    }

    /**
     * Display list of all URLs
     *
     * @param Request  $request  The request object
     * @param Response $response The response object
     * @return Response
     */
    public function index(Request $request, Response $response): Response
    {
        $urls = $this->urlService->findAllWithLatestChecks();
        return $this->view->render($response, 'urls/index.twig', [
            'urls' => $urls
        ]);
    }

    /**
     * Store a new URL
     *
     * @param Request  $request  The request object
     * @param Response $response The response object
     * @return Response
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';

        try {
            $normalizedUrl = $this->urlService->validateUrl($url);
            $existingUrl = $this->urlService->findByName($normalizedUrl);

            if ($existingUrl) {
                $this->flash->addMessage('info', 'Страница уже существует');
                return $response
                    ->withHeader('Location', $this->routeParser->urlFor('urls.show', ['id' => $existingUrl['id']]))
                    ->withStatus(302);
            }

            $id = $this->urlService->create($normalizedUrl);
            $this->flash->addMessage('success', 'Страница успешно добавлена');

            return $response
                ->withHeader('Location', $this->routeParser->urlFor('urls.show', ['id' => $id]))
                ->withStatus(302);
        } catch (InvalidArgumentException $e) {
            $this->flash->addMessage('danger', $e->getMessage());
            return $response
                ->withHeader('Location', $this->routeParser->urlFor('home'))
                ->withStatus(302);
        }
    }

    /**
     * Display URL details
     *
     * @param Request  $request  The request object
     * @param Response $response The response object
     * @param array    $args     Route arguments
     * @return Response
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->urlService->findById($id);

        if (!$url) {
            return $this->view->render($response->withStatus(404), 'errors/404.twig');
        }

        $checks = $this->urlService->findUrlChecks($id);
        return $this->view->render(
            $response,
            'urls/show.twig',
            [
                'url' => $url,
                'checks' => $checks
            ]
        );
    }

    /**
     * Run URL check
     *
     * @param Request  $request  The request object
     * @param Response $response The response object
     * @param array    $args     Route arguments
     * @return Response
     */
    public function check(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->urlService->findById($id);

        if (!$url) {
            return $this->view->render($response->withStatus(404), 'errors/404.twig');
        }

        try {
            if (!isset($url['name']) || !is_string($url['name'])) {
                throw new Exception('Invalid URL data: missing name');
            }

            $this->urlCheckerService->check($id, $url['name']);
            $this->flash->addMessage('success', 'Страница успешно проверена');
        } catch (Exception $e) {
            $this->flash->addMessage('danger', 'Ошибка при проверке: ' . $e->getMessage());
        }

        return $response
            ->withHeader('Location', $this->routeParser->urlFor('urls.show', ['id' => $id]))
            ->withStatus(302);
    }
}
