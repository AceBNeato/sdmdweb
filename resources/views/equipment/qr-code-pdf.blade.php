@php
    use Illuminate\Support\Facades\Storage;
    $generatedAt = $generatedAt ?? now()->setTimezone('Asia/Manila');
    $generatedBy = $generatedBy ?? 'SDMD System';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Equipment QR Codes</title>
    <style>
        @page {
            size: A4;
            margin: 0.25in;
        }

        :root {
            --primary: #1d3557;
            --accent: #457b9d;
            --muted: #6c757d;
            --border: #d1d5db;
            --bg-light: #f8fafc;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --font-heading: 'Poppins', 'Segoe UI', sans-serif;
            --font-body: 'Inter', 'Segoe UI', sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-body);
            color: #1e293b;
            background: linear-gradient(135deg, rgba(69, 123, 157, 0.18), rgba(29, 53, 87, 0.28));
            min-height: 100vh;
            padding: 24px 0 48px;
        }

        .actions {
            text-align: center;
            margin-bottom: 24px;
        }

        .actions a,
        .actions button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 999px;
            border: 1px solid var(--accent);
            background: var(--accent);
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .actions a.secondary {
            background: #fff;
            color: var(--accent);
        }

        .actions a:hover,
        .actions button:hover {
            box-shadow: 0 6px 18px rgba(29, 53, 87, 0.18);
        }

        .page {
            width: 8.27in;
            min-height: 11.69in;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            padding: 28px 32px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
            border: 1px solid rgba(148, 163, 184, 0.25);
        }

        .page-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 26px;
            margin: 0;
            color: var(--primary);
        }

        .page-header .meta {
            margin-top: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        .summary {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 22px;
            font-size: 13px;
            color: var(--muted);
        }

        .summary span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(69, 123, 157, 0.08);
            color: var(--accent);
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .qr-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.32);
            box-shadow: var(--card-shadow);
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            page-break-inside: avoid;
            min-height: 260px;
        }


        .qr-image img {
            padding: 1rem;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .qr-meta {
            width: 100%;
        }

        .qr-meta h2 {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--primary);
        }

        .qr-meta .serial {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.4px;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .qr-meta .detail {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .qr-code-label {
            margin-top: 10px;
            padding: 6px 10px;
            font-size: 11px;
            border-radius: 999px;
            background: rgba(29, 53, 87, 0.08);
            color: var(--primary);
            display: inline-block;
        }

        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: var(--muted);
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .page {
                width: auto;
                min-height: auto;
                border-radius: 0;
                box-shadow: none;
                border: none;
                padding: 18px 18px 24px;
            }

            .qr-grid {
                gap: 16px;
            }

            .qr-card {
                box-shadow: none;
                border: 1px solid rgba(148, 163, 184, 0.4);
            }

            .footer-note {
                margin-top: 18px;
            }
        }
    </style>
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

        <p class="footer-note">Printed via SDMD Equipment Management • {{ now()->setTimezone('Asia/Manila')->format('M d, Y g:i A') }}</p>
    </div>
</body>
</html>
