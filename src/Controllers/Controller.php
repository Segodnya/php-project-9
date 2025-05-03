<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use App\Repositories\UrlRepository;
use App\Repositories\UrlCheckRepository;
use App\Services\Analyzer;
use App\Services\RequestHandler;
use App\Services\ResponseBuilder;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;

abstract class Controller
{
    protected ContainerInterface $container;
    protected ResponseBuilder $responseBuilder;
    protected RequestHandler $requestHandler;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->responseBuilder = $container->get(ResponseBuilder::class);
        $this->requestHandler = $container->get(RequestHandler::class);
    }

    /**
     * Renders a template with the given data
     * 
     * @deprecated Use responseBuilder->view() directly
     */
    protected function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->responseBuilder->view($template, $data);
    }

    /**
     * Creates a redirect response
     */
    protected function redirect(string $url, int $status = 302): ResponseInterface
    {
        return $this->responseBuilder->redirect($url, $status);
    }

    /**
     * Creates a not found response
     */
    protected function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return $this->responseBuilder->notFound($message);
    }

    protected function getRenderer(): PhpRenderer
    {
        return $this->container->get(PhpRenderer::class);
    }

    protected function getFlash(): Messages
    {
        return $this->container->get(Messages::class);
    }

    protected function getUrlRepository(): UrlRepository
    {
        return $this->container->get(UrlRepository::class);
    }

    protected function getUrlCheckRepository(): UrlCheckRepository
    {
        return $this->container->get(UrlCheckRepository::class);
    }

    protected function getAnalyzer(): Analyzer
    {
        return $this->container->get(Analyzer::class);
    }
} 