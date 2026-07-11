<?php

namespace App\Controllers\Platform;

use App\Core\Database;
use App\Models\Organization;

class DashboardController extends PlatformController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $stats = [
            'organizations' => (int) $pdo->query('SELECT COUNT(*) FROM organizations')->fetchColumn(),
            'active_organizations' => (int) $pdo->query("SELECT COUNT(*) FROM organizations WHERE status = 'active'")->fetchColumn(),
            'tenant_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE organization_id IS NOT NULL')->fetchColumn(),
            'platform_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE organization_id IS NULL')->fetchColumn(),
        ];

        $this->view('platform/dashboard/index', [
            'pageTitle' => 'Platform Dashboard',
            'stats' => $stats,
            'recentOrganizations' => array_slice(Organization::all(), 0, 8),
        ]);
    }
}
