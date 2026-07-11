<?php

namespace App\Core;

class AuditLogger
{
    public static function log(string $action, string $table, ?string $recordId = null, ?array $old = null, ?array $new = null): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, device, created_at)
            VALUES (:user_id, :action, :table_name, :record_id, :old_value, :new_value, :ip_address, :device, NOW())');

        $stmt->execute([
            'user_id' => Auth::id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_value' => $old !== null ? json_encode($old) : null,
            'new_value' => $new !== null ? json_encode($new) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'device' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
