<?php

declare(strict_types=1);

use App\Core\Config;

$rootPath = dirname(__DIR__);
$autoloadPath = $rootPath . '/vendor/autoload.php';

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    spl_autoload_register(static function (string $class) use ($rootPath): void {
        $prefix = 'App\\';
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = $rootPath . '/app/' . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

$config = Config::get();

$timezone = $config['app']['timezone'] ?? 'UTC';
if (!empty($timezone) && is_string($timezone)) {
    date_default_timezone_set($timezone);
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $sessionConfig = $config['session'] ?? [];

    session_start([
        'cookie_lifetime' => $sessionConfig['lifetime'] ?? 7200,
        'cookie_httponly' => $sessionConfig['httponly'] ?? true,
        'cookie_secure' => $sessionConfig['secure'] ?? false,
        'cookie_samesite' => $sessionConfig['samesite'] ?? 'Lax',
    ]);
}

return $config;
