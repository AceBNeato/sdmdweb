<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Equipment;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\PdfService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ReportController extends BaseController
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->middleware('auth');
        $this->middleware('permission:reports.view')->only(['index', 'show']);
        $this->middleware('permission:reports.generate')->only(['history', 'export', 'equipmentHistory']);
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        $query = \App\Models\EquipmentHistory::with(['equipment', 'user.roles']);

        // Filter by office for staff users
        if (Auth::user()->is_staff) {
            $query->whereHas('equipment', function($q) {
                $q->where('office_id', Auth::user()->office_id);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action_taken', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhere('responsible_person', 'like', "%{$search}%")
                  ->orWhereHas('equipment', function($eq) use ($search) {
                      $eq->where('model_number', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%");
                  });
            });
        }

        // Date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        $equipmentHistory = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        return view('reports.index', [
            'equipmentHistory' => $equipmentHistory
        ]);
    }

    public function history($id)
    {
        $equipment = Equipment::with(['office', 'equipmentType', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        // Check if user is staff and equipment belongs to a different office
        if (Auth::user()->is_staff && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }
        
        return view('reports.history', compact('equipment'));
    }

    /**
     * Generate printable equipment history report
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function equipmentHistory(Equipment $equipment)
    {
        // Check if user is staff and equipment belongs to a different office
        if (Auth::user()->is_staff && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }

        $equipment->load([
            'office', 
            'history' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'history.user'
        ]);

        return view('reports.history', [
            'equipment' => $equipment,
            'page_title' => 'Equipment History - ' . $equipment->model_number
        ]);
    }
    
    /**
     * Export equipment history as PDF or show ICT history sheet
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function exportEquipmentHistory(Equipment $equipment)
    {
        // Check if user is staff and equipment belongs to a different office
        if (Auth::user()->is_staff && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }

        $equipment->load(['office', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'history.user']);

        return view('reports.ict_history_sheet', [
            'equipment' => $equipment,
            'history' => $equipment->history
        ]);
    }

    /**
     * Generate PDF for equipment history (legacy method)
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    private function generateEquipmentHistoryPdf(Equipment $equipment)
    {
        $equipment->load(['office', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        // Format history data for PDF
        $historyData = $equipment->history->map(function($item) {
            return [
                'date' => $item->created_at->format('Y-m-d'),
                'jo_number' => $item->jo_number,
                'action_taken' => $item->action_taken,
                'remarks' => $item->remarks,
                'responsible_person' => $item->responsible_person
            ];
        })->toArray();

        // Generate PDF using our service
        $pdf = $this->pdfService->generateEquipmentHistorySheet(
            $equipment->model_number,
            $equipment->serial_number,
            $equipment->office ? $equipment->office->name . ' - ' . $equipment->office->campus->name : 'Not Assigned',
            $historyData
        );

        // Output PDF to browser
        return response($pdf->Output('equipment_history_'.$equipment->id.'.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }

    /**
     * Export equipment history data
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'format' => 'required|in:pdf,csv,word',
        ]);

        $equipment = Equipment::with(['office', 'maintenanceLogs'])->findOrFail($request->equipment_id);

        // Check if user is staff and equipment belongs to a different office
        if (Auth::user()->is_staff && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }

        if ($request->format === 'csv') {
            return $this->exportEquipmentHistoryCsv($equipment);
        }

        if ($request->format === 'word') {
            return $this->exportEquipmentHistoryWord($equipment);
        }

        return $this->generateEquipmentHistoryPdf($equipment);
    }

    /**
     * Export equipment history as CSV
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    private function exportEquipmentHistoryCsv(Equipment $equipment)
    {
        $filename = "equipment_history_{$equipment->id}_" . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($equipment) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, ['Equipment History Report']);
            fputcsv($file, ['Generated on:', now()->format('F j, Y g:i A')]);
            fputcsv($file, ['Generated by:', Auth::user()->name]);
            fputcsv($file, []);

            // Equipment details
            fputcsv($file, ['Equipment Details']);
            fputcsv($file, ['Model Number', $equipment->model_number]);
            fputcsv($file, ['Serial Number', $equipment->serial_number]);
            fputcsv($file, ['Equipment Type', $equipment->equipment_type]);
            fputcsv($file, ['Status', $equipment->status]);
            fputcsv($file, ['Condition', $equipment->condition]);
            fputcsv($file, ['Location', $equipment->location]);
            fputcsv($file, ['Office', $equipment->office ? $equipment->office->name : 'N/A']);
            fputcsv($file, []);

            // Maintenance history
            fputcsv($file, ['Maintenance History']);
            fputcsv($file, ['Date', 'Action', 'Details', 'User']);

            foreach ($equipment->maintenanceLogs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->action,
                    $log->details,
                    $log->user ? $log->user->name : 'System'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export equipment history as Word document
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    private function exportEquipmentHistoryWord(Equipment $equipment)
    {
        $phpWord = new PhpWord();

        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('USeP ICT System');
        $properties->setCompany('University of Southeastern Philippines');
        $properties->setTitle('ICT Equipment History Sheet');
        $properties->setDescription('Equipment maintenance and history tracking');

        // Add a section
        $section = $phpWord->addSection([
            'marginLeft' => 720,    // 0.5 inch
            'marginRight' => 720,   // 0.5 inch
            'marginTop' => 720,     // 0.5 inch
            'marginBottom' => 720,  // 0.5 inch
        ]);

        // Header with university information
        $header = $section->addHeader();
        $header->addText('Republic of the Philippines', ['bold' => true, 'size' => 12], ['alignment' => 'center']);
        $header->addText('University of Southeastern Philippines', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
        $header->addText('Iñigo St., Bo. Obrero, Davao City 8000', ['size' => 10], ['alignment' => 'center']);
        $header->addText('Telephone: (082) 227-8192', ['size' => 10], ['alignment' => 'center']);
        $header->addText('Website: www.usep.edu.ph', ['size' => 10], ['alignment' => 'center']);
        $header->addText('Email: president@usep.edu.ph', ['size' => 10], ['alignment' => 'center']);

        // Form information table (top right)
        $table = $header->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('Form No.', ['size' => 9]);
        $table->addCell(3000)->addText('FM-USeP-ICT-04', ['size' => 9]);
        $table->addRow();
        $table->addCell(2000)->addText('Issue Status', ['size' => 9]);
        $table->addCell(3000)->addText('01', ['size' => 9]);
        $table->addRow();
        $table->addCell(2000)->addText('Revision No.', ['size' => 9]);
        $table->addCell(3000)->addText('00', ['size' => 9]);
        $table->addRow();
        $table->addCell(2000)->addText('Date Effective', ['size' => 9]);
        $table->addCell(3000)->addText('23 December 2022', ['size' => 9]);
        $table->addRow();
        $table->addCell(2000)->addText('Approved by', ['size' => 9]);
        $table->addCell(3000)->addText('President', ['size' => 9]);

        // Title
        $section->addText('ICT EQUIPMENT HISTORY SHEET', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
        $section->addTextBreak(1);

        // Equipment details table
        $equipmentTable = $section->addTable();
        $equipmentTable->addRow();
        $equipmentTable->addCell(3000)->addText('Equipment:', ['bold' => true]);
        $equipmentTable->addCell(6000)->addText($equipment->model_number);

        $equipmentTable->addRow();
        $equipmentTable->addCell(3000)->addText('Property/Serial Number:', ['bold' => true]);
        $equipmentTable->addCell(6000)->addText($equipment->serial_number);

        $equipmentTable->addRow();
        $equipmentTable->addCell(3000)->addText('Location:', ['bold' => true]);
        $equipmentTable->addCell(6000)->addText($equipment->office->name ?? 'N/A');

        $section->addTextBreak(1);

        // History table
        $historyTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80
        ]);

        // Header row
        $historyTable->addRow();
        $historyTable->addCell(1200)->addText('Date', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(1200)->addText('JO Number', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(2500)->addText('Actions Taken', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(2000)->addText('Remarks', ['bold' => true], ['alignment' => 'center']);
        $historyTable->addCell(1500)->addText('Responsible SDMD Personnel', ['bold' => true], ['alignment' => 'center']);

        // Data rows
        foreach ($equipment->history as $entry) {
            $historyTable->addRow();
            $historyTable->addCell(1200)->addText($entry->created_at ? $entry->created_at->format('m/d/Y') : '');
            $historyTable->addCell(1200)->addText($entry->jo_number ?? '');
            $historyTable->addCell(2500)->addText($entry->action_taken ?? '');
            $historyTable->addCell(2000)->addText($entry->remarks ?? '');
            $historyTable->addCell(1500)->addText($entry->responsible_person ?? ($entry->user ? $entry->user->name : ''));
        }

        // Add empty rows to make it look like a form
        $emptyRows = max(0, 20 - $equipment->history->count());
        for ($i = 0; $i < $emptyRows; $i++) {
            $historyTable->addRow();
            $historyTable->addCell(1200)->addText('');
            $historyTable->addCell(1200)->addText('');
            $historyTable->addCell(2500)->addText('');
            $historyTable->addCell(2000)->addText('');
            $historyTable->addCell(1500)->addText('');
        }

        // Footer
        $section->addTextBreak(2);
        $footerTable = $section->addTable();
        $footerTable->addRow();
        $footerTable->addCell(7000)->addText('Systems and Data Management Division (SDMD)', ['size' => 10]);
        $footerTable->addCell(2000)->addText('Page 1 of 1', ['size' => 10], ['alignment' => 'right']);

        // Generate filename and save
        $filename = "equipment_history_{$equipment->id}_" . now()->format('Y-m-d') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word_export');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
