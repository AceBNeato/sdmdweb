@php
    use Illuminate\Support\Facades\Storage;
    $generatedAt = $generatedAt ?? now();
    $generatedBy = $generatedBy ?? 'SDMD System';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Equipment QR Codes</title>
    <link href="{{ asset('css/equipment-qr-code-pdf.css') }}" rel="stylesheet">
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M4 1a2 2 0 0 0-2 2v2H1.5A1.5 1.5 0 0 0 0 6.5v3A1.5 1.5 0 0 0 1.5 11H2v2a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-2h.5a1.5 1.5 0 0 0 1.5-1.5v-3A1.5 1.5 0 0 0 12.5 5H12V3a2 2 0 0 0-2-2H4zm8 2v2H4V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1zM4 9h8v4H4V9z"/>
            </svg>
            Print
        </button>
        <a href="{{ route($routePrefix . '.equipment.index') }}" class="secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M11.354 15.354a.5.5 0 0 1-.708 0L4.293 9l6.353-6.354a.5.5 0 1 1 .708.708L5.707 9l5.647 5.646a.5.5 0 0 1 0 .708"/>
            </svg>
            Back to Equipment
        </a>
    </div>

    <div class="page">
        <header class="page-header">
            <h1>Equipment QR Codes</h1>
            <div class="meta">Generated on {{ $generatedAt->format('M d, Y g:i A') }} • {{ $generatedBy }}</div>
        </header>

        <div class="summary">
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 0a5 5 0 0 0-5 5v1H2a2 2 0 0 0-2 2v4.5A2.5 2.5 0 0 0 2.5 15H5v1a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-1h2.5A2.5 2.5 0 0 0 16 12.5V8a2 2 0 0 0-2-2h-1V5a5 5 0 0 0-5-5z" />
                </svg>
                Total Selected: {{ $equipments->count() }}
            </span>
            <span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M14 4.5V14a2 2 0 0 1-2 2h-3v-4H7v4H4a2 2 0 0 1-2-2V4.5l6-3.5 6 3.5z" />
                </svg>
                Offices Represented: {{ $equipments->pluck('office.name')->filter()->unique()->count() }}
            </span>
        </div>

        <section class="qr-grid">
            @foreach($equipments as $equipment)
                <article class="qr-card">
                    <div class="qr-image">
                        @if($equipment->qr_code_image_path && Storage::disk('public')->exists($equipment->qr_code_image_path))
                            <img src="{{ asset('storage/' . $equipment->qr_code_image_path) }}" alt="QR code for {{ $equipment->model_number }}">
                        @else
                            <img src="{{ route($routePrefix . '.equipment.qrcode', $equipment) }}" alt="QR code for {{ $equipment->model_number }}">
                        @endif
                    </div>

                    <div class="qr-meta">
                        <h2>{{ $equipment->model_number ?? 'Unknown Model' }}</h2>
                        <div class="serial">Serial: {{ $equipment->serial_number ?? 'N/A' }}</div>
                        <div class="detail">Type: {{ $equipment->equipmentType->name ?? 'N/A' }}</div>
                        <div class="detail">Office: {{ $equipment->office->name ?? 'Unassigned' }}</div>
                    </div>

                    @if($equipment->qr_code)
                        <span class="qr-code-label">QR: {{ $equipment->qr_code }}</span>
                    @endif
                </article>
            @endforeach
        </section>

        <p class="footer-note">Printed via SDMD Equipment Management • {{ now()->format('M d, Y g:i A') }}</p>
    </div>
</body>
</html>
