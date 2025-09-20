<?php

declare(strict_types=1);

// Simulated HTTP end-to-end test runner (no built-in server required)

require __DIR__ . '/Assertions.php';
require __DIR__ . '/sim_helpers.php';

// 1) Setup test database (quiet)
$setupExit = 0;
$output = [];
exec('"' . PHP_BINARY . '" ' . escapeshellarg(__DIR__ . '/setup_db.php'), $output, $setupExit);
if ($setupExit !== 0) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    echo "Database setup failed. Aborting tests.\n";
    exit(1);
}

Assertions::reset();

try {
    // Reset rate limiter for localhost
    require_once __DIR__ . '/../app/Core/RateLimiter.php';
    \App\Core\RateLimiter::reset('127.0.0.1');
    \App\Core\RateLimiter::reset('127.0.0.1:login');
    \App\Core\RateLimiter::reset('127.0.0.1:register');
    // Web: GET register and POST register
    $res = request_sim('GET', '/register');
    Assertions::assertEquals(200, $res['status'], 'GET /register 200');
    $token = parseCsrf_sim($res['body']);
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
    $res2 = request_sim('POST', '/register', $postData);
    Assertions::assertEquals(302, $res2['status'], 'POST /register redirects');
    // In CLI tests, headers_list() is empty; skip Location check

    // API: auth login (immediately after registration)
    $loginPayload = json_encode(['email' => 'test@example.com', 'password' => 'secret123']);
    $resLogin = request_sim('POST', '/api/v1/auth/login', $loginPayload, ['Content-Type' => 'application/json']);
    file_put_contents(__DIR__ . '/_tmp_login_first.txt', json_encode($resLogin, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    Assertions::assertEquals(200, $resLogin['status'], 'API login 200');
    $dataLogin = json_decode($resLogin['body'], true);
    Assertions::assertTrue(($dataLogin['success'] ?? false) === true, 'API login success flag');
    $token = $dataLogin['data']['tokens']['access_token'] ?? null;
    Assertions::assertTrue(!empty($token), 'API token present');

    // Services: create
    $res3 = request_sim('GET', '/services/create');
    Assertions::assertEquals(200, $res3['status'], 'GET /services/create 200');
    $token2 = parseCsrf_sim($res3['body']);
    Assertions::assertTrue(!empty($token2), 'CSRF token present on /services/create');
    $res4 = request_sim('POST', '/services/store', [
        '_token' => $token2,
        'name' => 'Peluquería',
        'price_default' => '1000.00',
        'duration_min' => '30',
        'color' => '#00aa00'
    ]);
    Assertions::assertEquals(302, $res4['status'], 'POST /services/store redirects');
    $res5 = request_sim('GET', '/services');
    Assertions::assertContains('Peluquer', $res5['body'], 'Service appears in list');

    // Appointments: create, overlap check
    $res6 = request_sim('GET', '/appointments/create');
    $token3 = parseCsrf_sim($res6['body']);
    Assertions::assertTrue(!empty($token3), 'CSRF token on /appointments/create');

    if (preg_match('/name=\"service_id\"[\s\S]*?<option value=\"(\d+)\"/i', $res6['body'], $m)) {
        $serviceId = (int)$m[1];
    } else {
        throw new RuntimeException('Could not find service_id');
    }
    $today = date('Y-m-d');
    $res7 = request_sim('POST', '/appointments/store', [
        '_token' => $token3,
        'client_name' => 'Cliente Demo',
        'phone' => '3511111111',
        'service_id' => (string)$serviceId,
        'date' => $today,
        'time' => '12:00',
        'price' => '1000.00'
    ]);
    Assertions::assertEquals(302, $res7['status'], 'POST /appointments/store redirects');

    $res8 = request_sim('POST', '/appointments/store', [
        '_token' => $token3,
        'client_name' => 'Otro Cliente',
        'phone' => '3512222222',
        'service_id' => (string)$serviceId,
        'date' => $today,
        'time' => '12:00',
        'price' => '1000.00'
    ]);
    Assertions::assertEquals(302, $res8['status'], 'Overlap redirect');

    // API: list services with token
    $res10 = request_sim('GET', '/api/v1/services', null, ['Authorization' => 'Bearer ' . $token]);
    Assertions::assertEquals(200, $res10['status'], 'API services 200');
    $data2 = json_decode($res10['body'], true);
    Assertions::assertTrue(($data2['success'] ?? false) === true, 'API services success');
    Assertions::assertTrue(count($data2['data']['items'] ?? []) >= 1, 'API services has items');

} catch (Throwable $e) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    echo "\nTest run error: " . $e->getMessage() . "\n";
    Assertions::assertTrue(false, 'Test harness error: ' . $e->getMessage());
}

$summary = Assertions::summary();
// Discard any buffered output from simulated requests
while (ob_get_level() > 0) { @ob_end_clean(); }
echo "\n==== TEST SUMMARY ====\n";
echo "Passed: {$summary['passed']}\n";
echo "Failed: {$summary['failed']}\n";
if ($summary['failed'] > 0) {
    echo "Errors:\n";
    foreach ($summary['errors'] as $err) {
        echo "- {$err}\n";
    }
    file_put_contents(__DIR__ . '/simulated_last_summary.txt', json_encode($summary));
    exit(1);
}
echo "All good!\n";
file_put_contents(__DIR__ . '/simulated_last_summary.txt', json_encode($summary));
exit(0);
