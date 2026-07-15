/**
 * Leaflet map helpers - farm/block GPS picker and read-only location display.
 * Requires Leaflet to already be loaded (footer.php includes it when
 * render_footer(['js' => ['map']]) is used).
 */
function initMapPicker(containerId, latInputId, lngInputId, initialLat = -1.9441, initialLng = 30.0619, zoom = 13) {
    const map = L.map(containerId).setView([initialLat, initialLng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    const existingLat = parseFloat(latInput.value);
    const existingLng = parseFloat(lngInput.value);
    const startPos = (!isNaN(existingLat) && !isNaN(existingLng)) ? [existingLat, existingLng] : [initialLat, initialLng];

    const marker = L.marker(startPos, { draggable: true }).addTo(map);
    map.setView(startPos, zoom);

    function updateInputs(latlng) {
        latInput.value = latlng.lat.toFixed(8);
        lngInput.value = latlng.lng.toFixed(8);
    }

    marker.on('dragend', () => updateInputs(marker.getLatLng()));
    map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        updateInputs(e.latlng);
    });

    return map;
}

function initMapView(containerId, points) {
    const map = L.map(containerId);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const markers = points.map((p) => L.marker([p.lat, p.lng]).addTo(map).bindPopup(p.label || ''));

    if (markers.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
    } else {
        map.setView([-1.9441, 30.0619], 6);
    }

    return map;
}
