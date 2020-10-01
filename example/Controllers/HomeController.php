<?php

namespace DanieXl\SimpleRouter\Example\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HomeController
{
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function test($test)
    {
        return new Response(json_encode([
            'status' => 'success',
            'code' => 200,
            'message' => 'Home Controller',
            'data' => [
                'controller' => [$test],
            ]
        ]));
    }
}