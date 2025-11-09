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
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;

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

      return view('accounts.index', compact('users', 'roles', 'campuses'));
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

    /**
     * Show the admin profile in a modal-only flow.
     */
    public function profile()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to view your profile.');
        }

        try {
            $user->loadMissing(['office', 'campus']);

            $recentActivities = Activity::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            if ($recentActivities->isEmpty()) {
                Activity::create([
                    'user_id' => $user->id,
                    'action' => 'Account Created',
                    'description' => 'Admin account created in the system',
                ]);

                $recentActivities = Activity::where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();
            }

            if (request()->ajax() || request()->boolean('modal')) {
                return view('profile.show_modal', [
                    'user' => $user,
                    'recentActivities' => $recentActivities,
                ]);
            }

            return redirect()->route('admin.dashboard');
        } catch (\Exception $e) {
            Log::error('Admin profile error: ' . $e->getMessage(), [
                'admin_id' => $user->id ?? null,
            ]);

            return back()->with('error', 'Failed to load profile. Please try again.');
        }
    }

    /**
     * Show the admin edit profile modal.
     */
    public function editProfile()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to edit your profile.');
        }

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', [
                'admin' => $user,
            ]);
        }

        return redirect()->route('admin.dashboard');
    }
}
