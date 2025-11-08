// Session Lockout Functionality
// Locks the screen after inactivity and requires password to unlock

(function() {
    'use strict';

    // Configuration - separate from session timeout
    const lockoutTimeoutMinutes = window.sessionData?.lockoutTimeoutMinutes || 1;
    const lockoutTimeoutMs = lockoutTimeoutMinutes * 60 * 1000;

    let lastActivityTime = Date.now();
    let isLocked = false;
    let lockoutTimer;

    // Elements
    const lockModal = document.getElementById('session-lock-modal');
    const unlockForm = document.getElementById('unlock-form');
    const unlockPassword = document.getElementById('unlock-password');
    const unlockBtn = document.getElementById('unlock-btn');
    const unlockError = document.getElementById('unlock-error');

    // CSRF token for AJAX requests
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function resetLockoutTimer() {
        if (isLocked) return; // Don't reset if already locked

        lastActivityTime = Date.now();

        // Clear existing timer
        if (lockoutTimer) {
            clearTimeout(lockoutTimer);
        }

        // Set new lockout timer
        lockoutTimer = setTimeout(() => {
            showLockModal();
        }, lockoutTimeoutMs);
    }

    function showLockModal() {
        if (isLocked) return;

        isLocked = true;
        lockModal.style.display = 'flex';

        // Focus on password field
        setTimeout(() => {
            unlockPassword.focus();
        }, 100);

        // Clear password field
        unlockPassword.value = '';
        unlockError.classList.add('d-none');
    }

    function hideLockModal() {
        isLocked = false;
        lockModal.style.display = 'none';
        unlockPassword.value = '';
        unlockError.classList.add('d-none');
    }

    function unlockSession() {
        const password = unlockPassword.value.trim();

        if (!password) {
            showUnlockError('Please enter your password.');
            return;
        }

        // Disable button during verification
        unlockBtn.disabled = true;
        unlockBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Verifying...';

        // Send AJAX request to verify password
        fetch(window.sessionData.unlockUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                hideLockModal();
                resetLockoutTimer();
                showToast('Session unlocked successfully!', 'success');
            } else {
                showUnlockError(data.message || 'Invalid password. Please try again.');
            }
        })
        .catch(error => {
            console.error('Unlock error:', error);
            showUnlockError('An error occurred. Please try again.');
        })
        .finally(() => {
            unlockBtn.disabled = false;
            unlockBtn.innerHTML = 'Unlock';
        });
    }

    function showUnlockError(message) {
        unlockError.textContent = message;
        unlockError.classList.remove('d-none');
        unlockPassword.focus();
        unlockPassword.select();
    }

    function showToast(message, type = 'success') {
        // Use existing toast system if available
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    // Track user activity
    function trackActivity() {
        if (!isLocked) {
            resetLockoutTimer();
        }
    }

    // Activity event listeners (same as session timeout)
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    activityEvents.forEach(event => {
        document.addEventListener(event, trackActivity, true);
    });

    // Form submission handler
    if (unlockForm) {
        unlockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            unlockSession();
        });
    }

    // Unlock button handler
    if (unlockBtn) {
        unlockBtn.addEventListener('click', function(e) {
            e.preventDefault();
            unlockSession();
        });
    }

    // Password field enter key handler
    if (unlockPassword) {
        unlockPassword.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                unlockSession();
            }
        });
    }

    // Initialize lockout timer on page load
    document.addEventListener('DOMContentLoaded', function() {
        resetLockoutTimer();
    });

    // Handle page visibility changes (tab switching)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && !isLocked) {
            // Reset timer when user returns to tab
            resetLockoutTimer();
        }
    });

    // Handle window focus/blur
    window.addEventListener('focus', function() {
        if (!isLocked) {
            resetLockoutTimer();
        }
    });

    // Export functions for potential external use
    window.SessionLock = {
        lock: showLockModal,
        unlock: hideLockModal,
        isLocked: () => isLocked,
        resetTimer: resetLockoutTimer
    };

})();
