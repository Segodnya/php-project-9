<?php

// Suppress deprecation warnings
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Connection;
use App\UrlRepository;
use App\UrlCheckRepository;
use App\Validator;
use App\Analyzer;

session_start();

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
try {
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // No .env file, continue with default environment
}

// Create Container
$container = new Container();

// Configure the renderer with the layout
$container->set('renderer', function () {
    // Create a new PhpRenderer instance with the templates directory
    $phpView = new PhpRenderer(__DIR__ . '/../templates');

    // Explicitly set the layout file to use for all templates
    $phpView->setLayout('layout.phtml');

    return $phpView;
});

$container->set('flash', function () {
    return new Messages();
});

$container->set('pdo', function () {
    return Connection::get();
});

$container->set('url_repository', function ($c) {
    return new UrlRepository($c->get('pdo'));
});

$container->set('url_check_repository', function ($c) {
    return new UrlCheckRepository($c->get('pdo'));
});

$container->set('analyzer', function () {
    return new Analyzer();
});

// Create App with Container
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Add routes
$app->get('/', function (Request $request, Response $response) {
    $flash = $this->get('flash')->getMessages();
    $params = [
        'flash' => $flash,
        'title' => 'Анализатор страниц'
    ];

    // Render the template with the layout
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');

$app->post('/urls', function (Request $request, Response $response) {
    $urlRepository = $this->get('url_repository');
    $flash = $this->get('flash');
    $data = $request->getParsedBody();

    $url = $data['url']['name'] ?? '';
    $errors = Validator::validateUrl($url);

    if (!empty($errors)) {
        $flash->addMessage('danger', implode(', ', $errors));
        $params = [
            'errors' => ['url' => implode(', ', $errors)],
            'flash' => $flash->getMessages(),
            'title' => 'Анализатор страниц'
        ];
        return $this->get('renderer')->render($response->withStatus(422), 'index.phtml', $params);
    }

    $normalizedUrl = Validator::normalizeUrl($url);

    $existingUrl = $urlRepository->findByName($normalizedUrl);

    if ($existingUrl) {
        $flash->addMessage('info', 'Страница уже существует');
        return $response->withHeader('Location', '/urls/' . $existingUrl['id'])
            ->withStatus(302);
    }

    $id = $urlRepository->create($normalizedUrl);

    $flash->addMessage('success', 'Страница успешно добавлена');
    return $response->withHeader('Location', '/urls/' . $id)
        ->withStatus(302);
})->setName('urls.store');

$app->get('/urls', function (Request $request, Response $response) {
    $urlRepository = $this->get('url_repository');
    $urlCheckRepository = $this->get('url_check_repository');
    $flash = $this->get('flash')->getMessages();

    $urls = $urlRepository->findAll();

    // Add the latest check data to each URL
    foreach ($urls as &$url) {
        $latestCheck = $urlCheckRepository->findLatestByUrlId($url['id']);
        if ($latestCheck) {
            $url['last_check_created_at'] = $latestCheck['created_at'];
            $url['last_check_status_code'] = $latestCheck['status_code'];
        }
    }

    $params = [
        'urls' => $urls,
        'flash' => $flash,
        'title' => 'Сайты'
    ];

    // Explicitly tell it to use the layout
    return $this->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.index');

$app->get('/urls/{id:[0-9]+}', function (Request $request, Response $response, array $args) {
    $id = (int) $args['id'];
    $urlRepository = $this->get('url_repository');
    $urlCheckRepository = $this->get('url_check_repository');
    $flash = $this->get('flash')->getMessages();

    $url = $urlRepository->findById($id);

    if (!$url) {
        $flash = $this->get('flash');
        $flash->addMessage('danger', 'Страница не найдена');
        return $response->withHeader('Location', '/urls')
            ->withStatus(302);
    }

    $checks = $urlCheckRepository->findByUrlId($id);

    $params = [
        'url' => $url,
        'checks' => $checks,
        'flash' => $flash,
        'title' => 'Сайт ' . $url['name']
    ];

    // Explicitly tell it to use the layout
    return $this->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('urls.show');

$app->post('/urls/{id:[0-9]+}/checks', function (Request $request, Response $response, array $args) {
    $id = (int) $args['id'];
    $urlRepository = $this->get('url_repository');
    $urlCheckRepository = $this->get('url_check_repository');
    $analyzer = $this->get('analyzer');
    $flash = $this->get('flash');

    $url = $urlRepository->findById($id);

    if (!$url) {
        $flash->addMessage('danger', 'Страница не найдена');
        return $response->withHeader('Location', '/urls')
            ->withStatus(302);
    }

    try {
        $checkData = $analyzer->analyze($url['name']);

        $urlCheckRepository->create(
            $id,
            $checkData['status_code'],
            $checkData['h1'] ?? null,
            $checkData['title'] ?? null,
            $checkData['description'] ?? null
        );

        $flash->addMessage('success', 'Страница успешно проверена');
    } catch (\Exception $e) {
        $flash->addMessage('danger', 'Произошла ошибка при проверке: ' . $e->getMessage());
    }

    return $response->withHeader('Location', '/urls/' . $id)
        ->withStatus(302);
})->setName('urls.checks.store');

$app->run();
