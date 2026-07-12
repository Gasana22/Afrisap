<?php
require_once __DIR__ . '/includes/auth-check.php';

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('procurement.manage');

    $organizationId = is_platform_user() ? (int) ($_POST['organization_id'] ?? 0) : current_organization_id();
    $name = trim($_POST['name'] ?? '');

    if (!$organizationId || $name === '') {
        $error = 'Organization and name are required.';
    } else {
        db()->prepare(
            'INSERT INTO suppliers (organization_id, name, contact_person, phone, email, address, category)
             VALUES (:org, :name, :contact, :phone, :email, :address, :category)'
        )->execute([
            'org' => $organizationId,
            'name' => $name,
            'contact' => trim($_POST['contact_person'] ?? '') ?: null,
            'phone' => trim($_POST['phone'] ?? '') ?: null,
            'email' => trim($_POST['email'] ?? '') ?: null,
            'address' => trim($_POST['address'] ?? '') ?: null,
            'category' => trim($_POST['category'] ?? '') ?: null,
        ]);
        flash('success', 'Supplier added.');
        redirect('/admin/suppliers.php');
    }
}

if (is_platform_user()) {
    $suppliers = db()->query('SELECT s.*, o.name AS organization_name FROM suppliers s LEFT JOIN organizations o ON o.id = s.organization_id ORDER BY s.name')->fetchAll();
} else {
    $stmt = db()->prepare('SELECT * FROM suppliers WHERE organization_id = :org ORDER BY name');
    $stmt->execute(['org' => current_organization_id()]);
    $suppliers = $stmt->fetchAll();
}

$organizations = is_platform_user() ? db()->query('SELECT id, name FROM organizations ORDER BY name')->fetchAll() : [];

$pageTitle = 'Suppliers';
$activePage = 'suppliers';
require __DIR__ . '/includes/header.php';
?>

<h1>Suppliers</h1>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem; max-width:420px;">
    <h2 style="margin-top:0; font-size:1rem;">Add a supplier</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/suppliers.php">
        <?php if (is_platform_user()): ?>
            <label>Organization</label>
            <select name="organization_id" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="">Select…</option>
                <?php foreach ($organizations as $org): ?>
                    <option value="<?= (int) $org['id'] ?>"><?= e($org['name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <label>Name</label>
        <input type="text" name="name" required style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Contact person</label>
        <input type="text" name="contact_person" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Phone</label>
        <input type="text" name="phone" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Email</label>
        <input type="email" name="email" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
        <label>Category</label>
        <input type="text" name="category" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
        <button type="submit" class="btn">Add</button>
    </form>
</div>

<table>
    <thead><tr><th>Name</th><th>Contact</th><th>Phone</th><th>Category</th><?php if (is_platform_user()): ?><th>Organization</th><?php endif; ?></tr></thead>
    <tbody>
        <?php if (!$suppliers): ?><tr><td colspan="5">No suppliers yet.</td></tr><?php endif; ?>
        <?php foreach ($suppliers as $s): ?>
            <tr>
                <td><?= e($s['name']) ?></td>
                <td><?= e($s['contact_person'] ?? '—') ?></td>
                <td><?= e($s['phone'] ?? '—') ?></td>
                <td><?= e($s['category'] ?? '—') ?></td>
                <?php if (is_platform_user()): ?><td><?= e($s['organization_name'] ?? '—') ?></td><?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/includes/footer.php'; ?>
