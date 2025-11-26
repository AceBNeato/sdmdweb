<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Activity;

class TechnicianLoginController extends Controller
{
    protected $redirectTo = '/technician';

    public function __construct()
    {
        $this->middleware('guest:technician')->except('logout');
    }

    protected function guard()
    {
        return Auth::guard('technician');
    }

    /**
     * Show the technician login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.welcome');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('technician')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $technician = Auth::guard('technician')->user();

            // Check if user has technician role
            if (!$technician->hasRole('technician')) {
                Auth::guard('technician')->logout();
                return back()->withErrors([
                    'email' => 'You do not have technician access. Please contact the administrator.',
                ])->withInput($request->only('email'));
            }

            // Check if technician is active
            if (!$technician->is_available) {
                Auth::guard('technician')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the administrator.',
                ])->withInput($request->only('email'));
            }

            // Log the successful login
            Log::info('Technician logged in', [
                'email' => $technician->email,
                'specialization' => $technician->specialization,
                'employee_id' => $technician->employee_id
            ]);

            // Log to activity table using new method
            Activity::logUserLogin($technician);

            return redirect(route('technician.qr-scanner'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Get the current user before logout
        $user = Auth::guard('technician')->user();

        // Log logout before actually logging out
        if ($user) {
            Activity::logUserLogout($user);
        }

        // Fast logout - just log out the technician guard
        Auth::guard('technician')->logout();

        // Minimal session cleanup for speed
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Fast redirect with minimal headers
        return redirect('/login?logout=' . time())->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache'
        ]);
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        return redirect()->route('welcome');
    }

    /**
     * Show the QR scanner for technicians.
     *
     * @return \Illuminate\View\View
     */
    public function qrScanner()
    {
        return view('qr-scanner');
    }

    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('technician')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'current_password' => 'nullable|required_with:new_password|current_password:technician',
            'new_password' => 'nullable|min:8|confirmed',
            // Technician fields
            'specialization' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'skills' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480',
        ]);

        DB::beginTransaction();

        try {
            // Log the incoming request data for debugging
            \Log::info('Profile update request data:', [
                'user_id' => $user->id,
                'has_file' => $request->hasFile('profile_image'),
                'validated_data' => $validated
            ]);
            // Handle profile image upload
            if ($request->hasFile('profile_photo')) {
                \Log::info('Profile photo file detected', [
                    'file_name' => $request->file('profile_photo')->getClientOriginalName(),
                    'file_size' => $request->file('profile_photo')->getSize(),
                    'mime_type' => $request->file('profile_photo')->getMimeType()
                ]);

                // Delete old profile image if exists
                if ($user->profile_photo) {
                    $oldImagePath = 'public/' . $user->profile_photo;
                    if (Storage::exists($oldImagePath)) {
                        Storage::delete($oldImagePath);
                        \Log::info('Deleted old profile photo', ['path' => $oldImagePath]);
                    } else {
                        \Log::warning('Old profile photo not found', ['path' => $oldImagePath]);
                    }
                }

                // Store new profile image
                $imagePath = $request->file('profile_photo')->store('profile_images', 'public');
                $validated['profile_photo'] = $imagePath;
                \Log::info('New profile photo stored', ['path' => $imagePath]);
            } else {
                \Log::info('No profile photo file in request');
            }

            // Update all user fields including employee_id in a single update
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'password' => !empty($validated['new_password']) ? bcrypt($validated['new_password']) : $user->password,
                'specialization' => $validated['specialization'] ?? null,
                'skills' => $validated['skills'] ?? null,
                'is_available' => $validated['is_active'] ?? true,
                'employee_id' => $validated['employee_id'] ?? null,
                'profile_photo' => $validated['profile_photo'] ?? $user->profile_photo,
            ];

            // Filter out null values but keep empty strings for employee_id and profile_photo
            $updateData = array_filter($updateData, function($value, $key) {
                return $key === 'employee_id' || $key === 'profile_photo' ? true : $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            // Log the data that will be updated
            \Log::info('Updating user with data:', $updateData);

            // Perform a single update
            $user->update($updateData);
            \Log::info('User updated successfully', ['user_id' => $user->id]);

            // Log the activity
            Activity::create([
                'user_id' => $user->id,
                'action' => 'Profile Updated',
                'description' => 'Updated personal information and contact details',
            ]);

            // Update technician fields in users table
            if (!empty($technicianData)) {
                $user->update($technicianData);
                \Log::info('Technician data updated', $technicianData);
            }

            DB::commit();
            \Log::info('Transaction committed successfully');

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
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone
                    ]
                ]);
            }

            return redirect()->route('technician.profile')
                ->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating profile: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            \Log::error('Request data: ' . json_encode($request->except(['current_password', 'new_password', 'new_password_confirmation', 'profile_image'])));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update profile. Please try again.',
                    'error' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to update profile: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Display the technician's profile.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function profile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to view your profile.');
        }

        try {
            // Get fresh user data with all relationships
            $user = User::withTrashed()->findOrFail($user->id);
            
            // Debug log the user data
            \Log::info('User data in profile:', [
                'user_id' => $user->id,
                'specialization' => $user->specialization,
                'employee_id' => $user->employee_id,
                'is_deleted' => $user->trashed()
            ]);

            // The user itself contains the technician data
            $technician = $user;

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
                    'description' => 'Technician account created in the system',
                ]);
                
                // Refetch activities after creating the initial one
                $recentActivities = Activity::where('user_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();
            }

            if (request()->ajax() || request()->boolean('modal')) {
                return view('profile.show_modal', [
                    'user' => $technician,
                    'recentActivities' => $recentActivities,
                ]);
            }

            return redirect()->route('technician.qr-scanner');
            
        } catch (\Exception $e) {
            Log::error('Error loading profile: ' . $e->getMessage());
            return back()->with('error', 'Error loading profile data. Please try again.');
        }
    }

    /**
     * Display the technician's profile edit form.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function editProfile()
    {
        $user = Auth::guard('technician')->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to edit your profile.');
        }

        // Get the technician data from user record (since we integrated technician fields into users table)
        $technician = $user; // The user itself now contains technician data

        if (request()->ajax() || request()->boolean('modal')) {
            return view('profile.edit_modal', compact('technician'));
        }

        return redirect()->route('technician.qr-scanner');
    }

}
