<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Equipment;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\PdfService;

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
        // Start with a query that groups by equipment_id
        $query = \App\Models\EquipmentHistory::with(['equipment', 'equipment.office', 'user.role'])
            ->selectRaw('equipment_id, MAX(created_at) as latest_updated_at, COUNT(*) as total_entries')
            ->groupBy('equipment_id');

        // Filter by office for staff users only (not admins or super admins)
        if (Auth::user()->is_staff && !Auth::user()->is_admin) {
            $query->whereHas('equipment', function($q) {
                $q->where('office_id', Auth::user()->office_id);
            });
        }

        // Office filter
        if ($request->filled('office_id')) {
            $query->whereHas('equipment', function($q) use ($request) {
                $q->where('office_id', $request->office_id);
            });
        }

        // Equipment type filter
        if ($request->filled('type')) {
            $query->whereHas('equipment', function($q) use ($request) {
                $q->where('equipment_type_id', $request->type);
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('equipment', function($eq) use ($search) {
                $eq->where('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        // Date filter (filter on the latest entry date)
        if ($request->filled('date_from')) {
            $query->havingRaw('MAX(created_at) >= ?', [$request->date_from]);
        }

        // Order by latest updated date and paginate
        $equipmentGroups = $query->orderBy('latest_updated_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Load full equipment relationships and latest history details
        $equipmentGroups->getCollection()->transform(function ($group) {
            $equipment = \App\Models\Equipment::with(['office', 'equipmentType'])
                ->findOrFail($group->equipment_id);
            
            // Get the latest history entry for this equipment
            $latestHistory = \App\Models\EquipmentHistory::with('user')
                ->where('equipment_id', $group->equipment_id)
                ->orderBy('created_at', 'desc')
                ->first();

            $group->equipment = $equipment;
            $group->latest_entry = $latestHistory;
            $group->total_entries = (int) $group->total_entries;
            
            return $group;
        });

        return view('reports.index', [
            'equipmentHistory' => $equipmentGroups
        ]);
    }

    public function history($id)
    {
        $equipment = Equipment::with(['office', 'equipmentType', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        // Check if user is staff and equipment belongs to a different office
        // Super admins and admins can access all equipment history
        if (Auth::user()->is_staff && !Auth::user()->is_admin && $equipment->office_id !== Auth::user()->office_id) {
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
        // Super admins and admins can access all equipment history
        if (Auth::user()->is_staff && !Auth::user()->is_admin && $equipment->office_id !== Auth::user()->office_id) {
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
        // Super admins and admins can access all equipment history
        if (Auth::user()->is_staff && !Auth::user()->is_admin && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }

        // Load equipment with paginated history (15 items per page)
        $equipment->load(['office']);
        
        $history = $equipment->history()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate total pages needed (15 items per page)
        $perPage = 15;
        $totalPages = ceil($history->count() / $perPage);
        $currentPage = request()->get('page', 1);
        
        // Get items for current page
        $currentPageItems = $history->forPage($currentPage, $perPage);

        return view('reports.ict_history_sheet', [
            'equipment' => $equipment,
            'history' => $currentPageItems,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'hasMorePages' => $currentPage < $totalPages
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
            'format' => 'required|in:pdf,csv',
        ]);

        $equipment = Equipment::with(['office', 'maintenanceLogs'])->findOrFail($request->equipment_id);

        // Check if user is staff and equipment belongs to a different office
        // Super admins and admins can access all equipment history
        if (Auth::user()->is_staff && !Auth::user()->is_admin && $equipment->office_id !== Auth::user()->office_id) {
            abort(403, 'You do not have permission to access reports for this equipment.');
        }

        if ($request->format === 'csv') {
            return $this->exportEquipmentHistoryCsv($equipment);
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

    // Word export removed
}
