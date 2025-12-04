/**
 * SweetAlert Notification System
 * Replaces toast notifications with SweetAlert2 for better UX
 */

class SweetAlertSystem {
    constructor() {
        this.defaultOptions = {
            toast: false,
            position: 'center',
            showConfirmButton: true,
            timer: 3000,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: true,
            allowEnterKey: true
        };
        
        this.init();
    }

    init() {
        // Process session messages on page load
        this.processSessionMessages();
        
        // Override Laravel's flash message handling
        this.overrideFlashMessages();
        
        // Set up global SweetAlert helper
        window.SweetAlert = this;
    }

    /**
     * Process session messages from Laravel
     */
    processSessionMessages() {
        console.log('Processing session messages:', window.sessionMessages);
        if (window.sessionMessages) {
            Object.entries(window.sessionMessages).forEach(([type, message]) => {
                console.log(`Found message: ${type} = ${message}`);
                if (message) {
                    this.show(type, message);
                }
            });
        } else {
            console.log('No session messages found');
        }
    }

    /**
     * Show a SweetAlert notification (always centered modal)
     */
    show(type, message, options = {}) {
        console.log(`Showing SweetAlert: ${type} - ${message}`);
        
        // Force modal mode - never allow toast
        const config = {
            ...this.defaultOptions,
            ...this.getConfig(type, message, options),
            toast: false  // Explicitly force modal mode
        };
        
        // Always use Swal.fire for centered modals
        Swal.fire(config);
    }

    /**
     * Get SweetAlert configuration based on type
     */
    getConfig(type, message, options = {}) {
        const configs = {
            success: {
                icon: 'success',
                title: 'Success!',
                text: message,
                confirmButtonText: 'Great!',
                confirmButtonColor: '#10b981',
                background: '#f0fdf4',
                color: '#065f46'
            },
            error: {
                icon: 'error',
                title: 'Error!',
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
                background: '#fef2f2',
                color: '#991b1b'
            },
            warning: {
                icon: 'warning',
                title: 'Warning!',
                text: message,
                confirmButtonText: 'Understood',
                confirmButtonColor: '#f59e0b',
                background: '#fffbeb',
                color: '#92400e'
            },
            info: {
                icon: 'info',
                title: 'Information',
                text: message,
                confirmButtonText: 'Got it',
                confirmButtonColor: '#3b82f6',
                background: '#eff6ff',
                color: '#1e40af'
            }
        };

        return { ...configs[type], ...options };
    }

    /**
     * Show success notification
     */
    success(message, options = {}) {
        this.show('success', message, options);
    }

    /**
     * Show error notification
     */
    error(message, options = {}) {
        this.show('error', message, options);
    }

    /**
     * Show warning notification
     */
    warning(message, options = {}) {
        this.show('warning', message, options);
    }

    /**
     * Show info notification
     */
    info(message, options = {}) {
        this.show('info', message, options);
    }

    /**
     * Show confirmation dialog
     */
    confirm(message, options = {}) {
        const defaultConfirmOptions = {
            title: 'Are you sure?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            reverseButtons: true,
            toast: false,
            position: 'center'
        };

        return Swal.fire({ ...defaultConfirmOptions, ...options });
    }

    /**
     * Show toast notification (small, top-right) - DISABLED
     * This method is disabled to force all notifications to be centered modals
     */
    toast(type, message, options = {}) {
        console.warn('toast() method is disabled. Using show() instead for centered modal.');
        this.show(type, message, options);
    }

    /**
     * Show loading dialog
     */
    loading(message = 'Loading...', options = {}) {
        const loadingConfig = {
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
            toast: false,
            position: 'center',
            ...options
        };

        return Swal.fire(loadingConfig);
    }

    /**
     * Close current SweetAlert
     */
    close() {
        Swal.close();
    }

    /**
     * Override Laravel's default flash message handling
     */
    overrideFlashMessages() {
        // Override any existing toast system calls with new signature: showToast(type, message)
        window.showToast = (type, message) => this.show(type, message);
        window.showNotification = (type, message) => this.show(type, message);
        
        // Universal AJAX response handler (handles redirect/reload)
        window.handleToastResponse = (response, fallbackType = 'success') => {
            if (!response) {
                return;
            }

            if (typeof response === 'string') {
                this.show(fallbackType, response);
                return;
            }

            let message = response.message;
            const type = (response.success === false || response.error) ? 'error' : (response.toastType || fallbackType);
            if (type === 'error') {
                message = message || response.error;
            }
            if (!message) {
                return;
            }

            // Handle special actions
            if (response.redirect) {
                this.successWithRedirect(message, response.redirect, response.delay || 0);
                return;
            }
            if (response.reload) {
                this.successWithReload(message, response.delay || 0);
                return;
            }

            this.show(type, message);
        };
    }

    /**
     * Handle form submissions with SweetAlert feedback
     */
    handleFormSubmit(form, options = {}) {
        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        const originalText = submitButton ? submitButton.textContent : '';
        
        // Show loading
        this.loading(options.loadingMessage || 'Processing...', {
            didOpen: () => {
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = options.loadingText || 'Processing...';
                }
            }
        });

        return new Promise((resolve, reject) => {
            // Form will be submitted normally, SweetAlert will be handled by server response
            setTimeout(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
                resolve();
            }, 100);
        });
    }

    /**
     * Show success with redirect (only on confirm or timer)
     */
    successWithRedirect(message, redirectUrl, delay = null) {
        const config = {
            ...this.getConfig('success', message),
            showConfirmButton: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: delay || undefined,
            timerProgressBar: !!delay
        };

        return Swal.fire(config).then((result) => {
            if (result.isConfirmed || result.dismiss === 'timer') {
                window.location.href = redirectUrl;
            }
        });
    }

    /**
     * Show success with reload (only on confirm or timer)
     */
    successWithReload(message, delay = null) {
        const config = {
            ...this.getConfig('success', message),
            showConfirmButton: true,
            confirmButtonText: 'Reload Page',
            allowOutsideClick: false,
            allowEscapeKey: false,
            timer: delay || undefined,
            timerProgressBar: !!delay
        };

        return Swal.fire(config).then((result) => {
            if (result.isConfirmed || result.dismiss === 'timer') {
                window.location.reload();
            }
        });
    }

    /**
     * Show error with reload option
     */
    errorWithReload(message) {
        this.error(message, {
            showCancelButton: true,
            confirmButtonText: 'Reload Page',
            cancelButtonText: 'Stay Here',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            didClose: (result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            }
        });
    }
}

// Initialize SweetAlert system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SweetAlertSystem();
});

// Universal AJAX Response Interceptor for SweetAlert Notifications
// Catches ALL AJAX (XHR, fetch, jQuery under the hood) ONCE with opt-in for errors
(function() {
    // Override setRequestHeader to detect opt-in
    const originalSetRequestHeader = XMLHttpRequest.prototype.setRequestHeader;
    XMLHttpRequest.prototype.setRequestHeader = function(name, value) {
        if (name.toLowerCase() === 'x-sweetalert') {
            this._useSweetAlert = value === 'true';
        }
        return originalSetRequestHeader.apply(this, [name, value]);
    };

    const originalOpen = XMLHttpRequest.prototype.open;
    const originalSend = XMLHttpRequest.prototype.send;
    const originalFetch = window.fetch;

    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        this._useSweetAlert = false;
        return originalOpen.apply(this, [method, url, ...args]);
    };

    XMLHttpRequest.prototype.send = function(body) {
        const xhr = this;
        let useSweetAlert = this._useSweetAlert || false;
        if (!useSweetAlert && typeof body === 'string') {
            useSweetAlert = body.includes('use_sweetalert=1');
        }

        const originalOnReadyStateChange = this.onreadystatechange;
        this.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status !== 0) {
                const contentType = xhr.getResponseHeader('content-type') || '';
                let responseData;

                if (contentType.includes('application/json')) {
                    try {
                        responseData = JSON.parse(xhr.responseText);
                        const hasToastData = responseData.message || responseData.error || responseData.success !== undefined;
                        if (!hasToastData) return;

                        const isSuccess = xhr.status >= 200 && xhr.status < 300;
                        const shouldHandle = isSuccess ? true : useSweetAlert;

                        if (shouldHandle && window.handleToastResponse) {
                            setTimeout(() => window.handleToastResponse(responseData), 0);
                        }
                    } catch (e) {}
                } else if (xhr.status >= 400 && useSweetAlert) {
                    responseData = {
                        message: (xhr.responseText || xhr.statusText || 'An error occurred').slice(0, 500),
                        error: true
                    };
                    if (window.handleToastResponse) {
                        setTimeout(() => window.handleToastResponse(responseData), 0);
                    }
                }
            }
            if (originalOnReadyStateChange) originalOnReadyStateChange.apply(this, arguments);
        };

        return originalSend.apply(this, arguments);
    };

    // Override fetch
    window.fetch = function(input, init = {}) {
        let useSweetAlert = false;
        const headers = init.headers;
        if (headers) {
            if (headers instanceof Headers) {
                useSweetAlert = headers.get('X-SweetAlert') === 'true';
            } else if (typeof headers === 'object') {
                useSweetAlert = headers['X-SweetAlert'] === 'true';
            }
        }
        if (!useSweetAlert && typeof init.body === 'string') {
            useSweetAlert = init.body.includes('use_sweetalert=1');
        }

        return originalFetch.call(this, input, init).then((response) => {
            const clone = response.clone();
            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                return clone.json().then((data) => {
                    const hasToastData = data.message || data.error || data.success !== undefined;
                    if (!hasToastData) return response;

                    const isSuccess = response.ok;
                    const shouldHandle = isSuccess ? true : useSweetAlert;

                    if (shouldHandle && window.handleToastResponse) {
                        setTimeout(() => window.handleToastResponse(data), 0);
                    }
                    return response;
                }).catch(() => response);
            } else if (!response.ok && useSweetAlert) {
                return clone.text().then((text) => {
                    window.handleToastResponse({
                        message: (text || response.statusText || 'An error occurred').slice(0, 500),
                        error: true
                    });
                    return response;
                });
            }
            return response;
        });
    };
})();

// REMOVED: Redundant jQuery global handlers (universal interceptor handles everything ONCE)

// Export for use in other scripts
window.SweetAlertSystem = SweetAlertSystem;