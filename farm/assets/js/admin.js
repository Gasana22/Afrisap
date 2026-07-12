(function () {
    'use strict';

    // ---------- Mobile sidebar toggle ----------
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var backdrop = document.querySelector('[data-sidebar-backdrop]');
    function closeSidebar() { document.body.classList.remove('sidebar-open'); }
    if (toggle) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
    document.querySelectorAll('.sidebar nav a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });

    // ---------- Flash alerts: dismissible, success ones fade on their own ----------
    document.querySelectorAll('.alert').forEach(function (alertEl) {
        var closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'alert-close';
        closeBtn.setAttribute('aria-label', 'Dismiss');
        closeBtn.textContent = '×';
        closeBtn.addEventListener('click', function () { dismiss(alertEl); });
        alertEl.appendChild(closeBtn);

        if (alertEl.classList.contains('alert-success')) {
            setTimeout(function () { dismiss(alertEl); }, 5000);
        }
    });

    function dismiss(el) {
        if (!el || !el.parentNode) return;
        el.style.transition = 'opacity 200ms ease-out, transform 200ms ease-out, margin 200ms ease-out, padding 200ms ease-out';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-4px)';
        setTimeout(function () {
            el.style.maxHeight = '0px';
            el.style.margin = '0';
            el.style.padding = '0';
            el.style.border = 'none';
            el.style.overflow = 'hidden';
        }, 200);
    }

    // ---------- Mini in-table bar charts: scale each column's bars to the
    // largest value present, so a report table stays honest about magnitude
    // (never compares two different units on one scale). ----------
    document.querySelectorAll('[data-mini-bar-group]').forEach(function (group) {
        var cells = group.querySelectorAll('[data-mini-bar-value]');
        var max = 0;
        cells.forEach(function (cell) {
            var v = parseFloat(cell.getAttribute('data-mini-bar-value'));
            if (!isNaN(v) && v > max) max = v;
        });
        if (max <= 0) return;
        cells.forEach(function (cell) {
            var v = parseFloat(cell.getAttribute('data-mini-bar-value')) || 0;
            var fill = cell.querySelector('.mini-bar-fill');
            if (fill) fill.style.width = Math.max((v / max) * 100, v > 0 ? 4 : 0) + '%';
        });
    });
})();
