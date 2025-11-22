<?php

namespace App\Http\Controllers\User;

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
    public function dashboard()
    {
        // Redirect admin directly to QR scanner
        return redirect()->route('admin.qr-scanner');
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function accounts(Request $request)
    {
        $query = User::with(['role', 'office', 'campus'])
            ->where(function($q) {
                $q->whereNull('role_id')
                  ->orWhereHas('role', function($r) {
                      $r->where('name', '!=', 'super-admin');
                  });
            })
            ->latest();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->whereHas('role', function($q) use ($request) {
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
                'user' => $user,
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to update your profile.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
            'skills' => 'nullable|string',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:8',
            'new_password_confirmation' => 'nullable|string|min:8|same:new_password',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            // Verify current password if changing password
            if (!empty($validated['new_password'])) {
                if (empty($validated['current_password'])) {
                    return back()->with('error', 'Current password is required when changing password.');
                }
                if (!\Hash::check($validated['current_password'], $user->password)) {
                    return back()->with('error', 'Current password is incorrect.');
                }
            }

            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'skills' => $validated['skills'] ?? null,
            ];

            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                try {
                    $file = $request->file('profile_photo');
                    $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                    
                    // Save directly to public/storage/profile-photos to bypass symlink issues
                    $destination = public_path('storage/profile-photos');
                    if (!file_exists($destination)) {
                        mkdir($destination, 0755, true);
                    }
                    
                    $file->move($destination, $filename);
                    $updateData['profile_photo'] = 'profile-photos/' . $filename;
                } catch (\Exception $e) {
                    \Log::error('Profile photo upload error: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                    ]);
                    return back()->with('error', 'Failed to upload profile photo. Please try again.');
                }
            }

            $user->update($updateData);

            // Update password if provided
            if (!empty($validated['new_password'])) {
                $user->update(['password' => \Hash::make($validated['new_password'])]);
            }

            // Log the activity
            Activity::create([
                'user_id' => $user->id,
                'action' => 'Profile Updated',
                'description' => 'Admin profile updated',
            ]);

            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Admin profile update error: ' . $e->getMessage(), [
                'admin_id' => $user->id,
            ]);

            return back()->with('error', 'Failed to update profile. Please try again.');
        }
    }
}
