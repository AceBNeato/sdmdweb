@php
    $prefix = $routePrefix ?? 'admin';
    $selectedOfficeId = $selectedOfficeId ?? (request('office_id') ?? 'all');
@endphp

<!-- Modal Header -->
<div class="modal-header bg-gradient border-0 pb-3">
    <div class="d-flex align-items-center">
        <div class="modal-icon-wrapper me-3">
            <i class='bx bx-barcode'></i>
        </div>
        <div>
            <h5 class="modal-title mb-0">Print QR Codes</h5>
            <small class="text-muted">Select equipment to generate printable QR codes</small>
        </div>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<!-- Modal Body -->
<div class="modal-body p-4">
    <!-- Filter Section -->
    <div class="filter-section bg-light rounded-lg p-3 mb-4">
        <form id="printQrFilterForm" action="{{ route($prefix . '.equipment.print-qrcodes') }}" method="GET" class="print-filter-form">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4 col-xl-5">
                    <label for="print-office-id" class="form-label fw-semibold">
                        <i class='bx bx-building me-1'></i>Office Filter
                    </label>
                    <select name="office_id" id="print-office-id" class="form-select form-select-lg">
                        <option value="all" {{ $selectedOfficeId === 'all' ? 'selected' : '' }}>
                            <i class='bx bx-globe me-1'></i>All Offices
                        </option>
                        @foreach($campuses as $campus)
                            <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                @foreach($campus->offices as $office)
                                    <option value="{{ $office->id }}" {{ (string) $selectedOfficeId === (string) $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-8 col-xl-7">
                    <div class="d-flex flex-wrap gap-2 align-items-end">
                        <div class="flex-grow-1 flex-sm-grow-0">
                            <button type="submit" class="btn btn-primary btn-lg w-100 w-sm-auto">
                                <i class='bx bx-filter-alt me-2'></i>Apply Filter
                            </button>
                        </div>
                        <div class="flex-grow-1 flex-sm-grow-0">
                            <button type="button" class="btn btn-outline-secondary btn-lg w-100 w-sm-auto reset-print-filters" 
                                    data-url="{{ route($prefix . '.equipment.print-qrcodes') }}">
                                <i class='bx bx-reset me-2'></i>Reset
                            </button>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="badge bg-info text-white p-2 fs-6">
                                <i class='bx bx-package me-1'></i>
                                {{ $equipment->count() }} Equipment Found
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if($equipment->count() > 0)
        <!-- Selection Controls -->
        <div class="selection-controls bg-white border rounded-lg p-3 mb-4 shadow-sm">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="form-check form-check-lg">
                        <input class="form-check-input" type="checkbox" value="1" id="selectAllQrcodes">
                        <label class="form-check-label fw-semibold" for="selectAllQrcodes">
                            <i class='bx bx-check-square me-1'></i>Select All
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-6 text-center">
                    <div class="selection-counter">
                        <span class="badge bg-primary fs-6 p-2">
                            <i class='bx bx-check me-1'></i>
                            <span class="selected-count-value">0</span> Selected
                        </span>
                    </div>
                </div>
                <div class="col-12 col-md-4 col-lg-3 text-md-end">
                    <button type="button" class="btn btn-success btn-lg w-100 w-md-auto print-selected-btn" 
                            data-pdf-url="{{ $printPdfRoute ?? '#' }}" disabled>
                        <i class='bx bx-printer me-2'></i>Print Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Equipment List -->
            <div class="col-12 col-lg-5 col-xl-4">
                <div class="equipment-list-card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0">
                            <i class='bx bx-list-ul me-2'></i>Equipment List
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="qr-select-list">
                            @foreach($equipment as $item)
                                <div class="qr-select-item p-3 border-bottom">
                                    <div class="form-check">
                                        <input class="form-check-input qr-select-checkbox" type="checkbox" 
                                               value="{{ $item->id }}" id="select-equipment-{{ $item->id }}" checked>
                                        <label class="form-check-label w-100" for="select-equipment-{{ $item->id }}">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-primary">{{ $item->model_number }}</div>
                                                    <small class="d-block text-muted">
                                                        <i class='bx bx-hash me-1'></i>{{ $item->serial_number }}
                                                    </small>
                                                    <small class="d-block text-muted">
                                                        <i class='bx bx-building me-1'></i>{{ $item->office?->name ?? 'No Office' }}
                                                    </small>
                                                    <small class="d-block text-muted">
                                                        <i class='bx bx-category me-1'></i>{{ $item->equipmentType?->name ?? 'N/A' }}
                                                    </small>
                                                </div>
                                                <div class="qr-status-indicator">
                                                    @if($item->qr_code_image_path && Storage::disk('public')->exists($item->qr_code_image_path))
                                                        <i class='bx bx-check-circle text-success'></i>
                                                    @else
                                                        <i class='bx bx-x-circle text-warning'></i>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Preview -->
            <div class="col-12 col-lg-7 col-xl-8">
                <div class="qr-preview-card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class='bx bx-qr-scan me-2'></i>QR Code Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="qr-print-preview" id="qrPrintArea">
                            <!-- Print Header (hidden normally) -->
                            <div class="print-header d-none">
                                <div class="text-center mb-4">
                                    <h2 class="h3 mb-2">Equipment QR Codes</h2>
                                    <p class="mb-0 text-muted">
                                        {{ $selectedOfficeId !== 'all' ? ($campuses->flatMap->offices->firstWhere('id', $selectedOfficeId)->name ?? 'All Offices') : 'All Offices' }}
                                        • Generated on {{ now()->setTimezone('Asia/Manila')->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>

                            <!-- QR Grid -->
                            <div class="qr-preview-grid" data-equipment-ids="{{ $equipment->pluck('id')->join(',') }}">
                                @foreach($equipment as $item)
                                    <div class="qr-preview-item" data-equipment-id="{{ $item->id }}">
                                        <div class="qr-image-wrapper">
                                            @if($item->qr_code_image_path && Storage::disk('public')->exists($item->qr_code_image_path))
                                                <img src="{{ asset('storage/' . $item->qr_code_image_path) }}" 
                                                     alt="QR Code" class="qr-code-img">
                                            @else
                                                <img src="{{ route($prefix . '.equipment.qrcode', $item) }}" 
                                                     alt="QR Code" class="qr-code-img">
                                            @endif
                                        </div>
                                        <div class="qr-details">
                                            <div class="equipment-name">{{ $item->model_number }}</div>
                                            <div class="equipment-info">
                                                <div class="info-item">
                                                    <i class='bx bx-hash'></i>
                                                    <span>{{ $item->serial_number }}</span>
                                                </div>
                                                <div class="info-item">
                                                    <i class='bx bx-building'></i>
                                                    <span>{{ $item->office?->name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="info-item">
                                                    <i class='bx bx-category'></i>
                                                    <span>{{ $item->equipmentType?->name ?? 'N/A' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="empty-state py-5 text-center">
            <div class="empty-state-icon mb-4">
                <i class='bx bx-qr-scan'></i>
            </div>
            <h4 class="text-muted mb-3">No Equipment Found</h4>
            <p class="text-muted mb-4">
                <i class='bx bx-info-circle me-1'></i>
                Adjust the filters to find equipment with QR codes.
            </p>
            <div class="empty-state-actions">
                <button type="button" class="btn btn-outline-primary reset-print-filters" 
                        data-url="{{ route($prefix . '.equipment.print-qrcodes') }}">
                    <i class='bx bx-reset me-2'></i>Reset Filters
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Hidden Form for PDF Generation -->
<form id="printQrcodesPdfForm" action="{{ $printPdfRoute ?? '#' }}" method="GET" target="_blank" style="display:none;">
    <input type="hidden" name="equipment_ids" value="">
</form>

<style>
    /* Modal Header Styling */
    .modal-header.bg-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .modal-icon-wrapper {
        width: 48px;
        height: 48px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }
    
    .modal-icon-wrapper i {
        font-size: 24px;
        color: white;
    }

    /* Filter Section */
    .filter-section {
        border: 1px solid #e9ecef;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .rounded-lg {
        border-radius: 12px;
    }

    /* Selection Controls */
    .selection-controls {
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .form-check-lg .form-check-input {
        width: 20px;
        height: 20px;
        margin-top: 6px;
    }

    /* Equipment List Card */
    .equipment-list-card {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        height: 500px;
    }
    
    .equipment-list-card .card-header {
        border-bottom: none;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }

    .qr-select-list {
        max-height: 420px;
        overflow-y: auto;
        background: white;
    }
    
    .qr-select-item {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .qr-select-item:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
    }
    
    .qr-select-item:last-child {
        border-bottom: none;
    }
    
    .qr-select-item .form-check {
        margin-bottom: 0;
    }
    
    .qr-select-item .form-check-input {
        margin-top: 8px;
    }
    
    .qr-status-indicator {
        font-size: 18px;
    }

    /* QR Preview Card */
    .qr-preview-card {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        height: 500px;
    }
    
    .qr-preview-card .card-header {
        border-bottom: none;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .qr-print-preview {
        max-height: 420px;
        overflow-y: auto;
        padding: 1rem;
        background: white;
    }

    /* QR Grid */
    .qr-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1.25rem;
    }

    .qr-preview-item {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        text-align: center;
        background: white;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .qr-preview-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        border-color: #007bff;
    }
    
    .qr-preview-item.selected {
        border-color: #28a745;
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    }
    
    .qr-preview-item.unselected {
        opacity: 0.4;
        transform: scale(0.95);
    }

    .qr-image-wrapper {
        margin-bottom: 1rem;
        padding: 0.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    .qr-code-img {
        max-width: 120px;
        max-height: 120px;
        border-radius: 8px;
        object-fit: contain;
    }

    .qr-details {
        text-align: left;
    }
    
    .equipment-name {
        font-weight: 700;
        color: #007bff;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .equipment-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .info-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .info-item i {
        font-size: 14px;
        color: #adb5bd;
    }

    /* Empty State */
    .empty-state-icon {
        font-size: 64px;
        color: #6c757d;
        opacity: 0.5;
    }
    
    .empty-state-icon i {
        display: block;
    }

    /* Badge Styling */
    .badge.bg-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    }
    
    .badge.bg-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }

    /* Button Enhancements */
    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .btn-success:disabled {
        background: #6c757d;
        border-color: #6c757d;
        cursor: not-allowed;
    }

    /* Form Controls */
    .form-select-lg {
        padding: 0.75rem 1rem;
        font-weight: 500;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .form-select-lg:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }

        #qrPrintArea, #qrPrintArea * {
            visibility: visible;
        }

        #qrPrintArea {
            position: absolute;
            inset: 0;
            width: 100%;
            padding: 24px;
            background: white;
        }

        .modal-header,
        .selection-controls,
        .filter-section,
        .btn,
        .qr-select-list,
        #selectAllQrcodes,
        .qr-select-checkbox,
        .form-check,
        .equipment-list-card,
        .qr-preview-card .card-header {
            display: none !important;
        }

        .qr-print-preview {
            max-height: none !important;
            overflow: visible !important;
            padding: 0 !important;
        }

        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #000;
        }

        .qr-preview-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 1.5rem !important;
        }

        .qr-preview-item {
            page-break-inside: avoid;
            border: 2px solid #000 !important;
            padding: 1rem !important;
        }

        .qr-preview-item:only-child {
            grid-column: 1 / -1;
        }
        
        .qr-preview-item.unselected {
            display: none !important;
        }
    }

    /* QR Grid */
    .qr-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1.25rem;
    }

    /* Responsive Design */
    @media (max-width: 576px) {
        /* Extra Small Devices */
        .equipment-list-card,
        .qr-preview-card {
            height: 350px;
        }
        
        .qr-select-list,
        .qr-print-preview {
            max-height: 280px;
        }
        
        .qr-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.75rem;
        }
        
        .selection-controls .row {
            flex-direction: column;
            gap: 1rem;
        }
        
        .selection-controls .text-center,
        .selection-controls .text-md-end {
            text-align: center !important;
        }
        
        .selection-controls .col-12 {
            order: 2;
        }
        
        .selection-controls .col-12:first-child {
            order: 1;
        }
        
        .selection-controls .col-12:last-child {
            order: 3;
        }
        
        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
        
        .form-select-lg {
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
        }
    }

    @media (min-width: 577px) and (max-width: 768px) {
        /* Small Devices */
        .equipment-list-card,
        .qr-preview-card {
            height: 400px;
        }
        
        .qr-select-list,
        .qr-print-preview {
            max-height: 320px;
        }
        
        .qr-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
        }
        
        .selection-controls .row {
            flex-direction: row;
            gap: 1rem;
        }
    }

    @media (min-width: 769px) and (max-width: 992px) {
        /* Medium Devices */
        .equipment-list-card,
        .qr-preview-card {
            height: 450px;
        }
        
        .qr-select-list,
        .qr-print-preview {
            max-height: 370px;
        }
        
        .qr-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
    }

    @media (min-width: 993px) and (max-width: 1200px) {
        /* Large Devices */
        .equipment-list-card,
        .qr-preview-card {
            height: 480px;
        }
        
        .qr-select-list,
        .qr-print-preview {
            max-height: 400px;
        }
        
        .qr-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 1.25rem;
        }
    }

    @media (min-width: 1201px) {
        /* Extra Large Devices */
        .equipment-list-card,
        .qr-preview-card {
            height: 500px;
        }
        
        .qr-select-list,
        .qr-print-preview {
            max-height: 420px;
        }
        
        .qr-preview-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.25rem;
        }
    }

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }

        #qrPrintArea, #qrPrintArea * {
            visibility: visible;
        }

        #qrPrintArea {
            position: absolute;
            inset: 0;
            width: 100%;
            padding: 24px;
            background: white;
        }

        .modal-header,
        .selection-controls,
        .filter-section,
        .btn,
        .qr-select-list,
        #selectAllQrcodes,
        .qr-select-checkbox,
        .form-check,
        .equipment-list-card,
        .qr-preview-card .card-header {
            display: none !important;
        }

        .qr-print-preview {
            max-height: none !important;
            overflow: visible !important;
            padding: 0 !important;
        }

        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #000;
        }

        .qr-preview-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 1.5rem !important;
        }

        .qr-preview-item {
            page-break-inside: avoid;
            border: 2px solid #000 !important;
            padding: 1rem !important;
        }

        .qr-preview-item:only-child {
            grid-column: 1 / -1;
        }
        
        .qr-preview-item.unselected {
            display: none !important;
        }
    }
</style>
