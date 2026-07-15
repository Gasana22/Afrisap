<?php
/**
 * Audit logging - records who changed what, for the traceability/audit.php
 * viewer and general accountability.
 */

function audit_log(?int $organizationId, ?int $userId, string $action, string $tableName, ?int $recordId, ?array $oldValues = null, ?array $newValues = null): void
{
    db_insert('audit_logs', [
        'organization_id' => $organizationId,
        'user_id' => $userId,
        'action' => $action,
        'table_name' => $tableName,
        'record_id' => $recordId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
}

function audit_trail(int $organizationId, ?string $tableName = null, int $limit = 100): array
{
    $sql = 'SELECT al.*, u.name AS user_name FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            WHERE al.organization_id = :org_id';
    $params = ['org_id' => $organizationId];

    if ($tableName) {
        $sql .= ' AND al.table_name = :table_name';
        $params['table_name'] = $tableName;
    }

    $sql .= ' ORDER BY al.created_at DESC LIMIT ' . (int) $limit;

    return db_all($sql, $params);
}
