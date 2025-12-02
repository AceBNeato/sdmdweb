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
            timerProgressBar: false,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
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
     * Show a SweetAlert notification
     */
    show(type, message, options = {}) {
        console.log(`Showing SweetAlert: ${type} - ${message}`);
        const config = this.getConfig(type, message, options);
        
        if (config.toast) {
            // For toast-style notifications
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })(config);
        } else {
            // For modal-style notifications
            Swal.fire(config);
        }
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
            reverseButtons: true
        };

        return Swal.fire({ ...defaultConfirmOptions, ...options });
    }

    /**
     * Show toast notification (small, top-right)
     */
    toast(type, message, options = {}) {
        const toastConfig = {
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            ...this.getConfig(type, message, options)
        };

        Swal.fire(toastConfig);
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
        window.showToast = (type, message) => this.toast(type, message);
        window.showNotification = (type, message) => this.show(type, message);
        
        // Add universal AJAX response handler
        window.handleToastResponse = (response, fallbackType = 'success') => {
            if (!response) {
                return;
            }

            if (typeof response === 'string') {
                this.toast(fallbackType, response);
                return;
            }

            const message = response.message || response.error || response.statusText;
            if (!message) {
                return;
            }

            const type = (response.success === false || response.error) ? 'error' : (response.toastType || fallbackType);
            this.toast(type, message);
        };
        
        // Override jQuery AJAX success/error handlers if they exist
        if (window.$) {
            $(document).ajaxSuccess((event, xhr, settings) => {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    this.success(xhr.responseJSON.message);
                }
            });

            $(document).ajaxError((event, xhr, settings) => {
                // Only show SweetAlert for AJAX requests that specifically want it
                // Check if the request has a custom header or data property indicating SweetAlert handling
                if (settings.data && typeof settings.data === 'string' && settings.data.includes('use_sweetalert=1')) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        this.error(xhr.responseJSON.message);
                    } else if (xhr.status !== 0) {
                        this.error('An error occurred while processing your request.');
                    }
                }
                // Also handle requests with custom header
                if (settings.headers && settings.headers['X-SweetAlert'] === 'true') {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        this.error(xhr.responseJSON.message);
                    } else if (xhr.status !== 0) {
                        this.error('An error occurred while processing your request.');
                    }
                }
            });
        }
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
     * Show success with redirect
     */
    successWithRedirect(message, redirectUrl, delay = 1500) {
        this.success(message, {
            timer: delay,
            showConfirmButton: false,
            didClose: () => {
                window.location.href = redirectUrl;
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
// This catches ALL AJAX calls (XMLHttpRequest, fetch, jQuery.ajax) at the browser level
(function() {
    // Store original methods
    const originalXMLHttpRequestOpen = XMLHttpRequest.prototype.open;
    const originalXMLHttpRequestSend = XMLHttpRequest.prototype.send;
    const originalFetch = window.fetch;

    // Track XMLHttpRequest instances
    const xhrInstances = new WeakMap();

    // Override XMLHttpRequest.open to capture method and URL
    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        xhrInstances.set(this, { method: method.toUpperCase(), url: url });
        return originalXMLHttpRequestOpen.apply(this, [method, url, ...args]);
    };

    // Override XMLHttpRequest.send to intercept responses
    XMLHttpRequest.prototype.send = function(body) {
        const xhr = this;
        const requestInfo = xhrInstances.get(xhr) || {};

        // Override onreadystatechange to catch responses
        const originalOnReadyStateChange = this.onreadystatechange;
        this.onreadystatechange = function(e) {
            if (xhr.readyState === 4 && xhr.status !== 0) {
                // Only process JSON responses or responses with message content
                let responseData = null;
                let shouldShowAlert = false;

                try {
                    // Try to parse as JSON
                    if (xhr.responseText && xhr.getResponseHeader('content-type')?.includes('application/json')) {
                        responseData = JSON.parse(xhr.responseText);
                        // Check if response has message, error, or success fields
                        if (responseData.message || responseData.error || responseData.success !== undefined) {
                            shouldShowAlert = true;
                        }
                    }
                    // Also check for plain text responses that might be error messages
                    else if (xhr.responseText && xhr.status >= 400) {
                        responseData = { message: xhr.responseText, error: true };
                        shouldShowAlert = true;
                    }
                } catch (parseError) {
                    // If JSON parsing fails but we have a non-200 status, show the status text
                    if (xhr.status >= 400) {
                        responseData = { message: xhr.statusText || 'Request failed', error: true };
                        shouldShowAlert = true;
                    }
                }

                if (shouldShowAlert && responseData && window.handleToastResponse) {
                    // Use setTimeout to ensure this runs after the current call stack
                    setTimeout(function() {
                        window.handleToastResponse(responseData);
                    }, 0);
                }
            }

            // Call original handler
            if (originalOnReadyStateChange) {
                originalOnReadyStateChange.apply(this, arguments);
            }
        };

        return originalXMLHttpRequestSend.apply(this, arguments);
    };

    // Override fetch for modern browsers
    if (originalFetch) {
        window.fetch = function(input, init) {
            const url = typeof input === 'string' ? input : input.url;
            const method = (init?.method || 'GET').toUpperCase();

            return originalFetch.apply(this, arguments).then(function(response) {
                // Clone the response so we can read it without consuming it
                const responseClone = response.clone();

                // Only process JSON responses
                if (response.headers.get('content-type')?.includes('application/json')) {
                    return responseClone.json().then(function(data) {
                        // Check if response has toast-relevant fields
                        if ((data.message || data.error || data.success !== undefined) && window.handleToastResponse) {
                            setTimeout(function() {
                                window.handleToastResponse(data);
                            }, 0);
                        }
                        // Return original response for chaining
                        return response;
                    }).catch(function() {
                        // JSON parsing failed, return original response
                        return response;
                    });
                }

                return response;
            });
        };
    }
})();

// Keep jQuery handlers as fallback for jQuery-specific features
if (window.jQuery) {
    const $document = jQuery(document);

    // Only handle jQuery events that bypass our universal interceptor
    $document.on('ajaxSuccess', function(event, xhr) {
        // This will be redundant with our universal interceptor, but kept as fallback
        if (!xhr || !xhr.responseJSON) {
            return;
        }

        const data = xhr.responseJSON;
        if (!data.message && !data.error && data.success === undefined) {
            return;
        }

        if (window.handleToastResponse) {
            window.handleToastResponse(data);
        }
    });

    $document.on('ajaxError', function(event, jqXHR) {
        if (!jqXHR || jqXHR.status === 0) {
            return;
        }

        const payload = jqXHR.responseJSON || {
            message: jqXHR.statusText || 'An unexpected error occurred.'
        };

        if (window.handleToastResponse) {
            window.handleToastResponse(payload, 'error');
        }
    });
}

// Export for use in other scripts
window.SweetAlertSystem = SweetAlertSystem;
