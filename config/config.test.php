<?php

return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'agendaflow_test',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    'app' => [
        'name' => 'AgendaFlow (Test)',
        'slogan' => 'Pruebas automatizadas',
        'url' => 'http://127.0.0.1:8083',
        'timezone' => 'America/Argentina/Cordoba',
        'locale' => 'es-AR',
        'currency' => 'ARS',
        'debug' => true,
    ],

    'business' => [
        'trial_days' => 14,
        'plan_price' => 8900.00,
        'plan_name' => 'AgendaFlow Mensual',
    ],

    'mercadopago' => [
        'access_token' => 'TEST-ACCESS-TOKEN',
        'public_key' => 'TEST-PUBLIC-KEY',
        'sandbox' => true,
        'webhook_secret' => '',
        'enabled' => false,
    ],

    'session' => [
        'lifetime' => 7200,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'security' => [
        'csrf_token_name' => '_token',
        'password_algo' => PASSWORD_DEFAULT,
    ],

    'paths' => [
        'storage' => dirname(__DIR__) . '/storage',
        'logs' => dirname(__DIR__) . '/storage/logs',
        'views' => dirname(__DIR__) . '/app/Views',
    ],
];

