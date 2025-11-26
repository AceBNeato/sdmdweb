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
    <!-- Filters & Bulk Actions -->
    <div class="filter-section rounded-lg p-3 mb-4">
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
                    <div class="d-flex flex-wrap gap-2 align-items-end justify-content-lg-end">
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

        @if($equipment->count() > 0)
            <div class="selection-controls mt-3 pt-3">
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
        @endif
    </div>

    @if($equipment->count() > 0)
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
                    <div class="p-2 border-bottom bg-light">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class='bx bx-search'></i>
                            </span>
                            <input type="text"
                                   id="qrEquipmentSearch"
                                   class="form-control"
                                   placeholder="Search by model, serial, office or type">
                        </div>
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
