<?php

declare(strict_types=1);

use App\Core\Http\Dispatcher;
use App\Core\Router;
use App\Routing\RouteRegistrar;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = require dirname(__DIR__) . '/bootstrap/init.php';

$router = new Router();

RouteRegistrar::registerRest($router, '/api/v1');
RouteRegistrar::registerApiUtilities($router, '/api/v1');

Dispatcher::dispatch(
    $router,
    static function (Throwable $exception, string $method, string $uri): void {
        error_log(sprintf('[API][%s] %s %s - %s', date('c'), $method, $uri, $exception->getMessage()));
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => $exception->getMessage(),
        ]);
    }
);
