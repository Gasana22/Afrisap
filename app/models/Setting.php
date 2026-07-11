<?php

namespace App\Models;

use App\Core\Database;

class Setting
{
    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $stmt->execute(['k' => $key, 'v' => $value]);
    }
}
