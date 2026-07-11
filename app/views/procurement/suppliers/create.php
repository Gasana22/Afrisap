<h4 class="mb-4">Add Supplier</h4>
<div class="card" style="max-width: 560px;">
    <div class="card-body">
        <form method="post" action="/suppliers">
            <?= \App\Core\Csrf::field() ?>
            <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Contact person</label><input type="text" name="contact_person" class="form-control"></div>
            <div class="row g-3">
                <div class="col-md-6 mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            </div>
            <div class="mb-3"><label class="form-label">Address</label><input type="text" name="address" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="e.g. Seeds, Fertilizer, Equipment"></div>
            <button type="submit" class="btn btn-success">Add Supplier</button>
            <a href="/suppliers" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
