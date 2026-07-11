<p class="text-muted small mb-3">
    Create your farm's account. You'll be the Farm Owner — from there you can add
    your farms and invite your own team (Farm Manager, Agronomist, Field Workers,
    and more) from the Team page.
</p>
<form method="post" action="/signup">
    <?= \App\Core\Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Farm / organization name</label>
        <input type="text" name="organization_name" class="form-control" required autofocus placeholder="e.g. Masongora Cocoa Farm">
    </div>
    <div class="mb-3">
        <label class="form-label">Your name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
        <input type="text" name="phone" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="8">
    </div>
    <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input type="password" name="password_confirm" class="form-control" required minlength="8">
    </div>
    <button type="submit" class="btn btn-success w-100">Create farm account</button>
    <div class="text-center mt-3">
        <a href="/login" class="small">Already have an account? Sign in</a>
    </div>
</form>
