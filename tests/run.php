<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

require __DIR__ . '/ConfigAndHelpersTest.php';
require __DIR__ . '/UrlTest.php';

if (!empty($GLOBALS['test_failures'])) {
    exit(1);
}

echo "All tests passed.\n";
