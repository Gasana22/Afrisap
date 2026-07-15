/**
 * Worker portal behaviour: online/offline banner + confirm dialogs.
 * GPS capture lives in gps.js, offline queueing in offline.js.
 */
document.addEventListener('DOMContentLoaded', () => {
    function updateOnlineState() {
        document.body.classList.toggle('is-offline', !navigator.onLine);
    }
    window.addEventListener('online', updateOnlineState);
    window.addEventListener('offline', updateOnlineState);
    updateOnlineState();

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
