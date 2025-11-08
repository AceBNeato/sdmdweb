<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $permissions = Permission::latest()->get();
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions',
            'description' => 'nullable|string',
        ]);

        Permission::create([
            'name' => Str::slug($request->name),
            'display_name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission created successfully');
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string',
        ]);

        $permission->update([
            'name' => Str::slug($request->name),
            'display_name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission updated successfully');
    }

    public function destroy(Permission $permission)
    {
        if ($permission->roles()->count() > 0) {
            return back()->with('error', 'Cannot delete permission that is assigned to roles');
        }

        $permission->delete();
        return redirect()->route('permissions.index')
            ->with('success', 'Permission deleted successfully');
    }
}
