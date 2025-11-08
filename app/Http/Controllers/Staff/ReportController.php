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
    public function index()
    {
        $staff = auth('staff')->user();

        $equipmentCount = Equipment::where('office_id', $staff->office_id)
            ->count();

        // Get paginated equipment history
        $equipmentHistory = EquipmentHistory::whereHas('equipment', function($q) use ($staff) {
                $q->where('office_id', $staff->office_id);
            })
            ->with(['equipment', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get recent history (first page of paginated results)
        $recentHistory = EquipmentHistory::whereHas('equipment', function($q) use ($staff) {
                $q->where('office_id', $staff->office_id);
            })
            ->with(['equipment', 'user'])
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

        $pdf = Pdf::loadView('reports.ict_history_sheet', [
            'equipment' => $equipment,
            'history' => $equipment->history
        ]);

        return $pdf->download('equipment-history-' . $equipment->id . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
