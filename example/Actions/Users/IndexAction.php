<?php

namespace DanieXl\SimpleRouter\Example\Actions\Users;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexAction
{
    const ROUTE = [
        'path' => '/users/:id/posts/:pid',
        'method' => [Request::METHOD_GET, Request::METHOD_POST],
        'name' => 'users',
    ];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function __invoke(int $id, $pid)
    {
        dump($id, $pid, $this->request);
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