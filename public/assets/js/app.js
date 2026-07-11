(function () {
    const root = document.documentElement;
    const themeToggle = document.getElementById('themeToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');

    const savedTheme = localStorage.getItem('sfmtp_theme') || 'light';
    root.setAttribute('data-bs-theme', savedTheme);
    updateThemeIcon(savedTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const current = root.getAttribute('data-bs-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('sfmtp_theme', next);
            // Reload rather than just flipping the attribute: pages with
            // Chart.js canvases read theme-dependent CSS variables once at
            // construction time and don't repaint on their own, so an
            // in-place toggle would leave charts showing the old theme's
            // colors against the new theme's background.
            location.reload();
        });
    }

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    function updateThemeIcon(theme) {
        if (!themeToggle) return;
        const icon = themeToggle.querySelector('i');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
})();
