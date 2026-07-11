<form method="post" action="/forgot-password">
    <?= \App\Core\Csrf::field() ?>
    <p class="text-muted small">Enter your account email and we'll send you a reset link.</p>
    <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <button type="submit" class="btn btn-success w-100">Send reset link</button>
    <div class="text-center mt-3">
        <a href="/login" class="small">Back to login</a>
    </div>
</form>
