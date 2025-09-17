<?php

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('America/Argentina/Cordoba');

// Autoloader
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// Load configuration
$config = \App\Core\Config::get();

// Adjust timezone if configured
if (!empty($config['app']['timezone'])) {
    date_default_timezone_set($config['app']['timezone']);
}

// Start session
session_start([
    'cookie_lifetime' => $config['session']['lifetime'],
    'cookie_httponly' => $config['session']['httponly'],
    'cookie_secure' => $config['session']['secure'],
    'cookie_samesite' => $config['session']['samesite'],
]);

// Create router instance
$router = new \App\Core\Router();

// Define routes
// Public routes
$router->get('/', 'DashboardController@index', 'home');
$router->get('/login', 'AuthController@showLogin', 'login');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister', 'register');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout', 'logout');
$router->get('/forgot-password', 'AuthController@showForgotPassword', 'forgot-password');
$router->post('/forgot-password', 'AuthController@forgotPassword');
$router->get('/reset-password/{token}', 'AuthController@showResetPassword', 'reset-password');
$router->post('/reset-password', 'AuthController@resetPassword');

// Dashboard
$router->get('/dashboard', 'DashboardController@index', 'dashboard');

// Services
$router->get('/services', 'ServiceController@index', 'services');
$router->get('/services/create', 'ServiceController@create', 'services.create');
$router->post('/services/store', 'ServiceController@store', 'services.store');
$router->get('/services/{id}/edit', 'ServiceController@edit', 'services.edit');
$router->post('/services/{id}/update', 'ServiceController@update', 'services.update');
$router->post('/services/{id}/delete', 'ServiceController@delete', 'services.delete');

// Appointments
$router->get('/appointments', 'AppointmentController@index', 'appointments');
$router->get('/appointments/create', 'AppointmentController@create', 'appointments.create');
$router->post('/appointments/store', 'AppointmentController@store', 'appointments.store');
$router->get('/appointments/{id}/edit', 'AppointmentController@edit', 'appointments.edit');
$router->post('/appointments/{id}/update', 'AppointmentController@update', 'appointments.update');
$router->post('/appointments/{id}/cancel', 'AppointmentController@cancel', 'appointments.cancel');
$router->post('/appointments/{id}/complete', 'AppointmentController@complete', 'appointments.complete');
$router->get('/appointments/{id}/whatsapp', 'AppointmentController@whatsapp', 'appointments.whatsapp');

// Clients
$router->get('/clients', 'ClientController@index', 'clients');
$router->post('/clients/store', 'ClientController@store', 'clients.store');
$router->get('/clients/{id}/edit', 'ClientController@edit', 'clients.edit');
$router->post('/clients/{id}/update', 'ClientController@update', 'clients.update');
$router->post('/clients/{id}/delete', 'ClientController@delete', 'clients.delete');

// Settings
$router->get('/settings', 'SettingController@index', 'settings');
$router->post('/settings/update', 'SettingController@update', 'settings.update');
$router->post('/settings/hours', 'SettingController@updateHours', 'settings.hours');

// Subscription
$router->get('/subscription', 'SubscriptionController@index', 'subscription');
$router->post('/subscription/checkout', 'SubscriptionController@checkout', 'subscription.checkout');
$router->get('/subscription/success', 'SubscriptionController@success', 'subscription.success');
$router->get('/subscription/failure', 'SubscriptionController@failure', 'subscription.failure');
$router->post('/subscription/cancel', 'SubscriptionController@cancel', 'subscription.cancel');

// Reports
$router->get('/reports', 'ReportController@index', 'reports');
$router->get('/reports/export', 'ReportController@export', 'reports.export');

// Webhooks
$router->post('/webhook/mercadopago', 'WebhookController@mercadopago', 'webhook.mercadopago');

// API endpoints (for AJAX)
$router->get('/api/appointments/check-overlap', 'AppointmentController@checkOverlap');
$router->get('/api/services/{id}/price', 'ServiceController@getPrice');

// Get the request URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Debug output
echo "<!-- Debug Info:\n";
echo "Request URI: $uri\n";
echo "Request Method: $method\n";

// Remove query string
$uri = explode('?', $uri)[0];
echo "URI without query: $uri\n";

// Remove base path - FIXED FOR WINDOWS
$basePath = \App\Core\Url::basePath();
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Alternative: Try with index.php in the path
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, strlen('/index.php'));
}

if ($uri === '') {
    $uri = '/';
}

echo "Final URI for routing: $uri\n";
echo "-->\n";

try {
    $router->dispatch($method, $uri);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo "<h1>Error en AgendaFlow</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>URI: $uri</p>";
    echo "<p>Method: $method</p>";
    echo "<hr>";
    echo "<p><a href='" . \App\Core\Url::to('') . "'>Volver al inicio</a></p>";
}
