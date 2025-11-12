<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:technician');
    }

    public function dashboard()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to access the technician dashboard.');
        }

        // Get technician stats or data for dashboard
        $stats = [
            'total_equipment' => 0, // You can implement this based on your needs
            'recent_activities' => [],
            'pending_tasks' => []
        ];

        return view('technician.dashboard', compact('stats'));
    }

    public function profile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to view your profile.');
        }

        try {
            // For technicians, the user record contains the technician data directly
            $technician = $user;

            // Load office relationship if it exists
            $technician->loadMissing('office');

            $recentActivities = Activity::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            if ($recentActivities->isEmpty()) {
                Activity::create([
                    'user_id' => $user->id,
                    'action' => 'Account Created',
                    'description' => 'Technician account created in the system',
                ]);

                $recentActivities = Activity::where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();
            }

            if (request()->ajax() || request()->boolean('modal')) {
                return view('profile.show_modal', [
                    'user' => $technician, // Pass user model to view
                    'recentActivities' => $recentActivities,
                ]);
            }

            return redirect()->route('technician.qr-scanner');
        } catch (\Exception $e) {
            Log::error('Technician profile error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? null,
            ]);

            return back()->with('error', 'Failed to load profile. Please try again.');
        }
    }

    public function editProfile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('technician.login')->with('error', 'Please log in to edit your profile.');
        }

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', [
                'user' => $user,
            ]);
        }

        return redirect()->route('technician.dashboard');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('technician.login')->with('error', 'Please log in to update your profile.');
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
                'description' => 'Technician profile updated',
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully!',
                    'redirect' => route('technician.profile'),
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'profile_photo' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                        'specialization' => $user->specialization,
                        'employee_id' => $user->employee_id,
                        'skills' => $user->skills
                    ]
                ]);
            }

            return back()->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Technician profile update error: ' . $e->getMessage(), [
                'technician_id' => $user->id,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->with('error', 'Failed to update profile. Please try again.');
        }
    }
}
