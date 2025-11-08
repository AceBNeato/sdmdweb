<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Office;
use App\Models\Staff;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:equipment.view')->only(['index', 'show']);
        $this->middleware('permission:equipment.create')->only(['create', 'store']);
        $this->middleware('permission:equipment.edit')->only(['edit', 'update']);
        $this->middleware('permission:equipment.delete')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $query = Equipment::with('office', 'category');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by equipment type
        if ($request->has('equipment_type') && $request->equipment_type !== 'all') {
            $query->where('equipment_type_id', $request->equipment_type);
        }

        // Filter by office
        if ($request->has('office_id') && $request->office_id !== 'all') {
            $query->where('office_id', $request->office_id);
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $equipment = $query->latest()->paginate(10)->appends($request->query());

        $equipmentTypes = \App\Models\EquipmentType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');

        return view('equipment.index', compact('equipment', 'equipmentTypes', 'campuses', 'categories'));
    }

    public function create()
    {
        $equipment = new Equipment(); // Create empty equipment instance for form

        $equipmentTypes = \App\Models\EquipmentType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();
        $categories = \App\Models\Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $staff = collect(); // Empty collection for create - staff will be loaded via AJAX

        return view('equipment.form', compact('equipment', 'equipmentTypes', 'campuses', 'categories', 'offices', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment',
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'office_id' => 'required|exists:offices,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:serviceable,for_repair,defective',
            'condition' => 'required|in:good,not_working',
            'notes' => 'nullable|string',
        ]);

        $equipment = Equipment::create($validated);

        // Generate and save QR code using QRServer API
        $qrData = json_encode([
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
        ]);

        try {
            $qrSize = '200x200';
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size={$qrSize}&format=png";

            $response = Http::get($apiUrl);

            if ($response->successful()) {
                $fileName = 'equipment_' . $equipment->id . '.png';
                $path = 'qrcodes/' . $fileName;
                Storage::disk('public')->put($path, $response->body());

                // Update equipment with QR code image path
                $equipment->update(['qr_code_image_path' => $path]);
            } else {
                Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - API returned status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - ' . $e->getMessage());
        }

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    public function show(Equipment $equipment)
    {
        $equipment->load('office', 'equipmentType');

        // Generate QR code if missing
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Generate QR code image if missing
        if (!$equipment->qr_code_image_path || !Storage::disk('public')->exists($equipment->qr_code_image_path)) {
            try {
                $qrData = json_encode([
                    'id' => $equipment->id,
                    'type' => 'equipment',
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'status' => $equipment->status,
                ]);

                $qrSize = '200x200';
                $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size={$qrSize}&format=png";

                $response = Http::get($apiUrl);

                if ($response->successful()) {
                    $fileName = 'equipment_' . $equipment->id . '.png';
                    $path = 'qrcodes/' . $fileName;
                    Storage::disk('public')->put($path, $response->body());
                    $equipment->update(['qr_code_image_path' => $path]);
                }
            } catch (\Exception $e) {
                // QR code generation failed, but continue showing the page
            }
        }

        return view('equipment.show', compact('equipment'));
    }

    public function edit(Equipment $equipment)
    {
        $equipmentTypes = \App\Models\EquipmentType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id');

        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        $offices = Office::where('is_active', true)->orderBy('name')->get();

        return view('equipment.form', compact('equipment', 'equipmentTypes', 'campuses', 'categories', 'offices'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'office_id' => 'required|exists:offices,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:serviceable,for_repair,defective',
            'condition' => 'required|in:good,not_working',
            'notes' => 'nullable|string',
        ]);

        $equipment->update($validated);

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->forceDelete();

        return redirect()->route('admin.equipment.index')
            ->with('success', 'Equipment deleted successfully.');
    }

    public function updateStatus(Request $request, Equipment $equipment)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                Equipment::STATUS_SERVICEABLE,
                Equipment::STATUS_FOR_REPAIR,
                Equipment::STATUS_DEFECTIVE
            ])
        ]);

        $equipment->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipment status updated successfully.'
        ]);
    }

    protected function getEquipmentTypes()
    {
        return [
            'laptop' => 'Laptop',
            'desktop' => 'Desktop Computer',
            'tablet' => 'Tablet',
            'printer' => 'Printer',
            'scanner' => 'Scanner',
            'projector' => 'Projector',
            'monitor' => 'Monitor',
            'server' => 'Server',
            'network' => 'Network Device',
            'audio' => 'Audio Equipment',
            'video' => 'Video Equipment',
            'other' => 'Other Equipment',
        ];
    }

    public function qrCode(Equipment $equipment)
    {
        // If QR code image is saved, return it
        if ($equipment->qr_code_image_path && Storage::disk('public')->exists($equipment->qr_code_image_path)) {
            return response()->file(public_path('storage/' . $equipment->qr_code_image_path));
        }

        // Fallback to generating on-the-fly if no saved image
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Generate QR code using QRServer API
        $qrData = json_encode([
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'url' => route('admin.equipment.show', $equipment),
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->toISOString(),
        ]);

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size=300x300&format=svg";

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            return response($response->body())->header('Content-Type', 'image/svg+xml');
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
    }

    public function downloadQrCode(Equipment $equipment)
    {
        // Generate QR code if it doesn't exist
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Generate QR code using QRServer API
        $qrData = json_encode([
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'url' => route('admin.equipment.show', $equipment),
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->toISOString(),
        ]);

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size=300x300&format=svg";

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $filename = 'qr-code-' . Str::slug($equipment->model_number . '-' . $equipment->serial_number) . '.svg';
            return response($response->body())
                ->header('Content-Type', 'image/svg+xml')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
    }

    public function getOfficesByCampus(Request $request)
    {
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json(['offices' => []]);
        }

        $offices = Office::where('campus_id', $campusId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'campus_id']);

        return response()->json(['offices' => $offices]);
    }

    public function printQrcodes(Request $request)
    {
        $query = Equipment::with('office');

        // Filter by office
        if ($request->has('office_id') && $request->office_id !== 'all') {
            $query->where('office_id', $request->office_id);
        }

        $equipment = $query->latest()->get();

        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        return view('equipment.print-qrcodes', compact('equipment', 'campuses'));
    }

    public function scanView()
    {
        return view('equipment.scan');
    }

    public function scanQrCode(Request $request)
    {
        $qrData = $request->input('qr_data');

        // Parse QR data
        if (is_string($qrData)) {
            $qrData = json_decode($qrData, true);
        }

        if (!$qrData || !isset($qrData['id'])) {
            return response()->json(['success' => false, 'message' => 'Invalid QR code data']);
        }

        $equipment = Equipment::find($qrData['id']);

        if (!$equipment) {
            return response()->json(['success' => false, 'message' => 'Equipment not found']);
        }

        // Log the scan
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'scanned_qr',
            'description' => "Scanned QR code for equipment: {$equipment->model_number}",
            'metadata' => ['equipment_id' => $equipment->id]
        ]);

        return response()->json([
            'success' => true,
            'equipment' => [
                'id' => $equipment->id,
                'model_number' => $equipment->model_number,
                'serial_number' => $equipment->serial_number,
                'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                'location' => $equipment->location,
                'office' => $equipment->office->name ?? 'N/A',
                'status' => $equipment->status,
                'qr_code_image_path' => $equipment->qr_code_image_path,
                'maintenance_logs' => $equipment->maintenanceLogs()->latest()->take(5)->get(['action', 'details', 'created_at'])->toArray()
            ]
        ]);
    }
}
