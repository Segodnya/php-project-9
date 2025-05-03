<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    public function index(Request $request, Response $response): Response
    {
        $params = [
            'title' => 'Анализатор страниц'
        ];

        return $this->responseBuilder->view('home/index.twig', $params);
    }
} 