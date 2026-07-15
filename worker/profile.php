<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];
$loginEmail = $worker['login_email'] ?? null;

if (is_post() && csrf_verify()) {
    $phone = clean_string($_POST['phone'] ?? '');
    $emergencyContact = clean_string($_POST['emergency_contact'] ?? '');

    $errors = validate(['phone' => $phone], ['phone' => 'max:50']);

    if ($errors) {
        $GLOBALS['_page_errors'] = array_values($errors);
    } else {
        tenant_update('workers', $orgId, $workerId, [
            'phone' => $phone !== '' ? $phone : null,
            'emergency_contact' => $emergencyContact !== '' ? $emergencyContact : null,
        ]);
        audit_log($orgId, null, 'update', 'workers', $workerId, null, ['phone' => $phone, 'emergency_contact' => $emergencyContact]);
        session_flash('success', 'Profile updated.');
        redirect('worker/profile.php');
    }

    $worker = array_merge($worker, ['phone' => $phone, 'emergency_contact' => $emergencyContact]);
} else {
    $worker = tenant_find('workers', $orgId, $workerId) ?? $worker;
    $worker['login_email'] = $loginEmail;
}

render_header(['title' => 'My Profile', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>
    <h5 class="fw-bold mb-3">My Profile</h5>

    <div class="content-card p-3 mb-3">
        <h6 class="fw-bold small text-muted mb-2">Employee Details</h6>
        <ul class="list-unstyled small mb-0">
            <li class="mb-1"><span class="text-muted">Name:</span> <?= e($worker['name']) ?></li>
            <li class="mb-1"><span class="text-muted">Employee ID:</span> <?= e($worker['employee_id'] ?? '-') ?></li>
            <li class="mb-1"><span class="text-muted">Role:</span> <?= e($worker['role'] ?? '-') ?></li>
            <li class="mb-1"><span class="text-muted">Department:</span> <?= e($worker['department'] ?? '-') ?></li>
            <li class="mb-1"><span class="text-muted">Hire Date:</span> <?= e(format_date($worker['hire_date'] ?? null)) ?></li>
            <li class="mb-0"><span class="text-muted">Login Email:</span> <?= e($worker['login_email'] ?? $worker['email'] ?? '-') ?></li>
        </ul>
        <p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Name and role changes must go through your farm manager.</p>
    </div>

    <form method="post" class="content-card p-3 mb-3">
        <?= csrf_field() ?>
        <h6 class="fw-bold small text-muted mb-2">Contact Info</h6>
        <div class="mb-2">
            <label class="form-label small">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= e($worker['phone'] ?? '') ?>">
        </div>
        <div class="mb-2">
            <label class="form-label small">Emergency Contact</label>
            <input type="text" name="emergency_contact" class="form-control" value="<?= e($worker['emergency_contact'] ?? '') ?>" placeholder="Name and phone number">
        </div>
        <button type="submit" class="btn btn-primary w-100">Save Changes</button>
    </form>

    <a href="<?= base_url('worker/logout.php') ?>" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-1"></i> Log Out</a>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker']); ?>
