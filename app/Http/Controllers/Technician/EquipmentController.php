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
        $equipmentTypes = \App\Models\EquipmentType::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Get campuses with their active offices for filter
        $campuses = \App\Models\Campus::with(['offices' => function($query) {
            $query->where('is_active', true)->orderBy('name');
        }])->orderBy('name')->get();

        // Get categories for filter
        $categories = Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');

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
        $categories = Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        $equipmentTypes = EquipmentType::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id');

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

        // Log the activity
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'equipment.store',
            'description' => "Created new equipment: {$equipment->model_number} ({$equipment->serial_number})"
        ]);

        // Generate and save QR code using QRServer API with comprehensive structured data
        $qrData = json_encode([
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
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

        $categories = Category::where('is_active', true)->orderBy('name')->pluck('name', 'id');
        $equipmentTypes = EquipmentType::where('is_active', true)->orderBy('name')->pluck('name', 'id')->toArray();

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
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'equipment_type_id' => 'required|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'cost_of_purchase' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:serviceable,for_repair,defective',
            'condition' => 'nullable|in:good,not_working', // Now optional - auto-set based on status
            'office_id' => 'required|exists:offices,id',
        ]);

        // Auto-set condition based on status if not provided
        if (empty($validated['condition']) && isset($validated['status'])) {
            $validated['condition'] = $validated['status'] === 'serviceable' ? 'good' : 'not_working';
        }

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

        // Log the activity
        Activity::create([
            'user_id' => auth()->id(),
            'action' => 'equipment.update',
            'description' => "Updated equipment: {$equipment->model_number} ({$equipment->serial_number})"
        ]);

        // If relevant fields were changed, set session flag to show history confirmation
        if ($hasRelevantChanges) {
            session(['equipment_updated_show_history_prompt' => $equipment->id]);
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

        $validated = $request->validate([
            'status' => 'required|in:available,in_use,maintenance,disposed',
            'notes' => 'nullable|string',
        ]);

        $equipment->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $equipment->notes,
        ]);

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

                if (!$parsedData || !isset($parsedData['id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid QR code format'
                    ], 400);
                }

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

        // Create QR code with equipment information in JSON format
        $equipmentData = [
            'id' => $equipment->id,
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
            'status' => $equipment->status,
            'condition' => $equipment->condition,
            'location' => $equipment->location,
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->format('Y-m-d H:i:s'),
        ];

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode(json_encode($equipmentData)) . "&size=300x300&format=png";

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            return response($response->body())->header('Content-Type', 'image/png');
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
    }

    /**
     * Download the QR code for the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\StreamedResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadQrCode(Equipment $equipment)
    {
        if ($equipment->qr_code_image_path && Storage::disk('public')->exists($equipment->qr_code_image_path)) {
            return response()->download(public_path('storage/' . $equipment->qr_code_image_path));
        }

        // Fallback to generating on-the-fly
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Create QR code with equipment information in JSON format
        $equipmentData = [
            'id' => $equipment->id,
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
            'status' => $equipment->status,
            'condition' => $equipment->condition,
            'location' => $equipment->location,
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->format('Y-m-d H:i:s'),
        ];

        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode(json_encode($equipmentData)) . "&size=300x300&format=png";

        $response = Http::get($apiUrl);

        if ($response->successful()) {
            return response($response->body())
                ->header('Content-Type', 'image/png')
                ->header('Content-Disposition', 'attachment; filename="qrcode-' . $equipment->id . '.png"');
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
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
            'date' => 'required|date|before_or_equal:now',
            'jo_sequence' => 'required|string|max:2|regex:/^[0-9]{1,2}$/',
            'action_taken' => 'required|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
            'equipment_status' => 'required|in:serviceable,for_repair,defective',
        ]);

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

        // Build the full JO number from date and sequence
        $date = \Carbon\Carbon::parse($validated['date']);
        $joNumber = 'JO-' . $date->format('Y-m-d') . '-' . str_pad($validated['jo_sequence'], 2, '0', STR_PAD_LEFT);

        // Check if JO number already exists
        if (EquipmentHistory::where('jo_number', $joNumber)->exists()) {
            return back()->withErrors(['jo_sequence' => 'This Job Order number already exists. Please choose a different sequence.'])
                         ->withInput();
        }

        // Additional validation: prevent backdating beyond latest repair
        $selectedDateTime = new \DateTime($validated['date']);
        $latestRepair = EquipmentHistory::where('equipment_id', $equipment->id)
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
            DB::beginTransaction();

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

            \Log::info('History saved, equipment status update will proceed', [
                'history_saved' => true,
                'equipment_status' => $validated['equipment_status'],
                'will_set_condition_to_good' => $validated['equipment_status'] === 'serviceable'
            ]);

            // Update equipment status (now always required)
            \Log::info('Equipment status update triggered', [
                'equipment_id' => $equipment->id,
                'equipment_status' => $validated['equipment_status'],
                'current_status' => $equipment->status,
                'current_condition' => $equipment->condition
            ]);

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

            // Find all JO numbers for this date across all equipment
            $joQuery = 'JO-' . $date . '-%';
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

                if (count($parts) >= 4) {
                    $sequence = (int) end($parts); // JO-YYYY-MM-DD-XX -> XX
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
