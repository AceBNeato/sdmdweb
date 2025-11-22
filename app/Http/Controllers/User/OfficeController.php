<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Office;
use App\Models\Campus;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OfficeController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:settings.manage')->only(['index', 'show']);
        $this->middleware('permission:settings.manage')->only(['create', 'store']);
        $this->middleware('permission:settings.manage')->only(['edit', 'update']);
        $this->middleware('permission:settings.manage')->only(['destroy']);
    }

    /**
     * Display a listing of the offices.
     */
    public function index(Request $request)
    {
        $query = Office::with('campus');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('campus', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('is_active', $request->status === 'active');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['name', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $offices = $query->paginate(10)->appends($request->except('page'));
        $campuses = \App\Models\Campus::where('is_active', true)->get();

        return view('offices.index', compact('offices', 'campuses'));
    }

    /**
     * Show the form for creating a new office.
     */
    public function create()
    {
        $campuses = Campus::where('is_active', true)->get();
        
        // Always return modal content
        return view('offices.form-modal', compact('campuses'));
    }

    /**
     * Store a newly created office in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:offices,name',
            'campus_id' => 'required|exists:campuses,id',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:offices,email',
            'is_active' => 'sometimes|boolean',
        ]);

        // Ensure is_active is set
        $validated['is_active'] = $request->has('is_active');

        try {
            $office = Office::create($validated);
            
            // Log office creation
            Activity::logOfficeCreation($office);
            
            return redirect()
                ->route('admin.offices.index')
                ->with('success', 'Office created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create office. ' . $e->getMessage());
        }
    }

    /**
     * Display the specified office.
     */
    public function show(Office $office)
    {
        $office->load('campus');
        
        // Check if AJAX request
        if (request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            // Return only the modal content
            return view('offices.show-modal', compact('office'));
        }
        
        return view('offices.show', compact('office'));
    }

    /**
     * Show the form for editing the specified office.
     */
    public function edit(Office $office)
    {
        $campuses = Campus::where('is_active', true)->get();
        
        // Check if AJAX request
        if (request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            // Return only the modal content
            return view('offices.edit-modal', compact('office', 'campuses'));
        }
        
        return view('offices.edit', compact('office', 'campuses'));
    }

    /**
     * Update the specified office in storage.
     */
    public function update(Request $request, Office $office)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:offices,name,' . $office->id,
            'campus_id' => 'required|exists:campuses,id',
            'address' => 'nullable|string|max:500',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:offices,email,' . $office->id,
            'is_active' => 'sometimes|boolean',
        ]);

        // Track changes for logging
        $originalData = $office->getOriginal();
        $changes = [];

        // Ensure is_active is set
        $validated['is_active'] = $request->has('is_active');

        try {
            $office->update($validated);

            // Track field changes
            foreach (['name', 'campus_id', 'address', 'contact_number', 'email', 'is_active'] as $field) {
                if ($originalData[$field] != $office->$field) {
                    $oldValue = $originalData[$field];
                    $newValue = $office->$field;
                    
                    if ($field === 'campus_id') {
                        $oldCampus = \App\Models\Campus::find($oldValue);
                        $newCampus = \App\Models\Campus::find($newValue);
                        $changes[$field] = [
                            $oldCampus?->name ?? 'Unknown',
                            $newCampus?->name ?? 'Unknown'
                        ];
                    } else {
                        $changes[$field] = [$oldValue, $newValue];
                    }
                }
            }

            // Log office update
            Activity::logOfficeUpdate($office, $changes);
            
            return redirect()
                ->route('admin.offices.index')
                ->with('success', 'Office updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update office. ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified office from storage.
     */
    public function destroy(Office $office)
    {
        try {
            // Prevent deletion if there are staff members assigned to this office
            if ($office->staff()->exists()) {
                return redirect()
                    ->route('admin.offices.index')
                    ->with('error', 'Cannot delete office with assigned staff members. Please reassign or delete the staff members first.');
            }

            // Log office deletion before actual deletion
            Activity::logOfficeDeletion($office);

            $office->delete();

            return redirect()
                ->route('admin.offices.index')
                ->with('success', 'Office deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.offices.index')
                ->with('error', 'Failed to delete office. ' . $e->getMessage());
        }
    }

    /**
     * Toggle the active status of the specified office.
     */
    public function toggleStatus(Office $office)
    {
        $office->update([
            'is_active' => !$office->is_active
        ]);

        return response()->json([
            'status' => 'success',
            'is_active' => $office->is_active
        ]);
    }
}
