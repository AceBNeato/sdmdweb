<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:technician');
    }

    /**
     * Display the technician's dashboard with reports overview
     */
    public function index(Request $request)
    {
        $technician = auth('technician')->user();
        
        // Technicians have access to all equipment across all offices (like admin)
        $equipmentCount = Equipment::count();
            
        // Start with a query that groups by equipment_id - technicians see all equipment
        $query = EquipmentHistory::with(['equipment', 'equipment.office', 'user'])
            ->selectRaw('equipment_id, MAX(created_at) as latest_updated_at, COUNT(*) as total_entries')
            ->groupBy('equipment_id');

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
        $equipmentHistory = $query->orderBy('latest_updated_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Load full equipment relationships and latest history details
        $equipmentHistory->getCollection()->transform(function ($group) {
            $equipment = Equipment::with(['office', 'equipmentType'])
                ->findOrFail($group->equipment_id);
            
            // Get the latest history entry for this equipment
            $latestHistory = EquipmentHistory::with('user')
                ->where('equipment_id', $group->equipment_id)
                ->orderBy('created_at', 'asc')
                ->first();

            $group->equipment = $equipment;
            $group->latest_entry = $latestHistory;
            $group->total_entries = (int) $group->total_entries;
            
            return $group;
        });
            
        // Get recent history (first 5 equipment groups) - technicians see all history like admin
        $recentHistory = EquipmentHistory::with(['equipment', 'user'])
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();

        return view('reports.index', compact(
            'equipmentCount',
            'equipmentHistory',
            'recentHistory'
        ));
    }

    /**
     * Generate equipment history report
     */
    public function equipmentHistory(Request $request)
    {
        $technician = auth('technician')->user();
        
        // Technicians have access to all equipment across all offices (like admin)
        $query = Equipment::with(['history' => function($q) {
                $q->orderBy('created_at', 'desc');
            }]);
            
        if ($request->has('equipment_id')) {
            $query->where('id', $request->equipment_id);
        }
        
        $equipment = $query->get();
        
        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = PDF::loadView('reports.equipment_history_pdf', [
                'equipment' => $equipment
            ]);
            
            return $pdf->download('equipment_history_report_' . now()->format('Y-m-d') . '.pdf');
        }
        
        return view('reports.equipment_history', compact('equipment'));
    }

    /**
     * Show detailed history for a specific equipment
     */
    public function history(Equipment $equipment)
    {
        $technician = auth('technician')->user();

        // Technicians have access to all equipment across all offices
        $equipment->load(['office', 'equipmentType', 'history' => function($query) {
            $query->orderBy('created_at', 'asc');
        }, 'history.user']);

        return view('reports.history', compact('equipment'));
    }
    public function exportEquipmentHistory(Equipment $equipment)
    {
        $technician = auth('technician')->user();

        // Technicians have access to all equipment across all offices
        $equipment->load(['office']);
        
        $history = $equipment->history()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->orderBy('jo_number', 'asc')
            ->get();

        // REVERSE it so newest is first (for correct printed page order)
        $history = $history->reverse();

        // Calculate total pages needed (20 items per page)
        $perPage = 20;
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
     * Export equipment history as PDF
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function exportEquipmentHistoryPdf(Equipment $equipment)
    {
        $technician = auth('technician')->user();

        // Technicians have access to all equipment across all offices
        $equipment->load([
            'office', 
            'history' => function($query) {
                $query->orderBy('created_at', 'asc');
            }, 
            'history.user',
            'history.corrections' => function($query) {
                $query->where('type', 'equipment_history_corrected')
                      ->with('user');
            }
        ]);

        $pdf = Pdf::loadView('reports.equipment_history_pdf', [
            'equipment' => $equipment,
            'generated_at' => now()->format('M d, Y H:i'),
            'generated_by' => $technician->name
        ]);

        return $pdf->download('equipment-history-' . $equipment->id . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
