<?php
require_once __DIR__ . '/includes/auth-check.php';
require_tenant_user();

// Print-friendly full-page compliance report -- same tenant-ownership check
// as admin/trace-view.php, since this exposes the same underlying batch
// data (inputs, field activities, monitoring, journey, buyer info).

$batchId = (int) ($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';
$allowedTypes = ['organic', 'gap', 'export', 'food_safety', 'carbon'];

if (!in_array($type, $allowedTypes, true)) {
    http_response_code(404);
    exit('404 — unknown compliance report type.');
}

$stmt = db()->prepare(
    "SELECT tb.*, COALESCE(f1.name, f2.name) AS farm_name, COALESCE(f1.organization_id, f2.organization_id) AS organization_id,
            ct.name AS crop_type_name, an.species, an.breed
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

if (!$batch || (int) $batch['organization_id'] !== (int) current_organization_id()) {
    http_response_code(404);
    exit('404 — trace batch not found.');
}

audit_log('generate_compliance_report', 'trace_batches', (string) $batchId, null, ['type' => $type]);

$titles = [
    'organic' => 'Organic Certification Report',
    'gap' => 'Good Agricultural Practice (GAP) Compliance Report',
    'export' => 'Export Documentation',
    'food_safety' => 'Food Safety Audit Report',
    'carbon' => 'Carbon Reporting Summary',
];

$productName = $batch['batch_type'] === 'crop' ? $batch['crop_type_name'] : ($batch['species'] . ($batch['breed'] ? ' (' . $batch['breed'] . ')' : ''));

function reportRows(string $table, string $column, int $id, string $order = 'id'): array
{
    $stmt = db()->prepare("SELECT * FROM $table WHERE $column = :id ORDER BY $order");
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}

$pageTitle = $titles[$type];
require __DIR__ . '/includes/header.php';
?>

<div class="print-actions" style="margin-bottom:1rem;"><button onclick="window.print()">Print / Save as PDF</button></div>

<h1><?= e($titles[$type]) ?></h1>
<p class="muted">
    Batch <strong><?= e($batch['batch_code']) ?></strong> &middot;
    Product: <?= e($productName) ?> &middot;
    Farm: <?= e($batch['farm_name'] ?? '—') ?><br>
    Generated <?= e(date('Y-m-d H:i')) ?>
</p>

<?php if ($type === 'organic'): ?>
    <?php if ($batch['batch_type'] !== 'crop'): ?>
        <p>Organic certification reporting currently applies to crop batches.</p>
    <?php else: ?>
        <?php
        $inputs = reportRows('crop_inputs', 'crop_cycle_id', (int) $batch['crop_cycle_id']);
        $hasChemical = (bool) array_filter($inputs, fn ($i) => $i['input_type'] === 'chemical');
        $approvals = reportRows('trace_approvals', 'trace_batch_id', $batchId);
        $approvedTypes = array_map(fn ($a) => $a['approval_type'], array_filter($approvals, fn ($a) => $a['status'] === 'approved'));
        ?>
        <h2>Input Summary</h2>
        <p><span class="badge <?= $hasChemical ? 'bg-danger' : 'bg-success' ?>"><?= $hasChemical ? 'Synthetic chemical inputs recorded' : 'No synthetic chemical inputs recorded' ?></span></p>
        <table>
            <thead><tr><th>Type</th><th>Supplier</th><th>Quantity</th><th>Purchase Date</th></tr></thead>
            <tbody>
                <?php if (!$inputs): ?><tr><td colspan="4">None recorded.</td></tr><?php endif; ?>
                <?php foreach ($inputs as $i): ?>
                    <tr><td><?= e(ucfirst($i['input_type'])) ?></td><td><?= e($i['supplier_name'] ?? '') ?></td><td><?= e($i['quantity'] . ' ' . $i['unit']) ?></td><td><?= e($i['purchase_date'] ?? '') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Certifications</h2>
        <?php if (!$approvedTypes): ?><p>No approved certifications on record.</p><?php else: ?>
            <ul><?php foreach ($approvedTypes as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <p class="disclaimer">This report summarizes recorded inputs and certifications. It does not itself constitute organic certification; presentation to a certifying body remains the farm's responsibility.</p>
    <?php endif; ?>

<?php elseif ($type === 'gap'): ?>
    <?php if ($batch['batch_type'] !== 'crop'): ?>
        <p>GAP compliance reporting currently applies to crop batches.</p>
    <?php else: ?>
        <?php $activities = reportRows('field_activities', 'crop_cycle_id', (int) $batch['crop_cycle_id']); ?>
        <h2>Field Practice Log</h2>
        <table>
            <thead><tr><th>Date</th><th>Activity</th><th>Worker</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (!$activities): ?><tr><td colspan="4">None recorded.</td></tr><?php endif; ?>
                <?php foreach ($activities as $a): ?>
                    <tr><td><?= e($a['activity_date']) ?></td><td><?= e(ucfirst($a['activity_type'])) ?></td><td><?= e($a['worker_name'] ?? '') ?></td><td><?= e(ucfirst($a['status'])) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h2>Traceability Coverage</h2>
        <p><?= count($activities) ?> field operations recorded, <?= count(array_filter($activities, fn ($a) => $a['gps_lat'] !== null)) ?> with GPS-verified location.</p>
    <?php endif; ?>

<?php elseif ($type === 'export'): ?>
    <?php
    $qrStmt = db()->prepare('SELECT public_token FROM trace_qr_codes WHERE trace_batch_id = :id');
    $qrStmt->execute(['id' => $batchId]);
    $qrToken = $qrStmt->fetchColumn();
    $qrLink = $qrToken ? (rtrim(BASE_URL, '/') . '/trace.php?token=' . $qrToken) : null;
    ?>
    <h2>Shipment / Sale Details</h2>
    <?php if ($batch['batch_type'] === 'crop'): ?>
        <?php
        $salesStmt = db()->prepare(
            'SELECT h.harvest_date, h.quantity, h.unit, h.quality_grade, cs.buyer_name, cs.sale_date
             FROM harvests h LEFT JOIN crop_sales cs ON cs.harvest_id = h.id WHERE h.crop_cycle_id = :id'
        );
        $salesStmt->execute(['id' => $batch['crop_cycle_id']]);
        $sales = $salesStmt->fetchAll();
        ?>
        <table>
            <thead><tr><th>Harvest Date</th><th>Quantity</th><th>Grade</th><th>Buyer</th><th>Sale Date</th></tr></thead>
            <tbody>
                <?php if (!$sales): ?><tr><td colspan="5">None recorded.</td></tr><?php endif; ?>
                <?php foreach ($sales as $r): ?>
                    <tr><td><?= e($r['harvest_date']) ?></td><td><?= e($r['quantity'] . ' ' . $r['unit']) ?></td><td><?= e($r['quality_grade'] ?? '') ?></td><td><?= e($r['buyer_name'] ?? '—') ?></td><td><?= e($r['sale_date'] ?? '—') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php
        $saleStmt = db()->prepare('SELECT * FROM animal_sales WHERE animal_id = :id');
        $saleStmt->execute(['id' => $batch['animal_id']]);
        $sale = $saleStmt->fetch();
        ?>
        <?php if ($sale): ?>
            <table>
                <tr><th>Buyer</th><td><?= e($sale['buyer_name']) ?></td></tr>
                <tr><th>Sale Date</th><td><?= e($sale['sale_date']) ?></td></tr>
                <tr><th>Price</th><td><?= e($sale['sale_price']) ?></td></tr>
            </table>
        <?php else: ?>
            <p>Not yet sold.</p>
        <?php endif; ?>
    <?php endif; ?>
    <h2>Traceability Reference</h2>
    <p><?= $qrLink ? 'Traceability link: ' . e($qrLink) : 'No QR code generated yet for this batch.' ?></p>

<?php elseif ($type === 'food_safety'): ?>
    <h2>Health &amp; Safety Records</h2>
    <?php if ($batch['batch_type'] === 'crop'): ?>
        <?php $records = reportRows('monitoring_records', 'crop_cycle_id', (int) $batch['crop_cycle_id']); ?>
        <table>
            <thead><tr><th>Date</th><th>Type</th><th>Severity</th><th>Description</th></tr></thead>
            <tbody>
                <?php if (!$records): ?><tr><td colspan="4">None recorded.</td></tr><?php endif; ?>
                <?php foreach ($records as $r): ?>
                    <tr><td><?= e($r['record_date']) ?></td><td><?= e(ucfirst($r['type'])) ?></td><td><?= e($r['severity'] ?? '') ?></td><td><?= e($r['description'] ?? '') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php $records = reportRows('animal_treatments', 'animal_id', (int) $batch['animal_id']); ?>
        <table>
            <thead><tr><th>Date</th><th>Condition</th><th>Treatment</th><th>By</th></tr></thead>
            <tbody>
                <?php if (!$records): ?><tr><td colspan="4">None recorded.</td></tr><?php endif; ?>
                <?php foreach ($records as $r): ?>
                    <tr><td><?= e($r['treatment_date']) ?></td><td><?= e($r['condition_name']) ?></td><td><?= e($r['treatment']) ?></td><td><?= e($r['administered_by'] ?? '') ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <h2>Storage &amp; Processing Chain of Custody</h2>
    <?php $journey = reportRows('product_journey', 'trace_batch_id', $batchId, 'stage_date'); ?>
    <table>
        <thead><tr><th>Date</th><th>Stage</th><th>Location</th></tr></thead>
        <tbody>
            <?php if (!$journey): ?><tr><td colspan="3">None recorded.</td></tr><?php endif; ?>
            <?php foreach ($journey as $j): ?>
                <tr><td><?= e($j['stage_date']) ?></td><td><?= e(ucfirst($j['stage'])) ?></td><td><?= e($j['location'] ?? '') ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: /* carbon */ ?>
    <?php
    $syntheticInputs = 0;
    if ($batch['batch_type'] === 'crop') {
        $inputs = reportRows('crop_inputs', 'crop_cycle_id', (int) $batch['crop_cycle_id']);
        $syntheticInputs = count(array_filter($inputs, fn ($i) => in_array($i['input_type'], ['fertilizer', 'chemical'], true)));
    }
    $journey = reportRows('product_journey', 'trace_batch_id', $batchId);
    $distributionStages = count(array_filter($journey, fn ($j) => in_array($j['stage'], ['distribution', 'delivered'], true)));
    ?>
    <h2>Activity Summary</h2>
    <table>
        <tr><th>Synthetic fertilizer/chemical applications</th><td><?= $syntheticInputs ?></td></tr>
        <tr><th>Distribution/delivery movements recorded</th><td><?= $distributionStages ?></td></tr>
    </table>
    <p class="disclaimer">This is a foundational activity summary derived from operational records, not a certified carbon footprint calculation. A full carbon accounting requires emission factors per input/transport mode which are not yet modeled in this system.</p>
<?php endif; ?>

<style>
    .badge { display:inline-block; padding:2px 10px; border-radius:10px; font-size:0.75rem; color:#fff; }
    .bg-success { background:#0ca30c; } .bg-danger { background:#d03b3b; }
    .disclaimer { font-size:0.75rem; color:#888; font-style:italic; margin-top:1rem; }
    @media print { .print-actions { display:none; } }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
