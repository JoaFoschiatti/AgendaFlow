<?php

// Migration runner for AgendaFlow

echo "=== AgendaFlow Database Migration Tool ===\n\n";

// Load configuration
$config = require __DIR__ . '/config/config.php';

// Connect to MySQL server (without database selection)
try {
    $dsn = "mysql:host={$config['database']['host']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connected to MySQL server\n";
} catch (PDOException $e) {
    die("✗ Could not connect to MySQL: " . $e->getMessage() . "\n");
}

// Get all migration files
$migrationDir = __DIR__ . '/migrations';
$migrations = glob($migrationDir . '/*.sql');
sort($migrations);

echo "Found " . count($migrations) . " migration files\n\n";

// Run each migration
foreach ($migrations as $migrationFile) {
    $filename = basename($migrationFile);
    echo "Running: {$filename}... ";
    
    try {
        $sql = file_get_contents($migrationFile);
        
        // Split by semicolon but not inside strings
        $statements = preg_split('/;(?=(?:[^\'"]|[\'"][^\'"]*[\'"])*$)/', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "✓\n";
        
    } catch (PDOException $e) {
        echo "✗\n";
        echo "Error: " . $e->getMessage() . "\n";
        
        // Continue with next migration on error
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            echo "Skipping (already applied)\n";
            continue;
        }
        
        // Ask user if they want to continue
        echo "Continue with remaining migrations? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) != 'y') {
            echo "Migration aborted.\n";
            exit(1);
        }
        fclose($handle);
    }
}

echo "\n✓ All migrations completed successfully!\n";
echo "\nDefault test user credentials:\n";
echo "  Email: demo@agendaflow.com\n";
echo "  Password: password\n";
echo "\nYou can now access AgendaFlow at: {$config['app']['url']}/\n";