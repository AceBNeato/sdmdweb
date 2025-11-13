<?php

namespace App\Http\Controllers\User;

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
use App\Services\StoredProcedureService;
use App\Services\QrCodeService;

class EquipmentController extends Controller
{
    protected $qrCodeService;
    protected $storedProcedureService;

    public function __construct(QrCodeService $qrCodeService, StoredProcedureService $storedProcedureService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->storedProcedureService = $storedProcedureService;

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
        $query = Equipment::with('office', 'category', 'equipmentType');

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
            'brand' => 'required|string|max:100',
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

        // Log the activity
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'equipment.store',
            'description' => "Created new equipment: {$equipment->model_number} ({$equipment->serial_number})"
        ]);

        // Generate and save QR code using optimized service
        $qrData = [
            'type' => 'equipment_url',
            'url' => route('public.qr-scanner') . '?id=' . $equipment->id,
            'equipment_id' => $equipment->id,
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
        ];

        try {
            $qrPath = $this->qrCodeService->generateQrCode($qrData, '200x200', 'svg');
            if ($qrPath) {
                $equipment->update(['qr_code_image_path' => $qrPath]);
                Log::info('QR code generated and saved for new equipment', [
                    'equipment_id' => $equipment->id,
                    'qr_path' => $qrPath
                ]);
            } else {
                Log::warning('Failed to generate QR code for equipment ID: ' . $equipment->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - ' . $e->getMessage());
        }

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment added successfully.',
                'redirect' => route($prefix . '.equipment.index'),
                'equipment' => [
                    'id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'status' => $equipment->status,
                    'condition' => $equipment->condition,
                    'created_at' => $equipment->created_at
                ]
            ]);
        }

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
                $qrData = [
                    'url' => route('public.qr-scanner') . '?id=' . $equipment->id,
                    'equipment_id' => $equipment->id,
                    'model' => $equipment->equipment_model, // Use concatenated brand + model_number
                    'serial' => $equipment->serial_number,
                    'type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'status' => $equipment->status,
                ];

                $qrPath = $this->qrCodeService->generateQrCode($qrData, '200x200', 'svg');

                if ($qrPath) {
                    $equipment->update(['qr_code_image_path' => $qrPath]);
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
            'brand' => 'required|string|max:100',
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'office_id' => 'required|exists:offices,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:serviceable,for_repair,defective',
            'condition' => 'nullable|in:good,not_working', // Now optional - auto-set based on status
            'notes' => 'nullable|string',
        ]);

        // Auto-set condition based on status if not provided and status is set
        if (empty($validated['condition']) && isset($validated['status']) && !is_null($validated['status'])) {
            $validated['condition'] = $validated['status'] === 'serviceable' ? 'good' : 'not_working';
        }

        $equipment->update($validated);

        // Log the activity
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'equipment.update',
            'description' => "Updated equipment: {$equipment->equipment_model} ({$equipment->serial_number})"
        ]);

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment updated successfully.',
                'redirect' => route($prefix . '.equipment.index')
            ]);
        }

        return redirect()->route($prefix . '.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        // Log the activity before deletion
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'equipment.destroy',
            'description' => "Deleted equipment: {$equipment->equipment_model} ({$equipment->serial_number})"
        ]);

        $equipment->forceDelete();

        $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment deleted successfully.',
                'redirect' => route($prefix . '.equipment.index')
            ]);
        }

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

        // Log the activity
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'status.update',
            'description' => "Updated equipment status to {$request->status}: {$equipment->equipment_model} ({$equipment->serial_number})"
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
            'date' => 'required|date',
            'jo_number' => 'required|string|max:20|unique:equipment_history,jo_number',
            'action_taken' => 'required|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'equipment_status' => 'required|in:serviceable,for_repair,defective',
        ]);

        // JO number is now auto-generated and validated
        $joNumber = $validated['jo_number'];

        // Check if JO number already exists (additional check)
        if (\App\Models\EquipmentHistory::where('jo_number', $joNumber)->exists()) {
            return back()->withErrors(['jo_number' => 'This Job Order number already exists. Please try again.'])
                         ->withInput();
        }

        try {
            $user = auth()->user();
            $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');

            // Use stored procedure to create history and update equipment status
            $historyData = [
                'equipment_id' => $equipment->id,
                'user_id' => $user->id,
                'date' => $validated['date'],
                'jo_number' => $joNumber,
                'action_taken' => $validated['action_taken'],
                'remarks' => $validated['remarks'],
                'responsible_person' => $user->name,
                'equipment_status' => $validated['equipment_status'],
                'assigned_by_id' => $user->id
            ];

            $success = $this->storedProcedureService->createEquipmentHistory($historyData);

            if (!$success) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create history entry. Please try again.'
                    ], 422);
                }
                return back()
                    ->withInput()
                    ->with('error', 'Failed to create history entry. Please try again.');
            }

            $successMessage = 'History sheet saved!';
            $statusText = ucfirst(str_replace('_', ' ', $validated['equipment_status']));
            $successMessage .= ' Equipment status updated to ' . $statusText . '.';

            // Add condition update message if status was set to serviceable
            if ($validated['equipment_status'] === 'serviceable') {
                $successMessage .= ' Equipment condition set to Good.';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'redirect' => route($prefix . '.equipment.index')
                ]);
            }

            return redirect()
                ->route($prefix . '.equipment.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to add history entry. Please try again.'
                ], 500);
            }
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
            $yearMonth = date('y-m', strtotime($date)); // YY-MM format

            // Retry logic for handling concurrent requests
            $maxRetries = 5;
            $retryCount = 0;
            $joNumber = null;

            while ($retryCount < $maxRetries && !$joNumber) {
                // Find the next sequence number for this month (resets monthly)
                $latestJO = \App\Models\EquipmentHistory::where('jo_number', 'like', 'JO-' . $yearMonth . '-%')
                    ->orderBy('jo_number', 'desc')
                    ->first();

                $sequence = 1;
                if ($latestJO) {
                    // Extract sequence from latest JO number (format: JO-YY-MM-XXX)
                    $parts = explode('-', $latestJO->jo_number);
                    if (count($parts) >= 3) {
                        $sequence = (int) end($parts) + 1;
                    }
                }

                // Try to find the next available sequence
                for ($i = $sequence; $i <= 999; $i++) {
                    $sequenceFormatted = str_pad($i, 3, '0', STR_PAD_LEFT);
                    $candidateJONumber = 'JO-' . $yearMonth . '-' . $sequenceFormatted;

                    // Check if this number is available
                    if (!\App\Models\EquipmentHistory::where('jo_number', $candidateJONumber)->exists()) {
                        $joNumber = $candidateJONumber;
                        break;
                    }
                }

                if (!$joNumber) {
                    // No available numbers found, this shouldn't happen
                    break;
                }

                $retryCount++;
            }

            if (!$joNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to generate unique JO number - all numbers for this month are taken'
                ], 500);
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
            $result = $this->storedProcedureService->canBackdateRepair($equipment->id, $request->date);

            return response()->json($result);

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
        $selectedOfficeId = $request->get('office_id', 'all');

        $query = Equipment::with(['office', 'equipmentType']);

        if ($selectedOfficeId !== 'all' && $selectedOfficeId !== null) {
            $query->where('office_id', $selectedOfficeId);
        }

        $equipment = $query
            ->orderBy('office_id')
            ->orderBy('model_number')
            ->get();

        $campuses = Campus::with(['offices' => function ($query) {
            $query->where('is_active', true)->orderBy('name');
        }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $viewData = [
            'campuses' => $campuses,
            'equipment' => $equipment,
            'selectedOfficeId' => $selectedOfficeId,
            'routePrefix' => 'admin',
            'printPdfRoute' => route('admin.equipment.print-qrcodes.pdf'),
        ];

        if ($request->ajax()) {
            return view('equipment.print-qrcodes_modal', $viewData);
        }

        $redirectParams = array_filter([
            'print_qrcodes' => 1,
            'office_id' => $selectedOfficeId !== 'all' ? $selectedOfficeId : null,
        ], static function ($value) {
            return $value !== null;
        });

        return redirect()->route('admin.equipment.index', $redirectParams);
    }

    public function printQrcodesPdf(Request $request)
    {
        $equipmentIdsParam = $request->input('equipment_ids', []);

        if (is_string($equipmentIdsParam)) {
            $equipmentIds = array_filter(array_map('trim', explode(',', $equipmentIdsParam)));
        } elseif (is_array($equipmentIdsParam)) {
            $equipmentIds = array_filter($equipmentIdsParam);
        } else {
            $equipmentIds = [];
        }

        if (empty($equipmentIds)) {
            return redirect()
                ->route('admin.equipment.index')
                ->with('error', 'Please select at least one equipment to print.');
        }

        $equipments = Equipment::with(['office', 'equipmentType'])
            ->whereIn('id', $equipmentIds)
            ->orderBy('office_id')
            ->orderBy('model_number')
            ->get();

        if ($equipments->isEmpty()) {
            return redirect()
                ->route('admin.equipment.index')
                ->with('error', 'Selected equipment could not be found.');
        }

        $generatedAt = now();
        $generatedBy = optional(auth()->user())->name ?? 'SDMD System';

        return view('equipment.qr-code-pdf', [
            'equipments' => $equipments,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'routePrefix' => 'admin',
        ]);
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
                        'description' => "Scanned QR code for equipment: {$equipment->equipment_model}",
                        'metadata' => ['equipment_id' => $equipment->id]
                    ]);

                    return response()->json([
                        'success' => true,
                        'equipment' => [
                            'id' => $equipment->id,
                            'model_number' => $equipment->equipment_model, // Use concatenated brand + model_number
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

        if (is_array($qrData) && isset($qrData['equipment_id']) && !isset($qrData['id'])) {
            $qrData['id'] = $qrData['equipment_id'];
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
            'description' => "Scanned QR code for equipment: {$equipment->equipment_model}",
            'metadata' => ['equipment_id' => $equipment->id]
        ]);

        return response()->json([
            'success' => true,
            'equipment' => [
                'id' => $equipment->id,
                'model_number' => $equipment->equipment_model, // Use concatenated brand + model_number
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

        // Prepare QR data for viewing (Admin uses URL-based QR codes for public scanner)
        $qrData = [
            'type' => 'equipment_url',
            'url' => route('public.qr-scanner') . '?id=' . $equipment->id,
            'equipment_id' => $equipment->id,
            'model' => $equipment->equipment_model, // Use concatenated brand + model_number
            'serial' => $equipment->serial_number,
            'type_name' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'generated_at' => now()->toISOString(),
        ];

        // Use cached QR code service
        $qrPath = $this->qrCodeService->generateQrCode($qrData, '300x300', 'svg');

        if ($qrPath && Storage::disk('public')->exists($qrPath)) {
            // Save path to equipment for future use
            $equipment->update(['qr_code_image_path' => $qrPath]);
            return response()->file(public_path('storage/' . $qrPath));
        }

        // Fallback: return a simple text response
        return response('QR Code not available')->header('Content-Type', 'text/plain');
    }
}
