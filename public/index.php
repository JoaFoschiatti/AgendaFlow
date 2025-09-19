<?php

declare(strict_types=1);

use App\Core\Http\Dispatcher;
use App\Core\Router;
use App\Routing\RouteRegistrar;

$config = require dirname(__DIR__) . '/bootstrap/init.php';
$debug = (bool) ($config['app']['debug'] ?? false);

$router = new Router();

RouteRegistrar::registerWeb($router);
RouteRegistrar::registerAjax($router);
RouteRegistrar::registerRest($router, '/api');
RouteRegistrar::registerRest($router, '/api/v1');
RouteRegistrar::registerApiUtilities($router, '/api/v1');

Dispatcher::dispatch(
    $router,
    static function (Throwable $exception, string $method, string $uri) use ($debug): void {
        error_log(sprintf('[%s] %s %s - %s', date('c'), $method, $uri, $exception->getMessage()));
        http_response_code(500);

        if ($debug) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "Exception captured:\n";
            echo $exception->getMessage() . "\n\n";
            echo $exception->getTraceAsString();
            return;
        }

        echo 'Error interno del servidor';
    }
);
