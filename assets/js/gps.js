/**
 * GPS capture helper - fills hidden lat/lng inputs from the browser
 * geolocation API. Used by attendance clock-in/out and crop operation logs.
 */
function captureGps(latInputId, lngInputId, statusElId) {
    const statusEl = statusElId ? document.getElementById(statusElId) : null;
    const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };

    if (!navigator.geolocation) {
        setStatus('Geolocation not supported on this device.');
        return;
    }

    setStatus('Getting location...');

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            document.getElementById(latInputId).value = pos.coords.latitude.toFixed(8);
            document.getElementById(lngInputId).value = pos.coords.longitude.toFixed(8);
            setStatus(`Location captured (±${Math.round(pos.coords.accuracy)}m)`);
        },
        (err) => setStatus(`Could not get location: ${err.message}`),
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-gps-capture]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const { latField, lngField, statusField } = btn.dataset;
            captureGps(latField, lngField, statusField);
        });
    });
});
