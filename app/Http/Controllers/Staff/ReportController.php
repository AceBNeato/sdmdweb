<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:staff');
    }

    /**
     * Display the staff's dashboard with reports overview
     */
    public function index(Request $request)
    {
        $staff = auth('staff')->user();

        $equipmentCount = Equipment::where('office_id', $staff->office_id)
            ->count();

        // Start with a query that groups by equipment_id for staff's office only
        $query = EquipmentHistory::with(['equipment', 'equipment.office', 'user'])
            ->selectRaw('equipment_id, MAX(created_at) as latest_updated_at, COUNT(*) as total_entries')
            ->whereHas('equipment', function($q) use ($staff) {
                $q->where('office_id', $staff->office_id);
            })
            ->groupBy('equipment_id');

        // Office filter (only staff's office)
        if ($request->filled('office_id')) {
            $query->whereHas('equipment', function($q) use ($request, $staff) {
                $q->where('office_id', $staff->office_id);
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
                ->orderBy('created_at', 'desc')
                ->first();

            $group->equipment = $equipment;
            $group->latest_entry = $latestHistory;
            $group->total_entries = (int) $group->total_entries;
            
            return $group;
        });

        // Get recent history (first 5 equipment groups)
        $recentHistory = EquipmentHistory::with(['equipment', 'user'])
            ->whereHas('equipment', function($q) use ($staff) {
                $q->where('office_id', $staff->office_id);
            })
            ->orderBy('created_at', 'desc')
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
        $staff = auth('staff')->user();

        $query = Equipment::with(['history' => function($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->where('office_id', $staff->office_id);

        if ($request->has('equipment_id')) {
            $query->where('id', $request->equipment_id);
        }

        $equipment = $query->get();

        if ($request->has('export') && $request->export === 'pdf') {
            $pdf = PDF::loadView('reports.equipment_history_pdf', [
                'equipment' => $equipment,
                'office' => $staff->office
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
        $staff = auth('staff')->user();

        // Ensure staff can only view equipment from their office
        if ($equipment->office_id !== $staff->office_id) {
            abort(403, 'Unauthorized access to equipment.');
        }

        $equipment->load(['office', 'equipmentType', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'history.user']);

        return view('reports.history', compact('equipment'));
    }

    public function exportEquipmentHistory(Equipment $equipment)
    {
        $staff = auth('staff')->user();

        // Ensure staff can only export equipment from their office
        if ($equipment->office_id !== $staff->office_id) {
            abort(403, 'Unauthorized access to equipment.');
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
     * Export equipment history as PDF
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function exportEquipmentHistoryPdf(Equipment $equipment)
    {
        $staff = auth('staff')->user();

        // Ensure staff can only export equipment from their office
        if ($equipment->office_id !== $staff->office_id) {
            abort(403, 'Unauthorized access to equipment.');
        }

        $equipment->load(['office', 'history' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'history.user']);

        $pdf = Pdf::loadView('reports.equipment_history_pdf', [
            'equipment' => $equipment,
            'generated_at' => now()->format('M d, Y H:i'),
            'generated_by' => $staff->name
        ]);

        return $pdf->download('equipment-history-' . $equipment->id . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
