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
        // Override any existing toast system calls
        window.showToast = (type, message) => this.toast(type, message);
        window.showNotification = (type, message) => this.show(type, message);
        
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

// Export for use in other scripts
window.SweetAlertSystem = SweetAlertSystem;
