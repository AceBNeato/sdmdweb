<?php

namespace App\Http\Controllers;

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
        $this->middleware('role:admin');
    }

    public function index()
    {
        $roles = Role::with('permissions')->latest()->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => Str::slug($request->name),
                'display_name' => $request->name,
                'description' => $request->description,
            ]);

            $role->permissions()->sync($request->permissions);
            
            DB::commit();
            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating role: ' . $e->getMessage());
        }
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $role->load('permissions');
        return view('roles.edit', compact('role', 'permissions'));
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
            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role with assigned users');
        }

        $role->delete();
        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully');
    }
}
