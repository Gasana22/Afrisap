<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class AuditLogController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $pdo = Database::connection();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();

        $stmt = $pdo->prepare('SELECT al.*, u.name AS user_name FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        $this->view('admin/audit-logs/index', [
            'pageTitle' => 'Audit Log',
            'logs' => $stmt->fetchAll(),
            'page' => $page,
            'totalPages' => (int) ceil($total / $perPage),
        ]);
    }
}
