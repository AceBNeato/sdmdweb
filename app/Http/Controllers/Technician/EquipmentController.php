<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Equipment;
use App\Models\Office;
use App\Models\Campus;
use App\Models\Category;
use App\Models\EquipmentHistory;
use App\Models\EquipmentType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Activity;

class EquipmentController extends BaseController
{
    /**
     * Display the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View
     */
    public function show(Request $request, Equipment $equipment)
    {
        $user = Auth::guard('technician')->user();

        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.view')) {
        //     return redirect()->route('technician.equipment.index')
        //         ->with('error', 'You do not have permission to view equipment.');
        // }

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

        $prefix = 'technician'; // For technician routes

        if ($request->ajax()) {
            return view('equipment.show_modal', compact('equipment', 'prefix'));
        }

        return view('equipment.show_modal', compact('equipment', 'prefix'));
    }

    /**
     * Show the technician's equipment.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('technician.profile')
                ->with('error', 'Technician not found.');
        }

        // Permission check - allow access if technician is authenticated
        // Technicians have office-based access control
        // if ($user->user && !$user->user->can('equipment.view')) {
        //     return redirect()->route('technician.profile')
        //         ->with('error', 'You do not have permission to view equipment.');
        // }

        // Get equipment types for filter
        $equipmentTypes = \App\Models\EquipmentType::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Get campuses with their active offices for filter
        $campuses = \App\Models\Campus::with(['offices' => function($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->orderBy('name')->get();

        // Get categories for filter
        $categories = Category::orderBy('name')->pluck('name', 'id');

        // Build equipment query with filters
        $query = Equipment::with(['office', 'equipmentType'])
            ->where('status', '!=', 'retired'); // Exclude retired equipment

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply equipment type filter
        if ($request->filled('equipment_type') && $request->equipment_type !== 'all') {
            $query->where('equipment_type_id', $request->equipment_type);
        }

        // Apply office filter
        if ($request->filled('office_id') && $request->office_id !== 'all') {
            $query->where('office_id', $request->office_id);
        }

        // Apply category filter
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // Order by creation date, newest first
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $equipment = $query->paginate(12)->appends($request->query());

        return view('equipment.index', compact('equipment', 'equipmentTypes', 'campuses', 'categories'));
    }

    /**
     * Show the form for creating a new equipment.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $user = Auth::guard('technician')->user();
        
        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.create')) {
        //     return redirect()->route('technician.equipment.index')
        //         ->with('error', 'You do not have permission to create equipment.');
        // }
        
        $equipment = new Equipment();
        $categories = Category::orderBy('name')->pluck('name', 'id');
        $equipmentTypes = EquipmentType::orderBy('name')->pluck('name', 'id');

        // Technicians can now create equipment for any office
        $campuses = \App\Models\Campus::with(['offices' => function($query) {
            $query->where('is_active', true);
        }])->get();
        
        return view('equipment.form_modal', compact('equipment', 'categories', 'equipmentTypes', 'campuses'));
    }

    /**
     * Store a newly created equipment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $user = Auth::guard('technician')->user();
        
        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.create')) {
        //     return redirect()->route('technician.equipment.index')
        //         ->with('error', 'You do not have permission to create equipment.');
        // }
        
        $validated = $request->validate([
            'brand' => 'required|string|max:100',
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment',
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:serviceable,for_repair,defective', // Optional for new equipment
            'condition' => 'nullable|in:good,not_working', // Optional for new equipment
            'office_id' => 'required|exists:offices,id',
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

        // Technicians can now create equipment for any office
        // if ($validated['office_id'] != $user->office_id) {
        //     return back()->withErrors(['office_id' => 'You can only create equipment for your assigned office.']);
        // }

        // QR code will be auto-generated by the model's creating event
        $equipment = Equipment::create($validated);

        // Log the activity using new method
        Activity::logEquipmentCreation($equipment);

        // Generate and save QR code using QRServer API with comprehensive structured data
        $qrData = json_encode([
            'id' => $equipment->id,
            'type' => 'equipment',
            'model' => $equipment->equipment_model, // Use concatenated brand + model_number
            'serial' => $equipment->serial_number,
            'type_name' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
        ]);
        $qrSize = '200x200';
        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qrData) . "&size={$qrSize}";

        $response = Http::get($apiUrl);
        if ($response->successful()) {
            $fileName = 'equipment_' . $equipment->id . '.png';
            $path = 'qrcodes/' . $fileName;
            Storage::disk('public')->put($path, $response->body());

            // Update equipment with QR code image path
            $equipment->update(['qr_code_image_path' => $path]);
        } else {
            Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment added successfully!',
                'redirect' => route('technician.equipment.index'),
                'equipment' => [
                    'id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'status' => $equipment->status,
                    'condition' => $equipment->condition,
                    'created_at' => $equipment->created_at
                ],
                'toast' => [
                    'type' => 'success',
                    'message' => 'Equipment added successfully.',
                ]
            ]);
        }

        return redirect()->route('technician.equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    /**
     * Show the form for editing the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Equipment $equipment)
    {
        $user = Auth::guard('technician')->user();

        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.edit')) {
        //     return redirect()->back()->with('error', 'You do not have permission to edit equipment.');
        // }

        // Check if technician has access to this equipment (must be in their office)
        // Technicians now have access to all equipment across all offices
        // if ($equipment->office_id !== $user->office_id) {
        //     return redirect()->back()->with('error', 'You do not have permission to edit this equipment.');
        // }

        $categories = Category::orderBy('name')->pluck('name', 'id');
        $equipmentTypes = EquipmentType::orderBy('name')->pluck('name', 'id')->toArray();

        // Technicians can now edit equipment for any office
        $campuses = \App\Models\Campus::with(['offices' => function($query) {
            $query->where('is_active', true);
        }])->get();

        if (request()->ajax()) {
            // Return partial view for modal
            return view('equipment.form_modal', compact('equipment', 'categories', 'equipmentTypes', 'campuses'));
        }

        return view('equipment.form_modal', compact('equipment', 'categories', 'equipmentTypes', 'campuses'));
    }

    /**
     * Update the specified equipment in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Equipment $equipment)
    {
        $user = Auth::guard('technician')->user();
        // Resolve the underlying users.id regardless of guard model shape
        $userId = $user->user_id ?? ($user->id ?? optional($user->user)->id);

        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.edit')) {
        //     return redirect()->back()->with('error', 'You do not have permission to update equipment.');
        // }

        // Check if technician has access to this equipment (must be in their office)
        // Technicians now have access to all equipment across all offices
        // if ($equipment->office_id !== $user->office_id) {
        //     return redirect()->back()->with('error', 'You do not have permission to update this equipment.');
        // }

        $validated = $request->validate([
            'brand' => 'required|string|max:100',
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:serviceable,for_repair,defective',
            'condition' => 'nullable|in:good,not_working', // Now optional - auto-set based on status
            'office_id' => 'required|exists:offices,id',
        ]);

        // Auto-set condition based on status if not provided and status is set
        if (empty($validated['condition']) && isset($validated['status']) && !is_null($validated['status'])) {
            $validated['condition'] = $validated['status'] === 'serviceable' ? 'good' : 'not_working';
        }

        // Track changes for logging
        $originalData = $equipment->getOriginal();
        $changes = [];

        // Add the user who is updating the equipment
        $validated['assigned_by_id'] = $userId;

        // Ensure technician can only update equipment for their office
        // if ($validated['office_id'] != $user->office_id) {
        //     return back()->withErrors(['office_id' => 'You can only update equipment for your assigned office.']);
        // }

        // Check if relevant fields were changed (status, condition, location, description)
        $fieldsToCheck = ['status', 'condition', 'description'];
        $hasRelevantChanges = false;

        foreach ($fieldsToCheck as $field) {
            if (isset($validated[$field]) && $equipment->{$field} !== $validated[$field]) {
                $hasRelevantChanges = true;
                break;
            }
        }

        $equipment->update($validated);

        // Track field changes
        foreach (['brand', 'model_number', 'serial_number', 'equipment_type_id', 'description', 'purchase_date', 'cost_of_purchase', 'category_id', 'status', 'condition', 'office_id'] as $field) {
            if ($originalData[$field] != $equipment->$field) {
                $oldValue = $originalData[$field];
                $newValue = $equipment->$field;
                
                if ($field === 'office_id') {
                    $oldOffice = \App\Models\Office::find($oldValue);
                    $newOffice = \App\Models\Office::find($newValue);
                    $changes[$field] = [
                        $oldOffice?->name ?? 'Unknown',
                        $newOffice?->name ?? 'Unknown'
                    ];
                } elseif ($field === 'category_id') {
                    $oldCategory = \App\Models\Category::find($oldValue);
                    $newCategory = \App\Models\Category::find($newValue);
                    $changes[$field] = [
                        $oldCategory?->name ?? 'Unknown',
                        $newCategory?->name ?? 'Unknown'
                    ];
                } elseif ($field === 'equipment_type_id') {
                    $oldType = \App\Models\EquipmentType::find($oldValue);
                    $newType = \App\Models\EquipmentType::find($newValue);
                    $changes[$field] = [
                        $oldType?->name ?? 'Unknown',
                        $newType?->name ?? 'Unknown'
                    ];
                } else {
                    $changes[$field] = [$oldValue, $newValue];
                }
            }
        }

        // Log the activity using new method
        Activity::logEquipmentUpdate($equipment, $changes);

        // If relevant fields were changed, set session flag to show history confirmation
        if ($hasRelevantChanges) {
            session(['equipment_updated_show_history_prompt' => $equipment->id]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment updated successfully!',
                'redirect' => route('technician.equipment.index'),
                'equipment' => [
                    'id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'status' => $equipment->status,
                    'condition' => $equipment->condition,
                    'updated_at' => $equipment->updated_at
                ]
            ]);
        }

        return redirect()->route('technician.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    /**
     * Get equipment for a specific office (AJAX endpoint).
     *
     * @param  int  $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOfficeEquipment($officeId)
    {
        try {
            $user = Auth::guard('technician')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Technician not authenticated'
                ], 401);
            }

            // Verify the office exists and is active
            $office = Office::where('id', $officeId)->where('is_active', true)->first();

            if (!$office) {
                return response()->json([
                    'success' => false,
                    'message' => 'Office not found or inactive'
                ], 404);
            }

            // Get equipment for this office
            $equipment = Equipment::with('office')
                ->where('office_id', $officeId)
                ->where('status', '!=', 'retired') // Exclude retired equipment
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'model_number' => $item->model_number,
                        'serial_number' => $item->serial_number,
                        'equipment_type' => $item->equipmentType ? $item->equipmentType->name : 'N/A',
                        'status' => $item->status,
                        'location' => $item->location,
                        'qr_code_image_path' => $item->qr_code_image_path,
                        'qr_code' => $item->qr_code,
                        'created_at' => $item->created_at,
                        'office' => $item->office ? $item->office->name : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'equipment' => $equipment,
                'office' => [
                    'id' => $office->id,
                    'name' => $office->name,
                    'location' => $office->location,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading equipment data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the status of an equipment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $equipment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $equipment)
    {
        $user = Auth::guard('technician')->user();

        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.edit')) {
        //     return redirect()->back()->with('error', 'You do not have permission to update equipment status.');
        // }

        $equipment = Equipment::findOrFail($equipment);

        $oldStatus = $equipment->status;
        $oldNotes = $equipment->notes;

        $validated = $request->validate([
            'status' => 'required|in:available,in_use,maintenance,disposed',
            'notes' => 'nullable|string',
        ]);

        $equipment->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $equipment->notes,
        ]);

        // Log equipment status and notes changes
        $changes = [];
        if ($oldStatus !== $validated['status']) {
            $changes['status'] = [$oldStatus, $validated['status']];
        }
        if ($oldNotes !== ($validated['notes'] ?? $equipment->notes)) {
            $changes['notes'] = [$oldNotes, $validated['notes'] ?? $equipment->notes];
        }

        if (!empty($changes)) {
            Activity::logEquipmentUpdate($equipment, $changes);
        }

        return back()->with('success', 'Equipment status updated successfully!');
    }

    /**
     * Get equipment for a specific office assigned to the technician.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEquipmentByOffice(Request $request, $officeId)
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->get('per_page', 12); // Default 12 items per page

        $equipment = Equipment::where('office_id', $officeId)
            ->where('assigned_to_type', \App\Models\User::class)
            ->where('assigned_to_id', $user->id)
            ->with('category')
            ->paginate($perPage);

        return response()->json([
            'equipment' => $equipment->items(),
            'pagination' => [
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
                'per_page' => $equipment->perPage(),
                'total' => $equipment->total(),
                'from' => $equipment->firstItem(),
                'to' => $equipment->lastItem(),
                'has_pages' => $equipment->hasPages(),
                'next_page_url' => $equipment->nextPageUrl(),
                'prev_page_url' => $equipment->previousPageUrl(),
            ]
        ]);
    }

    /**
     * Decode QR code from uploaded image using QR Server API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function decodeQrCode(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        try {
            $image = $request->file('qr_image');

            // Store the uploaded image temporarily
            $tempPath = $image->store('temp', 'public');
            $imageUrl = asset('storage/' . $tempPath);

            // Use QR Server API to decode the QR code
            $apiUrl = "https://api.qrserver.com/v1/read-qr-code/?fileurl=" . urlencode($imageUrl);

            $response = Http::get($apiUrl);

            // Clean up the temporary file
            Storage::disk('public')->delete($tempPath);

            if ($response->successful()) {
                $result = $response->json();

                if (!empty($result) && isset($result[0]['symbol'][0]['data'])) {
                    $qrData = $result[0]['symbol'][0]['data'];

                    // Now process the decoded QR data using existing logic
                    return $this->processQrData($qrData);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No QR code found in the image'
                    ], 400);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode QR code from image'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process decoded QR data (extracted from decodeQrCode method)
     *
     * @param  string  $qrData
     * @return \Illuminate\Http\JsonResponse
     */
    private function processQrData($qrData)
    {
        try {
            // Check if it's a JSON format (new format)
            if (strpos($qrData, '{') === 0 || strpos($qrData, '"id"') !== false) {
                $parsedData = json_decode($qrData, true);

                if (!$parsedData) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR code format'
                    ], 400);
                }

                // Handle equipment_url type (current format used by technicians)
                if (isset($parsedData['type']) && $parsedData['type'] === 'equipment_url') {
                    if (!isset($parsedData['equipment_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Invalid QR code format - missing equipment_id'
                        ], 400);
                    }

                    $equipment = Equipment::with('office')
                        ->find($parsedData['equipment_id']);

                    if (!$equipment) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Equipment not found'
                        ], 404);
                    }

                    return response()->json([
                        'success' => true,
                        'equipment' => [
                            'id' => $equipment->id,
                            'model_number' => $equipment->model_number,
                            'serial_number' => $equipment->serial_number,
                            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
                            'status' => $equipment->status,
                            'condition' => $equipment->condition,
                            'location' => $equipment->location,
                            'office' => $equipment->office ? $equipment->office->name : 'N/A',
                            'qr_code' => $equipment->qr_code,
                            'qr_code_image_path' => $equipment->qr_code_image_path,
                        ]
                    ]);
                }

                // Handle direct id field (legacy format)
                if (isset($parsedData['id'])) {
                    $equipment = Equipment::with('office')
                        ->find($parsedData['id']);

                    if (!$equipment) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Equipment not found'
                        ], 404);
                    }

                    return response()->json([
                        'success' => true,
                        'equipment' => [
                            'id' => $equipment->id,
                            'model_number' => $equipment->model_number,
                            'serial_number' => $equipment->serial_number,
                            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
                            'status' => $equipment->status,
                            'condition' => $equipment->condition,
                            'location' => $equipment->location,
                            'office' => $equipment->office ? $equipment->office->name : 'N/A',
                            'qr_code' => $equipment->qr_code,
                            'qr_code_image_path' => $equipment->qr_code_image_path,
                        ]
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format - unsupported JSON structure'
                ], 400);
            }

            // Handle URL format (legacy support)
            if (preg_match('/\/equipment\/(\d+)/', $qrData, $matches)) {
                $equipmentId = $matches[1];

                $equipment = Equipment::with('office')
                    ->find($equipmentId);

                if (!$equipment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Equipment not found'
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'equipment' => [
                        'id' => $equipment->id,
                        'model_number' => $equipment->model_number,
                        'serial_number' => $equipment->serial_number,
                        'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
                        'status' => $equipment->status,
                        'condition' => $equipment->condition,
                        'location' => $equipment->location,
                        'office' => $equipment->office ? $equipment->office->name : 'N/A',
                        'qr_code' => $equipment->qr_code,
                        'qr_code_image_path' => $equipment->qr_code_image_path,
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code format'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process QR code scan result
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scanQrCode(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        $qrData = $request->qr_data;

        // Check if QR data is a URL with query parameters
        if (is_string($qrData) && strpos($qrData, '?') !== false) {
            // Parse URL query parameters
            $parsedUrl = parse_url($qrData);
            if (isset($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $queryParams);
                if (isset($queryParams['id'])) {
                    $equipment = Equipment::with('office')
                        ->find($queryParams['id']);

                    if (!$equipment) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Equipment not found'
                        ], 404);
                    }

                    return response()->json([
                        'success' => true,
                        'equipment' => [
                            'id' => $equipment->id,
                            'model_number' => $equipment->model_number,
                            'serial_number' => $equipment->serial_number,
                            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
                            'status' => $equipment->status,
                            'condition' => $equipment->condition,
                            'location' => $equipment->location,
                            'office' => $equipment->office ? $equipment->office->name : 'N/A',
                            'qr_code' => $equipment->qr_code,
                            'qr_code_image_path' => $equipment->qr_code_image_path,
                        ]
                    ]);
                }
            }
        }

        return $this->processQrData($request->qr_data);
    }

    /**
     * Show the scanned equipment details view
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function scanView(Request $request)
    {
        return view('equipment.scan');
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

        // Prepare QR data for technician view (URL-based for compatibility)
        $qrData = [
            'type' => 'equipment_url',
            'url' => route('technician.equipment.show', $equipment->id),
            'equipment_id' => $equipment->id,
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'generated_at' => now()->toISOString(),
        ];

        // Use the cached QR code service instead of external API
        $qrCodeService = app(\App\Services\QrCodeService::class);
        $qrPath = $qrCodeService->generateQrCode($qrData, '300x300', 'png');

        if ($qrPath && Storage::disk('public')->exists($qrPath)) {
            // Save path to equipment for future use
            $equipment->update(['qr_code_image_path' => $qrPath]);
            return response()->file(public_path('storage/' . $qrPath));
        }

        // Fallback: return a simple text response
        return response('QR Code not available')->header('Content-Type', 'text/plain');
    }


    protected function getEquipmentTypes()
    {
        return [
            'laptop' => 'Laptop',
            'desktop' => 'Desktop Computer',
            'router' => 'Router',
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

    protected function getEquipmentCategories()
    {
        return [
            'IT Equipment',
            'Audio/Visual',
            'Office Equipment',
            'Networking',
            'Medical Equipment',
            'Laboratory',
            'Industrial',
            'Lab Equipment',
            'Furniture',
            'Other'
        ];
    }

    /**
     * Show the form for creating a new history entry for the equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function createHistory(Equipment $equipment)
    {
        $user = Auth::guard('technician')->user();

        // Permission check - technicians have access based on authentication
        // if ($user->user && !$user->user->can('equipment.view')) {
        //     return redirect()->route('technician.equipment.index')
        //         ->with('error', 'You do not have permission to access equipment history.');
        // }

        // Check if equipment exists and is assigned to the technician's office
        // Technicians now have access to all equipment across all offices
        // if ($equipment->office_id !== $user->office_id) {
        //     return redirect()->back()->with('error', 'You do not have permission to access this equipment.');
        // }
            
        if (request()->ajax()) {
            // Return partial view for modal
            return view('equipment.history_modal', compact('equipment'));
        }

        // Load the view
        return view('equipment.history.create', [
            'equipment' => $equipment->load('office')
        ]);
    }

    /**
     * Store a newly created history entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeHistory(Request $request, Equipment $equipment)
    {
        // Debug: Log that the method was called
        \Log::info('storeHistory method called', [
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'equipment_id' => $equipment->id,
            'request_data' => $request->all()
        ]);

        $user = Auth::guard('technician')->user();
        // Resolve the underlying users.id and responsible name
        $userId = $user->user_id ?? ($user->id ?? optional($user->user)->id);
        $responsibleName = $user->name ?? optional($user->user)->name;

        // Check if technician has permission to view equipment (required for history access)
        // if ($user->user && !$user->user->can('equipment.view')) {
        //     return redirect()->route('technician.equipment.index')
        //         ->with('error', 'You do not have permission to add equipment history.');
        // }

        // Manual authorization check
        // Technicians now have access to all equipment across all offices
        // if ($equipment->office_id !== $user->office_id) {
        //     return redirect()->back()
        //         ->with('error', 'You do not have permission to add history for this equipment.');
        // }

        $validated = $request->validate([
            'date' => 'required|date',
            'action_taken' => 'required|string|max:1000',
            'equipment_status' => 'required|in:serviceable,for_repair,defective',
        ]);

        // Auto-generate remarks based on equipment status
        $statusRemarks = [
            'serviceable' => 'Equipment marked as serviceable',
            'for_repair' => 'repair',
            'defective' => 'Equipment marked as defective'
        ];
        $validated['remarks'] = $statusRemarks[$validated['equipment_status']] ?? '';

        // Debug: Log all incoming data
        \Log::info('History form submission', [
            'all_request_data' => $request->all(),
            'validated_data' => $validated,
            'equipment_status_raw' => $request->input('equipment_status'),
            'equipment_status_validated' => $validated['equipment_status'] ?? 'NOT_SET',
            'equipment_status_empty_check' => empty($validated['equipment_status']),
            'equipment_status_is_null' => is_null($validated['equipment_status']),
            'equipment_status_type' => gettype($validated['equipment_status'])
        ]);

        try {
            DB::beginTransaction();

            // Generate unique JO number for this date with retry logic for concurrency
            $date = $validated['date'];
            $yearMonth = date('y-m', strtotime($date)); // YY-MM format

            // Retry logic for handling concurrent requests
            $maxRetries = 5;
            $retryCount = 0;
            $joNumber = null;

            while ($retryCount < $maxRetries && !$joNumber) {
                // Find the next sequence number for this month (resets monthly)
                $latestJO = EquipmentHistory::where('jo_number', 'like', 'JO-' . $yearMonth . '-%')
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
                    if (!EquipmentHistory::where('jo_number', $candidateJONumber)->exists()) {
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
                throw new \Exception('Unable to generate unique JO number - all numbers for this month are taken');
            }

            $history = new EquipmentHistory([
                'equipment_id' => $equipment->id,
                'user_id' => $userId,
                'date' => $validated['date'],
                'jo_number' => $joNumber,
                'action_taken' => $validated['action_taken'],
                'remarks' => $validated['remarks'],
                'responsible_person' => $responsibleName, // Auto-fill with technician's name
            ]);

            $history->save();

            // Log equipment history creation
            Activity::logEquipmentHistoryCreation($history, $user);

            // Update equipment status (now always required)
            \Log::info('Equipment status update triggered', [
                'equipment_id' => $equipment->id,
                'equipment_status' => $validated['equipment_status'],
                'current_status' => $equipment->status,
                'current_condition' => $equipment->condition
            ]);

            $oldStatus = $equipment->status;
            $updateData = [
                'status' => $validated['equipment_status'],
                'assigned_by_id' => $userId,
            ];

            // Set condition based on status
            if ($validated['equipment_status'] === 'serviceable') {
                $updateData['condition'] = 'good';
                \Log::info('Setting condition to good for serviceable status');
            } elseif (in_array($validated['equipment_status'], ['for_repair', 'defective'])) {
                $updateData['condition'] = 'not_working';
                \Log::info('Setting condition to not_working for ' . $validated['equipment_status'] . ' status');
            }

            $result = $equipment->update($updateData);
            \Log::info('Equipment update result', [
                'update_result' => $result,
                'updated_data' => $updateData,
                'equipment_after_update' => [
                    'status' => $equipment->fresh()->status,
                    'condition' => $equipment->fresh()->condition
                ]
            ]);

            // Log equipment status change
            if ($oldStatus !== $validated['equipment_status']) {
                Activity::logEquipmentUpdate($equipment, ['status' => [$oldStatus, $validated['equipment_status']]], $user);
            }

            \Log::info('About to commit transaction', [
                'equipment_id' => $equipment->id,
                'history_created' => true,
                'equipment_status_updated' => $validated['equipment_status'],
                'equipment_condition_updated' => isset($updateData['condition']) ? $updateData['condition'] : 'unchanged'
            ]);

            DB::commit();

            \Log::info('Transaction committed successfully', [
                'equipment_id' => $equipment->id,
                'final_equipment_status' => $equipment->fresh()->status,
                'final_equipment_condition' => $equipment->fresh()->condition
            ]);

            $successMessage = 'History sheet saved!';
            $statusText = ucfirst(str_replace('_', ' ', $validated['equipment_status']));
            $successMessage .= ' Equipment status updated to ' . $statusText . '.';

            // Add condition update message
            if (isset($updateData['condition'])) {
                $conditionText = $updateData['condition'] === 'good' ? 'Good' : 'Not Working';
                $successMessage .= ' Equipment condition set to ' . $conditionText . '.';
            }

            return redirect()
                ->route('technician.equipment.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            \Log::error('Transaction rolled back due to error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'equipment_id' => $equipment->id,
                'validated_data' => $validated ?? 'not set'
            ]);
            
            DB::rollBack();
            
            return back()
                ->withInput()
                ->with('error', 'Failed to add history entry. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified history entry.
     *
     * @param  \App\Models\Equipment  $equipment
     * @param  \App\Models\EquipmentHistory  $history
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editHistory(Equipment $equipment, EquipmentHistory $history)
    {
        $user = Auth::guard('technician')->user();

        // Check if history belongs to this equipment
        if ($history->equipment_id !== $equipment->id) {
            return redirect()->route('technician.equipment.show', $equipment)
                ->with('error', 'History entry does not belong to this equipment.');
        }

        // Check if technician created this history entry
        $userId = $user->user_id ?? ($user->id ?? optional($user->user)->id);
        if ($history->user_id !== $userId) {
            return redirect()->route('technician.equipment.show', $equipment)
                ->with('error', 'You can only edit history entries you created.');
        }

        if (request()->ajax()) {
            return view('equipment.history_edit_modal', compact('equipment', 'history'));
        }

        return view('equipment.history.edit', [
            'equipment' => $equipment->load('office'),
            'history' => $history
        ]);
    }

    /**
     * Update the specified history entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @param  \App\Models\EquipmentHistory  $history
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateHistory(Request $request, Equipment $equipment, EquipmentHistory $history)
    {
        $user = Auth::guard('technician')->user();

        // Check if history belongs to this equipment
        if ($history->equipment_id !== $equipment->id) {
            return redirect()->route('technician.equipment.show', $equipment)
                ->with('error', 'History entry does not belong to this equipment.');
        }

        // Check if technician created this history entry
        $userId = $user->user_id ?? ($user->id ?? optional($user->user)->id);
        if ($history->user_id !== $userId) {
            return redirect()->route('technician.equipment.show', $equipment)
                ->with('error', 'You can only edit history entries you created.');
        }

        $validated = $request->validate([
            'action_taken' => 'required|string|max:1000',
            'equipment_status' => 'required|in:serviceable,for_repair,defective',
        ]);

        // Track changes for logging
        $originalData = $history->getOriginal();
        $changes = [];

        try {
            DB::beginTransaction();

            // Update the history entry
            $history->update([
                'action_taken' => $validated['action_taken'],
                'remarks' => $request->input('remarks') ?: $this->getRemarksFromStatus($validated['equipment_status']),
            ]);

            // Track history changes
            foreach (['action_taken', 'remarks'] as $field) {
                if ($originalData[$field] != $history->$field) {
                    $changes[$field] = [$originalData[$field], $history->$field];
                }
            }

            // Log maintenance update
            Activity::logMaintenanceUpdate($history, $changes, $user);

            // Update equipment status if provided
            if (isset($validated['equipment_status'])) {
                $oldStatus = $equipment->status;
                $updateData = [
                    'status' => $validated['equipment_status'],
                ];

                // Set condition based on status
                if ($validated['equipment_status'] === 'serviceable') {
                    $updateData['condition'] = 'good';
                } elseif (in_array($validated['equipment_status'], ['for_repair', 'defective'])) {
                    $updateData['condition'] = 'not_working';
                }

                $equipment->update($updateData);

                // Log equipment status change
                if ($oldStatus !== $validated['equipment_status']) {
                    Activity::logEquipmentUpdate($equipment, ['status' => [$oldStatus, $validated['equipment_status']]], $user);
                }
            }

            DB::commit();

            $successMessage = 'History entry updated successfully!';
            if (isset($validated['equipment_status'])) {
                $statusText = ucfirst(str_replace('_', ' ', $validated['equipment_status']));
                $successMessage .= ' Equipment status updated to ' . $statusText . '.';
            }

            return redirect()->route('technician.reports.history', $equipment->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update history entry', [
                'error' => $e->getMessage(),
                'equipment_id' => $equipment->id,
                'history_id' => $history->id,
                'user_id' => $userId
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update history entry. Please try again.');
        }
    }

    /**
     * Generate unique Job Order number for the given date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\JsonResponse
     */
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
                $latestJO = EquipmentHistory::where('jo_number', 'like', 'JO-' . $yearMonth . '-%')
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
                    if (!EquipmentHistory::where('jo_number', $candidateJONumber)->exists()) {
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

    /**
     * Check if the selected date can be used (prevent backdating beyond latest repair).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Check existing sequences for a date to validate consecutive numbering.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkSequences(Request $request, Equipment $equipment)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $date = $request->date;

            \Log::info('=== checkSequences START ===', [
                'equipment_id' => $equipment->id,
                'date_requested' => $date,
                'request_all' => $request->all()
            ]);

            // Find all JO numbers for this month (resets monthly)
            $yearMonth = date('y-m', strtotime($date)); // YY-MM format
            $joQuery = 'JO-' . $yearMonth . '-%';
            \Log::info('JO query pattern:', ['pattern' => $joQuery]);

            $existingJO = EquipmentHistory::where('jo_number', 'like', $joQuery)
                ->orderBy('jo_number')
                ->pluck('jo_number')
                ->toArray();

            \Log::info('Database results:', [
                'query_executed' => "WHERE jo_number LIKE '{$joQuery}'",
                'count_found' => count($existingJO),
                'jo_numbers_found' => $existingJO
            ]);

            // Extract sequences from JO numbers
            $existingSequences = [];
            foreach ($existingJO as $joNumber) {
                \Log::info('Processing JO number:', ['jo_number' => $joNumber]);
                $parts = explode('-', $joNumber);
                \Log::info('JO parts:', ['parts' => $parts, 'count' => count($parts)]);

                if (count($parts) >= 3) {
                    $sequence = (int) end($parts); // JO-YY-MM-XXX -> XXX
                    $existingSequences[] = $sequence;
                    \Log::info('Extracted sequence:', ['sequence' => $sequence]);
                } else {
                    \Log::info('Invalid JO format, skipping:', ['jo_number' => $joNumber]);
                }
            }

            \Log::info('All extracted sequences:', ['sequences' => $existingSequences]);

            // Sort sequences
            sort($existingSequences);

            // For strict consecutive numbering, find the next required sequence
            $nextSequence = 1;
            foreach ($existingSequences as $seq) {
                if ($seq === $nextSequence) {
                    $nextSequence++;
                } else {
                    // Gap found - this shouldn't happen with strict validation
                    // But if it does, nextSequence remains at the gap position
                    break;
                }
            }

            \Log::info('Strict consecutive validation', [
                'existing_sequences_sorted' => $existingSequences,
                'next_required_sequence' => $nextSequence,
                'total_entries' => count($existingSequences)
            ]);

            return response()->json([
                'success' => true,
                'existing_sequences' => $existingSequences,
                'next_sequence' => $nextSequence
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in checkSequences', [
                'error' => $e->getMessage(),
                'equipment_id' => $equipment->id,
                'date' => $request->date ?? 'null'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error checking sequences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear the history prompt session variable.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearHistoryPrompt(Equipment $equipment)
    {
        // Clear the session flag
        session()->forget('equipment_updated_show_history_prompt');
        
        return response()->json(['success' => true]);
    }

    private function getRemarksFromStatus($status)
    {
        $remarks = [
            'serviceable' => 'Serviceable',
            'for_repair' => 'For Repair',
            'defective' => 'Defective',
        ];

        return $remarks[$status] ?? 'Unknown status';
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
            'routePrefix' => 'technician',
            'printPdfRoute' => route('technician.equipment.print-qrcodes.pdf'),
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

        return redirect()->route('technician.equipment.index', $redirectParams);
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
                ->route('technician.equipment.index')
                ->with('error', 'Please select at least one equipment to print.');
        }

        $equipments = Equipment::with(['office', 'equipmentType'])
            ->whereIn('id', $equipmentIds)
            ->orderBy('office_id')
            ->orderBy('model_number')
            ->get();

        if ($equipments->isEmpty()) {
            return redirect()
                ->route('technician.equipment.index')
                ->with('error', 'Selected equipment could not be found.');
        }

        $generatedAt = now();
        $generatedBy = optional(auth('technician')->user())->name ?? 'SDMD System';

        return view('equipment.qr-code-pdf', [
            'equipments' => $equipments,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'routePrefix' => 'technician',
        ]);
    }
}
