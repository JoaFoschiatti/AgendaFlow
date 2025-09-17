<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Helpers;

runTest('Config::get returns data from example when custom file is missing', function (): void {
    Config::refresh();
    $config = Config::get();

    assertSame('AgendaFlow', Config::get('app.name'));
    assertSame($config['business']['trial_days'], Config::get('business.trial_days'));
});

runTest('Config::get returns default when key does not exist', function (): void {
    Config::refresh();
    assertSame('fallback', Config::get('nonexistent.key', 'fallback'));
});

runTest('Helpers::getTrialDaysRemaining includes current day when future date', function (): void {
    $tomorrow = (new DateTime('+1 day'))->setTime(23, 59, 59);
    $daysRemaining = Helpers::getTrialDaysRemaining($tomorrow->format('Y-m-d H:i:s'));
    assertGreaterThanOrEqual(1, $daysRemaining, 'Expected at least one day remaining for future trial end.');

    $yesterday = (new DateTime('-1 day'))->setTime(0, 0, 0);
    assertSame(0, Helpers::getTrialDaysRemaining($yesterday->format('Y-m-d H:i:s')));
});
