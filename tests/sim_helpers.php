<?php

declare(strict_types=1);

// Shared helpers for simulated HTTP testing

putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

// Prevent premature output so header() calls work in CLI
if (ob_get_level() === 0) { ob_start(); }

$root = dirname(__DIR__);
$publicIndex = $root . '/public/index.php';
if (!file_exists($publicIndex)) {
    throw new RuntimeException('public/index.php not found');
}

function resetGlobals(): void {
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    $_COOKIE = [];
    if (!headers_sent()) { header_remove(); }
    http_response_code(200);
}

function request_sim(string $method, string $path, $data = null, array $headers = []): array {
    global $publicIndex;

    resetGlobals();

    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['HTTP_HOST'] = '127.0.0.1';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    foreach ($headers as $k => $v) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $k));
        $_SERVER[$key] = $v;
        $lower = strtolower($k);
        if ($lower === 'authorization') {
            $_SERVER['Authorization'] = $v;
        }
        if ($lower === 'content-type') {
            $_SERVER['CONTENT_TYPE'] = $v;
        }
    }

    $replacedStream = false;
    if ($method === 'POST') {
        if (is_array($data)) {
            $_POST = $data;
        } elseif (is_string($data)) {
            $GLOBALS['__SIM_INPUT__'] = $data;
            // No need to override php://input; ApiController will read __SIM_INPUT__ in CLI tests
            $_SERVER['CONTENT_LENGTH'] = (string) strlen($data);
        }
    }

    ob_start();
    include $publicIndex;
    $body = ob_get_clean();
    $headersRaw = implode("\r\n", headers_list());
    $status = http_response_code();

    if ($replacedStream) {
        // No stream wrapper to restore when not overridden
        unset($GLOBALS['__SIM_INPUT__']);
    }

    return [
        'status' => $status,
        'headers' => $headersRaw,
        'body' => $body,
    ];
}

// No custom stream wrapper required

function parseCsrf_sim(string $html): ?string {
    if (preg_match('/name=\"_token\"\s+value=\"([a-f0-9]{64})\"/i', $html, $m)) {
        return $m[1];
    }
    return null;
}
