<?php

declare(strict_types=1);

use App\Core\Http\Dispatcher;
use App\Core\Router;
use App\Routing\RouteRegistrar;

$config = require dirname(__DIR__) . '/bootstrap/init.php';
$debug = (bool) ($config['app']['debug'] ?? false);

$router = new Router();

RouteRegistrar::registerRest($router, '/api/v1');
RouteRegistrar::registerApiUtilities($router, '/api/v1');

Dispatcher::dispatch(
    $router,
    static function (Throwable $exception, string $method, string $uri) use ($debug): void {
        error_log(sprintf('[API][%s] %s %s - %s', date('c'), $method, $uri, $exception->getMessage()));
        header('Content-Type: application/json');
        http_response_code(500);

        $payload = [
            'success' => false,
            'message' => 'Internal server error',
        ];

        if ($debug) {
            $payload['error'] = $exception->getMessage();
            $payload['trace'] = $exception->getTraceAsString();
        }

        echo json_encode($payload);
    }
);
