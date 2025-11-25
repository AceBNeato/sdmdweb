@extends('layouts.app')

@section('title', 'Device Setup for QR Scanning')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Device Setup for QR Scanning
                    </h4>
                </div>
                <div class="card-body">
                    <!-- HTTPS Check Banner -->
                    <div id="httpsWarning" class="alert alert-warning d-none" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            You are not using HTTPS
                        </h5>
                        <p class="mb-2">
                            Camera access requires a secure HTTPS connection. Please ensure your server is configured with HTTPS and a trusted certificate.
                        </p>
                        <hr>
                        <p class="mb-0">
                            <strong>Current URL:</strong> <code id="currentUrl"></code><br>
                            <strong>Expected URL:</strong> <code id="expectedUrl"></code>
                        </p>
                    </div>

                    <!-- Main Instructions -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-mobile-alt me-2"></i>For Mobile Devices</h5>
                            <div class="accordion" id="mobileAccordion">
                                <!-- iOS Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#iosSetup">
                                            <i class="fab fa-apple me-2"></i> iPhone/iPad (iOS)
                                        </button>
                                    </h2>
                                    <div id="iosSetup" class="accordion-collapse collapse show" data-bs-parent="#mobileAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li class="mb-2">
                                                    <strong>Install the Certificate</strong>
                                                    <ul class="mt-1">
                                                        <li>Open Safari and navigate to: <a href="/ca.crt" download>/ca.crt</a></li>
                                                        <li>Allow the profile to download</li>
                                                        <li>Go to <strong>Settings > General > VPN & Device Management</strong></li>
                                                        <li>Tap the downloaded certificate profile</li>
                                                        <li>Tap <strong>Install</strong> and enter your passcode</li>
                                                    </ul>
                                                </li>
                                                <li class="mb-2">
                                                    <strong>Trust the Certificate</strong>
                                                    <ul class="mt-1">
                                                        <li>Go to <strong>Settings > General > About</strong></li>
                                                        <li>Tap <strong>Certificate Trust Settings</strong></li>
                                                        <li>Enable the toggle for your certificate</li>
                                                    </ul>
                                                </li>
                                                <li class="mb-2">
                                                    <strong>Test the Scanner</strong>
                                                    <ul class="mt-1">
                                                        <li>Open Safari to this site (HTTPS)</li>
                                                        <li>Try the QR scanner</li>
                                                        <li>Allow camera permissions when prompted</li>
                                                    </ul>
                                                </li>
                                            </ol>
                                            <div class="alert alert-info mt-3">
                                                <small>
                                                    <strong>Note:</strong> iOS requires the certificate to be explicitly trusted for web browsing.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Android Section -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#androidSetup">
                                            <i class="fab fa-android me-2"></i> Android
                                        </button>
                                    </h2>
                                    <div id="androidSetup" class="accordion-collapse collapse" data-bs-parent="#mobileAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li class="mb-2">
                                                    <strong>Download the Certificate</strong>
                                                    <ul class="mt-1">
                                                        <li>Open Chrome and navigate to: <a href="/ca.crt" download>/ca.crt</a></li>
                                                        <li>The certificate will download to your Downloads folder</li>
                                                    </ul>
                                                </li>
                                                <li class="mb-2">
                                                    <strong>Install the Certificate</strong>
                                                    <ul class="mt-1">
                                                        <li>Open your device <strong>Settings</strong></li>
                                                        <li>Go to <strong>Security & privacy > More security & privacy > Encryption & credentials</strong></li>
                                                        <li>Tap <strong>Install from storage</strong> or <strong>Install certificate</strong></li>
                                                        <li>Navigate to your Downloads folder and select the certificate file</li>
                                                        <li>Name the certificate (e.g., "SDMD LAN")</li>
                                                        <li>For credential use: select <strong>VPN and apps</strong></li>
                                                    </ul>
                                                </li>
                                                <li class="mb-2">
                                                    <strong>Test the Scanner</strong>
                                                    <ul class="mt-1">
                                                        <li>Open Chrome to this site (HTTPS)</li>
                                                        <li>Try the QR scanner</li>
                                                        <li>Allow camera permissions when prompted</li>
                                                    </ul>
                                                </li>
                                            </ol>
                                            <div class="alert alert-info mt-3">
                                                <small>
                                                    <strong>Note:</strong> Steps may vary slightly by Android version and manufacturer.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5><i class="fas fa-desktop me-2"></i>Troubleshooting</h5>
                            <div class="accordion" id="troubleshootingAccordion">
                                <!-- Certificate Errors -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#certErrors">
                                            <i class="fas fa-exclamation-circle me-2"></i>
                                            Certificate Errors
                                        </button>
                                    </h2>
                                    <div id="certErrors" class="accordion-collapse collapse show" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>"Your connection is not private"</h6>
                                                <p>This means the certificate isn't trusted. Follow the installation steps above for your device.</p>
                                            </div>
                                            <div class="mb-3">
                                                <h6>"NET::ERR_CERT_AUTHORITY_INVALID"</h6>
                                                <p>The certificate authority isn't recognized. Make sure you've installed and trusted the CA certificate.</p>
                                            </div>
                                            <div class="mb-3">
                                                <h6>"Proceed with caution" (Advanced)</h6>
                                                <p>You can temporarily bypass this, but installing the certificate is recommended for permanent access.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Camera Issues -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cameraIssues">
                                            <i class="fas fa-camera me-2"></i>
                                            Camera Issues
                                        </button>
                                    </h2>
                                    <div id="cameraIssues" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Camera not working</h6>
                                                <ul>
                                                    <li>Ensure you're on HTTPS</li>
                                                    <li>Allow camera permissions when prompted</li>
                                                    <li>Check if another app is using the camera</li>
                                                    <li>Try refreshing the page</li>
                                                </ul>
                                            </div>
                                            <div class="mb-3">
                                                <h6>Older Android devices</h6>
                                                <p>If the in-browser scanner doesn't work, use your phone's camera app to scan the QR code, then tap the URL that appears.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Network Issues -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#networkIssues">
                                            <i class="fas fa-wifi me-2"></i>
                                            Network Issues
                                        </button>
                                    </h2>
                                    <div id="networkIssues" class="accordion-collapse collapse" data-bs-parent="#troubleshootingAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Can't reach the server</h6>
                                                <ul>
                                                    <li>Ensure you're on the same LAN/WiFi as the server</li>
                                                    <li>Check if you can ping the server hostname</li>
                                                    <li>Verify the server's IP/hostname in the URL</li>
                                                </ul>
                                            </div>
                                            <div class="mb-3">
                                                <h6>Wrong hostname</h6>
                                                <p>Make sure you're using the correct LAN hostname (e.g., sdmd.local) and not localhost.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Test Section -->
                    <div class="mt-4">
                        <h5><i class="fas fa-vial me-2"></i>Quick Test</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p>Test your setup:</p>
                                <a href="{{ route('public.qr-scanner') }}" class="btn btn-primary me-2" target="_blank">
                                    <i class="fas fa-qrcode me-1"></i> Open Public Scanner
                                </a>
                                <button onclick="testCameraAccess()" class="btn btn-outline-primary">
                                    <i class="fas fa-camera me-1"></i> Test Camera Access
                                </button>
                                <div id="testResult" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Section -->
                    @if(auth()->check() && auth()->user()->hasRole('admin'))
                    <div class="mt-4">
                        <h5><i class="fas fa-cogs me-2"></i>For Administrators</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6>Server Configuration Checklist</h6>
                                <ul class="mb-3">
                                    <li>Apache SSL vhost configured with the server certificate</li>
                                    <li>Internal CA certificate available at <code>/ca.crt</code></li>
                                    <li><code>APP_URL</code> in .env set to HTTPS URL (e.g., https://sdmd.local)</li>
                                    <li>Firewall allows HTTPS (port 443) from LAN</li>
                                </ul>
                                <a href="/ca.crt" class="btn btn-outline-secondary btn-sm" download>
                                    <i class="fas fa-download me-1"></i> Download CA Certificate
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on HTTPS
    const isHttps = window.location.protocol === 'https:';
    const httpsWarning = document.getElementById('httpsWarning');
    const currentUrl = document.getElementById('currentUrl');
    const expectedUrl = document.getElementById('expectedUrl');
    
    if (!isHttps) {
        httpsWarning.classList.remove('d-none');
        currentUrl.textContent = window.location.href;
        expectedUrl.textContent = window.location.href.replace(/^http:/, 'https:');
    }
    
    // Test camera access function
    window.testCameraAccess = function() {
        const resultDiv = document.getElementById('testResult');
        resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Testing camera access...';
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Camera API not supported on this device</div>';
            return;
        }
        
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                // Stop the test stream immediately
                stream.getTracks().forEach(track => track.stop());
                resultDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check me-2"></i>Camera access working!</div>';
            })
            .catch(function(err) {
                let message = 'Camera access denied';
                if (err.name === 'NotAllowedError') {
                    message = 'Camera permission denied. Please allow camera access in your browser settings.';
                } else if (err.name === 'NotFoundError') {
                    message = 'No camera found on this device.';
                } else if (err.name === 'NotSecureError') {
                    message = 'HTTPS required for camera access. Please use a secure connection.';
                }
                resultDiv.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>' + message + '</div>';
            });
    };
});
</script>
@endpush
