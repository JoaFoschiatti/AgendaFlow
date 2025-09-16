<?php

namespace App\Core;

class RateLimiter
{
    private static array $storage = [];
    private static string $storageFile;

    public static function init(): void
    {
        self::$storageFile = dirname(__DIR__, 2) . '/storage/rate_limits.json';

        if (file_exists(self::$storageFile)) {
            $content = file_get_contents(self::$storageFile);
            self::$storage = json_decode($content, true) ?? [];
            self::cleanup();
        }
    }

    public static function check(string $identifier, int $maxAttempts = 60, int $windowMinutes = 1): bool
    {
        self::init();

        $key = md5($identifier);
        $now = time();
        $window = $windowMinutes * 60;

        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [
                'attempts' => 1,
                'first_attempt' => $now,
                'expires_at' => $now + $window
            ];
            self::save();
            return true;
        }

        $data = &self::$storage[$key];

        // Check if window has expired
        if ($now > $data['expires_at']) {
            $data = [
                'attempts' => 1,
                'first_attempt' => $now,
                'expires_at' => $now + $window
            ];
            self::save();
            return true;
        }

        // Increment attempts
        $data['attempts']++;

        if ($data['attempts'] > $maxAttempts) {
            self::save();
            return false;
        }

        self::save();
        return true;
    }

    public static function getRemainingAttempts(string $identifier, int $maxAttempts = 60): int
    {
        self::init();

        $key = md5($identifier);

        if (!isset(self::$storage[$key])) {
            return $maxAttempts;
        }

        $now = time();
        if ($now > self::$storage[$key]['expires_at']) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - self::$storage[$key]['attempts']);
    }

    public static function getResetTime(string $identifier): ?int
    {
        self::init();

        $key = md5($identifier);

        if (!isset(self::$storage[$key])) {
            return null;
        }

        $now = time();
        if ($now > self::$storage[$key]['expires_at']) {
            return null;
        }

        return self::$storage[$key]['expires_at'] - $now;
    }

    public static function reset(string $identifier): void
    {
        self::init();

        $key = md5($identifier);
        unset(self::$storage[$key]);
        self::save();
    }

    private static function cleanup(): void
    {
        $now = time();
        foreach (self::$storage as $key => $data) {
            if ($now > $data['expires_at']) {
                unset(self::$storage[$key]);
            }
        }
    }

    private static function save(): void
    {
        $dir = dirname(self::$storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(
            self::$storageFile,
            json_encode(self::$storage, JSON_PRETTY_PRINT)
        );
    }

    public static function middleware(string $identifier = null, int $maxAttempts = 60, int $windowMinutes = 1): void
    {
        $identifier = $identifier ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if (!self::check($identifier, $maxAttempts, $windowMinutes)) {
            $resetTime = self::getResetTime($identifier);

            header('X-RateLimit-Limit: ' . $maxAttempts);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . time() + $resetTime);
            header('Retry-After: ' . $resetTime);

            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $resetTime
            ]);
            exit;
        }

        $remaining = self::getRemainingAttempts($identifier, $maxAttempts);
        header('X-RateLimit-Limit: ' . $maxAttempts);
        header('X-RateLimit-Remaining: ' . $remaining);
    }
}