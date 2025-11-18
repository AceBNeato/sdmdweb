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
    .then(response => response.json())
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
                    <a href="${window.qrScannerRoutes.view}?view_equipment=${data.equipment.id}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-show me-1"></i>View Details
                        </a>
                        ${!window.qrScannerRoutes.isStaff ? `<a href="${window.qrScannerRoutes.view}?history_equipment=${data.equipment.id}" class="btn btn-outline-secondary btn-sm">
                            <i class="bx bx-plus me-1"></i>Add History Sheet
                        </a>` : ''}
                        <button onclick="resetScanner()" class="scan-another-btn">
                            <i class="bx bx-qr-scan me-1"></i>Scan Another
                        </button>
                    </div>
                </div>
            `;
        } else {
            // Show error
            document.getElementById('my-qr-reader').innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Scan Failed</h5>
                    <p>${data.message || 'Unable to locate equipment for this QR code.'}</p>
                    <button onclick="resetScanner()" class="btn btn-primary btn-sm qr-btn">Try Again</button>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error processing QR code:', error);
        document.getElementById('my-qr-reader').innerHTML = `
            <div class="alert alert-danger">
                <h5 class="alert-heading">Error</h5>
                <p>An error occurred while processing the QR code.</p>
                <button onclick="resetScanner()" class="btn btn-primary btn-sm qr-btn">Try Again</button>
            </div>
        `;
    });
}

// Function to reset scanner for another scan
function resetScanner() {
    location.reload(); // Simple reload to restart scanner
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
