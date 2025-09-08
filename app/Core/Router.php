<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $namedRoutes = [];
    
    public function get(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('GET', $path, $handler, $name);
    }
    
    public function post(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('POST', $path, $handler, $name);
    }
    
    public function put(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('PUT', $path, $handler, $name);
    }
    
    public function delete(string $path, $handler, ?string $name = null): void
    {
        $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    private function addRoute(string $method, string $path, $handler, ?string $name = null): void
    {
        // Escape special characters first, then convert path parameters to regex
        $pattern = preg_quote($path, '#');
        $pattern = preg_replace('/\\\{([a-zA-Z_][a-zA-Z0-9_]*)\\\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[$method][$pattern] = $handler;
        
        if ($name !== null) {
            $this->namedRoutes[$name] = $path;
        }
    }
    
    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        
        if (!isset($this->routes[$method])) {
            $this->sendNotFound();
            return;
        }
        
        foreach ($this->routes[$method] as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                // Remove numeric keys from matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                $this->callHandler($handler, $params);
                return;
            }
        }
        
        $this->sendNotFound();
    }
    
    private function callHandler($handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }
        
        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler);
            
            $controllerClass = "\\App\\Controllers\\{$controller}";
            
            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller {$controllerClass} not found");
            }
            
            // Validate that the controller extends the base Controller class
            if (!is_subclass_of($controllerClass, \App\Core\Controller::class)) {
                throw new \Exception("Invalid controller: {$controller} must extend Controller");
            }
            
            $instance = new $controllerClass();
            
            if (!method_exists($instance, $method)) {
                throw new \Exception("Method {$method} not found in controller {$controller}");
            }
            
            call_user_func_array([$instance, $method], $params);
            return;
        }
        
        throw new \Exception("Invalid route handler");
    }
    
    private function sendNotFound(): void
    {
        http_response_code(404);
        echo "404 - Página no encontrada";
    }
    
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route {$name} not found");
        }
        
        $path = $this->namedRoutes[$name];
        
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }
}