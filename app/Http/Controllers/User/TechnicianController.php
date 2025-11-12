<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['edit', 'update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index()
    {
        $technicians = Technician::with('staff')
            ->withCount('assignedMaintenances')
            ->latest()
            ->paginate(10);
        return view('admin.accounts.technicians.index', compact('technicians'));
    }

    public function create()
    {
        $technician = new Technician();
        $staff = Staff::active()
            ->whereDoesntHave('technician')
            ->get(['id', 'first_name', 'last_name', 'email']);
            
        return view('admin.accounts.technicians.form', compact('technician', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id|unique:technicians,staff_id',
            'specialization' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $staff = Staff::findOrFail($request->staff_id);
            
            $technician = Technician::create([
                'staff_id' => $staff->id,
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            return redirect()
                ->route('admin.technicians.index')
                ->with('success', 'Technician added successfully');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create technician: ' . $e->getMessage());
        }
    }

    public function edit(Technician $technician)
    {
        $staff = Staff::active()->get(['id', 'first_name', 'last_name', 'email']);
        return view('admin.accounts.technicians.form', compact('technician', 'staff'));
    }

    public function show(Technician $technician)
    {
        // Load staff relationship to get office info
        $technician->loadMissing('staff.office');

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.show_modal', [
                'user' => $technician,
                'recentActivities' => [] // Technicians don't have activities in this system yet
            ]);
        }

        return redirect()->route('admin.technicians.index');
    }

    public function update(Request $request, Technician $technician)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id|unique:technicians,staff_id,' . $technician->id,
            'specialization' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $technician->update([
                'specialization' => $validated['specialization'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true
            ]);

            return redirect()
                ->route('admin.technicians.index')
                ->with('success', 'Technician updated successfully');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update technician: ' . $e->getMessage());
        }
    }

    public function destroy(Technician $technician)
    {
        try {
            // Check if technician has any assigned maintenances
            if ($technician->assignedMaintenances()->exists()) {
                return back()
                    ->with('error', 'Cannot delete technician with assigned maintenance records');
            }
            
            $technician->delete();
            
            return redirect()
                ->route('admin.technicians.index')
                ->with('success', 'Technician removed successfully');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete technician: ' . $e->getMessage());
        }
    }
}
