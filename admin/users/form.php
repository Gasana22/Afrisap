<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_super_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$user = ['name' => '', 'email' => '', 'role' => 'editor'];
$errors = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM admin_users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash_set('error', 'That user no longer exists.');
        redirect('/admin/users/index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $user['name'] = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['role'] = $_POST['role'] ?? 'editor';
    $password = $_POST['password'] ?? '';

    if ($user['name'] === '') {
        $errors['name'] = 'Enter a name.';
    }
    if ($user['email'] === '' || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email.';
    }
    if (!in_array($user['role'], ['super_admin', 'editor'], true)) {
        $errors['role'] = 'Choose a valid role.';
    }
    if (!$id && $password === '') {
        $errors['password'] = 'Set a password for the new user.';
    }
    if ($password !== '' && strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    // Don't let the last super admin demote themselves (or be demoted), or the account becomes unmanageable.
    if (!$errors && $id && $user['role'] !== 'super_admin') {
        $currentRole = db()->prepare('SELECT role FROM admin_users WHERE id = ?');
        $currentRole->execute([$id]);
        if ($currentRole->fetchColumn() === 'super_admin') {
            $superAdminCount = (int) db()->query("SELECT COUNT(*) FROM admin_users WHERE role = 'super_admin'")->fetchColumn();
            if ($superAdminCount <= 1) {
                $errors['role'] = 'Can\'t demote the last super admin.';
            }
        }
    }

    if (!$errors) {
        try {
            if ($id) {
                if ($password !== '') {
                    db()->prepare('UPDATE admin_users SET name=?, email=?, role=?, password_hash=? WHERE id=?')
                        ->execute([$user['name'], $user['email'], $user['role'], password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    db()->prepare('UPDATE admin_users SET name=?, email=?, role=? WHERE id=?')
                        ->execute([$user['name'], $user['email'], $user['role'], $id]);
                }
            } else {
                db()->prepare('INSERT INTO admin_users (name, email, role, password_hash) VALUES (?, ?, ?, ?)')
                    ->execute([$user['name'], $user['email'], $user['role'], password_hash($password, PASSWORD_DEFAULT)]);
            }
            flash_set('success', 'User saved.');
            redirect('/admin/users/index.php');
        } catch (PDOException $e) {
            $errors['email'] = str_contains($e->getMessage(), 'Duplicate') ? 'That email is already in use.' : 'Could not save the user.';
        }
    }
}

$page_title = $id ? 'Edit user' : 'Add user';
$page_eyebrow = 'Settings';
$active_nav = 'users';

require __DIR__ . '/../includes/header.php';
?>

<div class="panel">
  <div class="panel__body">
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-field<?= isset($errors['name']) ? ' has-error' : '' ?>">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= h($user['name']) ?>" autofocus required>
          <?php if (isset($errors['name'])): ?><span class="error-text"><?= h($errors['name']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['email']) ? ' has-error' : '' ?>">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= h($user['email']) ?>" required>
          <?php if (isset($errors['email'])): ?><span class="error-text"><?= h($errors['email']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['role']) ? ' has-error' : '' ?>">
          <label for="role">Role</label>
          <select id="role" name="role">
            <option value="editor" <?= $user['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
            <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
          </select>
          <?php if (isset($errors['role'])): ?><span class="error-text"><?= h($errors['role']) ?></span><?php endif; ?>
        </div>
        <div class="form-field<?= isset($errors['password']) ? ' has-error' : '' ?>">
          <label for="password">Password<?= $id ? ' (leave blank to keep current)' : '' ?></label>
          <input type="password" id="password" name="password" autocomplete="new-password">
          <?php if (isset($errors['password'])): ?><span class="error-text"><?= h($errors['password']) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn--primary">Save user</button>
        <a class="btn btn--ghost" href="<?= h(url('/admin/users/index.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
