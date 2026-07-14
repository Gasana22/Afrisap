<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_super_admin();

$page_title = 'Admin Users';
$page_eyebrow = 'Settings';
$active_nav = 'users';
$page_action_html = '<a class="btn btn--primary" href="' . h(url('/admin/users/form.php')) . '">Add user</a>';

$users = db()->query('SELECT * FROM admin_users ORDER BY name')->fetchAll();
$superAdminCount = 0;
foreach ($users as $u) {
    if ($u['role'] === 'super_admin') {
        $superAdminCount++;
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last login</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= h($user['name']) ?><?= (int) $user['id'] === (int) current_admin()['id'] ? ' <span class="table__meta">(you)</span>' : '' ?></td>
          <td class="table__meta"><?= h($user['email']) ?></td>
          <td class="table__meta"><?= h(str_replace('_', ' ', $user['role'])) ?></td>
          <td class="table__meta"><?= $user['last_login_at'] ? h(date('M j, Y g:ia', strtotime($user['last_login_at']))) : 'Never' ?></td>
          <td>
            <div class="table__actions">
              <a class="btn btn--ghost btn--sm" href="<?= h(url('/admin/users/form.php?id=' . $user['id'])) ?>">Edit</a>
              <?php if ((int) $user['id'] !== (int) current_admin()['id']): ?>
                <form method="post" action="<?= h(url('/admin/users/delete.php')) ?>" onsubmit="return confirm('Delete this admin user?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm"<?= $user['role'] === 'super_admin' && $superAdminCount <= 1 ? ' disabled title="Can\'t delete the last super admin"' : '' ?>>Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
