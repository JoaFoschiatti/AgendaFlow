<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});

$GLOBALS['test_failures'] = [];

function runTest(string $name, callable $test): void
{
    echo "Running {$name}...";
    try {
        $test();
        echo "OK\n";
    } catch (Throwable $e) {
        echo "FAIL\n";
        $GLOBALS['test_failures'][] = [
            'name' => $name,
            'message' => $e->getMessage()
        ];
        fwrite(STDERR, "[FAIL] {$name}: {$e->getMessage()}\n");
    }
}

function assertSame($expected, $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        if ($message === '') {
            $message = sprintf('Expected %s but received %s', var_export($expected, true), var_export($actual, true));
        }
        throw new RuntimeException($message);
    }
}

function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void
{
    if ($actual < $expected) {
        if ($message === '') {
            $message = sprintf('Expected a value >= %s but received %s', var_export($expected, true), var_export($actual, true));
        }
        throw new RuntimeException($message);
    }
}
