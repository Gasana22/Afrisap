<p class="text-muted small">We emailed a 6-digit verification code to your address. Enter it below to continue.</p>
<form method="post" action="/mfa">
    <?= \App\Core\Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Verification code</label>
        <input type="text" name="code" class="form-control text-center fs-4 letter-spacing" maxlength="6" pattern="\d{6}" required autofocus>
    </div>
    <button type="submit" class="btn btn-success w-100">Verify</button>
    <div class="text-center mt-3">
        <a href="/login" class="small">Back to login</a>
    </div>
</form>
