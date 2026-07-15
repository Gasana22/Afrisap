/**
 * Offline queueing for the worker portal. Actions performed while offline
 * (clock in/out, activity logs) are stored in localStorage and flushed to
 * api/v1/sync.php once connectivity returns.
 */
const SFPOffline = {
    KEY: 'sfp_offline_queue',

    queue() {
        try {
            return JSON.parse(localStorage.getItem(this.KEY) || '[]');
        } catch {
            return [];
        }
    },

    save(queue) {
        localStorage.setItem(this.KEY, JSON.stringify(queue));
        this.updatePendingBadge(queue.length);
    },

    enqueue(actionType, data) {
        const queue = this.queue();
        queue.push({ action_type: actionType, action_data: data, queued_at: new Date().toISOString() });
        this.save(queue);
    },

    updatePendingBadge(count) {
        const badge = document.getElementById('offlinePendingCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    },

    async flush() {
        if (!navigator.onLine) return;
        let queue = this.queue();
        if (!queue.length) return;

        const remaining = [];
        for (const item of queue) {
            try {
                await SFP.post('/api/v1/sync.php', item);
            } catch {
                remaining.push(item);
            }
        }
        this.save(remaining);
    },
};

document.addEventListener('DOMContentLoaded', () => {
    SFPOffline.updatePendingBadge(SFPOffline.queue().length);
    SFPOffline.flush();
    window.addEventListener('online', () => SFPOffline.flush());
});
