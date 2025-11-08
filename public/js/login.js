// public/js/login.js
document.addEventListener('DOMContentLoaded', function() {
    // Constants - Use global key for lockout (affects both login pages)
    const lockoutKey = 'login_lockout_global';
    const attemptsKey = 'login_attempts_global';
    const loginForms = ['login-form', 'admin-login-form'];

    // Initialize attempts counter
    let currentAttempts = parseInt(localStorage.getItem(attemptsKey) || '0');
    let remainingAttempts = Math.max(0, 3 - currentAttempts);

    // Form submission handling
    loginForms.forEach(formId => {
        const loginForm = document.getElementById(formId);
        const loginButton = loginForm ? loginForm.querySelector('.login-button') : null;

        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', function(e) {
                // Disable button during submission
                loginButton.disabled = true;
                loginButton.textContent = 'Logging in...';
            });
        }
    });

    // Check for existing global lockout on page load - delay to ensure DOM is ready
    setTimeout(() => {
        const existingLockout = localStorage.getItem(lockoutKey);
        if (existingLockout) {
            const lockoutData = JSON.parse(existingLockout);
            const elapsedSeconds = Math.floor((Date.now() / 1000) - lockoutData.startTime);
            const remainingSeconds = Math.max(0, lockoutData.duration - elapsedSeconds);

            if (remainingSeconds > 0) {
                // Restore lockout state from localStorage
                restoreLockoutState(remainingSeconds);
            } else {
                // Lockout expired, clean up
                localStorage.removeItem(lockoutKey);
                localStorage.removeItem(attemptsKey);
                currentAttempts = 0;
                remainingAttempts = 3;
            }
        }

        // Handle new lockout from server response (only if no existing lockout)
        if (!existingLockout) {
            const countdownElement = document.getElementById('countdown');
            const lockoutMessage = document.getElementById('lockout-message');

            if (countdownElement && lockoutMessage) {
                // New lockout from server response
                const remainingSeconds = parseInt(countdownElement.textContent);
                const lockoutStartTime = Math.floor(Date.now() / 1000);

                // Store globally in localStorage
                localStorage.setItem(lockoutKey, JSON.stringify({
                    startTime: lockoutStartTime,
                    duration: remainingSeconds
                }));

                // Disable form immediately for new lockout
                disableFormsDuringLockout();
                startCountdown(remainingSeconds);
            }
        }
    }, 100); // Small delay to ensure DOM is ready

    // Sync attempts counter with server on page load
    window.addEventListener('load', function() {
        const attemptsCounter = document.querySelector('.attempts-counter');
        if (attemptsCounter) {
            const serverAttempts = parseInt(attemptsCounter.textContent.split('/')[0]);
            if (!isNaN(serverAttempts) && serverAttempts !== remainingAttempts) {
                remainingAttempts = serverAttempts;
                currentAttempts = 3 - remainingAttempts;
                localStorage.setItem(attemptsKey, currentAttempts.toString());
            }
        }
    });

    function restoreLockoutState(remainingSeconds) {
        // Create lockout message if it doesn't exist
        let lockoutMessage = document.getElementById('lockout-message');
        if (!lockoutMessage) {
            const container = document.querySelector('.login-card');
            if (container) {
                // Detect admin login more reliably
                const isAdminLogin = window.location.pathname.includes('/admin') ||
                                   document.querySelector('h3')?.textContent?.includes('Admin');

                const messageDiv = document.createElement('div');
                messageDiv.id = 'lockout-message';
                messageDiv.className = 'mt-4 text-center';

                const bgColor = isAdminLogin ? 'bg-red-900 border-red-600 text-red-200' : 'bg-red-100 border-red-500 text-red-800';
                const textColor1 = isAdminLogin ? 'text-red-100' : 'text-red-900';
                const textColor2 = isAdminLogin ? 'text-red-300' : 'text-red-700';
                const textColor3 = isAdminLogin ? 'text-red-100' : 'text-red-900';

                messageDiv.innerHTML = `
                    <div class="${bgColor} border-2 px-4 py-3 rounded-lg relative shadow-lg animate-pulse">
                        <div class="flex items-center justify-center mb-2">
                            <span class="text-2xl mr-2">🚨</span>
                            <strong class="font-bold ${textColor1} text-lg">ACCOUNT LOCKED!</strong>
                            <span class="text-2xl ml-2">🚨</span>
                        </div>
                        <span class="block ${textColor2} font-semibold" style="color: ${isAdminLogin ? '#fca5a5' : '#dc2626'} !important;">
                            Too many failed login attempts. Please wait <span id="countdown" class="font-bold ${textColor3} text-xl" style="color: ${isAdminLogin ? '#fecaca' : '#b91c1c'} !important;">${remainingSeconds}</span> seconds before trying again.
                        </span>
                    </div>
                `;
                container.appendChild(messageDiv);
                lockoutMessage = messageDiv;
            }
        }

        // Disable form during lockout
        disableFormsDuringLockout();

        startCountdown(remainingSeconds);
    }

    function disableFormsDuringLockout() {
        loginForms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form && !form.hasAttribute('data-locked')) {
                // Mark form as locked to prevent double-processing
                form.setAttribute('data-locked', 'true');

                // Disable all inputs and buttons
                const inputs = form.querySelectorAll('input, button, select, textarea');
                inputs.forEach(input => {
                    input.disabled = true;
                    input.setAttribute('disabled', 'disabled');
                });

                // Change button text
                const button = form.querySelector('.login-button');
                if (button) {
                    button.textContent = 'Locked Out';
                    button.style.opacity = '0.6';
                    button.style.cursor = 'not-allowed';
                }

                // Prevent form submission during lockout
                form.onsubmit = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Account is currently locked. Please wait for the lockout period to end.');
                    return false;
                };

                // Add visual overlay to make it clear the form is disabled
                if (!form.querySelector('.lockout-overlay')) {
                    const overlay = document.createElement('div');
                    overlay.className = 'lockout-overlay';
                    overlay.style.cssText = `
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(255, 0, 0, 0.1);
                        z-index: 10;
                        pointer-events: none;
                        border-radius: 15px;
                    `;
                    form.style.position = 'relative';
                    form.appendChild(overlay);
                }
            }
        });
    }

    function enableFormsAfterLockout() {
        loginForms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                // Remove lock marker
                form.removeAttribute('data-locked');

                // Re-enable all inputs and buttons
                const inputs = form.querySelectorAll('input, button, select, textarea');
                inputs.forEach(input => {
                    input.disabled = false;
                    input.removeAttribute('disabled');
                });

                // Reset button
                const button = form.querySelector('.login-button');
                if (button) {
                    button.textContent = formId === 'admin-login-form' ? 'Login as Admin' : 'Login';
                    button.style.opacity = '1';
                    button.style.cursor = 'pointer';
                }

                // Restore original form submission
                form.onsubmit = null;

                // Remove overlay
                const overlay = form.querySelector('.lockout-overlay');
                if (overlay) {
                    overlay.remove();
                }
            }
        });
    }

    function startCountdown(initialSeconds) {
        let remainingSeconds = initialSeconds;
        const countdownElement = document.getElementById('countdown');
        const lockoutMessage = document.getElementById('lockout-message');

        const countdownInterval = setInterval(() => {
            remainingSeconds--;

            if (remainingSeconds <= 0) {
                clearInterval(countdownInterval);
                localStorage.removeItem(lockoutKey);
                localStorage.removeItem(attemptsKey);

                // Show success message
                const isAdminLogin = window.location.pathname.includes('/admin') ||
                                   document.querySelector('h3')?.textContent?.includes('Admin');
                const successColor = isAdminLogin ? 'text-green-400' : 'text-green-700';
                const successColor2 = isAdminLogin ? 'text-green-300' : 'text-green-700';

                lockoutMessage.innerHTML = `<strong class="font-bold ${successColor}">Lockout period ended!</strong><span class="block sm:inline ${successColor2}"> You can now try logging in again.</span>`;

                // Re-enable form
                enableFormsAfterLockout();

                // Reset attempts
                currentAttempts = 0;
                remainingAttempts = 3;

                // Hide message after 3 seconds
                setTimeout(() => {
                    if (lockoutMessage) {
                        lockoutMessage.style.display = 'none';
                    }
                }, 3000);
            } else {
                if (countdownElement) {
                    countdownElement.textContent = remainingSeconds;
                }

                // Update localStorage with current remaining time (but keep original duration)
                const currentData = JSON.parse(localStorage.getItem(lockoutKey) || '{}');
                // Don't update duration - keep the original lockout duration
                // currentData.duration = remainingSeconds; // This was the bug!
                localStorage.setItem(lockoutKey, JSON.stringify(currentData));
            }
        }, 1000);
    }

    // Password visibility toggle
    const togglePasswords = document.querySelectorAll('.toggle-password');
    togglePasswords.forEach(togglePassword => {
        const password = togglePassword.previousElementSibling;

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);

                // Toggle between show/hide icons
                if (type === 'password') {
                    this.classList.remove('bx-show');
                    this.classList.add('bx-hide');
                } else {
                    this.classList.remove('bx-hide');
                    this.classList.add('bx-show');
                }
            });
        }
    });

    // Basic cache clearing (simplified)
    (function() {
        // Clear any session data that might be lingering
        if (window.sessionStorage) {
            sessionStorage.clear();
        }
        if (window.localStorage) {
            // Only clear specific auth-related items, keep our global lockout/attempts data
            const keysToKeep = [lockoutKey, attemptsKey];
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && !keysToKeep.includes(key) && (key.includes('auth_token') || key.includes('user_data'))) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => localStorage.removeItem(key));
        }
    })();

    // Session timeout functionality
    const sessionTimeoutMinutes = 1; // Will be configurable via admin settings
    const sessionTimeoutMs = sessionTimeoutMinutes * 60 * 1000; // Convert to milliseconds
    let lastActivityTime = Date.now();
    let timeoutWarningShown = false;
    let logoutTimer;
    let warningTimer;

    // Elements for timeout display
    const timeoutModal = createTimeoutModal();
    document.body.appendChild(timeoutModal);

    function createTimeoutModal() {
        const modal = document.createElement('div');
        modal.id = 'session-timeout-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl">
                <div class="flex items-center mb-4">
                    <div class="text-yellow-500 text-2xl mr-3">⏰</div>
                    <h3 class="text-lg font-bold text-gray-900">Session Timeout Warning</h3>
                </div>
                <p class="text-gray-600 mb-6">
                    You will be automatically logged out in <span id="timeout-countdown" class="font-bold text-red-600">60</span> seconds due to inactivity.
                </p>
                <div class="flex space-x-3">
                    <button id="extend-session" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                        Stay Logged In
                    </button>
                    <button id="logout-now" class="flex-1 bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-200">
                        Logout Now
                    </button>
                </div>
            </div>
        `;
        return modal;
    }

    function resetSessionTimeout() {
        lastActivityTime = Date.now();
        timeoutWarningShown = false;

        // Clear existing timers
        if (logoutTimer) clearTimeout(logoutTimer);
        if (warningTimer) clearTimeout(warningTimer);

        // Hide timeout modal if visible
        timeoutModal.classList.add('hidden');

        // Set new warning timer (show warning 30 seconds before logout)
        const warningTime = sessionTimeoutMs - 30000; // 30 seconds before timeout
        if (warningTime > 0) {
            warningTimer = setTimeout(() => {
                showTimeoutWarning();
            }, warningTime);
        }

        // Set logout timer
        logoutTimer = setTimeout(() => {
            performLogout();
        }, sessionTimeoutMs);
    }

    function showTimeoutWarning() {
        if (timeoutWarningShown) return;
        timeoutWarningShown = true;

        timeoutModal.classList.remove('hidden');
        let countdown = 60;

        const countdownElement = document.getElementById('timeout-countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);

        // Setup button event listeners
        document.getElementById('extend-session').onclick = () => {
            clearInterval(countdownInterval);
            resetSessionTimeout();
        };

        document.getElementById('logout-now').onclick = () => {
            clearInterval(countdownInterval);
            performLogout();
        };
    }

    function performLogout() {
        // Show logout message
        const logoutMessage = document.createElement('div');
        logoutMessage.className = 'fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center';
        logoutMessage.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md mx-4 shadow-xl text-center">
                <div class="text-red-500 text-4xl mb-4">🔐</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Session Expired</h3>
                <p class="text-gray-600">You have been logged out due to inactivity.</p>
                <div class="mt-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                </div>
            </div>
        `;
        document.body.appendChild(logoutMessage);

        // Perform logout after 2 seconds
        setTimeout(() => {
            window.location.href = '/logout';
        }, 2000);
    }

    // Track user activity
    function trackActivity() {
        resetSessionTimeout();
    }

    // Activity event listeners
    const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
    activityEvents.forEach(event => {
        document.addEventListener(event, trackActivity, true);
    });

    // Initialize session timeout on page load
    resetSessionTimeout();

});
