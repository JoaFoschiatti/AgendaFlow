<?php

declare(strict_types=1);

// Ensure test env is set before bootstrap
putenv('APP_ENV=test');
$_ENV['APP_ENV'] = 'test';

require __DIR__ . '/../bootstrap/init.php';
require __DIR__ . '/Assertions.php';

use App\Core\Config;
use App\Core\DB;
use App\Core\Auth;
use App\Core\JWT as JwtCore;
use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Setting;

Assertions::reset();

// Ensure test env
\Assertions::assertTrue(Config::get('app.name') === 'AgendaFlow (Test)', 'Loads test config');

// DB connectivity
$pdo = DB::getInstance();
\Assertions::assertTrue($pdo instanceof PDO, 'PDO instance available');

// Create user
$userModel = new User();
$email = 'unit+' . uniqid() . '@example.com';
$uid = $userModel->createWithTrial([
    'name' => 'Unit User',
    'business_name' => 'Unit Biz',
    'email' => $email,
    'password' => 'unittest123',
]);
\Assertions::assertTrue(is_int($uid) && $uid > 0, 'User created');
$user = $userModel->find($uid);
\Assertions::assertTrue($user !== null, 'User retrievable');
\Assertions::assertTrue($userModel->isInTrial($uid) === true, 'User in trial');
\Assertions::assertTrue(Auth::verifyPassword('unittest123', $user['password_hash']), 'Password verifies');

// Create service
$serviceModel = new Service();
$sid = $serviceModel->create([
    'user_id' => $uid,
    'name' => 'Corte',
    'price_default' => 1500,
    'duration_min' => 30,
    'color' => '#123456',
    'is_active' => 1,
]);
\Assertions::assertTrue(is_int($sid) && $sid > 0, 'Service created');
$service = $serviceModel->find($sid);
\Assertions::assertEquals(30, (int)($service['duration_min'] ?? 0), 'Service duration stored');

// Settings
$settingModel = new Setting();
$ok = $settingModel->updateOrCreate($uid, 1, [
    'open_time' => '09:00:00',
    'close_time' => '18:00:00',
    'slot_minutes' => 30,
    'allow_overlaps' => 0,
    'closed' => 0,
]);
\Assertions::assertTrue($ok === true, 'Settings saved');
\Assertions::assertTrue($settingModel->allowsOverlaps($uid) === false, 'Overlaps disabled');

// Appointments
$aptModel = new Appointment();
$starts = date('Y-m-d') . ' 10:00:00';
$ends = date('Y-m-d') . ' 10:30:00';
$aid = $aptModel->create([
    'user_id' => $uid,
    'client_id' => null,
    'client_name' => 'Foo Bar',
    'service_id' => $sid,
    'price' => 1500,
    'starts_at' => $starts,
    'ends_at' => $ends,
    'status' => 'scheduled',
    'client_phone' => '3510000000',
]);
\Assertions::assertTrue(is_int($aid) && $aid > 0, 'Appointment created');
$apt = $aptModel->find($aid);
\Assertions::assertEquals('3510000000', $apt['phone'] ?? $apt['client_phone'] ?? null, 'Client phone alias works');

// Overlap check
\Assertions::assertTrue($aptModel->hasOverlap($uid, $starts, $ends) === true, 'Overlap detected');
\Assertions::assertTrue($aptModel->hasOverlap($uid, date('Y-m-d') . ' 11:00:00', date('Y-m-d') . ' 11:30:00') === false, 'No overlap later');

// JWT
$tokens = JwtCore::generateToken([
    'user_id' => $uid,
    'email' => $user['email'],
    'name' => $user['name']
]);
\Assertions::assertTrue(!empty($tokens['access_token']), 'Access token generated');
$payload = JwtCore::validateToken($tokens['access_token']);
\Assertions::assertEquals($uid, (int)$payload['user_id'], 'Access token validates');

$summary = \Assertions::summary();
echo json_encode($summary, JSON_PRETTY_PRINT) . "\n";
