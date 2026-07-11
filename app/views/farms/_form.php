<?php
/** @var array|null $farm */
$farm = $farm ?? null;
?>
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Farm name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($farm['name'] ?? '') ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Size (hectares)</label>
        <input type="text" name="size_hectares" class="form-control" value="<?= htmlspecialchars($farm['size_hectares'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">District</label>
        <input type="text" name="district" class="form-control" value="<?= htmlspecialchars($farm['district'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Village</label>
        <input type="text" name="village" class="form-control" value="<?= htmlspecialchars($farm['village'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">GPS Latitude</label>
        <input type="text" name="gps_lat" class="form-control" value="<?= htmlspecialchars($farm['gps_lat'] ?? '') ?>" placeholder="-1.9441">
    </div>
    <div class="col-md-6">
        <label class="form-label">GPS Longitude</label>
        <input type="text" name="gps_lng" class="form-control" value="<?= htmlspecialchars($farm['gps_lng'] ?? '') ?>" placeholder="30.0619">
    </div>
    <div class="col-md-6">
        <label class="form-label">Owner</label>
        <select name="owner_id" class="form-select">
            <option value="">— None —</option>
            <?php foreach ($owners as $owner): ?>
            <option value="<?= (int) $owner['id'] ?>" <?= (isset($farm['owner_id']) && (int) $farm['owner_id'] === (int) $owner['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($owner['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="active" <?= (($farm['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($farm['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
</div>

<div id="farmMap" style="height: 300px; margin-top: 1rem; border-radius: 0.5rem;"></div>
<p class="text-muted small mt-1">Click the map to set GPS coordinates (requires internet to load map tiles).</p>

<div class="mt-4">
    <button type="submit" class="btn btn-success">Save Farm</button>
    <a href="/farms" class="btn btn-outline-secondary">Cancel</a>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const latInput = document.querySelector('input[name="gps_lat"]');
    const lngInput = document.querySelector('input[name="gps_lng"]');
    const startLat = parseFloat(latInput.value) || -1.9441;
    const startLng = parseFloat(lngInput.value) || 30.0619;

    const map = L.map('farmMap').setView([startLat, startLng], latInput.value ? 13 : 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = (latInput.value && lngInput.value) ? L.marker([startLat, startLng]).addTo(map) : null;

    map.on('click', function (e) {
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(map);
        }
    });
})();
</script>
