// Equipment Show Page JavaScript
$(document).ready(function() {
    // Show/hide fields based on status selection
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            const status = this.value;
            const assignedToField = document.getElementById('assignedToField');
            const maintenanceDateField = document.getElementById('maintenanceDateField');

            if (status === 'in_use') {
                if (assignedToField) assignedToField.style.display = 'block';
                if (maintenanceDateField) maintenanceDateField.style.display = 'none';
            } else if (status === 'maintenance') {
                if (assignedToField) assignedToField.style.display = 'none';
                if (maintenanceDateField) maintenanceDateField.style.display = 'block';
            } else {
                if (assignedToField) assignedToField.style.display = 'none';
                if (maintenanceDateField) maintenanceDateField.style.display = 'none';
            }
        });
    }

    // Equipment status update functionality
    window.updateEquipmentStatus = function() {
        // admin-specific status update modal or functionality
        showToast('info', 'Status update feature coming soon for admins!');
    };

    // Toast functionality for equipment show page (type, message)
    function showToast(type = 'info', message) {
        const toastContainer = document.querySelector('.toast-container') || createToastContainer();

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class='bx ${type === 'success' ? 'bx-check-circle' : type === 'error' ? 'bx-error' : 'bx-info-circle'} me-2'></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 3000
        });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // Make showToast available globally for this page
    window.showToast = showToast;
});
