<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Url;

/**
 * Override configuration for isolated tests.
 */
function overrideConfig(array $config): void
{
    $reflection = new ReflectionClass(Config::class);

    $configProperty = $reflection->getProperty('config');
    $configProperty->setAccessible(true);
    $configProperty->setValue(null, $config);

    $usingExampleProperty = $reflection->getProperty('usingExample');
    $usingExampleProperty->setAccessible(true);
    $usingExampleProperty->setValue(null, false);

    Url::refresh();
}

$defaultConfig = Config::get();

runTest('Url::basePath uses configured app.url path', function () use ($defaultConfig): void {
    overrideConfig($defaultConfig);
    Url::refresh();

    assertSame('/AgendaFlow/public', Url::basePath());
    assertSame('/AgendaFlow/public/login', Url::to('login'));
    assertSame('/AgendaFlow/public', Url::to(''));
    assertSame('http://localhost/AgendaFlow/public/login', Url::full('login'));
});

runTest('Url::basePath falls back to script name when no app.url provided', function () use ($defaultConfig): void {
    $customConfig = $defaultConfig;
    $customConfig['app']['url'] = '';

    overrideConfig($customConfig);
    $_SERVER['SCRIPT_NAME'] = '/custom/path/index.php';
    Url::refresh();

    assertSame('/custom/path', Url::basePath());
    assertSame('/custom/path/dashboard', Url::to('dashboard'));
    assertSame('/custom/path', Url::to(''));
    assertSame('/custom/path/dashboard', Url::full('dashboard'));

    unset($_SERVER['SCRIPT_NAME']);
    overrideConfig($defaultConfig);
});

// Restore configuration to default after tests
overrideConfig($defaultConfig);
