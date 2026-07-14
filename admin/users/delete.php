<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_super_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/users/index.php');
}

csrf_verify();
$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) current_admin()['id']) {
    flash_set('error', 'You can\'t delete your own account.');
    redirect('/admin/users/index.php');
}

$target = db()->prepare('SELECT role FROM admin_users WHERE id = ?');
$target->execute([$id]);
$role = $target->fetchColumn();

if ($role === 'super_admin') {
    $superAdminCount = (int) db()->query("SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin'")->fetchColumn();
    if ($superAdminCount <= 1) {
        flash_set('error', 'Can\'t delete the last super admin.');
        redirect('/admin/users/index.php');
    }
}

db()->prepare('DELETE FROM admin_users WHERE id = ?')->execute([$id]);
flash_set('success', 'User deleted.');

redirect('/admin/users/index.php');
