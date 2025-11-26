/**
 * Global system updates checker
 * Polls /system/check-updates and silently reloads the page when new Activity
 * records are detected. Uses Activity IDs as a simple change token.
 */
(function() {
    const CHECK_INTERVAL_MS = 20000; // 20 seconds
    const CHECK_URL = '/system/check-updates';

    let lastActivityId = 0;
    let intervalId = null;

    function checkForUpdates() {
        const url = CHECK_URL + '?last_id=' + encodeURIComponent(lastActivityId || 0);

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) return null;
            return response.json();
        })
        .then(data => {
            if (!data) return;

            const latestId = data.latest_id || 0;

            // First response: establish baseline without reloading
            if (!lastActivityId) {
                lastActivityId = latestId;
                return;
            }

            if (data.has_updates) {
                // Advance baseline to pick up new changes
                lastActivityId = latestId;
            } else if (latestId > lastActivityId) {
                // No reload requested, but advance baseline to avoid false positives
                lastActivityId = latestId;
            }
        })
        .catch(() => {
            // Ignore errors to avoid console noise
        });
    }

    function startPolling() {
        if (!window.isAuthenticated) return;
        if (intervalId) clearInterval(intervalId);
        intervalId = setInterval(checkForUpdates, CHECK_INTERVAL_MS);
        checkForUpdates();
    }

    document.addEventListener('DOMContentLoaded', startPolling);
})();
