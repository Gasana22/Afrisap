<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Public page -- no auth-check.php on purpose. Anyone with the QR code, link,
// or the raw token can look this up. Only non-sensitive traceability info
// (crop/species, origin farm + district, journey stages) is queried below --
// never financial, contact, or other tenant-private data.

$token = trim($_GET['token'] ?? '');
$batch = null;
$journey = [];
$searched = $token !== '';

if ($searched) {
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
        $journeyStmt = db()->prepare(
            "SELECT pj.stage, pj.stage_date, pj.location FROM product_journey pj
             JOIN trace_batches tb ON tb.id = pj.trace_batch_id
             JOIN trace_qr_codes qr ON qr.trace_batch_id = tb.id
             WHERE qr.public_token = :token ORDER BY pj.stage_date"
        );
        $journeyStmt->execute(['token' => $token]);
        $journey = $journeyStmt->fetchAll();
    }
}

$pageTitle = 'Track a Product';
require __DIR__ . '/includes/site_header.php';
?>

<section class="trace-hero">
    <div class="trace-hero-inner">
        <span class="hero-eyebrow">Farm-to-buyer traceability</span>
        <h1>Track a product</h1>
        <p>Scan the QR code on the product, or type its tracking code below — no account needed.</p>
        <form method="GET" action="<?= BASE_URL ?>/trace.php" class="trace-form">
            <input type="text" name="token" value="<?= e($token) ?>" placeholder="e.g. 9F3C1A7B2E" required autofocus>
            <button type="submit" class="btn btn-on-dark">Track</button>
        </form>
    </div>
</section>

<div class="site-main">
    <?php if ($searched): ?>
        <div class="trace-result">
            <div class="trace-result-card">
                <?php if (!$batch): ?>
                    <span class="status-pill status-recalled">Not found</span>
                    <h2 style="margin-top:0.6rem;">This tracking code isn't recognized</h2>
                    <p class="muted">Double-check the code and try again, or ask whoever gave you the product for the correct link.</p>
                <?php else: ?>
                    <?php $farmName = $batch['farm_name'] ?? $batch['farm_name2']; $district = $batch['district'] ?? $batch['district2']; ?>
                    <span class="status-pill status-<?= e($batch['status']) ?>"><?= e($batch['status']) ?></span>
                    <h2><?= e($batch['crop_type_name'] ?? ($batch['species'] . ($batch['breed'] ? ' (' . $batch['breed'] . ')' : ''))) ?></h2>
                    <p class="muted">Batch <span class="trace-code"><?= e($batch['batch_code']) ?></span> &middot; Origin: <?= e($farmName ?? 'Unknown farm') ?><?= $district ? ', ' . e($district) : '' ?></p>

                    <?php if ($journey): ?>
                        <h3 style="font-size:1rem; margin-top:1.75rem;">Journey</h3>
                        <ul class="journey-timeline">
                            <?php foreach ($journey as $j): ?>
                                <li>
                                    <div class="journey-stage"><?= e(ucfirst($j['stage'])) ?></div>
                                    <div class="journey-meta"><?= e($j['stage_date']) ?><?= $j['location'] ? ' &middot; ' . e($j['location']) : '' ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/site_footer.php'; ?>
