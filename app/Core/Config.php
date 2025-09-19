<?php

namespace App\Core;

class Config
{
    private static ?array $config = null;
    private static bool $usingExample = false;

    public static function get(?string $key = null, $default = null)
    {
        $config = self::load();

        if ($key === null) {
            return $config;
        }

        $segments = explode('.', $key);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $basePath = dirname(__DIR__, 2) . '/config/';
        $primaryConfig = $basePath . 'config.php';
        $exampleConfig = $basePath . 'config.example.php';

        if (file_exists($primaryConfig)) {
            $config = require $primaryConfig;
        } elseif (file_exists($exampleConfig)) {
            $config = require $exampleConfig;
            self::$usingExample = true;
        } else {
            throw new \RuntimeException('No configuration file found.');
        }

        if (!is_array($config)) {
            throw new \RuntimeException('Configuration file must return an array.');
        }

        self::$config = $config;

        return self::$config;
    }

    public static function refresh(): void
    {
        self::$config = null;
        self::$usingExample = false;
    }

    public static function usingExample(): bool
    {
        if (self::$config === null) {
            self::load();
        }

        return self::$usingExample;
    }
}
