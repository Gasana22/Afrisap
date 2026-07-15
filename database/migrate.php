<?php
/**
 * CLI migration runner.
 *
 * Usage: php database/migrate.php
 *
 * Runs each database/migrations/NNN_*.php file in order inside a single
 * connection with foreign key checks temporarily disabled, since several
 * tables reference each other across migration files (e.g. crop_procurements
 * -> suppliers, which is created in a later-numbered file).
 */

require_once __DIR__ . '/../config/database.php';

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $statements = require $file;
    $name = basename($file);

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    echo "Migrated: $name\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "All migrations complete.\n";
