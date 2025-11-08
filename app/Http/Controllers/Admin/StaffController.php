<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['edit', 'update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    /**
     * Display a listing of staff members.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get users with staff role and their relationships
        $staffRole = Role::where('name', 'staff')->first();
        $staff = User::whereHas('roles', function($query) use ($staffRole) {
            $query->where('role_id', $staffRole->id);
        })->with(['office', 'campus'])
          ->latest()
          ->paginate(10);

        return view('admin.accounts.index', compact('staff'));
    }

    /**
     * Show the form for creating a new staff member.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $staff = new User();
        $offices = \App\Models\Office::active()->get();
        $campuses = \App\Models\Campus::active()->get();
        return view('admin.accounts.form', compact('staff', 'offices', 'campuses'));
    }

    /**
     * Store a newly created staff member in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'position' => 'required|string|max:255',
            'office_id' => 'required|exists:offices,id',
            'campus_id' => 'required|exists:campuses,id',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'sometimes|boolean'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = true;

        $user = User::create($validated);

        // Assign staff role
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $user->roles()->attach($staffRole->id);
        }

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    /**
     * Display the specified staff member.
     *
     * @param  \App\Models\User  $staff
     * @return \Illuminate\View\View
     */
    public function show(User $staff)
    {
        return view('admin.accounts.staff.show', compact('staff'));
    }

    /**
     * Show the form for editing the specified staff member.
     *
     * @param  \App\Models\User  $staff
     * @return \Illuminate\View\View
     */
    public function edit(User $staff)
    {
        $offices = \App\Models\Office::active()->get();
        $campuses = \App\Models\Campus::active()->get();
        return view('admin.accounts.form', compact('staff', 'offices', 'campuses'));
    }

    /**
     * Update the specified staff member in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $staff
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($staff->id)],
            'position' => 'required|string|max:255',
            'office_id' => 'required|exists:offices,id',
            'campus_id' => 'required|exists:campuses,id',
            'phone' => 'nullable|string|max:20',
            'is_admin' => 'sometimes|boolean',
            'change_password' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8|confirmed|required_if:change_password,1'
        ]);

        if ($request->change_password) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $staff->update($validated);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    /**
     * Toggle admin status for a staff member.
     *
     * @param  \App\Models\User  $staff
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAdmin(User $staff)
    {
        // Prevent self-demotion
        if (auth()->id() === $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot modify your own admin status.'
            ], 403);
        }

        $staff->update([
            'is_admin' => !$staff->is_admin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin status updated successfully',
            'is_admin' => $staff->is_admin
        ]);
    }

    /**
     * Toggle active status for a staff member.
     *
     * @param  \App\Models\User  $staff
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(User $staff)
    {
        $staff->update([
            'is_active' => !$staff->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'is_active' => $staff->is_active
        ]);
    }

    public function destroy(User $staff)
    {
        // Prevent self-deletion
        if (auth()->id() === $staff->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $staff->delete();
        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member deleted successfully.');
    }
}
