<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;

class PublicEquipmentController extends Controller
{
    /**
     * Show the public QR scanner page
     */
    public function scanner()
    {
        return view('equipment.public-qr-scanner');
    }

    /**
     * Process scanned QR code data (public access)
     */
    public function scanQrCode(Request $request)
    {
        // Check if equipment_id is provided (for direct URL access)
        if ($request->has('equipment_id')) {
            $equipment = Equipment::with('office', 'equipmentType')->find($request->equipment_id);

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found'
                ]);
            }

            // Return public equipment data
            return response()->json([
                'success' => true,
                'equipment' => [
                    'id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'status' => $equipment->status,
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'qr_code_image_path' => $equipment->qr_code_image_path,
                ]
            ]);
        }

        // Legacy QR data parsing (for backward compatibility)
        $qrData = $request->input('qr_data');

        // Parse QR data
        if (is_string($qrData)) {
            $qrData = json_decode($qrData, true);
        }

        if (!$qrData || !isset($qrData['id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code data'
            ]);
        }

        $equipment = Equipment::with('office', 'equipmentType')->find($qrData['id']);

        if (!$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Equipment not found'
            ]);
        }

        // Return public equipment data
        return response()->json([
            'success' => true,
            'equipment' => [
                'id' => $equipment->id,
                'model_number' => $equipment->model_number,
                'serial_number' => $equipment->serial_number,
                'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                'status' => $equipment->status,
                'office' => $equipment->office ? $equipment->office->name : 'N/A',
                'qr_code_image_path' => $equipment->qr_code_image_path,
            ]
        ]);
    }
}
