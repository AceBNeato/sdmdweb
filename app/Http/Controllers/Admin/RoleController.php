<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Enable permission checks
        $this->middleware('permission:roles.view')->only(['index']);
        $this->middleware('permission:roles.edit')->only(['edit', 'update']);
    }

    public function index()
    {
        $user = auth()->user();
        $roles = Role::with('permissions')
            ->when(!$user->is_admin, function($query) {
                // Non-admin users can only see non-admin roles
                return $query->where('name', '!=', 'admin');
            })
            ->latest()
            ->get();

        return view('admin.rbac.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('admin.rbac.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role->update([
                'name' => Str::slug($request->name),
                'display_name' => $request->name,
                'description' => $request->description,
            ]);

            $role->permissions()->sync($request->permissions);

            DB::commit();
            return redirect()->route('admin.rbac.roles.index')
                ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function permissions()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        return view('admin.rbac.roles.permissions', compact('roles', 'permissions'));
    }

    public function updatePermissions(Request $request)
    {
        $request->validate([
            'role_permissions' => 'required|array',
            'role_permissions.*' => 'array',
            'role_permissions.*.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->role_permissions as $roleId => $permissionIds) {
                $role = Role::find($roleId);
                if ($role) {
                    // Sync permissions - add checked, remove unchecked
                    $role->permissions()->sync($permissionIds);
                }
            }

            DB::commit();
            return redirect()->route('admin.rbac.roles.permissions')
                ->with('success', 'Role permissions updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating role permissions: ' . $e->getMessage());
        }
    }
}
