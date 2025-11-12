<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\PdfService;
use App\Models\Equipment; // Assuming you have an Equipment model
use App\Models\EquipmentHistory; // Assuming you have an EquipmentHistory model
use Illuminate\Http\Request;

class EquipmentReportController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
        $this->middleware('auth');
    }

    public function historySheet($id)
    {
        // Get equipment details
        $equipment = Equipment::with('history')->findOrFail($id);

        // Format history data
        $historyData = $equipment->history->map(function($item) {
            return [
                'date' => $item->created_at->format('Y-m-d'),
                'jo_number' => $item->jo_number,
                'action_taken' => $item->action_taken,
                'remarks' => $item->remarks,
                'responsible_person' => $item->responsible_person
            ];
        })->toArray();

        // Generate PDF
        $pdf = $this->pdfService->generateEquipmentHistorySheet(
            $equipment->name,
            $equipment->serial_number,
            $equipment->location,
            $historyData
        );

        // Output PDF to browser
        return response($pdf->Output('equipment_history_'.$equipment->id.'.pdf', 'I'))
            ->header('Content-Type', 'application/pdf');
    }
}
