<?php

class Assertions
{
    private static int $passed = 0;
    private static int $failed = 0;
    private static array $errors = [];

    public static function reset(): void
    {
        self::$passed = 0;
        self::$failed = 0;
        self::$errors = [];
    }

    public static function assertTrue($cond, string $message = ''): void
    {
        if ($cond) {
            self::$passed++;
        } else {
            self::$failed++;
            self::$errors[] = $message ?: 'Expected true';
        }
    }

    public static function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected === $actual) {
            self::$passed++;
        } else {
            self::$failed++;
            $msg = $message ?: 'Values are not equal';
            self::$errors[] = $msg . "\nExpected: " . var_export($expected, true) . "\nActual:   " . var_export($actual, true);
        }
    }

    public static function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        if (strpos($haystack, $needle) !== false) {
            self::$passed++;
        } else {
            self::$failed++;
            self::$errors[] = $message ?: "Expected to find substring: {$needle}";
        }
    }

    public static function summary(): array
    {
        return [
            'passed' => self::$passed,
            'failed' => self::$failed,
            'errors' => self::$errors,
        ];
    }
}

