# SimpleRouter

## Installation
Install by composer
```sh
composer require danie-xl/simple-router
```

## Usage
Create request from globals
```php
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
```

### Instantiate SimpleRouter

Instantiate without routes defined
```php
$router = new DanieXl\SimpleRouter($request);
```

### Add routes

Most simple way is to add the routes to the constructor
```php
// Instantiate with routes defined
$router = new DanieXl\SimpleRouter($request, [
    Actions\Users\HomeAction::class,
    [
        'name' => 'controller',
        'path' => '/controller/:test',
        'method' => 'GET',
        'controller' => HomeController::class . '@test',
    ],
]);
```

Or adding them later as Action
```php
$router->add(HomeAction::class);
// Or
$router->addAction(HomeAction::class);
```

Or adding them as route (array)
```php
$route = [
    'name' => 'controller',
    'path' => '/controller/:test',
    'method' => 'GET',
    'controller' => HomeController::class . '@test',
];
$router->add($route);
// Or
$routes->addRoute($route);
```
