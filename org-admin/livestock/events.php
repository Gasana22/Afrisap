<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user();
$organization = current_organization();
$orgId = (int) $organization['id'];

$animalId = (int) clean_int($_GET['animal_id'] ?? $_POST['animal_id'] ?? 0);
$animal = tenant_find('animals', $orgId, $animalId);

if (!$animal) {
    session_flash('error', 'Animal not found.');
    redirect('org-admin/livestock/index.php');
}

$eventTypes = ['birth', 'vaccination', 'feeding', 'weight', 'breeding', 'production', 'treatment', 'death', 'sale'];

$errors = [];
$input = ['event_type' => '', 'event_date' => date('Y-m-d'), 'details' => '', 'cost' => '', 'handler_id' => '', 'sale_price' => ''];

if (is_post() && csrf_verify()) {
    $input = [
        'event_type' => $_POST['event_type'] ?? '',
        'event_date' => $_POST['event_date'] ?? '',
        'details' => clean_string($_POST['details'] ?? ''),
        'cost' => $_POST['cost'] ?? '',
        'handler_id' => $_POST['handler_id'] ?? '',
        'sale_price' => $_POST['sale_price'] ?? '',
    ];

    $errors = validate($input, [
        'event_type' => 'required|in:' . implode(',', $eventTypes),
        'event_date' => 'required|date',
        'cost' => 'numeric',
        'sale_price' => 'numeric',
    ]);

    $handlerId = clean_int($input['handler_id']);
    if ($handlerId && !tenant_find('workers', $orgId, $handlerId)) {
        $errors['handler_id'] = 'Invalid handler selected.';
    }

    if (!$errors) {
        $eventId = db_insert('animal_events', [
            'animal_id' => $animalId,
            'event_type' => $input['event_type'],
            'event_date' => $input['event_date'],
            'details' => $input['details'] ?: null,
            'cost' => clean_float($input['cost']),
            'handler_id' => $handlerId,
        ]);

        if (in_array($input['event_type'], ['death', 'sale'], true) && !empty($_POST['update_status'])) {
            $newStatus = $input['event_type'] === 'death' ? 'deceased' : 'sold';
            $updateData = ['status' => $newStatus];
            if ($input['event_type'] === 'sale' && $input['sale_price'] !== '') {
                $updateData['sale_price'] = clean_float($input['sale_price']);
            }
            tenant_update('animals', $orgId, $animalId, $updateData);
            audit_log($orgId, $currentUser['id'], 'update', 'animals', $animalId, $animal, $updateData);
        }

        session_flash('success', 'Event logged.');
        redirect('org-admin/livestock/events.php?animal_id=' . $animalId);
    }
}

$GLOBALS['_page_errors'] = $errors;

$workers = tenant_all('workers', $orgId, "AND status = 'active' ORDER BY name");
$events = db_all(
    'SELECT e.*, w.name AS handler_name FROM animal_events e LEFT JOIN workers w ON w.id = e.handler_id WHERE e.animal_id = :animal_id ORDER BY e.event_date DESC, e.id DESC',
    ['animal_id' => $animalId]
);

render_header(['title' => 'Log Event - ' . ($animal['name'] ?: $animal['animal_id']), 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>
      <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/livestock/index.php') ?>">Livestock</a></li>
        <li class="breadcrumb-item"><a href="<?= base_url('org-admin/livestock/view.php?id=' . $animal['id']) ?>"><?= e($animal['name'] ?: $animal['animal_id']) ?></a></li>
        <li class="breadcrumb-item active">Log Event</li>
      </ol></nav>

      <div class="row g-3">
        <div class="col-lg-5">
          <div class="content-card">
            <h6 class="mb-3">Log Event</h6>
            <form method="post">
              <?= csrf_field() ?>
              <div class="mb-2">
                <label class="form-label">Event Type</label>
                <select name="event_type" class="form-select" id="eventType" required>
                  <option value="">Select type</option>
                  <?php foreach ($eventTypes as $et): ?>
                    <option value="<?= $et ?>" <?= $input['event_type'] === $et ? 'selected' : '' ?>><?= e(humanize($et)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">Event Date</label>
                <input type="date" name="event_date" class="form-control" required value="<?= e($input['event_date']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Details</label>
                <textarea name="details" class="form-control" rows="3"><?= e($input['details']) ?></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label">Cost</label>
                <input type="number" step="0.01" name="cost" class="form-control" value="<?= e((string) $input['cost']) ?>">
              </div>
              <div class="mb-2">
                <label class="form-label">Handler</label>
                <select name="handler_id" class="form-select">
                  <option value="">None</option>
                  <?php foreach ($workers as $w): ?>
                    <option value="<?= $w['id'] ?>" <?= (string) $input['handler_id'] === (string) $w['id'] ? 'selected' : '' ?>><?= e($w['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2" id="salePriceGroup">
                <label class="form-label">Sale Price <span class="text-muted small">(if this is a sale)</span></label>
                <input type="number" step="0.01" name="sale_price" class="form-control" value="<?= e((string) $input['sale_price']) ?>">
              </div>
              <div class="form-check mb-3">
                <input type="checkbox" name="update_status" value="1" class="form-check-input" id="updateStatus">
                <label class="form-check-label" for="updateStatus">Update animal status accordingly (death &rarr; deceased, sale &rarr; sold)</label>
              </div>
              <button type="submit" class="btn btn-primary w-100">Save Event</button>
            </form>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="content-card">
            <h6 class="mb-3">Event History</h6>
            <?php if (!$events): ?>
              <?php render_empty_state('No events logged yet.', 'bi-journal'); ?>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table-app">
                  <thead><tr><th>Date</th><th>Type</th><th>Details</th><th>Cost</th><th>Handler</th></tr></thead>
                  <tbody>
                    <?php foreach ($events as $ev): ?>
                      <tr>
                        <td><?= e(format_date($ev['event_date'])) ?></td>
                        <td><?= e(humanize($ev['event_type'])) ?></td>
                        <td><?= e($ev['details'] ?: '-') ?></td>
                        <td><?= $ev['cost'] !== null ? format_money((float) $ev['cost']) : '-' ?></td>
                        <td><?= e($ev['handler_name'] ?: '-') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
