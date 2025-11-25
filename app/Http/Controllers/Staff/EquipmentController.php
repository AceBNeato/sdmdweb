<?php

namespace App\Http\Controllers\Staff;


use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Campus;
use App\Models\EquipmentType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Activity;
use App\Services\QrCodeService;

class EquipmentController extends Controller
{
    private $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Display the QR scanner page for staff users.
     */
    public function qrScanner()
    {
        return view('qr-scanner');
    }

    /**
     * Display a listing of the equipment.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::guard('staff')->user();   

        // Debug: Check if user has office assigned
        if (!$user->office_id) {
            return back()->withErrors(['error' => 'You have not been assigned to an office. Please contact your administrator.']);
        }

        // Start with equipment from the staff's office
        $query = Equipment::where('office_id', $user->office_id);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('model_number', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Optimized query - remove history loading for better performance
        $equipment = $query->with(['category', 'office', 'equipmentType'])->paginate(10);

        $equipmentTypes = $this->getEquipmentTypes();

        // For staff users, only load their office data (much faster)
        $userOffice = \App\Models\Office::find($user->office_id);
        $campuses = collect([
            (object) [
                'id' => $userOffice->campus_id ?? 0,
                'name' => $userOffice->campus->name ?? 'Current Campus',
                'code' => $userOffice->campus->code ?? 'N/A',
                'offices' => collect([$userOffice])
            ]
        ]);

        // Get categories for filter
        $categories = \App\Models\Category::orderBy('name')->pluck('name', 'id');

        return view('equipment.index', compact('equipment', 'equipmentTypes', 'campuses', 'categories'));
    }

    /**
     * Show the form for creating a new equipment.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Check if any categories exist
        $categoriesCount = \App\Models\Category::count();
        if ($categoriesCount === 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create equipment. No equipment types/categories have been added yet. Please ask your administrator to add categories in Settings first.',
                    'error_type' => 'no_categories'
                ], 400);
            }
            
            return redirect()->back()->with('error', 'Cannot create equipment. No equipment types/categories have been added yet. Please ask your administrator to add categories in Settings first.');
        }

        $user = Auth::guard('staff')->user();

        $equipment = new Equipment();
        $categories = $this->getEquipmentCategories();
        $equipmentTypes = $this->getEquipmentTypes();
        
        // Staff can only create equipment for their own office
        $campuses = \App\Models\Campus::with(['offices' => function($query) use ($user) {
            $query->where('is_active', true)
                  ->where('id', $user->office_id) // Only their office
                  ->orderBy('name');
        }])->where('is_active', true)->get();
        
        if (request()->ajax()) {
            // Return partial view for modal
            return view('equipment.form_modal', compact('equipment', 'categories', 'equipmentTypes', 'campuses'));
        }
        
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
        // Check if any categories exist before proceeding
        $categoriesCount = \App\Models\Category::count();
        if ($categoriesCount === 0) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot create equipment. No equipment types/categories have been added yet. Please ask your administrator to add categories in Settings first.',
                    'error_type' => 'no_categories'
                ], 400);
            }
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Cannot create equipment. No equipment types/categories have been added yet. Please ask your administrator to add categories in Settings first.');
        }

        $user = Auth::guard('staff')->user();
        
        // Permission check - staff have access based on authentication
        // if ($user->user && !$user->user->can('equipment.create')) {
        //     return redirect()->route('staff.equipment.index')
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

        // Staff can now create equipment for any office
        if ($validated['office_id'] != $user->office_id) {
            return back()->withErrors(['office_id' => 'You can only create equipment for your assigned office.']);
        }

        // QR code will be auto-generated by the model's creating event
        $equipment = Equipment::create($validated);

        // Log the activity using new method
        Activity::logEquipmentCreation($equipment);

        // Generate and save QR code using optimized service
        $qrData = [
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'N/A',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
        ];

        try {
            // For staff equipment, use public URL mode for maximum compatibility
            $qrPath = $this->qrCodeService->generateQrCode($qrData, '200x200', 'png', publicUrl: true);
            if ($qrPath) {
                $equipment->update(['qr_code_image_path' => $qrPath]);
                Log::info('QR code generated and saved for new equipment', [
                    'equipment_id' => $equipment->id,
                    'qr_path' => $qrPath
                ]);
            } else {
                Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for equipment ID: ' . $equipment->id . ' - ' . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment added successfully.',
                'redirect' => route('staff.equipment.index'),
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

        return redirect()->route('staff.equipment.index')
            ->with('success', 'Equipment added successfully.');
    }

    /**
     * Display the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View
     */
    public function show(Request $request, Equipment $equipment)
    {
        $user = Auth::guard('staff')->user();

        // Ensure staff can only view equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only view equipment from your office.');
        }

        $equipment->load('office', 'equipmentType');

        // Generate QR code if missing
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Generate QR code image if missing
        if (!$equipment->qr_code_image_path || !Storage::disk('public')->exists($equipment->qr_code_image_path)) {
            try {
                $qrData = [
                    'id' => $equipment->id,
                    'type' => 'equipment',
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'status' => $equipment->status,
                ];

                $qrPath = $this->qrCodeService->generateQrCode($qrData, '200x200', 'png', publicUrl: true);

                if ($qrPath) {
                    $equipment->update(['qr_code_image_path' => $qrPath]);
                }
            } catch (\Exception $e) {
                // QR code generation failed, but continue showing the page
            }
        }

        $prefix = 'staff'; // For staff routes

        if (request()->ajax()) {
            return view('equipment.show_modal', compact('equipment', 'prefix'));
        }

        return view('equipment.show_modal', compact('equipment', 'prefix'));
    }

    /**
     * Show the form for editing the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View
     */
    public function edit(Equipment $equipment)
    {
        $user = Auth::guard('staff')->user();

        // Ensure staff can only edit equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only edit equipment from your office.');
        }

        $categories = $this->getEquipmentCategories();
        $equipmentTypes = $this->getEquipmentTypes();
        $campuses = Campus::with('offices')->get();

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
        $user = Auth::guard('staff')->user();

        // Ensure staff can only update equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only update equipment from your office.');
        }

        $validated = $request->validate([
            'brand' => 'required|string|max:100',
            'model_number' => 'required|string|max:100',
            'serial_number' => 'required|string|max:100|unique:equipment,serial_number,' . $equipment->id,
            'equipment_type_id' => 'required|integer|exists:equipment_types,id',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
        ]);

        // Track changes for logging
        $originalData = $equipment->getOriginal();
        $changes = [];

        $equipment->update($validated);

        // Track field changes
        foreach (['brand', 'model_number', 'serial_number', 'equipment_type_id', 'description', 'purchase_date'] as $field) {
            if ($originalData[$field] != $equipment->$field) {
                $oldValue = $originalData[$field];
                $newValue = $equipment->$field;
                
                if ($field === 'equipment_type_id') {
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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Equipment updated successfully.',
                'redirect' => route('staff.equipment.index'),
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

        return redirect()->route('staff.equipment.index')
            ->with('success', 'Equipment updated successfully.');
    }

    /**
     * Remove the specified equipment from storage.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Equipment $equipment)
    {
        $user = Auth::guard('staff')->user();

        // Ensure staff can only delete equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only delete equipment from your office.');
        }

        // Log the activity before deletion using new method
        Activity::logEquipmentDeletion($equipment);

        $equipment->delete();

        return redirect()->route('staff.equipment.index')
            ->with('success', 'Equipment deleted successfully.');
    }

    /**
     * Update the status of the specified equipment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, Equipment $equipment)
    {
        $request->validate([
            'status' => 'required|in:available,in_use,maintenance,disposed'
        ]);

        $equipment->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Equipment status updated successfully.'
        ]);
    }

    /**
     * Process QR code scan result for Staff
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scanQrCode(Request $request)
    {
        $request->validate([
            'qr_data' => 'required|string',
        ]);

        try {
            $qrData = $request->qr_data;

            // Check if QR data is a URL with query parameters
            if (is_string($qrData) && strpos($qrData, '?') !== false) {
                // Parse URL query parameters
                $parsedUrl = parse_url($qrData);
                if (isset($parsedUrl['query'])) {
                    parse_str($parsedUrl['query'], $queryParams);
                    if (isset($queryParams['id'])) {
                        $equipment = Equipment::with('office', 'equipmentType')
                            ->find($queryParams['id']);

                        if (!$equipment) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Equipment not found'
                            ], 404);
                        }

                        // Check if staff has access to this equipment (equipment from their office)
                        $user = Auth::guard('staff')->user();
                        if (!$equipment->office_id || $equipment->office_id !== $user->office_id) {
                            return response()->json([
                                'success' => false,
                                'message' => 'You do not have access to this equipment'
                            ], 403);
                        }

                        // Log the QR scan
                        Activity::logQrCodeScan($equipment, $user);

                        return response()->json([
                            'success' => true,
                            'equipment' => [
                                'id' => $equipment->id,
                                'model_number' => $equipment->model_number,
                                'serial_number' => $equipment->serial_number,
                                'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                                'status' => $equipment->status,
                                'location' => $equipment->location,
                                'office' => $equipment->office ? $equipment->office->name : 'N/A',
                                'qr_code' => $equipment->qr_code,
                            ]
                        ]);
                    }
                }
            }

            // Parse QR data as JSON (legacy format)
            $qrData = json_decode($qrData, true);

            if (is_array($qrData) && isset($qrData['equipment_id']) && !isset($qrData['id'])) {
                $qrData['id'] = $qrData['equipment_id'];
            }

            if (!$qrData || !isset($qrData['id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid QR code format'
                ], 400);
            }

            $equipment = Equipment::with('office', 'equipmentType')
                ->find($qrData['id']);

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found'
                ], 404);
            }

            // Check if staff has access to this equipment (equipment from their office)
            $user = Auth::guard('staff')->user();
            if (!$equipment->office_id || $equipment->office_id !== $user->office_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this equipment'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'equipment' => [
                    'id' => $equipment->id,
                    'model_number' => $equipment->model_number,
                    'serial_number' => $equipment->serial_number,
                    'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
                    'status' => $equipment->status,
                    'location' => $equipment->location,
                    'office' => $equipment->office ? $equipment->office->name : 'N/A',
                    'qr_code' => $equipment->qr_code,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing QR code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the scanned equipment details view for Staff
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function scanView(Request $request)
    {
        return view('equipment.scan');
    }

    /**
     * Get the equipment types for the form
     *
     * @return array
     */
    protected function getEquipmentTypes()
    {
        // Cache equipment types for better performance
        return \Cache::remember('equipment_types', 3600, function () {
            return \App\Models\EquipmentType::orderBy('name')->pluck('name', 'id');
        });
    }

    /**
     * Get the equipment categories for the form
     *
     * @return array
     */
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
     * Show the QR code for the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function qrCode(Equipment $equipment)
    {
        $user = Auth::guard('staff')->user();

        // Ensure staff can only access QR code for equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only access QR codes for equipment from your office.');
        }

        // If QR code image is saved, return it
        if ($equipment->qr_code_image_path && Storage::disk('public')->exists($equipment->qr_code_image_path)) {
            return response()->file(public_path('storage/' . $equipment->qr_code_image_path));
        }

        // Fallback to generating on-the-fly if no saved image
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Prepare QR data for viewing
        $qrData = [
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'url' => route('staff.equipment.show', $equipment),
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->toISOString(),
        ];

        // Use cached QR code service with public URL for stickers
        $qrPath = $this->qrCodeService->generateQrCode($qrData, '300x300', 'png', publicUrl: true);

        if ($qrPath && Storage::disk('public')->exists($qrPath)) {
            // Save path to equipment for future use
            $equipment->update(['qr_code_image_path' => $qrPath]);
            return response()->file(public_path('storage/' . $qrPath));
        }

        // Fallback: return a simple text response
        return response('QR Code not available')->header('Content-Type', 'text/plain');
    }


    /**
     * Print the QR code for the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\Http\Response
     */
    public function printQRCode(Equipment $equipment)
    {
        $user = Auth::guard('staff')->user();

        // Ensure staff can only print QR code for equipment from their office
        if ($equipment->office_id !== $user->office_id) {
            abort(403, 'You can only print QR codes for equipment from your office.');
        }

        // Generate QR code if it doesn't exist
        if (!$equipment->qr_code) {
            $equipment->qr_code = 'EQP-' . Str::upper(Str::random(8));
            $equipment->save();
        }

        // Prepare QR data for printing
        $qrData = [
            'id' => $equipment->id,
            'type' => 'equipment',
            'model_number' => $equipment->model_number,
            'serial_number' => $equipment->serial_number,
            'equipment_type' => $equipment->equipmentType ? $equipment->equipmentType->name : 'Unknown',
            'office' => $equipment->office ? $equipment->office->name : 'N/A',
            'status' => $equipment->status,
            'url' => route('staff.equipment.show', $equipment),
            'qr_code' => $equipment->qr_code,
            'created_at' => $equipment->created_at->toISOString(),
        ];

        // Use cached QR code service with public URL for stickers
        $qrPath = $this->qrCodeService->generateQrCode($qrData, '300x300', 'png', publicUrl: true);

        if ($qrPath && Storage::disk('public')->exists($qrPath)) {
            $filename = 'qr-code-' . Str::slug($equipment->model_number . '-' . $equipment->serial_number) . '.png';

            return response()->download(
                storage_path('app/public/' . $qrPath),
                $filename,
                ['Content-Type' => 'image/png']
            );
        }

        // Fallback: return a simple text response
        return response('QR Code generation failed')->header('Content-Type', 'text/plain');
    }

    public function printQrcodes(Request $request)
    {
        $user = Auth::guard('staff')->user();

        // Check if user has office assigned
        if (!$user->office_id) {
            return back()->withErrors(['error' => 'You have not been assigned to an office. Please contact your administrator.']);
        }

        $query = Equipment::with(['office', 'equipmentType'])
            ->where('office_id', $user->office_id); // Only their office equipment

        $equipment = $query
            ->orderBy('office_id')
            ->orderBy('model_number')
            ->get();

        $campuses = Campus::with(['offices' => function ($query) use ($user) {
            $query->where('is_active', true)
                  ->where('id', $user->office_id) // Only their office
                  ->orderBy('name');
        }])
            ->where('is_active', true)
            ->get();

        $viewData = [
            'campuses' => $campuses,
            'equipment' => $equipment,
            'selectedOfficeId' => $user->office_id, // Always their office
            'routePrefix' => 'staff',
            'printPdfRoute' => route('staff.equipment.print-qrcodes.pdf'),
        ];

        if ($request->ajax()) {
            return view('equipment.print-qrcodes_modal', $viewData);
        }

        $redirectParams = array_filter([
            'print_qrcodes' => 1,
            'office_id' => $user->office_id,
        ], static function ($value) {
            return $value !== null;
        });

        return redirect()->route('staff.equipment.index', $redirectParams);
    }

    public function printQrcodesPdf(Request $request)
    {
        $user = Auth::guard('staff')->user();
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
                ->route('staff.equipment.index')
                ->with('error', 'Please select at least one equipment to print.');
        }

        $equipments = Equipment::with(['office', 'equipmentType'])
            ->whereIn('id', $equipmentIds)
            ->where('office_id', $user->office_id) // Only their office equipment
            ->orderBy('office_id')
            ->orderBy('model_number')
            ->get();

        if ($equipments->isEmpty()) {
            return redirect()
                ->route('staff.equipment.index')
                ->with('error', 'Selected equipment could not be found or you do not have access to it.');
        }

        $generatedAt = now();
        $generatedBy = optional(auth('staff')->user())->name ?? 'SDMD System';

        return view('equipment.qr-code-pdf', [
            'equipments' => $equipments,
            'generatedAt' => $generatedAt,
            'generatedBy' => $generatedBy,
            'routePrefix' => 'staff',
        ]);
    }
}
