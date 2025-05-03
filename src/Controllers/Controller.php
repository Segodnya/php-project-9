<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use App\Repositories\UrlRepository;
use App\Repositories\UrlCheckRepository;
use App\Services\Analyzer;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;

abstract class Controller
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->getRenderer()->render($response, $template, $data);
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