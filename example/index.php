<?php
/**
 * Run this example by running a PHP server:
 *      `php -S localhost:8080 -t ./example`
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use DanieXl\SimpleRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DanieXl\SimpleRouter\Example\Actions\HomeAction;
use DanieXl\SimpleRouter\Example\Actions\Users\IndexAction;
use DanieXl\SimpleRouter\Example\Controllers\HomeController;

// Add routes in constructor
$router = new SimpleRouter(Request::createFromGlobals(), [
    HomeAction::class,
    IndexAction::class,
    [
        'name' => 'test',
        'path' => '/test',
        'method' => 'GET',
        'response' => function () {
            return new Response(json_encode(['data' => 'test']));
        }
    ],
]);

// Add route by function
$router->addRoute([
    'name' => 'test2',
    'path' => '/test2',
    'method' => 'GET',
    'response' => function () {
        return new Response(json_encode(['data' => 'test2']));
    }
]);

// Other way to add route.
// Method 'add' accepts both array or string
$router->add([
    'name' => 'controller',
    'path' => '/controller/:test',
    'method' => 'GET',
    'controller' => HomeController::class . '@test',
]);
// Or as string
$router->add(IndexAction::class);

// Match routes with current request
$response = $router->match();

// Send the resonse
$response->send();
