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
            display: flex;
            flex-direction: column;
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
            padding: 8px 12px;
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
            font-size: 9pt;
        }

        .history-table th,
        .history-table td {
            border: 1px solid var(--border-color);
            padding: 6px 8px;
            text-align: center;
            line-height: 1.2;
        }

        .history-table th {
            font-weight: bold;
            background-color: #f5f5f5;
        }

        .history-table td.text-left {
            text-align: left;
        }

        .footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
            font-size: 9pt;
            color: #666;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
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

        .pagination-controls {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination-info {
            font-weight: 600;
            color: #495057;
        }

        .pagination-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .pagination-buttons .btn {
            padding: 6px 12px;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
        }

        .pagination-buttons .btn.active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .pagination-buttons .btn-outline-primary {
            background-color: white;
            border-color: #007bff;
            color: #007bff;
        }

        .pagination-buttons .btn-outline-primary:hover {
            background-color: #007bff;
            color: white;
        }

        @media print {
            @page {
                size: A4;
                margin: 0.25in;
            }
            body { -webkit-print-color-adjust: exact; margin: 0; }
            .page { 
                margin: 0; 
                border: none;
                height: auto;
                min-height: 11.69in;
                page-break-inside: avoid;
                overflow: visible;
            }
            .action-buttons { display: none; }
            .pagination-controls { display: none; }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary">
            <i class='bx bx-printer'></i> Print Current Page
        </button>
        <button onclick="printAllPages()" class="btn btn-success" style="margin-left: 10px;">
            <i class='bx bx-printer'></i> Print All Pages
        </button>
    </div>

    {{-- Pagination Controls --}}
    <div class="pagination-controls">
        <div class="pagination-info">
            Showing page {{ $currentPage }} of {{ $totalPages }} ({{ $history->count() }} records)
        </div>
        <div class="pagination-buttons">
            @if($currentPage > 1)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}" class="btn btn-outline-primary">
                    <i class='bx bx-chevron-left'></i> Previous
                </a>
            @endif
            
            {{-- Page numbers --}}
            @for($i = 1; $i <= $totalPages; $i++)
                @if($i == $currentPage)
                    <span class="btn btn-primary active">{{ $i }}</span>
                @else
                    <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}" class="btn btn-outline-primary">{{ $i }}</a>
                @endif
            @endfor
            
            @if($hasMorePages)
                <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}" class="btn btn-outline-primary">
                    Next <i class='bx bx-chevron-right'></i>
                </a>
            @endif
        </div>
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

        <div class="content-area">
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
                    <th width="10%">Date</th>
                    <th width="12%">JO Number</th>
                    <th width="38%">Actions Taken</th>
                    <th width="25%">Remarks</th>
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
                @php
                    // Calculate remaining rows to fill the page (15 rows per page)
                    $remainingRows = 15 - $history->count();
                @endphp
                @for($i = 0; $i < $remainingRows; $i++)
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
        </div>

        <div class="footer-bar">
            <span>Systems and Data Management Division (SDMD)</span>
            <span>Page {{ $currentPage }} of {{ $totalPages }}</span>
        </div>

        {{-- Add page break for next page if needed --}}
        @if($hasMorePages)
            <div style="page-break-after: always;"></div>
        @else
        @endif
    </div>

    <script>
        function printAllPages() {
            // Show loading indicator
            const printAllBtn = event.target;
            const originalText = printAllBtn.innerHTML;
            printAllBtn.innerHTML = '<i class=\'bx bx-loader-alt bx-spin\'></i> Loading all pages...';
            printAllBtn.disabled = true;
            
            // Get the base URL without page parameter
            const baseUrl = window.location.origin + window.location.pathname;
            const totalPages = {{ $totalPages }};
            
            // Create a container for all pages
            const allPagesContainer = document.createElement('div');
            allPagesContainer.style.display = 'none';
            document.body.appendChild(allPagesContainer);
            
            let loadedPages = 0;
            
            // Load pages sequentially to maintain order
            function loadPage(pageNumber) {
                const pageUrl = `${baseUrl}?page=${pageNumber}`;
                
                fetch(pageUrl)
                    .then(response => response.text())
                    .then(html => {
                        // Parse the HTML and extract the page content
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const pageContent = doc.querySelector('.page');
                        
                        if(pageContent) {
                            // Clone the page content
                            const pageClone = pageContent.cloneNode(true);
                            // Remove pagination controls from printed pages
                            const paginationControls = pageClone.querySelector('.pagination-controls');
                            if(paginationControls) {
                                paginationControls.style.display = 'none';
                            }
                            // Remove any existing page breaks to avoid blank pages
                            const pageBreaks = pageClone.querySelectorAll('[style*="page-break-after: always"]');
                            pageBreaks.forEach(breakElement => breakElement.remove());
                            
                            // Ensure proper page sizing for print
                            pageClone.style.height = 'auto';
                            pageClone.style.minHeight = '11.69in';
                            pageClone.style.overflow = 'visible';
                            pageClone.style.pageBreakInside = 'avoid';
                            
                            // Add page break only between pages (not after last page)
                            if(pageNumber < totalPages) {
                                pageClone.style.pageBreakAfter = 'always';
                            }
                            
                            allPagesContainer.appendChild(pageClone);
                        }
                        
                        loadedPages++;
                        
                        // Load next page or trigger print when all pages are loaded
                        if(pageNumber < totalPages) {
                            loadPage(pageNumber + 1);
                        } else if(loadedPages === totalPages) {
                            // All pages loaded, trigger print
                            triggerPrint();
                        }
                    })
                    .catch(error => {
                        console.error('Failed to load page ' + pageNumber, error);
                        loadedPages++;
                        
                        // Continue to next page or trigger print
                        if(pageNumber < totalPages) {
                            loadPage(pageNumber + 1);
                        } else if(loadedPages === totalPages) {
                            triggerPrint();
                        }
                    });
            }
            
            function triggerPrint() {
                // Hide current page content temporarily
                const currentContent = document.querySelector('.page');
                const currentPagination = document.querySelector('.pagination-controls');
                if(currentContent) currentContent.style.display = 'none';
                if(currentPagination) currentPagination.style.display = 'none';
                
                // Show all pages container
                allPagesContainer.style.display = 'block';
                
                // Trigger print dialog
                setTimeout(() => {
                    window.print();
                    
                    // Restore original view after print dialog closes
                    setTimeout(() => {
                        allPagesContainer.style.display = 'none';
                        if(currentContent) currentContent.style.display = 'block';
                        if(currentPagination) currentPagination.style.display = 'flex';
                        
                        // Clean up
                        document.body.removeChild(allPagesContainer);
                        
                        // Restore button
                        printAllBtn.innerHTML = originalText;
                        printAllBtn.disabled = false;
                    }, 1000);
                }, 500);
            }
            
            // Start loading from page 1
            loadPage(1);
        }
    </script>
</body>
</html>
