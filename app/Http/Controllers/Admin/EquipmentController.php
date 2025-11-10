<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\EquipmentHistory;
use App\Models\Office;
use App\Models\Staff;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
        $this->middleware('permission:history.create')->only(['createHistory']);
        $this->middleware('permission:history.store')->only(['storeHistory']);
        $this->middleware('permission:qr.scan')->only(['qrScanner']);
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

        return view('equipment.form_modal', compact('equipment', 'equipmentTypes', 'campuses', 'categories', 'offices', 'staff'));
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
            'status' => 'nullable|in:serviceable,for_repair,defective', // Optional for new equipment
            'condition' => 'nullable|in:good,not_working', // Optional for new equipment
            'notes' => 'nullable|string',
        ]);

        // Auto-set condition based on status if not provided
        if (empty($validated['condition']) && isset($validated['status'])) {
            $validated['condition'] = $validated['status'] === 'serviceable' ? 'good' : 'not_working';
        }

        // For new equipment (when status/condition fields are hidden), set default values
        if (!array_key_exists('status', $validated) || empty($validated['status'])) {
            $validated['status'] = 'serviceable';
            $validated['condition'] = 'good';   
        }

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

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        return redirect()->route($prefix . '.equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    public function show(Request $request, Equipment $equipment)
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
                // Generate QR code with URL that opens public scanner
                $qrUrl = route('public.qr-scanner') . '?id=' . $equipment->id;

                $qrSize = '200x200';
                $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrUrl) . "&size={$qrSize}&format=png";

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

        if (request()->ajax()) {
            // Return partial view for modal
            $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
            return view('equipment.show_modal', compact('equipment', 'prefix'));
        }

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
        return view('equipment.show_modal', compact('equipment', 'prefix'));
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

        if (request()->ajax()) {
            // Return partial view for modal
            return view('equipment.form_modal', compact('equipment', 'equipmentTypes', 'campuses', 'categories', 'offices'));
        }

        return view('equipment.form_modal', compact('equipment', 'equipmentTypes', 'campuses', 'categories', 'offices'));
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
            'condition' => 'nullable|in:good,not_working', // Now optional - auto-set based on status
            'notes' => 'nullable|string',
        ]);

        // Auto-set condition based on status if not provided
        if (empty($validated['condition'])) {
            $validated['condition'] = $validated['status'] === 'serviceable' ? 'good' : 'not_working';
        }

        $equipment->update($validated);

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        return redirect()->route($prefix . '.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->forceDelete();

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        return redirect()->route($prefix . '.equipment.index')
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

    public function createHistory(Equipment $equipment)
    {
        // Permission check
        if (!auth()->user()->hasPermissionTo('history.create')) {
            abort(403);
        }

        if (request()->ajax()) {
            // Return partial view for modal
            return view('equipment.history_modal', compact('equipment'));
        }

        // Load the view
        return view('equipment.history.create', [
            'equipment' => $equipment->load('office')
        ]);
    }

    public function storeHistory(Request $request, Equipment $equipment)
    {
        // Permission check
        if (!auth()->user()->hasPermissionTo('history.store')) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:now',
            'jo_sequence' => 'required|string|max:2|regex:/^[0-9]{1,2}$/',
            'action_taken' => 'required|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'equipment_status' => 'required|in:serviceable,for_repair,defective',
        ]);

        // Build the full JO number from date and sequence
        $date = \Carbon\Carbon::parse($validated['date']);
        $joNumber = 'JO-' . $date->format('Y-m-d') . '-' . str_pad($validated['jo_sequence'], 2, '0', STR_PAD_LEFT);

        // Check if JO number already exists
        if (\App\Models\EquipmentHistory::where('jo_number', $joNumber)->exists()) {
            return back()->withErrors(['jo_sequence' => 'This Job Order number already exists. Please choose a different sequence.'])
                         ->withInput();
        }

        // Additional validation: prevent backdating beyond latest repair
        $selectedDateTime = new \DateTime($validated['date']);
        $latestRepair = \App\Models\EquipmentHistory::where('equipment_id', $equipment->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestRepair) {
            $latestDateTime = new \DateTime($latestRepair->date);
            if ($selectedDateTime < $latestDateTime) {
                return back()
                    ->withInput()
                    ->with('error', 'Cannot backdate beyond the latest repair record for this equipment.');
            }
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $user = auth()->user();
            $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

            $history = new \App\Models\EquipmentHistory([
                'equipment_id' => $equipment->id,
                'user_id' => $user->id,
                'date' => $validated['date'],
                'jo_number' => $joNumber,
                'action_taken' => $validated['action_taken'],
                'remarks' => $validated['remarks'],
                'responsible_person' => $user->name,
            ]);

            $history->save();

            // Update equipment status
            $updateData = [
                'status' => $validated['equipment_status'],
                'assigned_by_id' => $user->id,
            ];

            // If setting status to serviceable, also set condition to good
            if ($validated['equipment_status'] === 'serviceable') {
                $updateData['condition'] = 'good';
            }

            $equipment->update($updateData);

            \Illuminate\Support\Facades\DB::commit();

            $successMessage = 'History sheet saved!';
            $statusText = ucfirst(str_replace('_', ' ', $validated['equipment_status']));
            $successMessage .= ' Equipment status updated to ' . $statusText . '.';

            // Add condition update message if status was set to serviceable
            if ($validated['equipment_status'] === 'serviceable') {
                $successMessage .= ' Equipment condition set to Good.';
            }

            return redirect()
                ->route($prefix . '.equipment.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Failed to add history entry. Please try again.');
        }
    }

    public function generateJONumber(Request $request, Equipment $equipment)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $date = $request->date;

            // Find the next sequence number for this date
            $latestJO = EquipmentHistory::where('jo_number', 'like', 'JO-' . $date . '-%')
                ->orderBy('jo_number', 'desc')
                ->first();

            $sequence = 1;
            if ($latestJO) {
                // Extract sequence from latest JO number (format: JO-YYYY-MM-DD-XX)
                $parts = explode('-', $latestJO->jo_number);
                if (count($parts) >= 4) {
                    $sequence = (int) end($parts) + 1;
                }
            }

            // Format sequence with leading zero if needed
            $sequenceFormatted = str_pad($sequence, 2, '0', STR_PAD_LEFT);

            $joNumber = 'JO-' . $date . '-' . $sequenceFormatted;

            // Double-check uniqueness (in case of concurrent requests)
            $exists = EquipmentHistory::where('jo_number', $joNumber)->exists();
            if ($exists) {
                // Find next available sequence
                for ($i = $sequence + 1; $i <= 99; $i++) {
                    $sequenceFormatted = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $joNumber = 'JO-' . $date . '-' . $sequenceFormatted;
                    if (!EquipmentHistory::where('jo_number', $joNumber)->exists()) {
                        break;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'jo_number' => $joNumber
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating JO number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkLatestRepair(Request $request, Equipment $equipment)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $selectedDateTime = new \DateTime($request->date);

            // Find the latest repair record for this equipment
            $latestRepair = EquipmentHistory::where('equipment_id', $equipment->id)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestRepair) {
                $latestDateTime = new \DateTime($latestRepair->date);

                // If trying to set a date earlier than the latest repair, prevent it
                if ($selectedDateTime < $latestDateTime) {
                    return response()->json([
                        'can_backdate' => false,
                        'latest_date' => $latestDateTime->format('Y-m-d\TH:i'),
                        'message' => 'Cannot backdate beyond the latest repair record.'
                    ]);
                }
            }

            return response()->json([
                'can_backdate' => true
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking latest repair: ' . $e->getMessage()
            ], 500);
        }
    }

    public function qrScanner()
    {
        return view('qr-scanner');
    }

    public function downloadQrCode(Equipment $equipment)
    {
        // Generate QR code if it doesn't exist
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Generate QR code using QRServer API with URL for public scanner
        $qrUrl = route('public.qr-scanner') . '?id=' . $equipment->id;

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrUrl) . "&size=300x300&format=svg";

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

        // Check if QR data is a URL with query parameters
        if (is_string($qrData) && strpos($qrData, '?') !== false) {
            // Parse URL query parameters
            $parsedUrl = parse_url($qrData);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['id'])) {
                    $equipment = Equipment::find($queryParams['id']);
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
                            'office' => $equipment->office ? $equipment->office->name : 'N/A',
                            'status' => $equipment->status,
                            'qr_code_image_path' => $equipment->qr_code_image_path,
                        ]
                    ]);
                }
            }
        }

        // Parse QR data as JSON (legacy format)
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
                'office' => $equipment->office ? $equipment->office->name : 'N/A',
                'status' => $equipment->status,
                'qr_code_image_path' => $equipment->qr_code_image_path,
            ]
        ]);
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

        // Generate QR code using QRServer API with URL for public scanner
        $qrUrl = route('public.qr-scanner') . '?id=' . $equipment->id;

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrUrl) . "&size=300x300&format=svg";

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            return response($response->body())->header('Content-Type', 'image/svg+xml');
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
    }
}
