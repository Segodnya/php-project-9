<?php

namespace App\Controllers;

use Psr\Container\ContainerInterface;

abstract class Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function render($response, $template, $data = [])
    {
        return $this->container->get('renderer')->render($response, $template, $data);
    }

    protected function flash()
    {
        return $this->container->get('flash');
    }

    protected function urlRepository()
    {
        return $this->container->get('url_repository');
    }

    protected function urlCheckRepository()
    {
        return $this->container->get('url_check_repository');
    }

    protected function analyzer()
    {
        return $this->container->get('analyzer');
    }
} 