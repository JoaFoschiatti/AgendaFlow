<?php

// API Configuration file
return [
    // JWT Configuration
    'jwt' => [
        'secret_key' => 'sk_7f8a9b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f',
        'algorithm' => 'HS256',
        'access_lifetime' => 86400, // 24 hours
        'refresh_lifetime' => 604800, // 7 days
    ],

    // API Configuration
    'api' => [
        'version' => 'v1',
        'rate_limit' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        'allowed_origins' => ['*'], // Configure specific origins in production
    ],
];