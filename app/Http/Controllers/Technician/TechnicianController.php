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
            return redirect()->route('login')
                ->with('error', 'Please log in to edit your profile.');
        }

        // For technicians, the user record contains the technician data directly
        $technician = $user;

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', [
                'user' => $technician, // Pass user model to view
            ]);
        }

        return redirect()->route('technician.qr-scanner');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to update your profile.');
        }

        // For technicians, the user record contains the technician data directly
        $technician = $user;

        // First validate the basic fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($technician->id)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'current_password' => 'nullable|required_with:new_password|current_password:technician',
            'new_password' => 'nullable|min:8|confirmed',
            'specialization' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'skills' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Manually check if email is already used by another technician
        $existingTechnician = \App\Models\User::where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'technician');
            })
            ->exists();

        if ($existingTechnician) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'The email has already been taken by another technician.']);
        }

        DB::beginTransaction();

        try {
            // Log the incoming request data for debugging
            Log::info('Technician profile update request data:', [
                'user_id' => $user->id,
                'has_file' => $request->hasFile('profile_photo'),
                'validated_data' => $validated
            ]);

            // Handle profile image upload
            if ($request->hasFile('profile_photo')) {
                Log::info('Profile photo file detected', [
                    'file_name' => $request->file('profile_photo')->getClientOriginalName(),
                    'file_size' => $request->file('profile_photo')->getSize(),
                    'mime_type' => $request->file('profile_photo')->getMimeType()
                ]);

                // Delete old profile image if exists
                if ($user->profile_photo) {
                    $oldImagePath = 'public/' . $user->profile_photo;
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                        Log::info('Deleted old profile photo', ['path' => $oldImagePath]);
                    } else {
                        Log::warning('Old profile photo not found', ['path' => $oldImagePath]);
                    }
                }

                // Store new profile image
                $imagePath = $request->file('profile_photo')->store('profile-photos', 'public');
                $validated['profile_photo'] = str_replace('public/', '', $imagePath);
                Log::info('New profile photo stored', ['path' => $imagePath]);
            } else {
                Log::info('No profile photo file in request');
            }

            // Update all user fields including employee_id in a single update
            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'password' => !empty($validated['new_password']) ? Hash::make($validated['new_password']) : $user->password,
                'specialization' => $validated['specialization'] ?? null,
                'skills' => $validated['skills'] ?? null,
                'is_active' => $validated['is_active'] ?? $user->is_active,
                'employee_id' => $validated['employee_id'] ?? null,
                'profile_photo' => $validated['profile_photo'] ?? $user->profile_photo,
            ];

            // Filter out null values but keep explicit keys (employee_id, profile_photo) even if empty string
            $updateData = array_filter($updateData, function($value, $key) {
                return $key === 'employee_id' || $key === 'profile_photo' ? true : $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            // Log the data that will be updated
            Log::info('Updating technician with data:', $updateData);

            // Perform a single update
            $user->update($updateData);
            Log::info('Technician updated successfully', ['user_id' => $user->id]);

            // Log the activity
            Activity::create([
                'user_id' => $user->id,
                'action' => 'Profile Updated',
                'description' => 'Updated personal information and contact details',
            ]);

            DB::commit();
            Log::info('Transaction committed successfully');

            // Refresh the user model to get updated relationships
            $user->refresh();

            // Update the user in the session
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
                        'skills' => $user->skills
                    ]
                ]);
            }

            return redirect()->route('technician.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating technician profile: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'An error occurred while updating your profile. Please try again.');
        }
    }
}
