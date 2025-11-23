<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Equipment;
use App\Models\Staff;
use App\Models\Office;
use App\Models\User;
use App\Models\EquipmentType;
use App\Models\Category;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;



class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:staff');
    }

    /**
     * Display the staff dashboard/profile.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        /** @var \App\Models\Staff $user */
        $user = Auth::guard('staff')->user();
        // Eager load the office relationship
        $user->load('office');
        
        // Fetch recent activities for the user
        $recentActivities = Activity::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();
        
        // If no activities exist, create an initial 'Account Created' activity
        if ($recentActivities->isEmpty()) {
            Activity::create([
                'user_id' => $user->id,
                'action' => 'Account Created',
                'description' => 'Staff account created in the system',
            ]);
            
            // Refetch activities after creating the initial one
            $recentActivities = Activity::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();
        }
        
        // If requested via AJAX or modal query, return the modal partial
        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.show_modal', compact('user', 'recentActivities'));
        }

        // Redirect to equipment page since staff doesn't have a profile page
        return redirect()->route('staff.equipment.index');
    }

    /**
     * Display a listing of the equipment assigned to the staff's office.
     *
     * @return \Illuminate\View\View
     */
    public function equipment()
    {
        $user = Auth::guard('staff')->user();

        // Eager load the office relationship
        $user->load('office');

        if (!$user->office) {
            return redirect()->route('staff.profile')
                ->with('warning', 'You are not assigned to any office. Please contact the administrator.');
        }

        // Get equipment assigned to the staff's office
        $equipment = Equipment::where('office_id', $user->office->id)
            ->with(['category', 'maintenanceLogs' => function($query) {
                $query->latest()->limit(5);
            }])
            ->paginate(10);

        // Data needed by shared equipment.index view
        $equipmentTypes = EquipmentType::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $campuses = Campus::with('offices')->where('is_active', true)->orderBy('name')->get();

        return view('equipment.index', compact('equipment', 'equipmentTypes', 'categories', 'campuses'));
    }

    /**
     * Display a listing of equipment assigned to the staff's office.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function officeEquipment()
    {
        // Redirect to the main equipment method since they now do the same thing
        return $this->equipment();
    }

    /**
     * Display the specified equipment.
     *
     * @param  \App\Models\Equipment  $equipment
     * @return \Illuminate\View\View
     */
    public function showEquipment(Equipment $equipment)
    {
        // Verify the equipment belongs to the current staff's office
        $user = Auth::guard('staff')->user();

        // Eager load the office relationship
        $user->load('office');

        if (!$user->office || $equipment->office_id !== $user->office->id) {
            abort(403, 'You do not have access to this equipment.');
        }

        // Load related data
        $equipment->load(['category', 'maintenanceLogs' => function($query) {
            $query->latest();
        }]);

        // For modal/AJAX requests, return the modal partial and pass guard prefix
        $prefix = 'staff';
        if (request()->ajax() || request()->boolean('modal')) {
            return view('equipment.show_modal', compact('equipment', 'user', 'prefix'));
        }

        return view('equipment.show', compact('equipment', 'user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('staff')->user();

        // First validate the basic fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'current_password' => 'nullable|required_with:new_password|current_password:staff',
            'new_password' => 'nullable|min:8|confirmed',
            'specialization' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'skills' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Manually check if email is already used by another staff member
        $existingStaff = \App\Models\User::where('email', $validated['email'])
            ->where('id', '!=', $user->id)
            ->whereHas('role', function ($q) {
                $q->where('name', 'staff');
            })
            ->exists();

        if ($existingStaff) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'The email has already been taken by another staff member.']);
        }

        DB::beginTransaction();

        try {
            // Log the incoming request data for debugging
            Log::info('Staff profile update request data:', [
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

                // Store new profile image directly to public/storage/profile-photos
                $file = $request->file('profile_photo');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                
                $destination = public_path('storage/profile-photos');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                
                $file->move($destination, $filename);
                $validated['profile_photo'] = 'profile-photos/' . $filename;
                Log::info('New profile photo stored', ['path' => $filename]);
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
                'specialization' => $validated['specialization'] ?? null,
                'skills' => $validated['skills'] ?? null,
                'is_active' => $validated['is_active'] ?? $user->is_active,
                'employee_id' => $validated['employee_id'] ?? null,
                'profile_photo' => $validated['profile_photo'] ?? $user->profile_photo,
            ];

            // If password is being changed, update password and clear must_change_password flag
            if (!empty($validated['new_password'])) {
                $updateData['password'] = Hash::make($validated['new_password']);
                $updateData['must_change_password'] = false;
                $updateData['password_changed_at'] = now();
                
                // Log password change
                Activity::logPasswordChange($user);
            }

            // Filter out null values but keep explicit keys (employee_id, profile_photo) even if empty string
            $updateData = array_filter($updateData, function($value, $key) {
                return $key === 'employee_id' || $key === 'profile_photo' ? true : $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            // Log the data that will be updated
            Log::info('Updating user with data:', $updateData);

            // Perform a single update
            $user->update($updateData);
            Log::info('User updated successfully', ['user_id' => $user->id]);

            // Log the activity
            Activity::create([
                'user_id' => $user->id,
                'type' => 'profile_updated',
                'description' => 'Staff profile updated',
            ]);

            DB::commit();
            Log::info('Transaction committed successfully');

            // Refresh the user model to get updated relationships
            $user->refresh();
            
            // Update the user in the session
            Auth::guard('staff')->setUser($user);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile updated successfully!',
                    'redirect' => route('staff.profile'),
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

            return redirect()->route('staff.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating staff profile: ' . $e->getMessage());
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

    /**
     * Show the form for editing the staff's profile.
     *
     * @return \Illuminate\View\View
     */
    public function editProfile()
    {
        $user = Auth::guard('staff')->user();

        if (!$user) {
            return redirect()->route('staff.login')->with('error', 'Please log in to edit your profile.');
        }

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', compact('user'));
        }

        return redirect()->route('staff.dashboard');
    }
}
