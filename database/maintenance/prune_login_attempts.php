<?php
/**
 * Deletes login_attempts rows older than 30 days. These are only used for the
 * rolling rate-limit window in App\Core\Auth and are not an audit record --
 * unlike audit_logs/trace_audits, they're safe to prune. Intended to run as a
 * daily cPanel cron job: php /path/to/database/maintenance/prune_login_attempts.php
 */

require __DIR__ . '/../../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::connection();
$stmt = $pdo->prepare('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
$stmt->execute();

echo "Pruned {$stmt->rowCount()} old login_attempts rows.\n";
