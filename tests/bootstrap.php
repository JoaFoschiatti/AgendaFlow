<?php

declare(strict_types=1);

$config = require __DIR__ . '/../bootstrap/init.php';

$GLOBALS['test_failures'] = [];

function runTest(string $name, callable $test): void
{
    echo "Running {$name}...";
    try {
        $test();
        echo "OK\n";
    } catch (Throwable $exception) {
        echo "FAIL\n";
        $GLOBALS['test_failures'][] = [
            'name' => $name,
            'message' => $exception->getMessage(),
        ];
        fwrite(STDERR, "[FAIL] {$name}: {$exception->getMessage()}\n");
    }
}

function assertSame($expected, $actual, string $message = ""): void
{
    if ($expected !== $actual) {
        if ($message === "") {
            $message = sprintf(
                'Expected %s but received %s',
                var_export($expected, true),
                var_export($actual, true)
            );
        }
        throw new RuntimeException($message);
    }
}

function assertGreaterThanOrEqual($expected, $actual, string $message = ""): void
{
    if ($actual < $expected) {
        if ($message === "") {
            $message = sprintf(
                'Expected a value >= %s but received %s',
                var_export($expected, true),
                var_export($actual, true)
            );
        }
        throw new RuntimeException($message);
    }
}
