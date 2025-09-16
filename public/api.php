<?php

// API Entry Point
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/Argentina/Cordoba');

// Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Create router instance
$router = new \App\Core\Router();

// API Version prefix
$apiPrefix = '/api/v1';

// ===========================
// PUBLIC AUTH ENDPOINTS
// ===========================
$router->post($apiPrefix . '/auth/login', 'Api\AuthApiController@login');
$router->post($apiPrefix . '/auth/register', 'Api\AuthApiController@register');
$router->post($apiPrefix . '/auth/refresh', 'Api\AuthApiController@refresh');

// ===========================
// PROTECTED AUTH ENDPOINTS
// ===========================
$router->get($apiPrefix . '/auth/me', 'Api\AuthApiController@me');
$router->post($apiPrefix . '/auth/logout', 'Api\AuthApiController@logout');

// ===========================
// APPOINTMENTS ENDPOINTS
// ===========================
$router->get($apiPrefix . '/appointments', 'Api\AppointmentApiController@index');
$router->get($apiPrefix . '/appointments/{id}', 'Api\AppointmentApiController@show');
$router->post($apiPrefix . '/appointments', 'Api\AppointmentApiController@store');
$router->put($apiPrefix . '/appointments/{id}', 'Api\AppointmentApiController@update');
$router->patch($apiPrefix . '/appointments/{id}', 'Api\AppointmentApiController@update');
$router->delete($apiPrefix . '/appointments/{id}', 'Api\AppointmentApiController@destroy');
$router->post($apiPrefix . '/appointments/availability', 'Api\AppointmentApiController@checkAvailability');

// ===========================
// SERVICES ENDPOINTS
// ===========================
$router->get($apiPrefix . '/services', 'Api\ServiceApiController@index');
$router->get($apiPrefix . '/services/{id}', 'Api\ServiceApiController@show');
$router->post($apiPrefix . '/services', 'Api\ServiceApiController@store');
$router->put($apiPrefix . '/services/{id}', 'Api\ServiceApiController@update');
$router->patch($apiPrefix . '/services/{id}', 'Api\ServiceApiController@update');
$router->delete($apiPrefix . '/services/{id}', 'Api\ServiceApiController@destroy');
$router->get($apiPrefix . '/services/{id}/statistics', 'Api\ServiceApiController@statistics');

// ===========================
// CLIENTS ENDPOINTS
// ===========================
$router->get($apiPrefix . '/clients', 'Api\ClientApiController@index');
$router->get($apiPrefix . '/clients/{id}', 'Api\ClientApiController@show');
$router->post($apiPrefix . '/clients', 'Api\ClientApiController@store');
$router->put($apiPrefix . '/clients/{id}', 'Api\ClientApiController@update');
$router->patch($apiPrefix . '/clients/{id}', 'Api\ClientApiController@update');
$router->delete($apiPrefix . '/clients/{id}', 'Api\ClientApiController@destroy');
$router->get($apiPrefix . '/clients/{id}/appointments', 'Api\ClientApiController@appointments');

// ===========================
// API DOCUMENTATION
// ===========================
$router->get($apiPrefix . '/docs', function() {
    header('Content-Type: text/html; charset=utf-8');
    include dirname(__DIR__) . '/app/Views/api/documentation.php';
});

// ===========================
// API ROOT
// ===========================
$router->get($apiPrefix, function() {
    header('Content-Type: application/json');
    echo json_encode([
        'name' => 'AgendaFlow API',
        'version' => 'v1',
        'description' => 'RESTful API for appointment scheduling',
        'documentation' => '/api/v1/docs',
        'endpoints' => [
            'auth' => '/api/v1/auth',
            'appointments' => '/api/v1/appointments',
            'services' => '/api/v1/services',
            'clients' => '/api/v1/clients'
        ],
        'status' => 'operational',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
});

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string
$uri = explode('?', $uri)[0];

// Remove base path if needed
$basePath = '/AgendaFlow/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

try {
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}