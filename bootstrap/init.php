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

$debug = (bool) ($config['app']['debug'] ?? false);

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$logDirectory = $config['paths']['logs'] ?? $rootPath . '/storage/logs';
if (!is_dir($logDirectory)) {
    @mkdir($logDirectory, 0755, true);
}
$logDirectory = rtrim($logDirectory, '/\\');
if ($logDirectory !== '') {
    ini_set('error_log', $logDirectory . DIRECTORY_SEPARATOR . 'php-error.log');
}

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
