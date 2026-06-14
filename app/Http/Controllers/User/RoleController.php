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
            
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('accounts.rbac.roles.index', compact('roles', 'permissions'));
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
        return redirect()->route('admin.rbac.roles.index');
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
            $changedRoles = [];

            foreach ($request->permissions as $roleId => $permissionIds) {
                $role = Role::find($roleId);
                if ($role) {
                    $originalPermissions = $role->permissions()->pluck('name')->toArray();
                    $role->permissions()->sync($permissionIds);

                    $role->load('permissions');
                    $newPermissions = $role->permissions->pluck('name')->toArray();

                    // Only track roles that actually changed
                    $added = array_diff($newPermissions, $originalPermissions);
                    $removed = array_diff($originalPermissions, $newPermissions);

                    if (!empty($added) || !empty($removed)) {
                        $changedRoles[] = [
                            'role' => $role->display_name,
                            'added' => array_values($added),
                            'removed' => array_values($removed),
                        ];
                    }
                }
            }

            // Create a single consolidated activity log for all changes
            if (!empty($changedRoles)) {
                $roleNames = array_column($changedRoles, 'role');
                $description = 'Updated permissions for ' . count($changedRoles) . ' role(s): ' . implode(', ', $roleNames);

                Activity::logSystemManagement(
                    'RBAC Permissions Updated',
                    $description,
                    'rbac',
                    null,
                    ['changes' => $changedRoles],
                    null,
                    'RBAC'
                );
            }

            DB::commit();
            
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                $msg = !empty($changedRoles)
                    ? 'Permissions updated for ' . count($changedRoles) . ' role(s) successfully'
                    : 'No permission changes detected';
                return response()->json([
                    'success' => true,
                    'message' => $msg
                ]);
            }
            
            // Regular redirect for non-AJAX requests
            return redirect()->route('admin.rbac.roles.index')
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
