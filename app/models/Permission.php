<?php

namespace App\Models;

use App\Core\Database;

class Permission
{
    public static function allGroupedByModule(): array
    {
        $rows = Database::connection()->query('SELECT * FROM permissions ORDER BY module, code')->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }
        return $grouped;
    }
}
