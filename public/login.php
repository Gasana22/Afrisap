<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$errors = [];

if (is_post() && csrf_verify()) {
    $email = clean_string($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $organizationId = clean_int($_POST['organization_id'] ?? null);

    $user = auth_attempt($email, $password);

    if (!$user) {
        $errors[] = 'Invalid email or password.';
    } elseif ($user['is_admin']) {
        auth_login_platform_admin($user);
        redirect('admin/dashboard.php');
    } else {
        $memberships = db_all(
            'SELECT ou.*, o.name AS org_name, o.slug FROM organization_users ou
             JOIN organizations o ON o.id = ou.organization_id
             WHERE ou.user_id = :user_id AND ou.status = "active" AND ou.role != "worker"',
            ['user_id' => $user['id']]
        );

        if (!$memberships) {
            $errors[] = 'This account has no active organization membership. Please contact your administrator.';
        } elseif (count($memberships) === 1) {
            auth_login_org_user($user, (int) $memberships[0]['organization_id']);
            redirect('org-admin/index.php');
        } elseif ($organizationId) {
            $match = array_filter($memberships, fn ($m) => (int) $m['organization_id'] === $organizationId);
            if ($match) {
                auth_login_org_user($user, $organizationId);
                redirect('org-admin/index.php');
            }
            $errors[] = 'Invalid organization selection.';
        } else {
            // Re-render the form with an organization picker.
            $GLOBALS['_memberships_to_pick'] = $memberships;
            $GLOBALS['_pending_email'] = $email;
        }
    }
} elseif (is_post()) {
    $errors[] = 'Your session expired, please try again.';
}

$GLOBALS['_page_errors'] = $errors;

render_header(['title' => 'Log In', 'context' => 'auth']);
render_auth_layout_start([
    'headline' => 'Welcome back to your farm.',
    'subtitle' => "Sign in to check today's tasks, log activities, and see how your season is shaping up.",
    'features' => [
        'Track every crop cycle from seed to sale',
        'Keep livestock and worker records up to date',
        'See income, expenses and profit instantly',
    ],
]);
?>
    <h3 class="mb-3">Welcome back</h3>
    <?php render_alerts(); ?>

    <?php if (!empty($GLOBALS['_memberships_to_pick'])): ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= e($GLOBALS['_pending_email']) ?>">
        <input type="hidden" name="password" value="">
        <p class="text-muted small">Your account belongs to more than one organization. Choose which to sign in to:</p>
        <?php foreach ($GLOBALS['_memberships_to_pick'] as $m): ?>
          <button type="submit" formnovalidate class="btn btn-outline-primary w-100 mb-2 text-start" name="organization_id" value="<?= (int) $m['organization_id'] ?>">
            <?= e($m['org_name']) ?> <span class="text-muted small">(<?= e(humanize($m['role'])) ?>)</span>
          </button>
        <?php endforeach; ?>
      </form>
    <?php else: ?>
      <form method="post" data-confirm-submit>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between mb-3 small">
          <a href="<?= base_url('public/forgot-password.php') ?>">Forgot password?</a>
          <a href="<?= base_url('worker/login.php') ?>">Field worker? Log in here</a>
        </div>
        <button type="submit" class="btn btn-primary w-100">Log In</button>
      </form>
      <p class="text-center mt-3 small text-muted">Don't have an account? <a href="<?= base_url('public/register.php') ?>">Start your free trial</a></p>
      <p class="text-center mt-2 small text-muted">Demo: owner@greenvalley.test / Password123!</p>
    <?php endif; ?>
<?php
render_auth_layout_end();
render_footer(['context' => 'auth']);
?>
