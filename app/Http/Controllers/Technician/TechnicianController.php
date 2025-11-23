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

        DB::beginTransaction();

        try {
            // Verify current password if changing password
            if (!empty($validated['new_password'])) {
                if (empty($validated['current_password'])) {
                    return back()->with('error', 'Current password is required when changing password.');
                }
                if (!Hash::check($validated['current_password'], $user->password)) {
                    return back()->with('error', 'Current password is incorrect.');
                }
            }

            // Log the incoming request data for debugging
            Log::info('Technician profile update request data:', [
                'user_id' => $user->id,
                'has_file' => $request->hasFile('profile_photo'),
                'validated_data' => $validated,
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_photo')) {
                Log::info('Technician profile photo file detected', [
                    'file_name' => $request->file('profile_photo')->getClientOriginalName(),
                    'file_size' => $request->file('profile_photo')->getSize(),
                    'mime_type' => $request->file('profile_photo')->getMimeType(),
                ]);

                // Delete old profile image if exists
                if ($user->profile_photo) {
                    $oldImagePath = 'public/' . $user->profile_photo;
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                        Log::info('Deleted old technician profile photo', ['path' => $oldImagePath]);
                    } else {
                        Log::warning('Old technician profile photo not found', ['path' => $oldImagePath]);
                    }
                }

                // Store new profile image directly to public/storage/profile-photos
                $file = $request->file('profile_photo');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();

                $destination = public_path('storage/profile-photos');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }

                $file->move($destination, $filename);
                $validated['profile_photo'] = 'profile-photos/' . $filename;
                Log::info('New technician profile photo stored', ['path' => $filename]);
            } else {
                Log::info('No technician profile photo file in request');
            }

            // Build update data
            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'employee_id' => $validated['employee_id'] ?? null,
                'specialization' => $validated['specialization'] ?? null,
                'skills' => $validated['skills'] ?? null,
                'profile_photo' => $validated['profile_photo'] ?? $user->profile_photo,
            ];

            // If password is being changed, update password and clear must_change_password flag
            if (!empty($validated['new_password'])) {
                $updateData['password'] = Hash::make($validated['new_password']);
                $updateData['must_change_password'] = false;
                $updateData['password_changed_at'] = now();
                
                // Clear the password change session flag
                session()->forget('must_change_password');
                
                // Log password change
                Activity::logPasswordChange($user);
            }

            // Filter out null values but always keep employee_id and profile_photo keys
            $updateData = array_filter($updateData, function ($value, $key) {
                return in_array($key, ['employee_id', 'profile_photo'], true) ? true : $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            Log::info('Updating technician user with data:', $updateData);

            // Perform update
            $user->update($updateData);
            Log::info('Technician user updated successfully', ['user_id' => $user->id]);

            // Log the activity
            Activity::create([
                'user_id' => $user->id,
                'type' => 'profile_updated',
                'description' => 'Technician profile updated',
            ]);

            DB::commit();
            Log::info('Technician profile transaction committed successfully');

            // Refresh user and update guard session
            $user->refresh();
            Auth::guard('technician')->setUser($user);

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
                        'skills' => $user->skills,
                    ],
                ]);
            }

            return redirect()->route('technician.profile')
                ->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Technician profile update error: ' . $e->getMessage(), [
                'technician_id' => $user->id,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null,
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'An error occurred while updating your profile. Please try again.');
        }
    }
}
