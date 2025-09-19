<?php

declare(strict_types=1);

namespace App\Routing;

use App\Core\Router;

final class RouteRegistrar
{
    public static function registerWeb(Router $router): void
    {
        $routes = [
            ["GET", "/", "DashboardController@index", "home"],
            ["GET", "/login", "AuthController@showLogin", "login"],
            ["POST", "/login", "AuthController@login"],
            ["GET", "/register", "AuthController@showRegister", "register"],
            ["POST", "/register", "AuthController@register"],
            ["GET", "/logout", "AuthController@logout", "logout"],
            ["GET", "/forgot-password", "AuthController@showForgotPassword", "forgot-password"],
            ["POST", "/forgot-password", "AuthController@forgotPassword"],
            ["GET", "/reset-password/{token}", "AuthController@showResetPassword", "reset-password"],
            ["POST", "/reset-password", "AuthController@resetPassword"],
            ["GET", "/dashboard", "DashboardController@index", "dashboard"],
            ["GET", "/services", "ServiceController@index", "services"],
            ["GET", "/services/create", "ServiceController@create", "services.create"],
            ["POST", "/services/store", "ServiceController@store", "services.store"],
            ["GET", "/services/{id}/edit", "ServiceController@edit", "services.edit"],
            ["POST", "/services/{id}/update", "ServiceController@update", "services.update"],
            ["POST", "/services/{id}/delete", "ServiceController@delete", "services.delete"],
            ["GET", "/appointments", "AppointmentController@index", "appointments"],
            ["GET", "/appointments/create", "AppointmentController@create", "appointments.create"],
            ["POST", "/appointments/store", "AppointmentController@store", "appointments.store"],
            ["GET", "/appointments/{id}/edit", "AppointmentController@edit", "appointments.edit"],
            ["POST", "/appointments/{id}/update", "AppointmentController@update", "appointments.update"],
            ["POST", "/appointments/{id}/cancel", "AppointmentController@cancel", "appointments.cancel"],
            ["POST", "/appointments/{id}/complete", "AppointmentController@complete", "appointments.complete"],
            ["GET", "/appointments/{id}/whatsapp", "AppointmentController@whatsapp", "appointments.whatsapp"],
            ["GET", "/clients", "ClientController@index", "clients"],
            ["POST", "/clients/store", "ClientController@store", "clients.store"],
            ["GET", "/clients/{id}/edit", "ClientController@edit", "clients.edit"],
            ["POST", "/clients/{id}/update", "ClientController@update", "clients.update"],
            ["POST", "/clients/{id}/delete", "ClientController@delete", "clients.delete"],
            ["GET", "/settings", "SettingController@index", "settings"],
            ["POST", "/settings/update", "SettingController@update", "settings.update"],
            ["POST", "/settings/hours", "SettingController@updateHours", "settings.hours"],
            ["GET", "/subscription", "SubscriptionController@index", "subscription"],
            ["POST", "/subscription/checkout", "SubscriptionController@checkout", "subscription.checkout"],
            ["GET", "/subscription/success", "SubscriptionController@success", "subscription.success"],
            ["GET", "/subscription/failure", "SubscriptionController@failure", "subscription.failure"],
            ["POST", "/subscription/cancel", "SubscriptionController@cancel", "subscription.cancel"],
            ["GET", "/reports", "ReportController@index", "reports"],
            ["GET", "/reports/export", "ReportController@export", "reports.export"],
            ["POST", "/api/payment/preference", "PaymentController@createPreference", "payment.preference"],
            ["GET", "/payment/success", "PaymentController@success", "payment.success"],
            ["GET", "/payment/failure", "PaymentController@failure", "payment.failure"],
            ["GET", "/payment/pending", "PaymentController@pending", "payment.pending"],
            ["GET", "/payment/history", "PaymentController@history", "payment.history"],
            ["POST", "/webhook/mercadopago", "WebhookController@mercadopago", "webhook.mercadopago"],
            ["POST", "/webhook/payment", "PaymentController@webhook", "webhook.payment"],
        ];

        self::map($router, $routes);
    }

    public static function registerAjax(Router $router): void
    {
        self::map($router, [
            ["GET", "/api/appointments/check-overlap", "AppointmentController@checkOverlap"],
            ["GET", "/api/services/{id}/price", "ServiceController@getPrice"],
        ]);
    }

    public static function registerRest(Router $router, string $prefix = '/api'): void
    {
        $base = rtrim($prefix, '/');

        $routes = [
            ["POST", $base . '/auth/login', "Api\\AuthApiController@login"],
            ["POST", $base . '/auth/register', "Api\\AuthApiController@register"],
            ["POST", $base . '/auth/refresh', "Api\\AuthApiController@refresh"],
            ["GET", $base . '/auth/me', "Api\\AuthApiController@me"],
            ["POST", $base . '/auth/logout', "Api\\AuthApiController@logout"],
            ["GET", $base . '/services', "Api\\ServiceApiController@index"],
            ["GET", $base . '/services/{id}', "Api\\ServiceApiController@show"],
            ["POST", $base . '/services', "Api\\ServiceApiController@store"],
            ["PUT", $base . '/services/{id}', "Api\\ServiceApiController@update"],
            ["PATCH", $base . '/services/{id}', "Api\\ServiceApiController@update"],
            ["DELETE", $base . '/services/{id}', "Api\\ServiceApiController@destroy"],
            ["GET", $base . '/services/{id}/stats', "Api\\ServiceApiController@statistics"],
            ["GET", $base . '/services/{id}/statistics', "Api\\ServiceApiController@statistics"],
            ["GET", $base . '/clients', "Api\\ClientApiController@index"],
            ["GET", $base . '/clients/{id}', "Api\\ClientApiController@show"],
            ["POST", $base . '/clients', "Api\\ClientApiController@store"],
            ["PUT", $base . '/clients/{id}', "Api\\ClientApiController@update"],
            ["PATCH", $base . '/clients/{id}', "Api\\ClientApiController@update"],
            ["DELETE", $base . '/clients/{id}', "Api\\ClientApiController@destroy"],
            ["GET", $base . '/clients/{id}/appointments', "Api\\ClientApiController@appointments"],
            ["GET", $base . '/appointments', "Api\\AppointmentApiController@index"],
            ["GET", $base . '/appointments/{id}', "Api\\AppointmentApiController@show"],
            ["POST", $base . '/appointments', "Api\\AppointmentApiController@store"],
            ["PUT", $base . '/appointments/{id}', "Api\\AppointmentApiController@update"],
            ["PATCH", $base . '/appointments/{id}', "Api\\AppointmentApiController@update"],
            ["DELETE", $base . '/appointments/{id}', "Api\\AppointmentApiController@destroy"],
            ["GET", $base . '/appointments/by-date/{date}', "Api\\AppointmentApiController@byDate"],
            ["GET", $base . '/appointments/availability', "Api\\AppointmentApiController@checkAvailability"],
            ["POST", $base . '/appointments/availability', "Api\\AppointmentApiController@checkAvailability"],
            ["PATCH", $base . '/appointments/{id}/status', "Api\\AppointmentApiController@updateStatus"],
            ["PATCH", $base . '/appointments/{id}/cancel', "Api\\AppointmentApiController@cancel"],
        ];

        self::map($router, $routes);
    }

    public static function registerApiUtilities(Router $router, string $prefix = '/api/v1'): void
    {
        $base = rtrim($prefix, '/');
        $root = dirname(__DIR__, 2);

        $router->get(
            $base,
            static function () use ($base): void {
                header('Content-Type: application/json');
                echo json_encode([
                    'name' => 'AgendaFlow API',
                    'version' => 'v1',
                    'description' => 'RESTful API for appointment scheduling',
                    'documentation' => $base . '/docs',
                    'endpoints' => [
                        'auth' => $base . '/auth',
                        'appointments' => $base . '/appointments',
                        'services' => $base . '/services',
                        'clients' => $base . '/clients',
                    ],
                    'status' => 'operational',
                    'timestamp' => date('Y-m-d H:i:s'),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        );

        $docsPath = $root . '/app/Views/api/documentation.php';

        $router->get(
            $base . '/docs',
            static function () use ($docsPath): void {
                header('Content-Type: text/html; charset=utf-8');

                if (file_exists($docsPath)) {
                    include $docsPath;
                    return;
                }

                http_response_code(404);
                echo 'API documentation not available.';
            }
        );
    }

    private static function map(Router $router, array $routes): void
    {
        foreach ($routes as $route) {
            [$method, $path, $handler, $name] = array_pad($route, 4, null);
            $methodName = strtolower($method);

            if (!method_exists($router, $methodName)) {
                throw new \InvalidArgumentException("Unsupported HTTP method: {$method}");
            }

            $router->{$methodName}($path, $handler, $name);
        }
    }
}
