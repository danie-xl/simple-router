<?php

namespace DanieXl;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleRouter
{
    /** @var Request */
    private Request $request;
    /** @var array */
    private array $routes;
    /** @var array */
    private $options = [
        'headers' => ['Content-Type', 'application/json', false],
    ];

    /**
     * SimpleRouter Constructor
     *
     * @param Request $request
     * @param array $actions
     */
    public function __construct(Request $request, array $actions = [], array $options = [])
    {
        $this->request = $request;
        $this->options = array_merge($this->options, $options);
        foreach ($actions as $action) {
            $this->add($action);
        }
    }

    /**
     * Add new route/action in string like 'namespace::class'
     *
     * @param string $action
     * @return void
     * @throws InvalidArgumentException
     */
    public function add($route): void
    {
        if (is_array($route)) {
            $this->addRoute($route);
        } elseif (is_string($route)) {
            $this->addRouteAction($route);
        } else {
            $type = gettype($route);
            throw new \InvalidArgumentException(
                "Error, invalid route type. Given '$type'. Valid types: 'string', 'array"
            );
        }
    }

    /**
     * Adds Route Action
     *
     * @param string $action
     * @return void
     * @throws \InvalidArgumentException|\RunTimeException
     */
    public function addRouteAction(string $action)
    {
        if (!class_exists($action)) {
            throw new InvalidArgumentException("Error, invalid class given: '$action'.");
        } elseif (!defined("$action::ROUTE")) {
            throw new RuntimeException("Error, missing required constant 'ROUTE' in class '$action'.");
        }

        $route = $action::ROUTE;
        $route['action'] = $action;

        $this->addRoute($route);
    }

    /**
     * Validates route and adds it to the route collection
     *
     * @param array $route
     * @return void
     */
    public function addRoute(array $route)
    {
        $this->validate($route);
        $route['method'] = (array) $route['method'];

        $this->routes[] = $route;
    }

    /**
     * Validates route keys
     *
     * @param array $route
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validate(array $route): void
    {
        foreach (['path', 'name', 'method'] as $key) {
            if (empty($route[$key])) {
                throw new InvalidArgumentException("Error, missing required key '$key' in '{$route['action']}::ROUTE'");
            }
        }

        if (empty($route['action']) && empty($route['response']) && empty($route['controller'])) {
            throw new \Exception("Error, missing one required params: 'action', 'response' or 'controller'.");
        } elseif (!empty($route['response']) && !is_callable($route['response'])) {
            $type = gettype($route['response']);
            throw new InvalidArgumentException("Error, param 'response' must be a callable. Given type '$type'.");
        } elseif (!empty($route['controller']) && strpos($route['controller'], '@') === false) {
            throw new \Exception("Error, param 'controller' must contain a '@', like between 'Controller@method'");
        }
    }

    /**
     * Explodes path and resets array keys
     *
     * @param string $path
     * @param callable $callback
     * @return array
     */
    private function explodePath(string $path, callable $callback = null): array
    {
        $filter = $callback
            ? array_filter(explode('/', $path), $callback)
            : array_filter(explode('/', $path));

        return array_values($filter);
    }

    /**
     * Matches $routes||$this->routes on PATH_INFO
     * @param array $routes
     * @return array
     * @throws Exception
     */
    private function matchRoutesOnPath(array $routes = null): array
    {
        $matched = [];
        $requestedPath = $this->request->getPathInfo();
        foreach ($routes ?? $this->routes as $route) {
            $pattern = sprintf(
                "@^%s$@D",
                preg_replace('/\\\:[a-zA-Z0-9\_\-]+/', '([a-zA-Z0-9\-\_]+)', preg_quote($route['path']))
            );

            if (preg_match($pattern, $requestedPath, $route['params'])) {
                array_shift($route['params']);
                $matched[] = $route;
            }
        }

        if (empty($matched)) {
            throw new \Exception("Error, not found", 404);
        }

        return $matched;
    }

    /**
     * Try's to dispatch the Response from $route
     *
     * @param array $route
     * @return Response|null
     */
    private function dispatch(array $route)
    {
        $response = null;
        if (!empty($route['response'])) {
            $response = $route['response'](...$route['params']);
        } elseif (!empty($route['controller'])) {
            list($controller, $method) = explode('@', $route['controller']);
            $instance = new $controller($this->request);
            if (method_exists($instance, $method)) {
                $response = $instance->$method(...$route['params']);
            }
        } else {
            $instance = new $route['action']($this->request);
            $response = $instance(...$route['params']);
        }

        return $response;
    }

    /**
     * Prepares the response and sets default options like headers.
     *
     * @param array $route
     * @return Response
     */
    private function prepare(array $route): Response
    {
        $response = $this->dispatch($route);
        if (!$response instanceof Response) {
            $given = gettype($response);
            $given = $given === 'object' ? get_class($response) : $given;

            throw new \Exception("Error, route must return a instance of Response, given '$given'.");
        }

        foreach ($this->options['headers'] ?? [] as $name => $value) {
            if (is_array($value)) {
                $value = array_values($value);
                $response->headers->set(...$value);
                continue;
            }

            $response->headers->set($name, $value);
        }

        return $response->prepare($this->request);
    }

    /**
     * Updates Request with matched route
     *
     * @param array $route
     * @return void
     */
    private function updateRequest(array $route): void
    {
        $this->request->segments = new ParameterBag($this->explodePath($this->request->getPathInfo()));
        $paramNames = $this->explodePath($route['path'], function ($value) {
            return strpos($value, ':') !== false;
        });

        foreach ($route['params'] as $key => $value) {
            $name = str_replace(':', '', $paramNames[$key]);
            $this->request->attributes->set($name, $value);
            $this->request->$name = $value;
        }
    }

    /**
     * Matches current Request with given routes
     *
     * @return Response
     * @throws \Exception
     */
    public function match(): Response
    {
        dd($this->routes);
        $validMethods = [];
        $requestedMethod = $this->request->getMethod();
        foreach ($this->matchRoutesOnPath() as $route) {
            $validMethods = array_merge($validMethods, $route['method']);
            if (in_array($requestedMethod, $route['method'], true)) {
                $this->updateRequest($route);

                return $this->prepare($route);
            }
        }

        $validMethods = implode(', ', $validMethods);
        throw new \Exception("Error, method '$requestedMethod' is not allowed! allowed method('s): '$validMethods'");
    }
}
