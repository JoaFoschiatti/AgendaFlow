<?php

return [
    // Database configuration
    'database' => [
        'host' => 'localhost',
        'dbname' => 'agendaflow',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    // Application settings
    'app' => [
        'name' => 'AgendaFlow',
        'slogan' => 'Fluye con tu agenda digital',
        'url' => 'http://localhost/AgendaFlow',
        'timezone' => 'America/Argentina/Cordoba',
        'locale' => 'es-AR',
        'currency' => 'ARS',
        'debug' => true,
    ],
    
    // Business settings
    'business' => [
        'trial_days' => 14,
        'plan_price' => 8900.00,
        'plan_name' => 'AgendaFlow Mensual',
    ],
    
    // MercadoPago settings
    'mercadopago' => [
        'access_token' => 'YOUR_ACCESS_TOKEN_HERE',
        'public_key' => 'YOUR_PUBLIC_KEY_HERE',
        'sandbox' => true,
        'webhook_secret' => 'YOUR_WEBHOOK_SECRET_HERE',
    ],
    
    // Session settings
    'session' => [
        'lifetime' => 7200, // 2 hours
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ],
    
    // Security
    'security' => [
        'csrf_token_name' => '_token',
        'password_algo' => PASSWORD_DEFAULT,
    ],
    
    // Paths
    'paths' => [
        'storage' => dirname(__DIR__) . '/storage',
        'logs' => dirname(__DIR__) . '/storage/logs',
        'views' => dirname(__DIR__) . '/app/Views',
    ],
];