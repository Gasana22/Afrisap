<form method="post" action="/login">
    <?= \App\Core\Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Sign in</button>
    <div class="text-center mt-3">
        <a href="/forgot-password" class="small">Forgot password?</a>
    </div>
</form>
