<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Public page -- no auth-check.php on purpose. Anyone with the QR code / link
// can see this. Only non-sensitive traceability info is queried below.

$token = $_GET['token'] ?? '';

$stmt = db()->prepare(
    "SELECT tb.batch_code, tb.batch_type, tb.status,
            ct.name AS crop_type_name, an.species, an.breed,
            f1.name AS farm_name, f1.district, f2.name AS farm_name2, f2.district AS district2
     FROM trace_qr_codes qr
     JOIN trace_batches tb ON tb.id = qr.trace_batch_id
     LEFT JOIN crop_cycles cc ON cc.id = tb.crop_cycle_id
     LEFT JOIN plots p ON p.id = cc.plot_id
     LEFT JOIN blocks b ON b.id = p.block_id
     LEFT JOIN farms f1 ON f1.id = b.farm_id
     LEFT JOIN crop_types ct ON ct.id = cc.crop_type_id
     LEFT JOIN animals an ON an.id = tb.animal_id
     LEFT JOIN farms f2 ON f2.id = an.farm_id
     WHERE qr.public_token = :token"
);
$stmt->execute(['token' => $token]);
$batch = $stmt->fetch();

if ($batch) {
    $farmName = $batch['farm_name'] ?? $batch['farm_name2'];
    $district = $batch['district'] ?? $batch['district2'];

    $journeyStmt = db()->prepare(
        "SELECT pj.stage, pj.stage_date, pj.location FROM product_journey pj
         JOIN trace_batches tb ON tb.id = pj.trace_batch_id
         JOIN trace_qr_codes qr ON qr.trace_batch_id = tb.id
         WHERE qr.public_token = :token ORDER BY pj.stage_date"
    );
    $journeyStmt->execute(['token' => $token]);
    $journey = $journeyStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trace — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site.css">
</head>
<body class="auth-page">
    <div class="auth-card" style="width:420px;">
        <h1>Product Trace</h1>
        <?php if (!$batch): ?>
            <p class="muted">This trace code isn't recognized.</p>
        <?php else: ?>
            <p><strong><?= e($batch['crop_type_name'] ?? ($batch['species'] . ($batch['breed'] ? ' (' . $batch['breed'] . ')' : ''))) ?></strong></p>
            <p class="muted">Batch <?= e($batch['batch_code']) ?> · <?= e(ucfirst($batch['status'])) ?></p>
            <p class="muted">Origin: <?= e($farmName ?? 'Unknown farm') ?><?= $district ? ', ' . e($district) : '' ?></p>

            <?php if ($journey): ?>
                <h2 style="font-size:1rem;">Journey</h2>
                <ul style="padding-left:1.2rem; font-size:0.9rem;">
                    <?php foreach ($journey as $j): ?>
                        <li><?= e(ucfirst($j['stage'])) ?> — <?= e($j['stage_date']) ?><?= $j['location'] ? ' (' . e($j['location']) . ')' : '' ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
