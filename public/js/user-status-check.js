/**
 * Global user status checker
 * Polls /admin/accounts/check-status and forces logout if user is deactivated
 */
(function() {
    let checkInterval;
    const CHECK_INTERVAL_MS = 15000; // 15 seconds
    const STATUS_URL = '/session/check-status';

    function checkUserStatus() {
        fetch(STATUS_URL, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.status === 401) {
                // Not logged in at all
                forceLogout();
                return;
            }
            return response.json();
        })
        .then(data => {
            if (!data) return;

            // If backend says this session is already logged out, just redirect quietly
            if (data.logged_out) {
                forceLogout();
                return;
            }

            // If account has been deactivated while user is online, show warning then logout
            if (data.is_active === false) {
                showDeactivatedAlertAndLogout();
            }
        })
        .catch(err => {
            // Silently fail to avoid spamming console on network errors
        });
    }

    function forceLogout() {
        clearInterval(checkInterval);
        // Clear any client-side storage if you use it
        // localStorage.clear();
        // sessionStorage.clear();
        window.location.href = '/login';
    }

    function showDeactivatedAlertAndLogout() {
        clearInterval(checkInterval);

        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Account Deactivated',
                text: 'Your account has been deactivated by an administrator. You will be logged out and returned to the login page.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                forceLogout();
            });
        } else {
            // Fallback if SweetAlert is not available
            alert('Your account has been deactivated by an administrator. You will be logged out.');
            forceLogout();
        }
    }

    function startPolling() {
        if (checkInterval) clearInterval(checkInterval);
        checkInterval = setInterval(checkUserStatus, CHECK_INTERVAL_MS);
        // Run once immediately
        checkUserStatus();
    }

    function stopPolling() {
        if (checkInterval) {
            clearInterval(checkInterval);
            checkInterval = null;
        }
    }

    // Auto-start if we are on a protected page and not on login page
    if (document.querySelector('meta[name="csrf-token"]') && !window.location.pathname.includes('/login')) {
        startPolling();
    }

    // Optional: stop polling when tab is not visible to save resources
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });

    // Expose controls globally if needed
    window.UserStatusCheck = {
        start: startPolling,
        stop: stopPolling,
        checkNow: checkUserStatus
    };
})();
