@extends('layouts.admin')

@section('page_title', 'Print QR Codes')

@section('content')
<div class="content">
    <div class="card mb-4">
        <div class="card-header">
            <h4><i class='bx bx-barcode me-2'></i>Print QR Codes</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.equipment.print-qrcodes') }}" method="GET" class="filter-form">
                <!-- Office Filter -->
                <div class="filter-group">
                    <label for="office_id">Office</label>
                    <select name="office_id" id="office_id" class="form-select">
                        <option value="all" {{ request('office_id') == 'all' || !request('office_id') ? 'selected' : '' }}>All Offices</option>
                        @foreach($campuses as $campus)
                            <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                @foreach($campus->offices->where('is_active', true) as $office)
                                    <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Filter
                    </button>
                    @if($equipment->count() > 0)
                    <button type="button" onclick="window.print()" class="btn btn-success">
                        <i class='bx bx-printer me-1'></i> Print QR Codes
                    </button>
                    @endif
                    <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-arrow-back me-1'></i> Back to Equipment
                    </a>
                </div>
            </form>
        </div>
    </div>

    @if($equipment->count() > 0)
    <div class="qr-codes-print-layout">
        <div class="print-header" style="display: none;">
            <h2>Equipment QR Codes - {{ request('office_id') !== 'all' ? ($equipment->first()->office->name ?? 'All Offices') : 'All Offices' }}</h2>
            <p>Generated on {{ now()->format('M d, Y H:i') }}</p>
        </div>

        <div class="qr-codes-grid">
            @foreach($equipment as $item)
            <div class="qr-code-item">
                <div class="qr-code-image">
                    @if($item->qr_code_image_path)
                        <img src="{{ asset('storage/' . $item->qr_code_image_path) }}" alt="QR Code" class="qr-code-img">
                    @elseif($item->qr_code)
                        <img src="{{ route('admin.equipment.qrcode', $item) }}" alt="QR Code" class="qr-code-img">
                    @else
                        <div class="qr-placeholder">
                            <i class='bx bx-qr-scan'></i>
                            <span>No QR Code</span>
                        </div>
                    @endif
                </div>
                <div class="qr-code-info">
                    <div class="model-number">{{ $item->model_number }}</div>
                    <div class="serial-number">{{ $item->serial_number }}</div>
                    <div class="office-name">{{ $item->office ? $item->office->name : 'N/A' }}</div>
                    <div class="equipment-type">{{ $item->equipmentType ? $item->equipmentType->name : 'N/A' }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
        <div class="empty-state">
            <i class='bx bx-qr-scan'></i>
            <h5>No Equipment Found</h5>
            <p>Select an office to view QR codes for printing.</p>
        </div>
    @endif
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .qr-codes-print-layout,
    .qr-codes-print-layout * {
        visibility: visible;
    }
    .qr-codes-print-layout {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }
    .filter-form,
    .btn,
    .card-header {
        display: none !important;
    }
}

.qr-codes-print-layout {
    background: white;
    padding: 20px;
}

.qr-codes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.qr-code-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    background: white;
    break-inside: avoid;
}

.qr-code-image {
    margin-bottom: 10px;
}

.qr-code-img {
    width: 120px;
    height: 120px;
    object-fit: contain;
}

.qr-placeholder {
    width: 120px;
    height: 120px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    margin: 0 auto;
}

.qr-placeholder i {
    font-size: 2rem;
    color: #6c757d;
}

.qr-placeholder span {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}

.qr-code-info {
    font-size: 0.8rem;
}

.qr-code-info > div {
    margin-bottom: 2px;
}

.model-number {
    font-weight: bold;
    font-size: 0.9rem;
}

.serial-number {
    color: #666;
}

.office-name,
.equipment-type {
    color: #888;
    font-size: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
}

.empty-state h5 {
    margin-bottom: 10px;
}
</style>
@endsection
