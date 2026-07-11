<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();

$pdo->exec(file_get_contents(__DIR__ . '/migrations/000_create_migrations_table.sql'));

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if ($name === '000_create_migrations_table.sql' || in_array($name, $applied, true)) {
        continue;
    }

    echo "Applying $name ... ";
    $sql = file_get_contents($file);

    $pdo->exec($sql);

    $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:m)');
    $stmt->execute(['m' => $name]);

    echo "done\n";
}

echo "Migrations up to date.\n";
