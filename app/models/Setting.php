<?php

namespace App\Models;

use App\Core\Database;

class Setting
{
    /** organization_id null = platform-wide settings row. */
    public static function all(?int $organizationId = null): array
    {
        $sql = $organizationId === null
            ? 'SELECT setting_key, setting_value FROM settings WHERE organization_id IS NULL'
            : 'SELECT setting_key, setting_value FROM settings WHERE organization_id = :org_id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($organizationId === null ? [] : ['org_id' => $organizationId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    public static function set(string $key, ?string $value, ?int $organizationId = null): void
    {
        $pdo = Database::connection();

        if ($organizationId !== null) {
            // The (organization_id, setting_key) unique index works normally for a
            // real organization_id -- ON DUPLICATE KEY UPDATE is safe here.
            $stmt = $pdo->prepare('INSERT INTO settings (organization_id, setting_key, setting_value) VALUES (:org_id, :k, :v)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            $stmt->execute(['org_id' => $organizationId, 'k' => $key, 'v' => $value]);
            return;
        }

        // MySQL unique indexes never treat two NULLs as equal, so ON DUPLICATE KEY
        // UPDATE can't detect an existing organization_id IS NULL row -- every call
        // would INSERT a fresh duplicate instead of updating. Check-then-write instead.
        $existing = $pdo->prepare('SELECT id FROM settings WHERE organization_id IS NULL AND setting_key = :k');
        $existing->execute(['k' => $key]);
        $id = $existing->fetchColumn();

        if ($id !== false) {
            $pdo->prepare('UPDATE settings SET setting_value = :v WHERE id = :id')->execute(['v' => $value, 'id' => $id]);
            return;
        }
        $pdo->prepare('INSERT INTO settings (organization_id, setting_key, setting_value) VALUES (NULL, :k, :v)')
            ->execute(['k' => $key, 'v' => $value]);
    }
}
