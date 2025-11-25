<!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-labelledby="qrScannerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="qrScannerModalLabel">
                    <i class='bx bx-qr-scan me-2'></i>Equipment Scanner
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="scanner-container">
                    <!-- Sidebar -->
                    <div class="scanner-sidebar">
                        <div class="p-4">
                            <div class="text-center mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex p-3 mb-3">
                                    <i class='bx bx-qr-scan text-primary' style="font-size:2.5rem;"></i>
                                </div>
                                <h4 class="mb-2 fw-semibold">Equipment Scanner</h4>
                                <p class="text-muted mb-4">Quickly scan and access equipment details</p>

                                <button id="startScannerBtn" class="btn btn-primary btn-lg w-100 mb-4 py-3 d-flex align-items-center justify-content-center">
                                    <i class='bx bx-camera me-2' style="font-size:1.5rem;"></i>
                                    <span>Start Scanner</span>
                                </button>

                                <div class="alert alert-info text-start small">
                                    <i class='bx bx-info-circle me-2'></i>
                                    Point your camera at a QR code to scan equipment details.
                                </div>
                            </div>

                            <!-- Scanner History -->
                            <div class="scanner-history">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 fw-semibold">
                                        <i class="bx bx-history text-primary me-2"></i> Recent Scans
                                    </h6>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0">
                                        <small>Clear All</small>
                                    </button>
                                </div>
                                <ul id="historyList" class="list-unstyled mb-0">
                                    <!-- History items will be populated by JavaScript -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Main Scanner Area -->
                    <div class="scanner-main">
                        <div id="scannerWrapper" class="text-center" style="display:none;">
                            <h5 class="mb-4 fw-semibold">Position QR code within frame</h5>
                            <div class="position-relative mx-auto" style="max-width: 500px;">
                                <div id="reader" class="rounded-3 overflow-hidden"></div>
                                <div class="scanner-frame"></div>
                                <div class="scanner-laser"></div>
                                <div class="scanner-overlay"></div>
                            </div>
                            <div class="mt-4">
                                <button id="stopScannerBtn" class="btn btn-outline-danger px-4 py-2">
                                    <i class='bx bx-stop-circle me-1'></i> Stop Scanner
                                </button>
                            </div>
                        </div>

                        <div id="scan-result" class="h-100 d-flex align-items-center justify-content-center">
                            <div class="text-center p-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-4 d-inline-flex mb-4" style="width: 140px; height: 140px;">
                                    <i class='bx bx-qr-scan text-primary' style="font-size:4rem;"></i>
                                </div>
                                <h4 class="mb-3 fw-semibold">Ready to Scan</h4>
                                <p class="text-muted mb-4">Click the button below to activate your camera and start scanning</p>
                                <button id="startScannerBtn2" class="btn btn-primary px-4 py-2">
                                    <i class='bx bx-camera me-1'></i> Activate Scanner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .scanner-container {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 1.5rem;
        min-height: 70vh;
    }

    @media (max-width: 992px) {
        .scanner-container {
            grid-template-columns: 1fr;
        }
    }

    .scanner-sidebar {
        background: #f8f9fc;
        border-radius: 12px;
        height: 100%;
        overflow-y: auto;
    }

    .scanner-main {
        background: #fff;
        border-radius: 12px;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .scanner-frame {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 3px solid rgba(13, 110, 253, 0.5);
        border-radius: 12px;
        pointer-events: none;
    }

    .scanner-laser {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        height: 2px;
        background: rgba(255, 0, 0, 0.5);
        animation: scan 2s infinite;
    }

    @keyframes scan {
        0% { top: 0; }
        50% { top: 100%; }
        100% { top: 0; }
    }

    .history-item {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
    }

    .history-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }

    /* Responsive adjustments for modal */
    @media (max-width: 1200px) {
        .scanner-container {
            grid-template-columns: 1fr;
        }
        
        .scanner-sidebar {
            max-height: 300px;
            overflow-y: auto;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Load html5-qrcode with CDN fallback and error detection
    (function() {
        var script = document.createElement('script');
        script.src = 'https://unpkg.com/html5-qrcode';
        script.onerror = function() {
            console.warn('Failed to load html5-qrcode from CDN, falling back to local copy');
            var fallback = document.createElement('script');
            fallback.src = '{{ asset('js/vendor/html5-qrcode.min.js') }}';
            document.head.appendChild(fallback);
        };
        document.head.appendChild(script);
    })();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('qrScannerModal');
        const startBtns = document.querySelectorAll('#startScannerBtn, #startScannerBtn2');
        const stopBtn = document.getElementById('stopScannerBtn');
        const scannerWrapper = document.getElementById('scannerWrapper');
        const scanResult = document.getElementById('scan-result');
        let html5QrCode = null;

        const hasMediaDevices = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        const isSecure = window.isSecureContext || window.location.protocol === 'https:';
        const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        const scannerSupported = typeof Html5Qrcode !== 'undefined' && hasMediaDevices && (isSecure || isLocalhost);

        // Only initialize when the modal is shown
        modal.addEventListener('show.bs.modal', function() {
            // Reset UI state
            scannerWrapper.style.display = 'none';
            scanResult.style.display = 'flex';

            if (!scannerSupported) {
                const recommendedUrl = isSecure ? window.location.origin : ('https://' + window.location.host);
                scanResult.innerHTML = '<div class="text-center p-4 w-100">'
                    + '<div class="alert alert-warning mb-3">'
                    + '<h5 class="alert-heading">Camera scanner not available on this device</h5>'
                    + '<p class="mb-1">This browser cannot access the camera here (it requires HTTPS and a supported browser).</p>'
                    + '<p class="mb-2">You can still scan using your phone\'s camera or any QR app, then open ' + recommendedUrl + ' in your browser to view the equipment.</p>'
                    + '<div>'
                    + '<a href="/public/qr-setup" class="btn btn-outline-primary btn-sm me-2" target="_blank">Setup Guide</a>'
                    + '<a href="' + recommendedUrl + '/public/qr-scanner" class="btn btn-primary btn-sm">Try Public Scanner</a>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
                return;
            }
            
            // Initialize event listeners
            startBtns.forEach(btn => {
                btn.addEventListener('click', startScanner);
            });
            stopBtn.addEventListener('click', stopScanner);
        });

        // Clean up when modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    html5QrCode = null;
                }).catch(err => {
                    console.error("Error stopping scanner:", err);
                    html5QrCode = null;
                });
            }
            
            // Remove event listeners
            startBtns.forEach(btn => {
                btn.removeEventListener('click', startScanner);
            });
            stopBtn.removeEventListener('click', stopScanner);
        });

        function startScanner() {
            if (!scannerSupported) {
                const recommendedUrl = isSecure ? window.location.origin : ('https://' + window.location.host);
                alert('Camera scanner is not available on this device/browser. Please use your camera or any QR app and open ' + recommendedUrl + ' in your browser.');
                return;
            }

            scannerWrapper.style.display = 'block';
            scanResult.style.display = 'none';

            requestCameraAndStart();
        }

        function requestCameraAndStart() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    stream.getTracks().forEach(track => track.stop()); // close test stream
                    startHtml5QrScanner();
                })
                .catch(function(err) {
                    console.warn('Camera permission denied:', err);
                    scannerWrapper.style.display = 'none';
                    scanResult.style.display = 'flex';
                    const recommendedUrl = isSecure ? window.location.origin : ('https://' + window.location.host);
                    scanResult.innerHTML = '<div class="text-center p-4">'
                        + '<div class="alert alert-warning">'
                        + '<h5 class="alert-heading">Camera access required</h5>'
                        + '<p class="mb-2">Please allow camera access to scan QR codes.</p>'
                        + '<div class="mb-3">'
                        + '<a href="/public/qr-setup" class="btn btn-outline-primary btn-sm me-2" target="_blank">Setup Guide</a>'
                        + '<button class="btn btn-primary btn-sm" onclick="location.reload()">Try Again</button>'
                        + '</div>'
                        + '<p class="mb-0 text-muted">Or use your phone\'s camera app and open: <br><code>' + recommendedUrl + '/public/qr-scanner</code></p>'
                        + '</div>'
                        + '</div>';
                });
        }

        function startHtml5QrScanner() {
            try {
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("reader");
                }

                const qrboxFunction = function(viewfinderWidth, viewfinderHeight) {
                    const minEdgePercentage = 0.8; // Larger scan area for better detection
                    const minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                    const qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
                    return { width: qrboxSize, height: qrboxSize };
                };

                // Optimized config for older devices
                const config = { 
                    fps: 5, // Lower FPS for better performance
                    qrbox: qrboxFunction, 
                    aspectRatio: 1.0,
                    disableFlip: false, // Allow image flipping for better detection
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true // Use native detector when available
                    }
                };

                // Show scanning hint for older devices
                setTimeout(function() {
                    if (html5QrCode && html5QrCode.isScanning) {
                        const hint = document.createElement('div');
                        hint.className = 'alert alert-info mt-2';
                        hint.innerHTML = '<small><strong>Tip:</strong> Hold steady and ensure good lighting. If scanning takes too long, use your phone\'s camera app instead.</small>';
                        const scannerContainer = document.querySelector('#reader');
                        if (scannerContainer && scannerContainer.parentNode) {
                            scannerContainer.parentNode.insertBefore(hint, scannerContainer.nextSibling);
                        }
                    }
                }, 8000); // Show hint after 8 seconds

                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    onScanSuccess,
                    onScanFailure
                ).catch(err => {
                    console.error("Unable to start scanning", err);
                    showFallbackWithRetry("Unable to start the camera. Try using your phone's camera app instead.");
                });
            } catch (err) {
                console.error("Error initializing QR scanner:", err);
                showFallbackWithRetry("Failed to initialize QR scanner. Your device may not support this feature.");
            }
        }

        function showFallbackWithRetry(message) {
            scannerWrapper.style.display = 'none';
            scanResult.style.display = 'flex';
            const recommendedUrl = isSecure ? window.location.origin : ('https://' + window.location.host);
            scanResult.innerHTML = '<div class="text-center p-4">'
                + '<div class="alert alert-warning">'
                + '<h5 class="alert-heading">Scanner Not Working</h5>'
                + '<p class="mb-2">' + message + '</p>'
                + '<div class="mb-3">'
                + '<strong>Alternative:</strong> Use your phone\'s camera app to scan the QR code, then tap the link that appears.'
                + '</div>'
                + '<div class="mb-3">'
                + '<a href="/public/qr-setup" class="btn btn-outline-primary btn-sm me-2" target="_blank">Setup Guide</a>'
                + '<button class="btn btn-primary btn-sm" onclick="location.reload()">Try Again</button>'
                + '</div>'
                + '<p class="mb-0 text-muted">Or open directly: <br><code>' + recommendedUrl + '/public/qr-scanner</code></p>'
                + '</div>'
                + '</div>';
        }

        function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop()
                    .then(() => {
                        scannerWrapper.style.display = 'none';
                        scanResult.style.display = 'flex';
                    })
                    .catch(err => {
                        console.error("Error stopping scanner:", err);
                        // Ensure UI is reset even if there's an error
                        scannerWrapper.style.display = 'none';
                        scanResult.style.display = 'flex';
                    });
            } else {
                // Ensure UI is in the correct state
                scannerWrapper.style.display = 'none';
                scanResult.style.display = 'flex';
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Stop the scanner
            stopScanner();
            
            // Here you would typically handle the scanned result
            // For now, we'll just show an alert and add to history
            console.log('Scanned:', decodedText);
            
            // Add to history
            addToHistory('Scanned Equipment', decodedText);
            
            // Show success message
            alert(`Scanned: ${decodedText}`);
            
            // Here you would typically redirect to the equipment page or show details
            // window.location.href = `/equipment/${decodedText}`;
        }

        function onScanFailure(error) {
            // Handle scan failure
            console.warn('QR Code scan failed:', error);
        }

        function addToHistory(name, id) {
            const historyList = document.getElementById('historyList');
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const historyItem = document.createElement('li');
            historyItem.className = 'history-item d-flex align-items-center p-3 mb-2 bg-white rounded-3 shadow-sm';
            historyItem.innerHTML = `
                <div class="flex-shrink-0 me-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 text-primary">
                        <i class='bx bx-desktop'></i>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fw-semibold">${name}</h6>
                        <small class="text-muted">${timeString}</small>
                    </div>
                    <p class="mb-0 small text-muted">ID: ${id}</p>
                </div>
            `;
            
            // Add click handler to the history item
            historyItem.addEventListener('click', function() {
                // Handle history item click (e.g., show equipment details)
                console.log('Viewing equipment:', id);
                // window.location.href = `/equipment/${id}`;
            });
            
            // Add to the top of the history
            historyList.insertBefore(historyItem, historyList.firstChild);
            
            // Limit history items
            if (historyList.children.length > 5) {
                historyList.removeChild(historyList.lastChild);
            }
        }
    });
</script>
@endpush
