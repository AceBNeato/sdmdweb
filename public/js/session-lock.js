// Session Lockout Functionality
// Locks the screen after inactivity and requires password to unlock

(function() {
    'use strict';

    // Configuration - separate from session timeout
    let lockoutTimeoutMinutes = window.sessionData?.lockoutTimeoutMinutes || 1;
    let lockoutTimeoutMs = lockoutTimeoutMinutes * 60 * 1000;

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

    function updateLockoutSettings() {
        // Determine the correct session settings URL based on current user
        let settingsUrl = '/session-settings'; // default for admin
        
        if (window.location.pathname.includes('/staff/')) {
            settingsUrl = '/staff/session-settings';
        } else if (window.location.pathname.includes('/technician/')) {
            settingsUrl = '/technician/session-settings';
        }
        
        // Fetch updated settings from server
        fetch(settingsUrl, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.lockoutTimeoutMinutes !== undefined && data.lockoutTimeoutMinutes !== lockoutTimeoutMinutes) {
                    lockoutTimeoutMinutes = data.lockoutTimeoutMinutes;
                    lockoutTimeoutMs = lockoutTimeoutMinutes * 60 * 1000;
                    
                    // Reset timer with new settings
                    if (!isLocked) {
                        resetLockoutTimer();
                    }
                    
                    console.log('Session lock timeout updated to:', lockoutTimeoutMinutes, 'minutes');
                }
            })
            .catch(error => {
                console.log('Failed to fetch updated session settings:', error);
                // Don't show error to user - just use existing settings
            });
    }

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

        // Clear password field first
        unlockPassword.value = '';
        unlockError.classList.add('d-none');

        // Show modal
        lockModal.style.display = 'flex';

        // Force reflow to ensure proper rendering
        lockModal.offsetHeight;

        // Emit session lock event
        document.dispatchEvent(new CustomEvent('sessionLockShown'));

        // Persist lock state across page reloads
        sessionStorage.setItem('session_locked', 'true');

        // Hide all other content in the body
        Array.from(document.body.children).forEach(child => {
            if (child.id !== 'session-lock-modal' && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
                child.dataset.originalDisplay = window.getComputedStyle(child).display;
                child.style.display = 'none';
            }
        });

        // Start anti-tamper interval
        if (window.antiTamperInterval) clearInterval(window.antiTamperInterval);
        window.antiTamperInterval = setInterval(() => {
            if (!isLocked) return;
            
            // If they deleted the modal from the DOM, force reload to let backend block them
            if (!document.body.contains(lockModal)) {
                window.location.reload();
                return;
            }
            
            // Check if they tried to bypass via DevTools CSS (e.g. unchecking top: 0)
            const computedStyle = window.getComputedStyle(lockModal);
            if (computedStyle.display === 'none' || computedStyle.visibility === 'hidden' || computedStyle.opacity === '0' || computedStyle.top !== '0px') {
                window.location.reload();
            }
        }, 1000);

        // Focus on password field after modal is fully rendered
        setTimeout(() => {
            // Ensure field is enabled and focused
            unlockPassword.disabled = false;
            unlockPassword.removeAttribute('disabled');
            unlockPassword.focus();
            unlockPassword.select();
        }, 100);
    }

    function hideLockModal() {
        isLocked = false;
        lockModal.style.display = 'none';
        unlockPassword.value = '';
        unlockError.classList.add('d-none');

        // Restore body content
        Array.from(document.body.children).forEach(child => {
            if (child.id !== 'session-lock-modal' && child.tagName !== 'SCRIPT' && child.tagName !== 'STYLE') {
                if (child.dataset.originalDisplay !== undefined) {
                    child.style.display = child.dataset.originalDisplay;
                    delete child.dataset.originalDisplay;
                } else {
                    child.style.display = '';
                }
            }
        });

        if (window.antiTamperInterval) {
            clearInterval(window.antiTamperInterval);
            window.antiTamperInterval = null;
        }

        // Clear persistent lock state
        sessionStorage.removeItem('session_locked');

        // Emit session lock hidden event
        document.dispatchEvent(new CustomEvent('sessionLockHidden'));
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
                
                // Show centered SweetAlert using SweetAlertSystem
                if (typeof window.SweetAlert !== 'undefined') {
                    window.SweetAlert.success('Session unlocked successfully!', {
                        timer: 3000,
                        showConfirmButton: true
                    });
                } else {
                    console.log('Session unlocked successfully!');
                }
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

    function showToast(type = 'success', message) {
        // Use existing toast system if available
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        }
    }

    let lastHeartbeatTime = Date.now();
    const HEARTBEAT_INTERVAL = 60000; // 1 minute

    function sendHeartbeat() {
        const now = Date.now();
        if (now - lastHeartbeatTime > HEARTBEAT_INTERVAL) {
            lastHeartbeatTime = now;
            fetch('/session-heartbeat', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).catch(error => {
                console.log('Failed to send session heartbeat:', error);
            });
        }
    }

    // Track user activity
    function trackActivity() {
        if (!isLocked) {
            resetLockoutTimer();
            sendHeartbeat();
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
        // Check if session was locked before page reload
        if (sessionStorage.getItem('session_locked') === 'true') {
            showLockModal();
            return; // Don't start the timer if we're already locked
        }

        resetLockoutTimer();
        
        // Check for updated settings every 30 seconds
        setInterval(updateLockoutSettings, 30000);
    });

    // Handle page visibility changes (tab switching)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && !isLocked) {
            // Reset timer when user returns to tab
            resetLockoutTimer();
            // Also check for updated settings
            updateLockoutSettings();
        }
    });

    // Handle window focus/blur
    window.addEventListener('focus', function() {
        if (!isLocked) {
            resetLockoutTimer();
            // Also check for updated settings
            updateLockoutSettings();
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
