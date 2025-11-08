@extends('layouts.app')

@php
$prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
@endphp

@section('title', 'Scanned Equipment Details - SDMD Admin')
@section('page_title', 'Scanned Equipment Details')
@section('page_description', 'View details of equipment scanned via QR code')
@section('breadcrumbs')
    <a href="{{ route($prefix . '.equipment.index') }}">Equipment</a>
    <span class="separator">/</span>
    <span class="current">Scan</span>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">
                        <i class='bx bx-qr-scan me-2'></i>
                        Scanned Equipment Details
                    </h5>
                </div>
                <div class="card-body">
                    <div id="qrScanner" style="display: none;">
                        <div class="text-center mb-3">
                            <h6>Scan QR Code</h6>
                            <p class="text-muted">Point your camera at the equipment's QR code</p>
                        </div>
                        <div id="reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                        <div id="scanResult" class="mt-3 alert alert-info" style="display: none;"></div>
                    </div>

                    <div id="loadingSpinner" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading equipment details...</p>
                    </div>

                    <div id="equipmentDetails" style="display: none;">
                        <!-- Equipment details will be loaded here -->
                    </div>

                    <div id="errorMessage" class="alert alert-danger" style="display: none;">
                        <i class='bx bx-error-circle me-2'></i>
                        <span id="errorText"></span>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                            <i class='bx bx-arrow-back me-1'></i> Back
                        </button>
                        <button type="button" class="btn btn-primary" id="scanAgainButton">
                            <i class='bx bx-qr-scan me-1'></i> Scan Another QR Code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <style>
        .equipment-spec {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 0.375rem;
        }
        .equipment-spec i {
            width: 24px;
            margin-right: 0.75rem;
            color: #6c757d;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-available { background-color: #d4edda; color: #155724; }
        .status-in_use { background-color: #fff3cd; color: #856404; }
        .status-maintenance { background-color: #f8d7da; color: #721c24; }
        .status-disposed { background-color: #e2e3e5; color: #383d41; }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const equipmentId = urlParams.get('equipment_id');
            const qrData = urlParams.get('qr_data');

            if (!qrData && !equipmentId) {
                showScanner();
            } else if (qrData) {
                // Handle direct QR data (JSON format)
                loadEquipmentFromQrData(qrData);
            } else if (equipmentId) {
                // Handle equipment ID (legacy format)
                loadEquipmentDetails(equipmentId);
            } else {
                showError('No equipment data provided');
            }

            // Scan again button
            document.getElementById('scanAgainButton').addEventListener('click', function() {
                window.location.href = '{{ route($prefix . ".equipment.index") }}';
            });
        });

        function loadEquipmentFromQrData(qrData) {
            fetch(`{{ route($prefix . ".equipment.scan") }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    qr_data: qrData
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingSpinner').style.display = 'none';

                if (data.success) {
                    displayEquipmentDetails(data.equipment);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                document.getElementById('loadingSpinner').style.display = 'none';
                showError('Failed to load equipment details');
                console.error('Error:', error);
            });
        }

        function loadEquipmentDetails(equipmentId) {
            fetch(`{{ route($prefix . ".equipment.scan") }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    qr_data: JSON.stringify({ id: equipmentId })
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loadingSpinner').style.display = 'none';

                if (data.success) {
                    displayEquipmentDetails(data.equipment);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                document.getElementById('loadingSpinner').style.display = 'none';
                showError('Failed to load equipment details');
                console.error('Error:', error);
            });
        }

        function displayEquipmentDetails(equipment) {
            const detailsDiv = document.getElementById('equipmentDetails');
            detailsDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <h4 class="mb-3">${equipment.model_number}</h4>
                        <div class="equipment-spec">
                            <i class='bx bx-barcode'></i>
                            <div>
                                <strong>Serial Number:</strong> ${equipment.serial_number}
                            </div>
                        </div>
                        <div class="equipment-spec">
                            <i class='bx bx-category'></i>
                            <div>
                                <strong>Type:</strong> ${equipment.equipment_type}
                            </div>
                        </div>
                        <div class="equipment-spec">
                            <i class='bx bx-map'></i>
                            <div>
                                <strong>Location:</strong> ${equipment.location}
                            </div>
                        </div>
                        <div class="equipment-spec">
                            <i class='bx bx-building-house'></i>
                            <div>
                                <strong>Office:</strong> ${equipment.office}
                            </div>
                        </div>
                        <div class="equipment-spec">
                            <i class='bx bx-check-circle'></i>
                            <div>
                                <strong>Status:</strong>
                                <span class="status-badge status-${equipment.status.replace('_', '-')}">${equipment.status.replace('_', ' ')}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="qr-code-container text-center">
                            ${equipment.qr_code_image_path ?
                                `<img src="{{ asset('storage/') }}/${equipment.qr_code_image_path}" alt="QR Code" class="img-fluid" style="max-width: 150px;">` :
                                `<div class="text-muted">QR Code not available</div>`
                            }
                        </div>
                    </div>
                </div>

                ${equipment.maintenance_logs && equipment.maintenance_logs.length > 0 ? `
                <div class="mt-4">
                    <h5>Recent Maintenance History</h5>
                    <div class="list-group">
                        ${equipment.maintenance_logs.map(log => `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${log.action.replace('_', ' ')}</h6>
                                    <small>${log.created_at}</small>
                                </div>
                                <p class="mb-1">${log.details}</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            `;
            detailsDiv.style.display = 'block';
        }

        function showError(message) {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'block';
            document.getElementById('errorText').textContent = message;
        }

        function showScanner() {
            document.getElementById('qrScanner').style.display = 'block';
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('equipmentDetails').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            const html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('scanResult').style.display = 'block';
            document.getElementById('scanResult').innerHTML = `Scanned: <strong>${decodedText}</strong>`;
            
            // Load equipment details
            loadEquipmentFromQrData(decodedText);
        }

        function onScanFailure(error) {
            console.warn(`QR code scan error: ${error}`);
        }
    </script>
@endpush
