@php
    $prefix = $routePrefix ?? 'admin';
    $selectedOfficeId = $selectedOfficeId ?? (request('office_id') ?? 'all');
@endphp

<div class="print-qrcodes-modal-wrapper">
    <div class="modal-header border-0 pb-0">
        <h5 class="modal-title"><i class='bx bx-barcode me-2'></i>Print QR Codes</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <form id="printQrFilterForm" action="{{ route($prefix . '.equipment.print-qrcodes') }}" method="GET" class="print-filter-form mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label for="print-office-id" class="form-label">Office</label>
                    <select name="office_id" id="print-office-id" class="form-select">
                        <option value="all" {{ $selectedOfficeId === 'all' ? 'selected' : '' }}>All Offices</option>
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
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-shrink-0"><i class='bx bx-filter-alt me-1'></i> Apply</button>
                    <button type="button" class="btn btn-outline-secondary reset-print-filters" data-url="{{ route($prefix . '.equipment.print-qrcodes') }}">
                        <i class='bx bx-reset me-1'></i> Reset
                    </button>
                </div>
            </div>
        </form>

        @if($equipment->count() > 0)
            <div class="print-selection-bar d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="selectAllQrcodes">
                        <label class="form-check-label" for="selectAllQrcodes">
                            Select All
                        </label>
                    </div>
                </div>
                <div class="selected-count text-muted">
                    Selected: <span class="selected-count-value">0</span>
                </div>
                <div>
                    <button type="button" class="btn btn-success print-selected-btn" data-pdf-url="{{ $printPdfRoute ?? '#' }}" disabled>
                        <i class='bx bx-printer me-1'></i> Open Printable Layout
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="qr-select-list">
                        @foreach($equipment as $item)
                            <div class="form-check qr-select-item">
                                <input class="form-check-input qr-select-checkbox" type="checkbox" value="{{ $item->id }}" id="select-equipment-{{ $item->id }}" checked>
                                <label class="form-check-label" for="select-equipment-{{ $item->id }}">
                                    <span class="fw-semibold">{{ $item->model_number }}</span>
                                    <small class="d-block text-muted">{{ $item->serial_number }}</small>
                                    <small class="d-block text-muted">{{ $item->office?->name ?? 'No Office' }}</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="qr-print-preview" id="qrPrintArea">
                        <div class="print-header" style="display: none;">
                            <h2 class="h5 mb-1">Equipment QR Codes</h2>
                            <p class="mb-0">
                                {{ $selectedOfficeId !== 'all' ? ($campuses->flatMap->offices->firstWhere('id', $selectedOfficeId)->name ?? 'All Offices') : 'All Offices' }}
                                • Generated on {{ now()->setTimezone('Asia/Manila')->format('M d, Y H:i') }}
                            </p>
                        </div>

                        <div class="qr-preview-grid" data-equipment-ids="{{ $equipment->pluck('id')->join(',') }}">
                            @foreach($equipment as $item)
                                <div class="qr-preview-item" data-equipment-id="{{ $item->id }}">
                                    <div class="qr-image">
                                        @if($item->qr_code_image_path && Storage::disk('public')->exists($item->qr_code_image_path))
                                            <img src="{{ asset('storage/' . $item->qr_code_image_path) }}" alt="QR Code" class="img-fluid">
                                        @else
                                            <img src="{{ route($prefix . '.equipment.qrcode', $item) }}" alt="QR Code" class="img-fluid">
                                        @endif
                                    </div>
                                    <div class="qr-details">
                                        <div class="fw-semibold">{{ $item->model_number }}</div>
                                        <small class="text-muted d-block">Serial: {{ $item->serial_number }}</small>
                                        <small class="text-muted d-block">Office: {{ $item->office?->name ?? 'N/A' }}</small>
                                        <small class="text-muted d-block">Type: {{ $item->equipmentType?->name ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="empty-state py-5 text-center">
                <i class='bx bx-qr-scan display-4 mb-3 text-muted'></i>
                <h5>No Equipment Found</h5>
                <p class="mb-0 text-muted">Adjust the filters to find equipment with QR codes.</p>
            </div>
        @endif
    </div>
</div>

<form id="printQrcodesPdfForm" action="{{ $printPdfRoute ?? '#' }}" method="GET" target="_blank" style="display:none;">
    <input type="hidden" name="equipment_ids" value="">
</form>

<style>
    .print-qrcodes-modal-wrapper {
        padding: 0 0 1rem;
    }

    .print-filter-form .form-label {
        font-weight: 600;
    }

    .qr-select-list {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        max-height: 420px;
        overflow-y: auto;
        padding: 1rem;
        background: #f8f9fa;
    }

    .qr-select-item + .qr-select-item {
        margin-top: 0.75rem;
    }

    .qr-print-preview {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.25rem;
        background: white;
        max-height: 540px;
        overflow-y: auto;
    }

    .qr-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
    }

    .qr-preview-item {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        background: white;
    }

    .qr-preview-item img {
        max-width: 100%;
        margin-bottom: 0.85rem;
    }

    .qr-details small {
        font-size: 0.75rem;
    }

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
        .print-selection-bar,
        .btn,
        .qr-select-list,
        #selectAllQrcodes,
        .qr-select-checkbox,
        .form-check {
            display: none !important;
        }

        .qr-print-preview {
            max-height: none !important;
            overflow: visible !important;
        }

        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #000;
        }

        .qr-preview-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
            gap: 1.25rem !important;
        }

        .qr-preview-item {
            page-break-inside: avoid;
        }

        .qr-preview-item:only-child {
            grid-column: 1 / -1;
        }
    }
</style>
