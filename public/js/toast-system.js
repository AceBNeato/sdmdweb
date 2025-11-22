// Toast notification system - Now using SweetAlert2
function showToast(message, type = 'success') {
    // Use SweetAlert if available, fallback to original toast if not
    if (window.Swal && window.SweetAlert) {
        window.SweetAlert.toast(type, message);
        return;
    }
    
    // Fallback to original toast system
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        // Create toast container if it doesn't exist (fallback for technician pages)
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bx bx-${type === 'success' ? 'check-circle' : type === 'error' ? 'error-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="bx bx-x"></i>
        </button>
    `;

    toastContainer.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 5000);

    // Animate in
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
}

window.showToast = showToast;

window.handleToastResponse = function(response, fallbackType = 'success') {
    if (!response) {
        return;
    }

    if (typeof response === 'string') {
        showToast(response, fallbackType);
        return;
    }

    const message = response.message || response.error || response.statusText;
    if (!message) {
        return;
    }

    const type = (response.success === false || response.error) ? 'error' : (response.toastType || fallbackType);
    showToast(message, type);
};

// Universal AJAX Response Interceptor for Toast Notifications
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
                let shouldShowToast = false;

                try {
                    // Try to parse as JSON
                    if (xhr.responseText && xhr.getResponseHeader('content-type')?.includes('application/json')) {
                        responseData = JSON.parse(xhr.responseText);
                        // Check if response has message, error, or success fields
                        if (responseData.message || responseData.error || responseData.success !== undefined) {
                            shouldShowToast = true;
                        }
                    }
                    // Also check for plain text responses that might be error messages
                    else if (xhr.responseText && xhr.status >= 400) {
                        responseData = { message: xhr.responseText, error: true };
                        shouldShowToast = true;
                    }
                } catch (parseError) {
                    // If JSON parsing fails but we have a non-200 status, show the status text
                    if (xhr.status >= 400) {
                        responseData = { message: xhr.statusText || 'Request failed', error: true };
                        shouldShowToast = true;
                    }
                }

                if (shouldShowToast && responseData) {
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
                        if (data.message || data.error || data.success !== undefined) {
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

        window.handleToastResponse(data);
    });

    $document.on('ajaxError', function(event, jqXHR) {
        if (!jqXHR || jqXHR.status === 0) {
            return;
        }

        const payload = jqXHR.responseJSON || {
            message: jqXHR.statusText || 'An unexpected error occurred.'
        };

        window.handleToastResponse(payload, 'error');
    });
}

// Show toasts from session messages
document.addEventListener('DOMContentLoaded', function() {
    if (window.sessionMessages) {
        if (window.sessionMessages.success) {
            showToast(window.sessionMessages.success, 'success');
        }
        if (window.sessionMessages.error) {
            showToast(window.sessionMessages.error, 'error');
        }
        if (window.sessionMessages.info) {
            showToast(window.sessionMessages.info, 'info');
        }
    }
});
