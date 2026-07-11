<h4 class="mb-4">Edit <?= htmlspecialchars($supplier['name']) ?></h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/suppliers/<?= (int) $supplier['id'] ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($supplier['name']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Contact person</label><input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>"></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>"></div>
            </div>
            <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?= htmlspecialchars($supplier['address'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="<?= htmlspecialchars($supplier['category'] ?? '') ?>"></div>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="/suppliers" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
