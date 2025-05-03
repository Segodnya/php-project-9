<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Validation\UrlValidator;
use App\Exceptions\ValidationException;

class UrlController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $urls = $this->getUrlRepository()->findAll();

        // Add the latest check data to each URL
        foreach ($urls as &$url) {
            $latestCheck = $this->getUrlCheckRepository()->findLatestByUrlId($url['id']);
            if ($latestCheck) {
                $url['last_check_created_at'] = $latestCheck['created_at'];
                $url['last_check_status_code'] = $latestCheck['status_code'];
            }
        }

        $params = [
            'urls' => $urls,
            'flash' => $this->getFlash()->getMessages(),
            'title' => 'Сайты'
        ];

        return $this->render($response, 'urls/index.phtml', $params);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';
        
        try {
            $validator = $this->container->get(UrlValidator::class);
            $normalizedUrl = $validator->validateAndNormalize($url);
            
            $existingUrl = $this->getUrlRepository()->findByName($normalizedUrl);

            if ($existingUrl) {
                $this->getFlash()->addMessage('info', 'Страница уже существует');
                return $response->withHeader('Location', '/urls/' . $existingUrl['id'])
                    ->withStatus(302);
            }

            $id = $this->getUrlRepository()->create(['name' => $normalizedUrl]);

            $this->getFlash()->addMessage('success', 'Страница успешно добавлена');
            return $response->withHeader('Location', '/urls/' . $id)
                ->withStatus(302);
        } catch (ValidationException $e) {
            $this->getFlash()->addMessage('danger', implode(', ', $e->getErrors()));
            $params = [
                'errors' => ['url' => implode(', ', $e->getErrors())],
                'flash' => $this->getFlash()->getMessages(),
                'title' => 'Анализатор страниц'
            ];
            return $this->render($response->withStatus(422), 'index.phtml', $params);
        } catch (\Exception $e) {
            $this->getFlash()->addMessage('danger', 'Произошла ошибка: ' . $e->getMessage());
            return $this->render($response->withStatus(500), 'index.phtml', [
                'flash' => $this->getFlash()->getMessages(),
                'title' => 'Анализатор страниц'
            ]);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->getUrlRepository()->findById($id);

        if (!$url) {
            $this->getFlash()->addMessage('danger', 'Страница не найдена');
            return $response->withHeader('Location', '/urls')
                ->withStatus(302);
        }

        $checks = $this->getUrlCheckRepository()->findByUrlId($id);

        $params = [
            'url' => $url,
            'checks' => $checks,
            'flash' => $this->getFlash()->getMessages(),
            'title' => 'Сайт ' . $url['name']
        ];

        return $this->render($response, 'urls/show.phtml', $params);
    }
} 