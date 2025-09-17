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

// Payment routes
$router->post('/api/payment/preference', 'PaymentController@createPreference', 'payment.preference');
$router->get('/payment/success', 'PaymentController@success', 'payment.success');
$router->get('/payment/failure', 'PaymentController@failure', 'payment.failure');
$router->get('/payment/pending', 'PaymentController@pending', 'payment.pending');
$router->get('/payment/history', 'PaymentController@history', 'payment.history');

// Webhooks
$router->post('/webhook/mercadopago', 'WebhookController@mercadopago', 'webhook.mercadopago');
$router->post('/webhook/payment', 'PaymentController@webhook', 'webhook.payment');

// API endpoints (for AJAX)
$router->get('/api/appointments/check-overlap', 'AppointmentController@checkOverlap');
$router->get('/api/services/{id}/price', 'ServiceController@getPrice');

// REST API Routes
// Authentication
$router->post('/api/auth/login', 'Api\AuthApiController@login');
$router->post('/api/auth/register', 'Api\AuthApiController@register');
$router->post('/api/auth/refresh', 'Api\AuthApiController@refresh');
$router->get('/api/auth/me', 'Api\AuthApiController@me');

// Services API
$router->get('/api/services', 'Api\ServiceApiController@index');
$router->get('/api/services/{id}', 'Api\ServiceApiController@show');
$router->post('/api/services', 'Api\ServiceApiController@store');
$router->put('/api/services/{id}', 'Api\ServiceApiController@update');
$router->delete('/api/services/{id}', 'Api\ServiceApiController@destroy');
$router->get('/api/services/{id}/stats', 'Api\ServiceApiController@stats');

// Clients API
$router->get('/api/clients', 'Api\ClientApiController@index');
$router->get('/api/clients/{id}', 'Api\ClientApiController@show');
$router->post('/api/clients', 'Api\ClientApiController@store');
$router->put('/api/clients/{id}', 'Api\ClientApiController@update');
$router->delete('/api/clients/{id}', 'Api\ClientApiController@destroy');
$router->get('/api/clients/{id}/appointments', 'Api\ClientApiController@appointments');

// Appointments API
$router->get('/api/appointments', 'Api\AppointmentApiController@index');
$router->get('/api/appointments/{id}', 'Api\AppointmentApiController@show');
$router->post('/api/appointments', 'Api\AppointmentApiController@store');
$router->put('/api/appointments/{id}', 'Api\AppointmentApiController@update');
$router->delete('/api/appointments/{id}', 'Api\AppointmentApiController@destroy');
$router->get('/api/appointments/by-date/{date}', 'Api\AppointmentApiController@byDate');
$router->get('/api/appointments/availability', 'Api\AppointmentApiController@checkAvailability');
$router->patch('/api/appointments/{id}/status', 'Api\AppointmentApiController@updateStatus');
$router->patch('/api/appointments/{id}/cancel', 'Api\AppointmentApiController@cancel');

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
    error_log($e->getMessage());
    http_response_code(500);
    echo "Error interno del servidor";
}