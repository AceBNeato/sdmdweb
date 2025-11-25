// QR Scanner functionality
let htmlscanner;

function domReady(fn) {
    if (
        document.readyState === "complete" ||
        document.readyState === "interactive"
    ) {
        setTimeout(fn, 1000);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}

domReady(function () {
    const hasMediaDevices = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    const isSecure = window.isSecureContext || window.location.protocol === "https:";
    const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";

    const container = document.getElementById("my-qr-reader");

    if (!container) {
        return;
    }

    if (typeof Html5QrcodeScanner === "undefined" || !hasMediaDevices || (!isSecure && !isLocalhost)) {
        const recommendedUrl = isSecure ? window.location.origin : ("https://" + window.location.host);
        container.innerHTML = '<div class="alert alert-warning">'
            + '<h5 class="alert-heading">Camera scanner not available on this device</h5>'
            + '<p class="mb-1">This browser cannot access the camera here (it requires HTTPS and a supported browser).</p>'
            + '<p class="mb-0">You can still scan using your phone\'s camera or any QR app, then open ' + recommendedUrl + ' in your browser to view the equipment.</p>'
            + '</div>';
        return;
    }

    // If found your qr code
    function onScanSuccess(decodeText, decodeResult) {
        // Stop the scanner after successful scan
        htmlscanner.clear();

        // Process the scanned QR data via backend
        processScannedQrCode(decodeText);
    }

    htmlscanner = new Html5QrcodeScanner(
        "my-qr-reader",
        { fps: 10, qrbox: 250 }
    );
    htmlscanner.render(onScanSuccess);
});

// Function to process scanned QR code with backend
function processScannedQrCode(qrData) {
    // Show loading state
    document.getElementById('my-qr-reader').innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <p>Processing QR code...</p>
        </div>
    `;

    fetch(window.qrScannerRoutes.scan, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ qr_data: qrData })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Display equipment details
            document.getElementById('my-qr-reader').innerHTML = `
                <div class="alert alert-success">
                    <h5 class="alert-heading">Equipment Found!</h5>
                    <hr>
                    <p class="mb-2"><strong>Model:</strong> ${data.equipment.model_number || 'N/A'}</p>
                    <p class="mb-2"><strong>Serial:</strong> ${data.equipment.serial_number || 'N/A'}</p>
                    <p class="mb-2"><strong>Status:</strong> <span class="badge bg-${getStatusColor(data.equipment.status)}">${data.equipment.status || 'Unknown'}</span></p>
                    <p class="mb-0"><strong>Office:</strong> ${data.equipment.office || 'N/A'}</p>
                    <hr>
                    <div class="qr-actions">
                    <button onclick="viewEquipmentDetails(${data.equipment.id})" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-show me-1"></i>View Details
                        </button>
                        ${!window.qrScannerRoutes.isStaff ? `<button onclick="addHistorySheet(${data.equipment.id})" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-plus me-1"></i>Add History Sheet
                        </button>` : ''}
                        <button onclick="resetScanner()" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-qr-scan me-1"></i>Scan Another
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Show error with SweetAlert
            Swal.fire({
                icon: 'error',
                title: 'Scan Failed',
                text: data.message || 'Unable to locate equipment for this QR code.',
                confirmButtonText: 'Try Again',
                confirmButtonColor: '#3085d6'
                }).then(() => {
                    resetScanner();
                });
        }
    })
    .catch(error => {
        console.error('Error processing QR code:', error);
        console.error('Error message:', error.message);
        console.error('Scan route:', window.qrScannerRoutes.scan);
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: 'An error occurred while processing the QR code.<br><small>Debug: ' + error.message + '</small><br><small>Route: ' + window.qrScannerRoutes.scan + '</small>',
            confirmButtonText: 'Try Again',
            confirmButtonColor: '#3085d6'
        }).then(() => {
            resetScanner();
        });
    });
}

// Function to reset scanner for another scan
function resetScanner() {
    location.reload(); // Simple reload to restart scanner
}

// Function to view equipment details directly
function viewEquipmentDetails(equipmentId) {
    // Determine the correct route prefix based on current user
    let prefix = 'admin';
    if (window.qrScannerRoutes.isStaff) {
        prefix = 'staff';
    } else if (window.location.pathname.includes('/technician/')) {
        prefix = 'technician';
    }

    // Create modal if it doesn't exist
    if (!$('#viewEquipmentModal').length) {
        $('body').append(`
            <div class="modal fade" id="viewEquipmentModal" tabindex="-1" aria-labelledby="viewEquipmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewEquipmentModalLabel">Equipment Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="viewEquipmentContent">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    const modal = $('#viewEquipmentModal');
    const content = $('#viewEquipmentContent');

    // Show loading spinner
    content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    // Load content via AJAX
    $.ajax({
        url: `/${prefix}/equipment/${equipmentId}`,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            content.html(response);
            
            // Initialize Bootstrap modal
            const bootstrapModal = new bootstrap.Modal(document.getElementById('viewEquipmentModal'));
            bootstrapModal.show();
            
            // Reinitialize edit button handlers after content is loaded
            initializeEditHandlers();
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr.status, xhr.responseText, error);
            
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load',
                text: 'Failed to load equipment details. Error: ' + xhr.status + ' - ' + error,
                confirmButtonColor: '#3085d6'
            });
        }
    });
}

// Initialize edit button handlers
function initializeEditHandlers() {
    // Handle EDIT button clicks within the show modal
    $('.edit-equipment-btn').off('click').on('click', function() {
        var equipmentId = $(this).data('equipment-id');
        var url = $(this).data('url');
        
        // Create edit modal if it doesn't exist
        if (!$('#editEquipmentModal').length) {
            $('body').append(`
                <div class="modal fade" id="editEquipmentModal" tabindex="-1" aria-labelledby="editEquipmentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editEquipmentModalLabel">Edit Equipment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="editEquipmentContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        // Load edit form using AjaxHelper if available, otherwise fallback to plain AJAX
        if (window.AjaxHelper) {
            // Close the view modal first
            $('#viewEquipmentModal').modal('hide');
            
            // Add a small delay to ensure modal closes properly
            setTimeout(function() {
                window.AjaxHelper.loadModal(url, '#editEquipmentModal', '#editEquipmentContent', {
                    errorMessage: 'Failed to load equipment form. Please try again.',
                    showSuccessAlert: false,
                    showErrorAlert: false
                });
            }, 300);
        } else {
            // Fallback AJAX load
            $('#viewEquipmentModal').modal('hide');
            
            setTimeout(function() {
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function(response) {
                        $('#editEquipmentContent').html(response);
                        const editModal = new bootstrap.Modal(document.getElementById('editEquipmentModal'));
                        editModal.show();
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', xhr.status, xhr.responseText, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Load',
                            text: 'Failed to load equipment form. Error: ' + xhr.status + ' - ' + error,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }, 300);
        }
    });
}

// Function to add history sheet directly
function addHistorySheet(equipmentId) {
    // Determine the correct route prefix based on current user
    let prefix = 'admin';
    if (window.qrScannerRoutes.isStaff) {
        prefix = 'staff';
    } else if (window.location.pathname.includes('/technician/')) {
        prefix = 'technician';
    }

    // Create modal if it doesn't exist
    if (!$('#historyEquipmentModal').length) {
        $('body').append(`
            <div class="modal fade" id="historyEquipmentModal" tabindex="-1" aria-labelledby="historyEquipmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="historyEquipmentModalLabel">Add History Sheet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="historyEquipmentContent">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    const modal = $('#historyEquipmentModal');
    const content = $('#historyEquipmentContent');

    // Show loading spinner
    content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

    // Load content via AJAX
    $.ajax({
        url: `/${prefix}/equipment/${equipmentId}/history/create`,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            content.html(response);
            
            // Initialize Bootstrap modal
            const bootstrapModal = new bootstrap.Modal(document.getElementById('historyEquipmentModal'));
            bootstrapModal.show();
        },
        error: function(xhr, status, error) {
            console.log('AJAX Error:', xhr.status, xhr.responseText, error);
            
            Swal.fire({
                icon: 'error',
                title: 'Failed to Load',
                text: 'Failed to load history form. Error: ' + xhr.status + ' - ' + error,
                confirmButtonColor: '#3085d6'
            });
        }
    });
}

// Helper function for status colors
function getStatusColor(status) {
    const colors = {
        available: 'success',
        serviceable: 'success',
        in_use: 'primary',
        maintenance: 'warning',
        for_repair: 'warning',
        defective: 'danger',
        disposed: 'danger'
    };
    return colors[status] || 'secondary';
}
