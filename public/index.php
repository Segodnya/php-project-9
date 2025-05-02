<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Create Container
$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

// Create App with Container
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Define app routes
$app->get('/', function (Request $request, Response $response) {
    $renderer = $this->get('renderer');
    return $renderer->render($response, 'index.phtml');
});

$app->post('/analyze', function (Request $request, Response $response) {
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'] ?? '';

    // In a real application, we would analyze the URL here

    $response->getBody()->write("Analyzing URL: " . htmlspecialchars($url));
    return $response;
});

$app->run();