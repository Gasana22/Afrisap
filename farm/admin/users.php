<?php
require_once __DIR__ . '/includes/auth-check.php';

$error = flash('error');
$success = flash('success');

// Platform users manage the platform staff pool (organization_id IS NULL);
// tenant users manage their own organization's staff. The two pools never mix.
$roleScope = is_platform_user() ? 'platform' : 'tenant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('users.manage');

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $roleId = (int) ($_POST['role_id'] ?? 0);

    // Confirm the chosen role actually belongs to this user's pool (platform vs tenant).
    $roleCheck = db()->prepare('SELECT id FROM roles WHERE id = :id AND scope = :scope');
    $roleCheck->execute(['id' => $roleId, 'scope' => $roleScope]);

    if ($name === '' || $email === '' || strlen($password) < 8 || !$roleCheck->fetch()) {
        $error = 'Name, email, a valid role, and a password of at least 8 characters are required.';
    } else {
        try {
            db()->prepare(
                'INSERT INTO users (name, email, password_hash, role_id, organization_id, status, mfa_enabled)
                 VALUES (:name, :email, :hash, :role_id, :org_id, "active", 0)'
            )->execute([
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'role_id' => $roleId,
                'org_id' => is_platform_user() ? null : current_organization_id(),
            ]);
            flash('success', 'User created.');
            redirect('/admin/users.php');
        } catch (PDOException $e) {
            $error = 'That email is already in use.';
        }
    }
}

if (is_platform_user()) {
    $users = db()->query(
        "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
         WHERE r.scope = 'platform' ORDER BY u.created_at DESC"
    )->fetchAll();
} else {
    $stmt = db()->prepare(
        'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
         WHERE u.organization_id = :org ORDER BY u.created_at DESC'
    );
    $stmt->execute(['org' => current_organization_id()]);
    $users = $stmt->fetchAll();
}

$rolesStmt = db()->prepare('SELECT id, name FROM roles WHERE scope = :scope ORDER BY name');
$rolesStmt->execute(['scope' => $roleScope]);
$roles = $rolesStmt->fetchAll();

$pageTitle = 'Users';
$activePage = 'users';
require __DIR__ . '/includes/header.php';
?>

<h1>Users</h1>
<p class="muted"><?= is_platform_user() ? 'Platform staff.' : 'Your organization\'s staff.' ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add a user</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/users.php">
        <label>Name</label>
        <input type="text" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Email</label>
        <input type="email" name="email" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Role</label>
        <select name="role_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <option value="">Select…</option>
            <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
        <label>Initial password</label>
        <input type="password" name="password" required minlength="8" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Create</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th></tr></thead>
    <tbody>
        <?php if (!$users): ?><tr><td colspan="5">No users yet.</td></tr><?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['role_name']) ?></td>
                <td><?= e($u['status']) ?></td>
                <td><?= e($u['last_login_at'] ?? 'Never') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
