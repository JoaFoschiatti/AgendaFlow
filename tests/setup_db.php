<?php

declare(strict_types=1);

// Setup test database by applying agendaflow_database.sql into a test DB name

$root = dirname(__DIR__);
$sqlFile = $root . DIRECTORY_SEPARATOR . 'agendaflow_database.sql';
$dbName = getenv('TEST_DB_NAME') ?: 'agendaflow_test';
$host = 'localhost';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

if (!file_exists($sqlFile)) {
    fwrite(STDERR, "SQL file not found: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
// Replace database name to avoid clobbering production DB
$sql = preg_replace('/DROP DATABASE IF EXISTS\s+agendaflow;/i', "DROP DATABASE IF EXISTS {$dbName};", $sql);
$sql = preg_replace('/CREATE DATABASE\s+agendaflow\b/i', "CREATE DATABASE {$dbName}", $sql);
$sql = preg_replace('/USE\s+agendaflow;/i', "USE {$dbName};", $sql);

try {
    // Connect without dbname to run CREATE DATABASE
    $dsn = "mysql:host={$host};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec($sql);
    echo "Test database '{$dbName}' has been created and initialized.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "DB setup error: " . $e->getMessage() . "\n");
    exit(2);
}

