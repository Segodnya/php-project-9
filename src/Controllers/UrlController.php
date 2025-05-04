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
            'title' => 'Сайты'
        ];

        return $this->responseBuilder->view('urls/index.twig', $params);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $url = $data['url']['name'] ?? '';

        try {
            error_log('Validating URL: ' . $url);
            $validator = $this->container->get(UrlValidator::class);
            $normalizedUrl = $validator->validateAndNormalize($url);
            error_log('Normalized URL: ' . $normalizedUrl);

            $existingUrl = $this->getUrlRepository()->findByName($normalizedUrl);

            if ($existingUrl) {
                error_log('URL already exists with ID: ' . $existingUrl['id']);
                $this->getFlash()->addMessage('info', 'Страница уже существует');
                return $this->responseBuilder->redirect('/urls/' . $existingUrl['id']);
            }

            error_log('Creating new URL: ' . $normalizedUrl);
            $id = $this->getUrlRepository()->create(['name' => $normalizedUrl]);
            error_log('URL created with ID: ' . $id);

            $this->getFlash()->addMessage('success', 'Страница успешно добавлена');
            return $this->responseBuilder->redirect('/urls/' . $id);
        } catch (ValidationException $e) {
            error_log('Validation exception: ' . $e->getMessage());
            // The error middleware will handle this exception
            throw $e;
        } catch (\Exception $e) {
            error_log('Unexpected error in UrlController::store: ' . $e->getMessage());
            $this->getFlash()->addMessage('danger', 'Произошла ошибка: ' . $e->getMessage());
            return $this->responseBuilder->redirect('/');
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->getUrlRepository()->findById($id);

        if (!$url) {
            return $this->responseBuilder->notFound('Страница не найдена');
        }

        $checks = $this->getUrlCheckRepository()->findByUrlId($id);

        $params = [
            'url' => $url,
            'checks' => $checks,
            'title' => 'Сайт ' . $url['name']
        ];

        return $this->responseBuilder->view('urls/show.twig', $params);
    }
}