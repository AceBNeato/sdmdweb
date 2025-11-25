<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Activity;

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

        return view('accounts.rbac.roles.index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('accounts.rbac.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $originalRole = $role->replicate();
        $originalPermissions = $role->permissions()->pluck('name')->toArray();

        DB::beginTransaction();
        try {
            $role->update([
                'name' => Str::slug($request->name),
                'display_name' => $request->name,
                'description' => $request->description,
            ]);

            $role->permissions()->sync($request->permissions);

            $role->load('permissions');
            $newPermissions = $role->permissions->pluck('name')->toArray();

            Activity::logSystemManagement(
                'RBAC Role Updated',
                'Updated role "' . $originalRole->display_name . '" (ID: ' . $role->id . ')',
                'rbac',
                $role->id,
                [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'permissions' => implode(', ', $newPermissions),
                ],
                [
                    'name' => $originalRole->name,
                    'display_name' => $originalRole->display_name,
                    'description' => $originalRole->description,
                    'permissions' => implode(', ', $originalPermissions),
                ],
                'RBAC'
            );

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
        return view('accounts.rbac.roles.permissions', compact('roles', 'permissions'));
    }

    public function updatePermissions(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'array',
            'permissions.*.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->permissions as $roleId => $permissionIds) {
                $role = Role::find($roleId);
                if ($role) {
                    $originalPermissions = $role->permissions()->pluck('name')->toArray();
                    $role->permissions()->sync($permissionIds);

                    $role->load('permissions');
                    $newPermissions = $role->permissions->pluck('name')->toArray();

                    Activity::logSystemManagement(
                        'RBAC Role Permissions Updated',
                        'Updated permissions for role "' . $role->display_name . '" (ID: ' . $role->id . ')',
                        'rbac',
                        $role->id,
                        [
                            'permissions' => implode(', ', $newPermissions),
                        ],
                        [
                            'permissions' => implode(', ', $originalPermissions),
                        ],
                        'RBAC'
                    );
                }
            }

            DB::commit();
            
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role permissions updated successfully'
                ]);
            }
            
            // Regular redirect for non-AJAX requests
            return redirect()->route('admin.rbac.roles.permissions')
                ->with('success', 'Role permissions updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating role permissions: ' . $e->getMessage()
                ], 500);
            }
            
            // Regular redirect for non-AJAX requests
            return back()->with('error', 'Error updating role permissions: ' . $e->getMessage());
        }
    }
}
