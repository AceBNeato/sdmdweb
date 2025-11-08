<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Category;
use App\Models\Campus;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function accounts(Request $request)
    {
        $query = User::with(['roles', 'office', 'campus'])
            ->withCount('roles')
            ->whereDoesntHave('roles', function($q) {
                $q->where('name', 'super-admin');
            })
            ->latest();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by office
        if ($request->has('office') && $request->office !== 'all') {
            $query->where('office_id', $request->office);
        }

        $users = $query->paginate(10)->appends($request->except('page'));
        $roles = Role::where('name', '!=', 'super-admin')->get();
        $campuses = \App\Models\Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        // Determine the view based on the route
        if ($request->routeIs('admin.accounts')) {
            $view = 'accounts.index';
        } elseif ($request->routeIs('staff.accounts')) {
            $view = 'accounts.index';
        } elseif ($request->routeIs('technician.accounts')) {
            $view = 'accounts.index';
        } else {
            $view = 'accounts.index'; // default
        }

        return view($view, compact('users', 'roles', 'campuses'));
    }

    public function equipment(Request $request)
    {
        // Build query for equipment
        $query = Equipment::with(['equipmentType', 'office', 'category']);

        // Apply filters
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('equipment_type') && $request->equipment_type !== 'all') {
            $query->where('equipment_type_id', $request->equipment_type);
        }

        if ($request->has('office_id') && $request->office_id !== 'all') {
            $query->where('office_id', $request->office_id);
        }

        if ($request->has('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        $equipment = $query->paginate(10)->appends($request->except('page'));
        $equipmentTypes = EquipmentType::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        return view('equipment.index', compact('equipment', 'equipmentTypes', 'categories', 'campuses'));
    }
}
