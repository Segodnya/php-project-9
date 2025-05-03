<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $flash = $this->flash()->getMessages();
        $params = [
            'flash' => $flash,
            'title' => 'Анализатор страниц'
        ];

        return $this->render($response, 'index.phtml', $params);
    }
} 