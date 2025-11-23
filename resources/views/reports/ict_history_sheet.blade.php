<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment History Sheet</title>
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

        .header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 3px double var(--border-color);
            padding-bottom: 12px;
        }

        .header-left img {
            width: 95px;
            height: 95px;
            object-fit: contain;
        }

        .header-center {
            flex: 1;
            text-align: center;
            line-height: 1.35;
        }

        .header-center .republic {
            font-size: 11pt;
            letter-spacing: 0.4px;
        }

        .header-center .university {
            font-family: var(--script-font);
            font-size: 15pt;
            margin: 3px 0 4px;
        }

        .header-center .details {
            font-size: 10pt;
        }

        .header-center .details .link {
            text-decoration: underline;
        }

        .header-right {
            width: 220px;
        }

        .form-info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        .form-info-table td {
            border: 1px solid var(--border-color);
            padding: 4px 6px;
            white-space: nowrap;
        }

        .title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin: 18px 0 14px;
        }

        .equipment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 11pt;
        }

        .equipment-table td {
            border: 1px solid var(--border-color);
            padding: 6px 8px;
        }

        .equipment-table .label {
            width: 195px;
            font-weight: bold;
        }

        .equipment-table .value {
            height: 24px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .history-table th,
        .history-table td {
            border: 1px solid var(--border-color);
            padding: 5px 6px;
            text-align: center;
        }

        .history-table th {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .history-table td.text-left {
            text-align: left;
        }

        .footer-bar {
            width: 96%;
            border: 1px solid var(--border-color);
            margin-top: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 14px;
            font-size: 10pt;
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
    </style>
</head>
<body>
    <div class="action-buttons">
        <a href="{{ route('technician.reports.equipment.history.export.pdf', $equipment) }}" class="btn btn-primary" target="_blank">
            <i class='bx bx-file-pdf'></i> Export PDF
        </a>
        <button onclick="window.print()" class="btn btn-primary" style="margin-left: 10px;">
            <i class='bx bx-printer'></i> Print
        </button>
    </div>

    <div class="page">
        <div class="header">
            <div class="header-left">
                <img src="{{ asset('images/useplogo.png') }}" alt="University Seal">
            </div>
            <div class="header-center">
                <div class="republic">Republic of the Philippines</div>
                <div class="university">University of Southeastern Philippines</div>
                <div class="details">
                    Iñigo St., Bo. Obrero, Davao City 8000<br>
                    Telephone: (082) 227-8192<br>
                    Website: <span class="link">www.usep.edu.ph</span><br>
                    Email: <span class="link">president@usep.edu.ph</span>
                </div>
            </div>
            <div class="header-right">
                <table class="form-info-table">
                    <tr><td>Form No.</td><td>FM-USeP-ICT-04</td></tr>
                    <tr><td>Issue Status</td><td>01</td></tr>
                    <tr><td>Revision No.</td><td>00</td></tr>
                    <tr><td>Date Effective</td><td>23 December 2022</td></tr>
                    <tr><td>Approved by</td><td>President</td></tr>
                </table>
            </div>
        </div>

        <div class="title">ICT EQUIPMENT HISTORY SHEET</div>

        <table class="equipment-table">
            <tr>
                <td class="label">Equipment:</td>
                <td class="value">{{ $equipment->model_number }}</td>
            </tr>
            <tr>
                <td class="label">Property/Serial Number:</td>
                <td class="value">{{ $equipment->serial_number }}</td>
            </tr>
            <tr>
                <td class="label">Location:</td>
                <td class="value">{{ $equipment->office->name ?? 'N/A' }}</td>
            </tr>
        </table>

        <table class="history-table">
            <thead>
                <tr>
                    <th width="12%">Date</th>
                    <th width="15%">JO Number</th>
                    <th width="35%">Actions Taken</th>
                    <th width="23%">Remarks</th>
                    <th width="15%">Responsible SDMD Personnel</th>
                </tr>
            </thead>
            <tbody>
                @foreach($history as $entry)
                <tr>
                    <td>{{ $entry->created_at ? $entry->created_at->format('m/d/Y') : '' }}</td>
                    <td>{{ $entry->jo_number ?? '' }}</td>
                    <td class="text-left">
                        {{ $entry->action_taken ?? '' }}
                    </td>
                    <td class="text-left">
                        {{ $entry->remarks ?? '' }}
                    </td>
                    <td>
                        {{ $entry->responsible_person ?? ($entry->user ? $entry->user->name : '') }}
                    </td>
                </tr>
                @endforeach
                @for($i = $history->count(); $i < 20; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="text-left">&nbsp;</td>
                    <td class="text-left">&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="footer-bar">
            <span>Systems and Data Management Division (SDMD)</span>
            <span>Page 1 of 1</span>
        </div>
    </div>
</body>
</html>
