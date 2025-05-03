<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Twig\Extension\AbstractExtension;

/**
 * Service for rendering views using Twig templating engine
 */
class ViewService
{
    private Twig $twig;

    public function __construct(Twig $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Render a template using Twig
     *
     * @param ResponseInterface $response The response to render the template in
     * @param string $template The template file path
     * @param array $data Data to pass to the template
     * @return ResponseInterface
     */
    public function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->twig->render($response, $template, $data);
    }

    /**
     * Add an extension to Twig
     *
     * @param AbstractExtension $extension The extension to add
     * @return void
     */
    public function addExtension(AbstractExtension $extension): void
    {
        $this->twig->addExtension($extension);
    }

    /**
     * Get the Twig environment
     *
     * @return \Twig\Environment
     */
    public function getEnvironment(): \Twig\Environment
    {
        return $this->twig->getEnvironment();
    }

    /**
     * Add a global variable to Twig
     *
     * @param string $name The variable name
     * @param mixed $value The variable value
     * @return void
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twig->getEnvironment()->addGlobal($name, $value);
    }
}