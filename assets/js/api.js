/**
 * Minimal fetch wrapper for api/v1/* endpoints.
 */
const SFP = {
    csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    },

    async request(path, { method = 'GET', body = null } = {}) {
        const opts = {
            method,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        };

        if (body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify({ ...body, csrf_token: this.csrfToken() });
        }

        const res = await fetch(path, opts);
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(data.message || `Request failed (${res.status})`);
        }

        return data;
    },

    get(path) { return this.request(path); },
    post(path, body) { return this.request(path, { method: 'POST', body }); },
    put(path, body) { return this.request(path, { method: 'PUT', body }); },
    del(path) { return this.request(path, { method: 'DELETE', body: {} }); },
};

// Sidebar toggle shared by admin/org-admin/worker dashboards.
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});
