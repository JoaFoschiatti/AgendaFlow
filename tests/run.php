<?php

declare(strict_types=1);

// Simple end-to-end and unit test runner for AgendaFlow

require __DIR__ . '/Assertions.php';
require __DIR__ . '/HttpClient.php';

use function Assertions\assertTrue; // not used; keep via class

// 1) Prepare environment
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

// 2) Setup test database
echo "Setting up test database...\n";
$setupExit = 0;
passthru(PHP_BINARY . ' ' . escapeshellarg(__DIR__ . '/setup_db.php'), $setupExit);
if ($setupExit !== 0) {
    echo "Database setup failed. Aborting tests.\n";
    exit(1);
}

// 3) Start PHP built-in server for HTTP tests
$host = '127.0.0.1';
$port = 8090;
$docroot = realpath(__DIR__ . '/../public');

echo "Starting server at http://{$host}:{$port} ...\n";
$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['file', sys_get_temp_dir() . '/agendaflow_server_out.log', 'a'],
    2 => ['file', sys_get_temp_dir() . '/agendaflow_server_err.log', 'a'],
];
$env = array_merge($_ENV, ['APP_ENV' => 'test']);
$process = proc_open(PHP_BINARY . " -S {$host}:{$port} -t " . escapeshellarg($docroot), $descriptorSpec, $pipes, __DIR__ . '/..', $env);
if (!is_resource($process)) {
    echo "Failed to start PHP server.\n";
    exit(1);
}
// Give server time to start: wait up to ~5 seconds
for ($i = 0; $i < 25; $i++) {
    $conn = @fsockopen($host, $port, $errno, $errstr, 0.2);
    if (is_resource($conn)) {
        fclose($conn);
        break;
    }
    usleep(200000);
}

$client = new HttpClient("http://{$host}:{$port}");

function parseCsrf(string $html): ?string {
    if (preg_match('/name=\"_token\"\s+value=\"([a-f0-9]{64})\"/i', $html, $m)) {
        return $m[1];
    }
    return null;
}

Assertions::reset();

try {
    // Web: GET register and POST register
    $res = $client->get('/register');
    Assertions::assertEquals(200, $res['status'], 'GET /register 200');
    $token = parseCsrf($res['body']);
    Assertions::assertTrue(!empty($token), 'CSRF token present on /register');

    $postData = [
        '_token' => $token,
        'name' => 'Test User',
        'business_name' => 'Test Biz',
        'email' => 'test@example.com',
        'phone' => '3511234567',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'terms' => 'on'
    ];
    $res2 = $client->post('/register', $postData);
    Assertions::assertEquals(302, $res2['status'], 'POST /register redirects');
    Assertions::assertTrue(stripos($res2['headers'], 'Location: /dashboard') !== false, 'Redirect to /dashboard');

    // Services: create
    $res3 = $client->get('/services/create');
    Assertions::assertEquals(200, $res3['status'], 'GET /services/create 200');
    $token2 = parseCsrf($res3['body']);
    Assertions::assertTrue(!empty($token2), 'CSRF token present on /services/create');
    $res4 = $client->post('/services/store', [
        '_token' => $token2,
        'name' => 'Peluquería',
        'price_default' => '1000.00',
        'duration_min' => '30',
        'color' => '#00aa00'
    ]);
    Assertions::assertEquals(302, $res4['status'], 'POST /services/store redirects');
    $res5 = $client->get('/services');
    Assertions::assertContains('Peluquería', $res5['body'], 'Service appears in list');

    // Appointments: create, overlap check
    $res6 = $client->get('/appointments/create');
    $token3 = parseCsrf($res6['body']);
    Assertions::assertTrue(!empty($token3), 'CSRF token on /appointments/create');

    // Parse first service id from the select
    if (preg_match('/name=\"service_id\"[\s\S]*?<option value=\"(\d+)\"/i', $res6['body'], $m)) {
        $serviceId = (int)$m[1];
    } else {
        throw new RuntimeException('Could not find service_id');
    }
    $today = date('Y-m-d');
    $res7 = $client->post('/appointments/store', [
        '_token' => $token3,
        'client_name' => 'Cliente Demo',
        'phone' => '3511111111',
        'service_id' => (string)$serviceId,
        'date' => $today,
        'time' => '12:00',
        'price' => '1000.00'
    ]);
    Assertions::assertEquals(302, $res7['status'], 'POST /appointments/store redirects');

    // Overlap attempt same time
    $res8 = $client->post('/appointments/store', [
        '_token' => $token3,
        'client_name' => 'Otro Cliente',
        'phone' => '3512222222',
        'service_id' => (string)$serviceId,
        'date' => $today,
        'time' => '12:00',
        'price' => '1000.00'
    ]);
    // On overlap it redirects back to create with flash error
    Assertions::assertEquals(302, $res8['status'], 'Overlap redirect');

    // API: auth login
    $loginPayload = json_encode(['email' => 'test@example.com', 'password' => 'secret123']);
    $res9 = $client->post('/api/v1/auth/login', $loginPayload, ['Content-Type' => 'application/json']);
    Assertions::assertEquals(200, $res9['status'], 'API login 200');
    $data = json_decode($res9['body'], true);
    Assertions::assertTrue(($data['success'] ?? false) === true, 'API login success flag');
    $token = $data['data']['tokens']['access_token'] ?? null;
    Assertions::assertTrue(!empty($token), 'API token present');

    // API: list services with token
    $res10 = $client->get('/api/v1/services', ['Authorization' => 'Bearer ' . $token]);
    Assertions::assertEquals(200, $res10['status'], 'API services 200');
    $data2 = json_decode($res10['body'], true);
    Assertions::assertTrue(($data2['success'] ?? false) === true, 'API services success');
    Assertions::assertTrue(count($data2['data']['items'] ?? []) >= 1, 'API services has items');

} catch (Throwable $e) {
    echo "\nTest run error: " . $e->getMessage() . "\n";
    // Mark as failed so summary reflects the error
    Assertions::assertTrue(false, 'Test harness error: ' . $e->getMessage());
} finally {
    // Stop server
    if (isset($process) && is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
}

$summary = Assertions::summary();
echo "\n==== TEST SUMMARY ====\n";
echo "Passed: {$summary['passed']}\n";
echo "Failed: {$summary['failed']}\n";
if ($summary['failed'] > 0) {
    echo "Errors:\n";
    foreach ($summary['errors'] as $err) {
        echo "- {$err}\n";
    }
    exit(1);
}
echo "All good!\n";
exit(0);
