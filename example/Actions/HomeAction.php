<?php

namespace DanieXl\SimpleRouter\Example\Actions;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeAction
{
    const ROUTE = [
        'path' => '/',
        'method' => [Request::METHOD_GET],
        'name' => 'home',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function __invoke()
    {
        return new Response(json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Home',
            'data' => [
                'user' => [],
            ]
        ]));
    }
}