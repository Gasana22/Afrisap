/**
 * Org-admin dashboard behaviour: confirm dialogs, crop-filter tabs that
 * swap the yield chart's dataset without a page reload.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    const tabs = document.querySelectorAll('.crop-tabs [data-crop]');
    if (tabs.length && window.yieldChartInstance) {
        tabs.forEach((tab) => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                tabs.forEach((t) => t.classList.remove('active'));
                tab.classList.add('active');
                const dataset = window.yieldChartData?.[tab.dataset.crop];
                if (dataset) {
                    window.yieldChartInstance.data.datasets[0].data = dataset;
                    window.yieldChartInstance.update();
                }
            });
        });
    }
});
