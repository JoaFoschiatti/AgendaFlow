<?php

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Router;
use App\Core\Url;

final class Dispatcher
{
    public static function dispatch(Router $router, callable $onError): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $parts = explode('?', $uri);
        $uri = $parts[0] ?? '/';

        $basePath = Url::basePath();
        if ($basePath !== '' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        $scriptDir = $scriptName !== '' ? rtrim(str_replace('\\', '/', dirname($scriptName)), '/') : '';
        if ($scriptDir !== '' && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        if (strpos($uri, '/public/') === 0 || $uri === '/public') {
            $uri = substr($uri, strlen('/public'));
        }

        $uri = '/' . ltrim($uri, '/');

        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, strlen('/index.php'));
        }

        if ($uri === '') {
            $uri = '/';
        }

        try {
            $router->dispatch($method, $uri);
        } catch (\Throwable $exception) {
            $onError($exception, $method, $uri);
        }
    }
}
