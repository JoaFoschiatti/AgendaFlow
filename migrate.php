<?php

declare(strict_types=1);

use App\Core\Config;

require __DIR__ . '/bootstrap/init.php';

echo "=== AgendaFlow Database Migration Tool ===\n\n";

$config = Config::get();

try {
    $dsn = sprintf(
        'mysql:host=%s;charset=%s',
        $config['database']['host'],
        $config['database']['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "[OK] Connected to MySQL server\n";
} catch (PDOException $exception) {
    fwrite(STDERR, '[ERROR] Could not connect to MySQL: ' . $exception->getMessage() . "\n");
    exit(1);
}

$migrationDir = __DIR__ . '/migrations';
$migrations = glob($migrationDir . '/*.sql') ?: [];
sort($migrations);

echo 'Found ' . count($migrations) . " migration files\n\n";

foreach ($migrations as $migrationFile) {
    $filename = basename($migrationFile);
    echo "Running: {$filename}... ";

    try {
        $sql = file_get_contents($migrationFile) ?: '';
        $statements = preg_split('/;(?=(?:[^\'"]|[\'"][^\'"]*[\'"])*$)/', $sql) ?: [];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        echo "done\n";
    } catch (PDOException $exception) {
        echo "failed\n";
        echo 'Error: ' . $exception->getMessage() . "\n";

        if (
            strpos($exception->getMessage(), 'already exists') !== false ||
            strpos($exception->getMessage(), 'Duplicate') !== false
        ) {
            echo "Skipping (already applied)\n";
            continue;
        }

        echo 'Continue with remaining migrations? (y/n): ';
        $response = trim(fgets(STDIN));
        if (strtolower($response) !== 'y') {
            echo "Migration aborted.\n";
            exit(1);
        }
    }
}

echo "\n[OK] All migrations completed successfully!\n";
echo "\nDefault test user credentials:\n";
echo "  Email: demo@agendaflow.com\n";
echo "  Password: password\n";
echo "\nYou can now access AgendaFlow at: " . ($config['app']['url'] ?? 'http://localhost') . "/\n";
