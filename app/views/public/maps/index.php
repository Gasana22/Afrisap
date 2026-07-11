<h4 class="mb-3">Farm Map</h4>
<p class="text-muted small">Farms, plots, and recent field-activity GPS points. Toggle layers with the control in the top-right corner.</p>

<div id="farmMap" style="height: 70vh; border-radius: 0.5rem;"></div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
(function () {
    const farms = <?= json_encode($farms) ?>;
    const plots = <?= json_encode($plots) ?>;
    const activities = <?= json_encode($activities) ?>;

    const allPoints = farms.map(f => [parseFloat(f.gps_lat), parseFloat(f.gps_lng)])
        .concat(plots.map(p => [parseFloat(p.gps_lat), parseFloat(p.gps_lng)]));

    const center = allPoints.length ? allPoints[0] : [-1.9441, 30.0619];
    const map = L.map('farmMap').setView(center, allPoints.length ? 12 : 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const farmLayer = L.layerGroup();
    farms.forEach(function (f) {
        L.marker([parseFloat(f.gps_lat), parseFloat(f.gps_lng)], {
            icon: L.divIcon({ className: '', html: '<i class="bi bi-house-fill" style="color:#0f3d2e;font-size:24px;"></i>', iconSize: [24, 24] })
        }).bindPopup('<strong>' + f.name + '</strong><br>' + [f.district, f.village].filter(Boolean).join(' / '))
          .addTo(farmLayer);
    });
    farmLayer.addTo(map);

    const plotLayer = L.layerGroup();
    plots.forEach(function (p) {
        L.circleMarker([parseFloat(p.gps_lat), parseFloat(p.gps_lng)], { radius: 6, color: '#1a6b4f', fillOpacity: 0.8 })
          .bindPopup('<strong>Plot ' + p.plot_code + '</strong><br>' + p.farm_name + ' / ' + p.block_name)
          .addTo(plotLayer);
    });
    plotLayer.addTo(map);

    const activityLayer = L.layerGroup();
    activities.forEach(function (a) {
        L.circleMarker([parseFloat(a.gps_lat), parseFloat(a.gps_lng)], { radius: 4, color: '#eb6834', fillOpacity: 0.7 })
          .bindPopup('<strong>' + a.activity_type.charAt(0).toUpperCase() + a.activity_type.slice(1) + '</strong><br>' +
              a.batch_code + '<br>' + a.activity_date + (a.worker_name ? '<br>by ' + a.worker_name : ''))
          .addTo(activityLayer);
    });

    const heatPoints = activities.map(a => [parseFloat(a.gps_lat), parseFloat(a.gps_lng), 0.5]);
    const heatLayer = L.heatLayer(heatPoints, { radius: 25, blur: 20 });

    const overlays = {
        'Farms': farmLayer,
        'Plots': plotLayer,
        'Field Activities': activityLayer,
        'Activity Heatmap': heatLayer
    };
    L.control.layers(null, overlays, { collapsed: false }).addTo(map);

    if (allPoints.length > 1) {
        map.fitBounds(allPoints, { padding: [30, 30] });
    }
})();
</script>
