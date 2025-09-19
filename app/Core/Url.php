<?php

namespace App\Core;

class Url
{
    private static ?string $basePath = null;

    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }

        $configuredUrl = Config::get('app.url');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($requestUri !== '') {
            $requestUri = explode('?', $requestUri)[0];
        }

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            $parsed = parse_url($configuredUrl);
            $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

            if ($path !== '' && $path !== '/') {
                $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
                $publicPath = $path . '/public';

                if ($requestUri !== '' && strpos($requestUri, $publicPath) === 0) {
                    return self::$basePath = $publicPath;
                }

                if ($requestUri !== '' && strpos($requestUri, $path) !== 0) {
                    $normalizedScript = $scriptFilename !== '' ? str_replace('\\', '/', $scriptFilename) : '';

                    if ($normalizedScript !== '' && strpos($normalizedScript, '/public/index.php') !== false) {
                        return self::$basePath = '';
                    }

                    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                    $scriptDir = $scriptName !== '' ? rtrim(str_replace('\\', '/', dirname($scriptName)), '/') : '';
                    if ($scriptDir !== '' && $scriptDir !== '/' && strpos($requestUri, $scriptDir) === 0) {
                        return self::$basePath = $scriptDir;
                    }
                }

                return self::$basePath = $path;
            }
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName !== '') {
            $directory = str_replace('\\', '/', dirname($scriptName));
            if ($directory !== '/' && $directory !== '\\' && $directory !== '.') {
                return self::$basePath = rtrim($directory, '/');
            }
        }

        return self::$basePath = '';
    }

    public static function to(string $path = ''): string
    {
        $basePath = self::basePath();

        if ($path === '') {
            return $basePath === '' ? '/' : $basePath;
        }

        $normalizedPath = '/' . ltrim($path, '/');

        if ($basePath === '') {
            return $normalizedPath;
        }

        return $basePath . $normalizedPath;
    }

    public static function full(string $path = ''): string
    {
        $configuredUrl = Config::get('app.url');

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            $baseUrl = rtrim($configuredUrl, '/');
            if ($path === '') {
                return $baseUrl;
            }

            return $baseUrl . '/' . ltrim($path, '/');
        }

        return self::to($path);
    }

    public static function refresh(): void
    {
        self::$basePath = null;
    }
}