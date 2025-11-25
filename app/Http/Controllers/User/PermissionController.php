<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Activity;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Enable permission checks now that RBAC is fully set up
        $this->middleware('permission:permissions.view')->only(['index', 'show']);
        $this->middleware('permission:permissions.edit')->only(['edit', 'update']);
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

        Activity::logSystemManagement(
            'RBAC Permission Created',
            'Created permission "' . $permission->display_name . '" (' . $permission->name . ')',
            'rbac',
            $permission->id,
            [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'group' => $permission->group,
            ],
            null,
            'RBAC'
        );

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
        
        $originalPermission = $permission->replicate();
        
        // If no group is provided, use 'General'
        if (empty($validated['group'])) {
            $validated['group'] = 'General';
        }

        $permission->update($validated);

        Activity::logSystemManagement(
            'RBAC Permission Updated',
            'Updated permission "' . $originalPermission->display_name . '" (' . $originalPermission->name . ')',
            'rbac',
            $permission->id,
            [
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'group' => $permission->group,
            ],
            [
                'name' => $originalPermission->name,
                'display_name' => $originalPermission->display_name,
                'description' => $originalPermission->description,
                'group' => $originalPermission->group,
            ],
            'RBAC'
        );

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

        $permissionData = [
            'name' => $permission->name,
            'display_name' => $permission->display_name,
            'description' => $permission->description,
            'group' => $permission->group,
        ];

        $permission->delete();

        Activity::logSystemManagement(
            'RBAC Permission Deleted',
            'Deleted permission "' . $permissionData['display_name'] . '" (' . $permissionData['name'] . ')',
            'rbac',
            $permission->id,
            null,
            $permissionData,
            'RBAC'
        );

        return redirect()->route('admin.rbac.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
