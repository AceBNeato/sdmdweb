/**
 * AJAX Helper for SweetAlert Integration
 * Provides helper functions for common AJAX operations with SweetAlert feedback
 */

window.AjaxHelper = {
    /**
     * Perform AJAX request with SweetAlert feedback
     */
    request: function(options) {
        const defaultOptions = {
            method: 'POST',
            showLoading: true,
            loadingMessage: 'Processing...',
            successMessage: 'Operation completed successfully!',
            errorMessage: 'An error occurred. Please try again.',
            showSuccessAlert: true,
            showErrorAlert: true,
            redirectOnSuccess: false,
            reloadOnSuccess: false
        };

        const config = { ...defaultOptions, ...options };

        // Show loading if requested
        let loadingAlert = null;
        if (config.showLoading && window.Swal) {
            loadingAlert = Swal.fire({
                title: config.loadingMessage,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        // Perform the AJAX request
        return $.ajax({
            url: config.url,
            method: config.method,
            data: config.data,
            processData: config.processData !== false,
            contentType: config.contentType !== false ? 'application/x-www-form-urlencoded; charset=UTF-8' : false,
            headers: {
                'X-SweetAlert': 'true',
                ...(config.headers || {})
            },
            success: (response) => {
                // Close loading
                if (loadingAlert) {
                    Swal.close();
                }

                // Show success message
                if (config.showSuccessAlert && window.Swal && window.SweetAlert) {
                    if (config.redirectOnSuccess && response.redirect) {
                        window.SweetAlert.successWithRedirect(config.successMessage, response.redirect);
                    } else if (config.reloadOnSuccess) {
                        window.SweetAlert.success(config.successMessage, {
                            timer: 1500,
                            showConfirmButton: false,
                            didClose: () => {
                                window.location.reload();
                            }
                        });
                    } else {
                        window.SweetAlert.success(config.successMessage);
                    }
                }

                // Call custom success handler
                if (config.onSuccess) {
                    config.onSuccess(response);
                }
            },
            error: (xhr) => {
                // Close loading
                if (loadingAlert) {
                    Swal.close();
                }

                // Handle different error types
                let errorMessage = config.errorMessage;
                
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join('\n');
                    
                    if (window.Swal && window.SweetAlert) {
                        window.SweetAlert.error('Validation Error', {
                            text: errorMessage,
                            confirmButtonColor: '#ef4444'
                        });
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    // Server error message
                    errorMessage = xhr.responseJSON.message;
                    
                    if (window.Swal && window.SweetAlert) {
                        window.SweetAlert.error(errorMessage);
                    }
                } else {
                    // Generic error
                    if (window.Swal && window.SweetAlert) {
                        window.SweetAlert.error(errorMessage);
                    }
                }

                // Call custom error handler
                if (config.onError) {
                    config.onError(xhr);
                }
            },
            complete: () => {
                // Call complete handler
                if (config.onComplete) {
                    config.onComplete();
                }
            }
        });
    },

    /**
     * Submit form with SweetAlert feedback
     */
    submitForm: function(form, options = {}) {
        const $form = $(form);
        const formData = new FormData(form);

        return this.request({
            url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: formData,
            processData: false,
            contentType: false,
            ...options
        });
    },

    /**
     * Delete item with confirmation
     */
    delete: function(url, options = {}) {
        const defaultOptions = {
            confirmMessage: 'Are you sure you want to delete this item?',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            loadingMessage: 'Deleting...',
            successMessage: 'Item deleted successfully!',
            errorMessage: 'Failed to delete item. Please try again.',
            reloadOnSuccess: true
        };

        const config = { ...defaultOptions, ...options };

        // Show confirmation dialog
        if (window.Swal && window.SweetAlert) {
            return window.SweetAlert.confirm(config.confirmMessage, {
                confirmButtonText: config.confirmButtonText,
                cancelButtonText: config.cancelButtonText,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    return this.request({
                        url: url,
                        method: 'DELETE',
                        loadingMessage: config.loadingMessage,
                        successMessage: config.successMessage,
                        errorMessage: config.errorMessage,
                        reloadOnSuccess: config.reloadOnSuccess,
                        ...config
                    });
                }
            });
        } else {
            // Fallback to browser confirm
            if (confirm(config.confirmMessage)) {
                return this.request({
                    url: url,
                    method: 'DELETE',
                    ...config
                });
            }
        }
    },

    /**
     * Update status with SweetAlert feedback
     */
    updateStatus: function(url, data, options = {}) {
        const defaultOptions = {
            loadingMessage: 'Updating status...',
            successMessage: 'Status updated successfully!',
            errorMessage: 'Failed to update status. Please try again.',
            reloadOnSuccess: false
        };

        return this.request({
            url: url,
            method: 'POST',
            data: data,
            ...defaultOptions,
            ...options
        });
    },

    /**
     * Load content into modal with SweetAlert feedback
     */
    loadModal: function(url, modalSelector, contentSelector, options = {}) {
        const defaultOptions = {
            loadingMessage: 'Loading...',
            errorMessage: 'Failed to load content. Please try again.'
        };

        const config = { ...defaultOptions, ...options };

        // Show loading in modal
        $(contentSelector).html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
        $(modalSelector).modal('show');

        return this.request({
            url: url,
            method: 'GET',
            showLoading: false, // We're showing our own loading
            successMessage: false, // Don't show success for loading
            errorMessage: config.errorMessage,
            onError: (xhr) => {
                $(contentSelector).html('<div class="alert alert-danger m-3">' + config.errorMessage + '</div>');
            },
            onSuccess: (response) => {
                $(contentSelector).html(response);
            },
            ...config
        });
    }
};

// Auto-initialize common AJAX patterns
$(document).ready(function() {
    // Auto-handle delete links with class .ajax-delete
    $('.ajax-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        if (url) {
            window.AjaxHelper.delete(url);
        }
    });

    // Auto-handle status updates with class .ajax-status
    $('.ajax-status').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href') || $(this).data('url');
        const data = $(this).data('params') || {};
        if (url) {
            window.AjaxHelper.updateStatus(url, data);
        }
    });

    // Auto-handle form submissions with class .ajax-form
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        window.AjaxHelper.submitForm(this);
    });
});
