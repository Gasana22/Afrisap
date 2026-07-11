<?php
/**
 * Best-effort database backup via mysqldump, for hosts that allow shell_exec.
 * Many shared-hosting plans disable shell_exec entirely -- if that's the case
 * here, use cPanel's own "Backup Wizard" / MySQL export feature instead, or
 * ask the host to enable scheduled backups at the account level. This script
 * is a convenience for hosts that do allow it, not the only backup path.
 *
 * Intended to run as a daily cPanel cron job:
 *   php /path/to/database/maintenance/backup.php
 *
 * Keeps the last 14 daily backups in storage/backups and deletes older ones.
 */

require __DIR__ . '/../../vendor/autoload.php';

$config = require __DIR__ . '/../../app/config/config.php';
$db = $config['db'];

if (!function_exists('shell_exec') || stripos((string) ini_get('disable_functions'), 'shell_exec') !== false) {
    fwrite(STDERR, "shell_exec is disabled on this host. Use cPanel's Backup Wizard or MySQL export feature instead.\n");
    exit(1);
}

$backupDir = __DIR__ . '/../../storage/backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = $backupDir . '/sfmtp-' . date('Y-m-d_His') . '.sql.gz';

$command = sprintf(
    'mysqldump -h%s -P%d -u%s -p%s %s | gzip > %s',
    escapeshellarg($db['host']),
    (int) $db['port'],
    escapeshellarg($db['username']),
    escapeshellarg($db['password']),
    escapeshellarg($db['database']),
    escapeshellarg($filename)
);

shell_exec($command);

if (!file_exists($filename) || filesize($filename) === 0) {
    fwrite(STDERR, "Backup failed or produced an empty file: {$filename}\n");
    exit(1);
}

echo "Backup written to {$filename}\n";

// Keep only the last 14 backups.
$files = glob($backupDir . '/sfmtp-*.sql.gz');
sort($files);
$excess = count($files) - 14;
for ($i = 0; $i < $excess; $i++) {
    unlink($files[$i]);
    echo "Removed old backup: {$files[$i]}\n";
}
