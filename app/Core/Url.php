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

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            $parsed = parse_url($configuredUrl);
            $path = $parsed['path'] ?? '';
            if ($path !== '' && $path !== '/') {
                return self::$basePath = rtrim($path, '/');
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
