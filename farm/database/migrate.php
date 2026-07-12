<?php
/**
 * Migration runner.
 *
 * Applies every *.sql file in database/migrations/, in filename order, that
 * isn't already recorded in the `migrations` table. Run from the CLI:
 *
 *   php database/migrate.php
 *
 * Each file is split into individual statements on a semicolon followed by
 * a newline. That's safe for these migration files specifically (no
 * semicolons appear inside any string literal), but isn't a general-purpose
 * SQL splitter -- don't reuse this against arbitrary SQL.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = db();
$dir = __DIR__ . '/migrations';

// The tracking table is itself created by 000_create_migrations_table.sql,
// and (like every other migration file here) that file has no IF NOT EXISTS
// -- it's meant to run exactly once, same as the rest. So: create + record
// it as applied only if the table doesn't exist yet, then let the normal
// loop below skip it from then on.
$tableExists = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'migrations'"
)->fetchColumn();

if (!$tableExists) {
    $bootstrapFile = '000_create_migrations_table.sql';
    $pdo->exec(file_get_contents($dir . '/' . $bootstrapFile));
    $pdo->prepare('INSERT INTO migrations (migration) VALUES (:m)')->execute(['m' => $bootstrapFile]);
}

$applied = array_flip($pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN));

$files = glob($dir . '/*.sql');
sort($files);

$ranAny = false;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($applied[$name])) {
        echo "skip   $name\n";
        continue;
    }

    $sql = file_get_contents($file);
    $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

    try {
        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
        $pdo->prepare('INSERT INTO migrations (migration) VALUES (:m)')->execute(['m' => $name]);
        echo "applied $name\n";
        $ranAny = true;
    } catch (Throwable $e) {
        fwrite(STDERR, "FAILED $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo $ranAny ? "Migrations complete.\n" : "Nothing to do -- schema already up to date.\n";
