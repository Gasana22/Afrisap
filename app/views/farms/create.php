<h4 class="mb-4">Add Farm</h4>
<div class="card">
    <div class="card-body">
        <form method="post" action="/farms">
            <?= \App\Core\Csrf::field() ?>
            <?php require __DIR__ . '/_form.php'; ?>
        </form>
    </div>
</div>
