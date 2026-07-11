<h4 class="mb-4">Edit Farm</h4>
<div class="card">
    <div class="card-body">
        <form method="post" action="/farms/<?= (int) $farm['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php require __DIR__ . '/_form.php'; ?>
        </form>
    </div>
</div>
