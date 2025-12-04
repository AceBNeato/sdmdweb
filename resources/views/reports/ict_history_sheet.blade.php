<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Equipment History Sheet</title>
    <link rel="stylesheet" href="{{ asset('css/ict_history_sheet.css') }}">
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
        <table class="unified-table">
            <thead>
                <tr>
                    <th rowspan="5" class="logo-cell">
                        <img src="{{ asset('images/useplogo.png') }}" alt="University Seal">
                    </th>
                    <th rowspan="5" class="university-cell">
                        <div class="republic">Republic of the Philippines</div>
                        <div class="university">University of Southeastern Philippines</div>
                        <div class="details">
                            Iñigo St., Bo. Obrero, Davao City 8000<br>
                            Telephone: (082) 227-8192<br>
                            Website: <span class="link">www.usep.edu.ph</span><br>
                            Email: <span class="link">president@usep.edu.ph</span>
                        </div>
                    </th>
                    <th class="form-label">Form No.</th>
                    <th class="form-value">FM-USeP-ICT-04</th>
                </tr>
                <tr>
                    <th class="form-label">Issue Status</th>
                    <th class="form-value">01</th>
                </tr>
                <tr>
                    <th class="form-label">Revision No.</th>
                    <th class="form-value">00</th>
                </tr>
                <tr>
                    <th class="form-label">Date Effective</th>
                    <th class="form-value">23 December 2022</th>
                </tr>
                <tr>
                    <th class="form-label">Approved by</th>
                    <th class="form-value">President</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" class="title-cell">ICT EQUIPMENT HISTORY SHEET</td>
                </tr>
                <tr>
                    <td class="label-cell">Equipment:</td>
                    <td colspan="3" class="value-cell">{{ $equipment->model_number }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Property/Serial Number:</td>
                    <td colspan="3" class="value-cell">{{ $equipment->serial_number }}</td>
                </tr>
                <tr>
                    <td class="label-cell">Location:</td>
                    <td colspan="3" class="value-cell">{{ $equipment->office->name ?? 'N/A' }}</td>
                </tr>
            </tbody>
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
                    // Calculate remaining rows to fill the page (always 20 rows per page)
                    $remainingRows = 20 - $history->count();
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
