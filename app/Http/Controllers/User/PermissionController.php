<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Enable permission checks now that RBAC is fully set up
        $this->middleware('permission:permissions.view')->only(['index', 'show']);
        $this->middleware('permission:permissions.create')->only(['create', 'store']);
        $this->middleware('permission:permissions.edit')->only(['edit', 'update']);
        $this->middleware('permission:permissions.delete')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');
            
        return view('admin.rbac.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get unique permission groups for the dropdown
        $groups = Permission::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group');
            
        return view('admin.rbac.permissions.create', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'nullable|string|max:100',
        ]);

        // Generate the name if not provided
        if (empty($validated['name'])) {
            $validated['name'] = Str::slug($validated['display_name'], '.');
        }
        
        // If no group is provided, use 'General'
        if (empty($validated['group'])) {
            $validated['group'] = 'General';
        }

        $permission = Permission::create($validated);

        return redirect()->route('admin.rbac.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        // Get unique permission groups for the dropdown
        $groups = Permission::select('group')
            ->distinct()
            ->whereNotNull('group')
            ->orderBy('group')
            ->pluck('group');
            
        return view('admin.rbac.permissions.edit', compact('permission', 'groups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'name')->ignore($permission->id)
            ],
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'group' => 'nullable|string|max:100',
        ]);
        
        // If no group is provided, use 'General'
        if (empty($validated['group'])) {
            $validated['group'] = 'General';
        }

        $permission->update($validated);

        return redirect()->route('admin.rbac.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.rbac.permissions.index')
                ->with('error', 'Cannot delete permission. It is assigned to one or more roles.');
        }

        $permission->delete();

        return redirect()->route('admin.rbac.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
