<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get($route, $action) {
        $this->routes['GET'][$route] = $action;
    }

    public function post($route, $action) {
        $this->routes['POST'][$route] = $action;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];

        $uri = '/';
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = rtrim(\App\Core\Config::BASE_URL, '/');

        if (strpos($requestUri, $basePath) === 0) {
            $uri = substr($requestUri, strlen($basePath));

            // Strip .php extension for legacy compatibility
            if (substr($uri, -4) === '.php') {
                $uri = substr($uri, 0, -4);
            }

            if ($uri === '' || $uri === false) {
                $uri = '/';
            } else if ($uri !== '/' && $uri[0] !== '/') {
                $uri = '/' . $uri;
            }
        }

        if (isset($_GET['route'])) {
            $uri = '/' . ltrim($_GET['route'], '/');
        }

        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];
            if (is_callable($action)) {
                call_user_func($action);
            } else {
                list($controller, $methodName) = explode('@', $action);
                $controllerClass = "App\\Controllers\\" . $controller;
                if (class_exists($controllerClass)) {
                    $instance = new $controllerClass();
                    if (method_exists($instance, $methodName)) {
                        $instance->$methodName();
                        return;
                    }
                }
                $this->notFound();
            }
        } else {
            $this->notFound();
        }
    }

    private function notFound() {
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
        exit;
    }
}
