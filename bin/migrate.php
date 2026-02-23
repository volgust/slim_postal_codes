<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$pdo = require __DIR__ . '/../config/database.php';

$action = $argv[1] ?? null;

if (!$action) {
    echo "Usage: php bin/migrate.php [up|down|status]\n";
    exit(1);
}

/*
|--------------------------------------------------------------------------
| Ensure migrations table exists
|--------------------------------------------------------------------------
*/
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function getMigrationFiles(): array
{
    $files = glob(__DIR__ . '/../database/migrations/*.php');
    sort($files);
    return $files;
}

function getExecutedMigrations(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT migration FROM migrations");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getLastBatch(PDO $pdo): int
{
    $stmt = $pdo->query("SELECT MAX(batch) as batch FROM migrations");
    return (int) $stmt->fetchColumn();
}

/*
|--------------------------------------------------------------------------
| UP
|--------------------------------------------------------------------------
*/
if ($action === 'up') {
    $files = getMigrationFiles();
    $executed = getExecutedMigrations($pdo);
    $batch = getLastBatch($pdo) + 1;

    foreach ($files as $file) {
        $name = basename($file);

        if (in_array($name, $executed)) {
            continue;
        }

        echo "Running: $name\n";

        $migration = require $file;
        $migration->up($pdo);

        $stmt = $pdo->prepare(
            "INSERT INTO migrations (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$name, $batch]);
    }

    echo "Migrations completed.\n";
}

/*
|--------------------------------------------------------------------------
| DOWN (rollback last batch)
|--------------------------------------------------------------------------
*/
elseif ($action === 'down') {
    $lastBatch = getLastBatch($pdo);

    if ($lastBatch === 0) {
        echo "Nothing to rollback.\n";
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC"
    );
    $stmt->execute([$lastBatch]);

    $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($migrations as $name) {
        $file = __DIR__ . '/../database/migrations/' . $name;

        if (!file_exists($file)) {
            echo "Migration file missing: $name\n";
            continue;
        }

        echo "Rolling back: $name\n";

        $migration = require $file;
        $migration->down($pdo);

        $delete = $pdo->prepare(
            "DELETE FROM migrations WHERE migration = ?"
        );
        $delete->execute([$name]);
    }

    echo "Rollback completed.\n";
}

/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/
elseif ($action === 'status') {
    $files = getMigrationFiles();
    $executed = getExecutedMigrations($pdo);

    foreach ($files as $file) {
        $name = basename($file);
        $status = in_array($name, $executed) ? '✓ migrated' : '✗ pending';
        echo str_pad($name, 40) . " $status\n";
    }
} else {
    echo "Invalid command. Use: up | down | status\n";
}
