<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$currentUser = require_org_user(['owner', 'manager']);
$organization = current_organization();
$orgId = (int) $organization['id'];

$settings = json_decode((string) ($organization['settings'] ?? '{}'), true) ?: [];
$apiKey = $settings['api_key'] ?? null;

if (is_post() && csrf_verify()) {
    $newKey = bin2hex(random_bytes(24));
    $settings['api_key'] = $newKey;
    db_update('organizations', ['settings' => json_encode($settings)], 'id = :id', ['id' => $orgId]);
    audit_log($orgId, $currentUser['id'], 'update', 'organizations', $orgId, ['api_key' => $apiKey ? '***' : null], ['api_key' => '***']);
    session_flash('success', $apiKey ? 'API key regenerated.' : 'API key generated.');
    redirect('org-admin/settings/integration.php');
}

render_header(['title' => 'Integrations', 'context' => 'org-admin']);
?>
<div class="app-layout">
  <?php render_app_sidebar('org-admin', $_SERVER['SCRIPT_NAME'], $organization); ?>
  <div class="app-main">
    <?php render_app_topbar('org-admin', $currentUser, $organization); ?>
    <main class="app-content">
      <?php render_alerts(); ?>

      <div class="mb-3">
        <h4 class="mb-0">Integrations</h4>
        <p class="text-muted small mb-0">Manage the API key used to access the Smart Farm Platform API on behalf of your organization.</p>
      </div>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/profile.php') ?>">Profile</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/branding.php') ?>">Branding</a></li>
        <?php if (($currentUser['role'] ?? '') === 'owner'): ?>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/users.php') ?>">Users</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('org-admin/settings/roles.php') ?>">Roles &amp; Permissions</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="nav-link active" href="<?= base_url('org-admin/settings/integration.php') ?>">Integrations</a></li>
      </ul>

      <div class="content-card mb-4" style="max-width:720px;">
        <h5 class="mb-3">API Key</h5>
        <?php if ($apiKey): ?>
          <label class="form-label small">Current API Key</label>
          <div class="input-group mb-3">
            <input type="text" class="form-control font-monospace" value="<?= e($apiKey) ?>" readonly id="apiKeyField">
            <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('apiKeyField').value)"><i class="bi bi-clipboard"></i> Copy</button>
          </div>
          <form method="post" onsubmit="return confirm('Regenerating will invalidate the current key. Continue?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-arrow-repeat"></i> Regenerate API Key</button>
          </form>
        <?php else: ?>
          <?php render_empty_state('No API key generated yet for this organization.', 'bi-key'); ?>
          <form method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Generate API Key</button>
          </form>
        <?php endif; ?>
      </div>

      <div class="content-card" style="max-width:720px;">
        <h5 class="mb-3">Using the API</h5>
        <p class="text-muted small">The platform exposes a REST API under <code><?= e(base_url('api/v1/')) ?></code> for programmatic access to your organization's data (farms, crops, livestock, workers, inventory, finance and traceability records).</p>
        <p class="text-muted small">Send your API key as a request header on every call:</p>
        <pre class="bg-light p-3 rounded small">X-API-Key: <?= e($apiKey ?: 'your-api-key-here') ?></pre>
        <p class="text-muted small mb-0">API key authentication for <code>api/v1/</code> endpoints is being rolled out separately; this key is a placeholder credential you can start distributing to integrations today.</p>
      </div>
    </main>
  </div>
</div>
<?php render_footer(['context' => 'org-admin']); ?>
