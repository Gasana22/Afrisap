/**
 * Chart.js wrapper helpers. Requires Chart.js to already be loaded
 * (render_footer(['js' => ['charts']]) includes it from CDN).
 */
const SFP_CHART_COLORS = ['#1a7a4c', '#f2a71b', '#2563eb', '#dc3545', '#8b5cf6', '#0891b2'];

function renderLineChart(canvasId, labels, data, label = 'Value') {
    const ctx = document.getElementById(canvasId);
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label,
                data,
                borderColor: SFP_CHART_COLORS[0],
                backgroundColor: 'rgba(26,122,76,0.1)',
                tension: 0.35,
                fill: true,
                pointRadius: 3,
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

function renderBarChart(canvasId, labels, data, label = 'Value') {
    const ctx = document.getElementById(canvasId);
    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label, data, backgroundColor: SFP_CHART_COLORS[0], borderRadius: 6 }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

function renderMultiLineChart(canvasId, labels, series) {
    const ctx = document.getElementById(canvasId);
    const datasets = series.map((s, i) => ({
        label: s.label,
        data: s.data,
        borderColor: s.color || SFP_CHART_COLORS[i % SFP_CHART_COLORS.length],
        backgroundColor: (s.color || SFP_CHART_COLORS[i % SFP_CHART_COLORS.length]) + '1a',
        tension: 0.35,
        fill: false,
        pointRadius: 3,
    }));

    return new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            plugins: { legend: { display: true, position: 'bottom' } },
            scales: { y: { beginAtZero: true } },
        },
    });
}

function renderDoughnutChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    return new Chart(ctx, {
        type: 'doughnut',
        data: { labels, datasets: [{ data, backgroundColor: SFP_CHART_COLORS }] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
    });
}
