<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Exceptions\HttpRequestException;

class UrlCheckController extends Controller
{
    public function store(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $url = $this->getUrlRepository()->findById($id);

        if (!$url) {
            $this->getFlash()->addMessage('danger', 'Страница не найдена');
            return $response->withHeader('Location', '/urls')
                ->withStatus(302);
        }

        try {
            $checkData = $this->getAnalyzer()->analyze($url['name']);
            
            // Create an array of data instead of passing individual parameters
            $data = [
                'url_id' => $id,
                'status_code' => $checkData['status_code'],
                'h1' => $checkData['h1'] ?? null,
                'title' => $checkData['title'] ?? null,
                'description' => $checkData['description'] ?? null
            ];
            
            $this->getUrlCheckRepository()->create($data);

            $this->getFlash()->addMessage('success', 'Страница успешно проверена');
        } catch (HttpRequestException $e) {
            // More specific error handling for HTTP request errors
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage();
            
            if ($statusCode) {
                $this->getFlash()->addMessage('danger', 
                    "Ошибка при проверке: HTTP код {$statusCode}. {$message}");
            } else {
                $this->getFlash()->addMessage('danger', 
                    "Ошибка при проверке: {$message}");
            }
        } catch (\Exception $e) {
            $this->getFlash()->addMessage('danger', 'Произошла ошибка при проверке: ' . $e->getMessage());
        }

        return $response->withHeader('Location', '/urls/' . $id)
            ->withStatus(302);
    }
} 