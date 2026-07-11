<?php

namespace App\Controllers\Platform;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Models\Setting;

class SettingsController extends PlatformController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $dbSizeRow = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS mb
            FROM information_schema.tables WHERE table_schema = DATABASE()")->fetch();

        $uploadsPath = __DIR__ . '/../../../public/uploads';
        $uploadsSize = 0;
        foreach (glob($uploadsPath . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $uploadsSize += filesize($file);
            }
        }

        $this->view('platform/settings/index', [
            'pageTitle' => 'Platform Settings',
            'settings' => Setting::all(null),
            'dbSizeMb' => $dbSizeRow['mb'] ?? 0,
            'uploadsSizeMb' => round($uploadsSize / 1024 / 1024, 2),
        ]);
    }

    public function update(): void
    {
        $before = Setting::all(null);

        $fields = ['company_name', 'default_currency', 'default_units'];
        foreach ($fields as $field) {
            Setting::set($field, $this->input($field), null);
        }

        AuditLogger::log('update', 'settings', null, $before, Setting::all(null));

        $this->flash('success', 'Settings updated.');
        $this->redirect('/platform/settings');
    }
}
