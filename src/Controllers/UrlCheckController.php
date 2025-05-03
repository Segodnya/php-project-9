<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UrlCheckController extends Controller
{
    public function store(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->urlRepository()->findById($id);

        if (!$url) {
            $this->flash()->addMessage('danger', 'Страница не найдена');
            return $response->withHeader('Location', '/urls')
                ->withStatus(302);
        }

        try {
            $checkData = $this->analyzer()->analyze($url['name']);
            
            // Create an array of data instead of passing individual parameters
            $data = [
                'url_id' => $id,
                'status_code' => $checkData['status_code'],
                'h1' => $checkData['h1'] ?? null,
                'title' => $checkData['title'] ?? null,
                'description' => $checkData['description'] ?? null
            ];
            
            $this->urlCheckRepository()->create($data);

            $this->flash()->addMessage('success', 'Страница успешно проверена');
        } catch (\Exception $e) {
            $this->flash()->addMessage('danger', 'Произошла ошибка при проверке: ' . $e->getMessage());
        }

        return $response->withHeader('Location', '/urls/' . $id)
            ->withStatus(302);
    }
} 