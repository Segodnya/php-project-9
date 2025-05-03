<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Validation\UrlValidator;

class UrlController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $urls = $this->urlRepository()->findAll();

        // Add the latest check data to each URL
        foreach ($urls as &$url) {
            $latestCheck = $this->urlCheckRepository()->findLatestByUrlId($url['id']);
            if ($latestCheck) {
                $url['last_check_created_at'] = $latestCheck['created_at'];
                $url['last_check_status_code'] = $latestCheck['status_code'];
            }
        }

        $params = [
            'urls' => $urls,
            'flash' => $this->flash()->getMessages(),
            'title' => 'Сайты'
        ];

        return $this->render($response, 'urls/index.phtml', $params);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';
        
        $validator = new UrlValidator();
        $errors = $validator->validate($url);

        if (!empty($errors)) {
            $this->flash()->addMessage('danger', implode(', ', $errors));
            $params = [
                'errors' => ['url' => implode(', ', $errors)],
                'flash' => $this->flash()->getMessages(),
                'title' => 'Анализатор страниц'
            ];
            return $this->render($response->withStatus(422), 'index.phtml', $params);
        }

        $normalizedUrl = $validator->normalize($url);
        $existingUrl = $this->urlRepository()->findByName($normalizedUrl);

        if ($existingUrl) {
            $this->flash()->addMessage('info', 'Страница уже существует');
            return $response->withHeader('Location', '/urls/' . $existingUrl['id'])
                ->withStatus(302);
        }

        $id = $this->urlRepository()->create(['name' => $normalizedUrl]);

        $this->flash()->addMessage('success', 'Страница успешно добавлена');
        return $response->withHeader('Location', '/urls/' . $id)
            ->withStatus(302);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->urlRepository()->findById($id);

        if (!$url) {
            $this->flash()->addMessage('danger', 'Страница не найдена');
            return $response->withHeader('Location', '/urls')
                ->withStatus(302);
        }

        $checks = $this->urlCheckRepository()->findByUrlId($id);

        $params = [
            'url' => $url,
            'checks' => $checks,
            'flash' => $this->flash()->getMessages(),
            'title' => 'Сайт ' . $url['name']
        ];

        return $this->render($response, 'urls/show.phtml', $params);
    }
} 