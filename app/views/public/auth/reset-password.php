<form method="post" action="/reset-password">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" name="password" class="form-control" minlength="8" required autofocus>
    </div>
    <button type="submit" class="btn btn-success w-100">Reset password</button>
</form>
