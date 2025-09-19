<?php

declare(strict_types=1);

use App\Core\Http\Dispatcher;
use App\Core\Router;
use App\Routing\RouteRegistrar;

error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = require dirname(__DIR__) . '/bootstrap/init.php';

$router = new Router();

RouteRegistrar::registerWeb($router);
RouteRegistrar::registerAjax($router);
RouteRegistrar::registerRest($router, '/api');
RouteRegistrar::registerRest($router, '/api/v1');
RouteRegistrar::registerApiUtilities($router, '/api/v1');

Dispatcher::dispatch(
    $router,
    static function (Throwable $exception, string $method, string $uri): void {
        error_log(sprintf('[%s] %s %s - %s', date('c'), $method, $uri, $exception->getMessage()));
        http_response_code(500);
        echo 'Error interno del servidor';
    }
);
