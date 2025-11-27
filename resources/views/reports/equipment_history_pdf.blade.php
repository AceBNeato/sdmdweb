<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Equipment History Report</title>
    <style>
        @page {
            size: A4;
            margin: 0.25in;
        }

        :root {
            --border-color: #000;
            --body-font: 'Times New Roman', Times, serif;
            --script-font: 'Old English Text MT', 'Goudy Old Style', 'Times New Roman', serif;
        }

        body {
            margin: 0;
            color: #000;
            font-family: var(--body-font);
            font-size: 11pt;
            background: linear-gradient(135deg, #1d3041ff 0%, #4e657aff 100%);
        }

        .page {
            width: 8.27in;
            min-height: 11.69in;
            margin: 0 auto;
            border: 1.2px solid var(--border-color);
            box-sizing: border-box;
            padding: 0.2in 0.25in 0.2in;
            background: #fff;
        }

        .action-buttons {
            text-align: center;
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            text-decoration: none;
            border: 1px solid #007bff;
            border-radius: 1rem;
            background-color: #007bff;
            color: white;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .btn i {
            margin-right: 8px;
        }

        @media print {
            @page {
                size: A4;
                margin: 0.25in;
            }
            body { -webkit-print-color-adjust: exact; margin: 0; }
            .page { margin: 0; border: none; }
            .action-buttons { display: none; }
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header .subtitle {
            margin: 5px 0;
            font-size: 14px;
            color: #7f8c8d;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            background-color: #ecf0f1;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
            border-left: 4px solid #3498db;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 6px 0;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 30%;
            color: #2c3e50;
        }
        .history-section {
            margin-top: 30px;
        }
        .history-section h3 {
            background-color: #ecf0f1;
            padding: 8px;
            margin: 0 0 15px 0;
            font-size: 14px;
            border-left: 4px solid #e74c3c;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .history-table th {
            background-color: #34495e;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        .history-table td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        .history-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .qr-code {
            text-align: center;
            margin: 20px 0;
        }
        .qr-code img {
            max-width: 120px;
            height: auto;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .status-available {
            background-color: #d4edda;
            color: #155724;
        }
        .status-maintenance {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-assigned {
            background-color: #cce7ff;
            color: #0066cc;
        }
        
        @media print {
            .page-number {
                position: fixed;
                bottom: 0.5in;
                right: 0.5in;
                font-size: 10px;
                color: #333;
            }
            
            .page-number::after {
                content: counter(page);
            }
            
            .page-number::before {
                content: "Page ";
            }
            
            body {
                counter-reset: page;
            }
            .page {
                page-break-after: always;
                counter-increment: page;
                display: table;
                width: 100%;
                height: 100vh;
            }
            
            .page:last-child {
                page-break-after: auto;
            }
            
            .page-header {
                display: table-header-group;
                background-color: #fff;
                padding: 15px 0.25in;
                border-bottom: 2px solid #000;
                font-size: 14pt;
                font-weight: bold;
                text-align: center;
            }
            
            .page-footer {
                display: table-footer-group;
                background-color: #fff;
                padding: 10px 0.25in;
                border-top: 1px solid #000;
                font-size: 10pt;
                color: #666;
                text-align: center;
            }
            
            .page-content {
                display: table-row-group;
                padding: 20px 0.25in;
            }
            
            .header-repeat, .footer {
                display: block;
            }
            
            .content-area {
                margin: 0;
                padding: 20px 0.25in;
            }
        }
    </style>
</head>
<body data-generated="{{ $generated_at }}" data-user="{{ $generated_by }}">
    <div class="action-buttons">
        <button onclick="window.print()" class="btn">
            <i class='bx bx-printer'></i> Print
        </button>
    </div>

    @php
        $logs = $equipment->maintenanceLogs;
        $perPage = 25;
        $logChunks = $logs->count() ? $logs->chunk($perPage) : collect([collect()]);
    @endphp

    @foreach ($logChunks as $pageIndex => $pageLogs)
    <div class="page">
        @include('reports.partials.pdf_header')

        <div class="content-area">
            @if ($pageIndex === 0)
            <div class="info-section">
                <h3>Equipment Details</h3>
                <table class="info-table">
                    <tr>
                        <td>Model Number:</td>
                        <td>{{ $equipment->model_number }}</td>
                    </tr>
                    <tr>
                        <td>Serial Number:</td>
                        <td>{{ $equipment->serial_number }}</td>
                    </tr>
                    <tr>
                        <td>Equipment Type:</td>
                        <td>{{ $equipment->equipment_type }}</td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td>
                            <span class="status-badge status-{{ $equipment->status }}">
                                {{ ucfirst($equipment->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Location:</td>
                        <td>{{ $equipment->location }}</td>
                    </tr>
                    <tr>
                        <td>Office:</td>
                        <td>{{ $equipment->office ? $equipment->office->name : 'N/A' }}</td>
                    </tr>
                    @if ($equipment->purchase_date)
                    <tr>
                        <td>Purchase Date:</td>
                        <td>{{ $equipment->purchase_date->format('M d, Y') }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            @if ($equipment->qr_code_image_path)
            <div class="qr-code">
                <img src="{{ public_path('storage/' . $equipment->qr_code_image_path) }}" alt="QR Code">
            </div>
            @endif
            @endif

            @if ($logs->count() > 0)
            <div class="history-section">
                <h3>Maintenance History @if($pageIndex > 0)(continued)@endif</h3>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pageLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                            <td>{{ $log->details }}</td>
                            <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @elseif ($pageIndex === 0)
            <div class="history-section">
                <h3>Maintenance History</h3>
                <p>No maintenance history available for this equipment.</p>
            </div>
            @endif
        </div>

        @include('reports.partials.pdf_footer')
    </div>
    @endforeach
</body>
</html>
