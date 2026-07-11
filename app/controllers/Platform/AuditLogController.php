<?php

namespace App\Controllers\Platform;

use App\Core\Database;

/** Cross-tenant oversight view -- intentionally unscoped, this is the one
 * place in the app that's supposed to see every organization's activity. */
class AuditLogController extends PlatformController
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $pdo = Database::connection();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

        $stmt = $pdo->prepare('SELECT al.*, u.name AS user_name, o.name AS organization_name FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            LEFT JOIN organizations o ON o.id = al.organization_id
            ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $this->view('platform/audit-logs/index', [
            'pageTitle' => 'Platform Audit Log',
            'logs' => $stmt->fetchAll(),
            'page' => $page,
            'totalPages' => (int) ceil($total / $perPage),
        ]);
    }
}
