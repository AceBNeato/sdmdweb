// Session Settings Form Handler
// Handles AJAX submission of session settings with SweetAlert notifications

document.addEventListener('DOMContentLoaded', function() {
    // Look specifically for the session form by checking for the session lockout input
    const sessionSettingsForm = document.querySelector('input[name="session_lockout_minutes"]')?.closest('form');
    
    if (sessionSettingsForm) {
        // Remove any existing event listeners to prevent duplicates
        const newForm = sessionSettingsForm.cloneNode(true);
        sessionSettingsForm.parentNode.replaceChild(newForm, sessionSettingsForm);
        
        newForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Settings Updated',
                        text: 'Session lockout duration has been saved successfully.',
                        icon: 'success',
                        confirmButtonColor: '#3b82f6',
                        confirmButtonText: 'Got it'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        html: `
                            <div class="text-left">
                                <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Failed to save session settings.</p>
                                <p class="text-sm text-gray-600">${data.message || 'An unknown error occurred.'}</p>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Network Error!',
                    html: `
                        <div class="text-left">
                            <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>A network error occurred while saving settings.</p>
                            <p class="text-sm text-gray-600">Error: ${error.message}</p>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                });
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
