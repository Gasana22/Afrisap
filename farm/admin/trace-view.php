<?php
require_once __DIR__ . '/includes/auth-check.php';

$batchId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    "SELECT tb.*, COALESCE(f1.name, f2.name) AS farm_name, COALESCE(f1.organization_id, f2.organization_id) AS organization_id,
            ct.name AS crop_type_name, an.species
     FROM trace_batches tb
     LEFT JOIN crop_cycles cc ON cc.id = tb.crop_cycle_id
     LEFT JOIN plots p ON p.id = cc.plot_id
     LEFT JOIN blocks b ON b.id = p.block_id
     LEFT JOIN farms f1 ON f1.id = b.farm_id
     LEFT JOIN crop_types ct ON ct.id = cc.crop_type_id
     LEFT JOIN animals an ON an.id = tb.animal_id
     LEFT JOIN farms f2 ON f2.id = an.farm_id
     WHERE tb.id = :id"
);
$stmt->execute(['id' => $batchId]);
$batch = $stmt->fetch();

if (!$batch || (!is_platform_user() && (int) $batch['organization_id'] !== (int) current_organization_id())) {
    http_response_code(404);
    exit('404 — trace batch not found.');
}

$error = flash('error');
$success = flash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_permission('traceability.manage');
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_qr') {
        $token = bin2hex(random_bytes(16));
        // No local image library / composer dependency available -- the QR
        // image itself is rendered client-side via a public QR image API,
        // keyed off the public verification URL. Swap for a self-hosted
        // generator later if an offline/no-third-party requirement comes up.
        $verifyUrl = BASE_URL . '/trace.php?token=' . $token;
        $qrImagePath = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($verifyUrl);

        db()->prepare(
            'INSERT INTO trace_qr_codes (trace_batch_id, public_token, qr_image_path)
             VALUES (:batch, :token, :path)
             ON DUPLICATE KEY UPDATE public_token = VALUES(public_token), qr_image_path = VALUES(qr_image_path), generated_at = NOW()'
        )->execute(['batch' => $batchId, 'token' => $token, 'path' => $qrImagePath]);

        flash('success', 'QR code generated.');
        redirect('/admin/trace-view.php?id=' . $batchId);
    }

    if ($action === 'add_document') {
        db()->prepare(
            'INSERT INTO trace_documents (trace_batch_id, document_type, file_path, uploaded_by, notes)
             VALUES (:batch, :type, :path, :by, :notes)'
        )->execute([
            'batch' => $batchId,
            'type' => $_POST['document_type'] ?? 'other',
            'path' => trim($_POST['file_path'] ?? ''),
            'by' => current_user()['id'],
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Document attached.');
        redirect('/admin/trace-view.php?id=' . $batchId);
    }

    if ($action === 'add_approval') {
        db()->prepare(
            'INSERT INTO trace_approvals (trace_batch_id, approval_type, status) VALUES (:batch, :type, "pending")'
        )->execute(['batch' => $batchId, 'type' => trim($_POST['approval_type'] ?? '')]);
        flash('success', 'Approval requirement added.');
        redirect('/admin/trace-view.php?id=' . $batchId);
    }

    if ($action === 'decide_approval') {
        $approvalId = (int) ($_POST['approval_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        if (in_array($decision, ['approved', 'rejected'], true)) {
            db()->prepare(
                'UPDATE trace_approvals SET status = :status, approved_by = :by, approved_at = NOW()
                 WHERE id = :id AND trace_batch_id = :batch'
            )->execute(['status' => $decision, 'by' => current_user()['id'], 'id' => $approvalId, 'batch' => $batchId]);
            flash('success', 'Approval updated.');
        }
        redirect('/admin/trace-view.php?id=' . $batchId);
    }

    if ($action === 'add_journey') {
        db()->prepare(
            'INSERT INTO product_journey (trace_batch_id, stage, stage_date, location, responsible_user_id, notes)
             VALUES (:batch, :stage, :date, :location, :by, :notes)'
        )->execute([
            'batch' => $batchId,
            'stage' => $_POST['stage'] ?? 'storage',
            'date' => $_POST['stage_date'] ?: date('Y-m-d'),
            'location' => trim($_POST['location'] ?? '') ?: null,
            'by' => current_user()['id'],
            'notes' => trim($_POST['notes'] ?? '') ?: null,
        ]);
        flash('success', 'Journey stage logged.');
        redirect('/admin/trace-view.php?id=' . $batchId);
    }
}

$qrStmt = db()->prepare('SELECT * FROM trace_qr_codes WHERE trace_batch_id = :batch');
$qrStmt->execute(['batch' => $batchId]);
$qr = $qrStmt->fetch();

$docsStmt = db()->prepare('SELECT * FROM trace_documents WHERE trace_batch_id = :batch ORDER BY uploaded_at DESC');
$docsStmt->execute(['batch' => $batchId]);
$documents = $docsStmt->fetchAll();

$approvalsStmt = db()->prepare('SELECT * FROM trace_approvals WHERE trace_batch_id = :batch ORDER BY created_at DESC');
$approvalsStmt->execute(['batch' => $batchId]);
$approvals = $approvalsStmt->fetchAll();

$journeyStmt = db()->prepare('SELECT * FROM product_journey WHERE trace_batch_id = :batch ORDER BY stage_date DESC');
$journeyStmt->execute(['batch' => $batchId]);
$journey = $journeyStmt->fetchAll();

$pageTitle = $batch['batch_code'];
$activePage = 'traceability';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/traceability.php">&larr; All trace batches</a></p>
<h1><?= e($batch['crop_type_name'] ?? $batch['species'] ?? $batch['batch_type']) ?> — <?= e($batch['batch_code']) ?></h1>
<p class="muted"><?= e($batch['farm_name'] ?? '—') ?> · Status: <?= e($batch['status']) ?></p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Public QR code</h2>
    <?php if ($qr): ?>
        <img src="<?= e($qr['qr_image_path']) ?>" alt="QR code" style="border:1px solid #eee; border-radius:4px;">
        <p class="muted">Public link: <a href="<?= BASE_URL ?>/trace.php?token=<?= e($qr['public_token']) ?>" target="_blank"><?= e(BASE_URL . '/trace.php?token=' . $qr['public_token']) ?></a></p>
    <?php else: ?>
        <p class="muted">No QR code generated yet.</p>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>">
        <input type="hidden" name="action" value="generate_qr">
        <button type="submit" class="btn"><?= $qr ? 'Regenerate' : 'Generate' ?> QR code</button>
    </form>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Documents</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th>Notes</th></tr></thead>
        <tbody>
            <?php if (!$documents): ?><tr><td colspan="4">None yet.</td></tr><?php endif; ?>
            <?php foreach ($documents as $d): ?>
                <tr><td><?= e($d['document_type']) ?></td><td><?= e($d['file_path']) ?></td><td><?= e($d['uploaded_at']) ?></td><td><?= e($d['notes'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Attach a document</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_document">
            <label>Type</label>
            <select name="document_type" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="certificate">Certificate</option><option value="invoice">Invoice</option>
                <option value="contract">Contract</option><option value="other">Other</option>
            </select>
            <label>File path / URL</label>
            <input type="text" name="file_path" required placeholder="storage/uploads/..." style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Attach</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Approvals</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Type</th><th>Status</th><th></th></tr></thead>
        <tbody>
            <?php if (!$approvals): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($approvals as $a): ?>
                <tr>
                    <td><?= e($a['approval_type']) ?></td>
                    <td><?= e($a['status']) ?></td>
                    <td>
                        <?php if ($a['status'] === 'pending'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>" style="display:inline;">
                                <input type="hidden" name="action" value="decide_approval">
                                <input type="hidden" name="approval_id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="decision" value="approved">
                                <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem;">Approve</button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>" style="display:inline;">
                                <input type="hidden" name="action" value="decide_approval">
                                <input type="hidden" name="approval_id" value="<?= (int) $a['id'] ?>">
                                <input type="hidden" name="decision" value="rejected">
                                <button type="submit" class="btn" style="padding:0.3rem 0.6rem; font-size:0.8rem; background:#a33;">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Add an approval requirement</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_approval">
            <label>Approval type</label>
            <input type="text" name="approval_type" required placeholder="e.g. Organic certification" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Add</button>
        </form>
    </details>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <h2 style="margin-top:0;">Product journey</h2>
    <table style="margin-bottom:1rem;">
        <thead><tr><th>Stage</th><th>Date</th><th>Location</th></tr></thead>
        <tbody>
            <?php if (!$journey): ?><tr><td colspan="3">None yet.</td></tr><?php endif; ?>
            <?php foreach ($journey as $j): ?>
                <tr><td><?= e($j['stage']) ?></td><td><?= e($j['stage_date']) ?></td><td><?= e($j['location'] ?? '—') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <details>
        <summary style="cursor:pointer; color:#2f5233;">Log a journey stage</summary>
        <form method="POST" action="<?= BASE_URL ?>/admin/trace-view.php?id=<?= $batchId ?>" style="margin-top:0.75rem; max-width:420px;">
            <input type="hidden" name="action" value="add_journey">
            <label>Stage</label>
            <select name="stage" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
                <option value="storage">Storage</option><option value="processing">Processing</option>
                <option value="packaging">Packaging</option><option value="distribution">Distribution</option><option value="delivered">Delivered</option>
            </select>
            <label>Date</label>
            <input type="date" name="stage_date" value="<?= date('Y-m-d') ?>" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Location</label>
            <input type="text" name="location" style="width:100%; padding:0.5rem; margin-bottom:0.75rem;">
            <label>Notes</label>
            <input type="text" name="notes" style="width:100%; padding:0.5rem; margin-bottom:1rem;">
            <button type="submit" class="btn">Save</button>
        </form>
    </details>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
