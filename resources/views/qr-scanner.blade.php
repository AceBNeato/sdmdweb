@extends('layouts.app')


@section('title', 'QR Scanner')

@section('page_title', 'QR Scanner')
@section('page_description', 'Scan QR codes to access equipment details')


@push('styles')
<style>
    /* Adjusted styles for integration */
    body {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        text-align: center;
    }   

    .qr-container {
        display: flex;
        justify-content: center; /* Align to the right */
        align-items: center;
        padding: 20px;
        background: linear-gradient(to bottom, #7790c2ff, #333348ff);
    }

    .qr-container h1 {
        color: #ffffff;
        position: absolute;
        left: 50%;
        top: 20%;
        transform: translate(-50%, -50%);
        z-index: 1;
    }

    .section {
        background-color: #ffffff;
        padding: 50px 30px;
        border: 1.5px solid #b2b2b2;
        border-radius: 0.25em;
        box-shadow: 0 20px 25px rgba(0, 0, 0, 0.25);
        max-width: 400px; /* Limit width for better right alignment */
    }

    #my-qr-reader {
        padding: 20px !important;
        border: 1.5px solid #b2b2b2 !important;
        border-radius: 8px;
    }

    #my-qr-reader img[alt="Info icon"] {
        display: none;
    }

    #my-qr-reader img[alt="Camera based scan"] {
        width: 100px !important;
        height: 100px !important;
    }

    button {
        padding: 10px 20px;
        border: 1px solid #b2b2b2;
        outline: none;
        border-radius: 0.25em;
        color: white;
        font-size: 15px;
        cursor: pointer;
        margin-top: 15px;
        margin-bottom: 10px;
        background-color: #008000ad;
        transition: 0.3s background-color;
    }

    button:hover {
        background-color: #008000;
    }

    #html5-qrcode-anchor-scan-type-change {
        text-decoration: none !important;
        color: #1d9bf0;
    }

    video {
        width: 100% !important;
        border: 1px solid #b2b2b2 !important;
        border-radius: 0.25em;
    }
</style>
@endpush

@section('content')
    <div class="qr-container">
        <div class="section">
            <div id="my-qr-reader">
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
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

            let htmlscanner = new Html5QrcodeScanner(
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

            fetch('{{ route("technician.equipment.scan") }}', {
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
                            <p class="mb-0"><strong>Office:</strong> ${data.equipment.office ? data.equipment.office.name : 'N/A'}</p>
                            <hr>
                            <a href="{{ url('technician/equipment') }}/${data.equipment.id}" class="btn btn-primary btn-sm">View Full Details</a>
                            <button onclick="resetScanner()" class="btn btn-secondary btn-sm ms-2">Scan Another</button>
                        </div>
                    `;
                } else {
                    // Show error
                    document.getElementById('my-qr-reader').innerHTML = `
                        <div class="alert alert-danger">
                            <h5 class="alert-heading">Scan Failed</h5>
                            <p>${data.message || 'Unable to locate equipment for this QR code.'}</p>
                            <button onclick="resetScanner()" class="btn btn-primary btn-sm">Try Again</button>
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
                        <button onclick="resetScanner()" class="btn btn-primary btn-sm">Try Again</button>
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
    </script>
@endsection