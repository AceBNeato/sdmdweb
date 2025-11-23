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
    public function index()
    {
        $technician = auth('technician')->user();
        
        // Technicians have access to all equipment across all offices (like admin)
        $equipmentCount = Equipment::count();
            
        // Get paginated equipment history - technicians see all history like admin
        $equipmentHistory = EquipmentHistory::with(['equipment', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        // Get recent history (first page of paginated results) - technicians see all history like admin
        $recentHistory = EquipmentHistory::with(['equipment', 'user'])
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
            $query->orderBy('created_at', 'desc');
        }, 'history.user']);

        return view('reports.history', compact('equipment'));
    }
    public function exportEquipmentHistory(Equipment $equipment)
    {
        $technician = auth('technician')->user();

        // Technicians have access to all equipment across all offices
        $equipment->load([
            'office', 
            'history' => function($query) {
                $query->orderBy('created_at', 'desc');
            }, 
            'history.user',
            'history.corrections' => function($query) {
                $query->where('type', 'equipment_history_corrected')
                      ->with('user');
            }
        ]);

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
        $technician = auth('technician')->user();

        // Technicians have access to all equipment across all offices
        $equipment->load([
            'office', 
            'history' => function($query) {
                $query->orderBy('created_at', 'desc');
            }, 
            'history.user',
            'history.corrections' => function($query) {
                $query->where('type', 'equipment_history_corrected')
                      ->with('user');
            }
        ]);

        $pdf = Pdf::loadView('reports.ict_history_sheet', [
            'equipment' => $equipment,
            'history' => $equipment->history
        ]);

        return $pdf->download('equipment-history-' . $equipment->id . '-' . now()->format('Y-m-d') . '.pdf');
    }
}
