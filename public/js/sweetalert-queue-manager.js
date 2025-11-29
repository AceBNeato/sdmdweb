/**
 * SweetAlert Queue Manager - Fixed Version
 * Prevents overlapping modals and manages notification queue
 */

class SweetAlertQueueManager {
    constructor() {
        this.queue = [];
        this.isProcessing = false;
        this.currentModal = null;
        this.sessionLockActive = false;
        
        // Store original Swal.fire BEFORE overriding
        this.originalSwalFire = Swal.fire;
        
        this.init();
    }

    init() {
        // Override Swal.fire to use queue
        this.overrideSwalFire();
        
        // Listen for session lock events
        this.setupSessionLockHandling();
        
        // Clean up on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }

    overrideSwalFire() {
        // Store reference to original method
        const originalFire = this.originalSwalFire;
        const self = this;
        
        Swal.fire = async function(...args) {
            // If session lock is active, queue the alert
            if (self.sessionLockActive) {
                return new Promise((resolve) => {
                    self.queue.push({
                        type: 'swal',
                        args: args,
                        resolve: resolve
                    });
                });
            }

            // If another modal is active, queue this one
            if (self.isProcessing && Swal.isVisible()) {
                return new Promise((resolve) => {
                    self.queue.push({
                        type: 'swal',
                        args: args,
                        resolve: resolve
                    });
                });
            }

            // Process immediately using ORIGINAL method
            return self.processSwal(args);
        };
    }

    async processSwal(args) {
        this.isProcessing = true;
        
        try {
            // Use ORIGINAL Swal.fire, not the overridden one
            const result = await this.originalSwalFire.apply(Swal, args);
            return result;
        } finally {
            this.isProcessing = false;
            
            // Process next item in queue
            this.processQueue();
        }
    }

    setupSessionLockHandling() {
        // Listen for session lock events
        document.addEventListener('sessionLockShown', () => {
            this.sessionLockActive = true;
            this.closeCurrentModal();
        });

        document.addEventListener('sessionLockHidden', () => {
            this.sessionLockActive = false;
            this.processQueue();
        });
    }

    closeCurrentModal() {
        if (Swal.isVisible()) {
            Swal.close({
                reason: 'session-lock'
            });
        }
    }

    processQueue() {
        if (this.queue.length === 0 || this.isProcessing || this.sessionLockActive) {
            return;
        }

        const item = this.queue.shift();
        
        if (item.type === 'swal') {
            this.processSwal(item.args).then(item.resolve);
        }
    }

    // Safe toast that doesn't conflict with modals
    safeToast(type, message, options = {}) {
        if (this.sessionLockActive || this.isProcessing) {
            // Queue toast for later
            setTimeout(() => this.safeToast(type, message, options), 500);
            return;
        }

        const toastConfig = {
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: type,
            title: message,
            ...options
        };

        // Use ORIGINAL method
        this.originalSwalFire.apply(Swal, [toastConfig]);
    }

    // Safe loading that can be cancelled
    safeLoading(message, options = {}) {
        if (this.currentLoading) {
            this.currentLoading.close();
        }

        this.currentLoading = this.originalSwalFire.apply(Swal, [{
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
            ...options
        }]);

        return this.currentLoading;
    }

    closeLoading() {
        if (this.currentLoading) {
            this.currentLoading.close();
            this.currentLoading = null;
        }
    }

    cleanup() {
        this.closeCurrentModal();
        this.closeLoading();
        this.queue = [];
        this.isProcessing = false;
    }

    // Get queue status for debugging
    getQueueStatus() {
        return {
            queueLength: this.queue.length,
            isProcessing: this.isProcessing,
            sessionLockActive: this.sessionLockActive,
            hasVisibleModal: Swal.isVisible()
        };
    }
}

// Initialize queue manager
window.SweetAlertQueue = new SweetAlertQueueManager();

// Export for global access
window.safeToast = (type, message, options) => window.SweetAlertQueue.safeToast(type, message, options);
window.safeLoading = (message, options) => window.SweetAlertQueue.safeLoading(message, options);
window.closeLoading = () => window.SweetAlertQueue.closeLoading();
