<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffLoginController extends Controller
{
    protected $redirectTo = '/staff';

    public function __construct()
    {
        $this->middleware('guest:staff')->except('logout');
    }

    protected function guard()
    {
        return Auth::guard('staff');
    }

    /**
     * Show the technician login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        // Check if user is already authenticated with any guard
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.qr-scanner');
        }
        
        if (Auth::guard('staff')->check()) {
            return redirect()->route('staff.equipment.index');
        }
        
        if (Auth::guard('technician')->check()) {
            return redirect()->route('technician.qr-scanner');
        }
        
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
    // app/Http/Controllers/Auth/StaffLoginController.php
public function login(Request $request)
{
    // First, check if any user is already authenticated across all guards
    $existingUser = null;
    $existingGuard = null;
    $guards = ['web', 'staff', 'technician'];
    
    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            $existingUser = Auth::guard($guard)->user();
            $existingGuard = $guard;
            break;
        }
    }
    
    // If someone is already logged in, redirect to their dashboard
    if ($existingUser) {
        $redirectRoute = $existingGuard === 'web' ? 'admin.qr-scanner' : 
                        ($existingGuard === 'staff' ? 'staff.equipment.index' : 'technician.qr-scanner');
        
        return redirect()->route($redirectRoute)->with('info', 
            "User {$existingUser->name} is already logged in. Redirected to their dashboard.");
    }

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::guard('staff')->attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        $staff = Auth::guard('staff')->user();
        
        // IMPORTANT: Logout from all other guards to prevent session conflicts
        $this->logoutFromOtherGuards('staff');

        // Check if user has staff role
        if (!$staff->hasRole('staff')) {
            Auth::guard('staff')->logout();
            return back()->withErrors([
                'email' => 'You do not have staff access. Please contact the administrator.',
            ])->withInput($request->only('email'));
        }

        // Check if staff is active
        if (!$staff->is_active) {
            Auth::guard('staff')->logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ])->withInput($request->only('email'));
        }

        // Log the successful login
        Log::info('Staff logged in', [
            'email' => $staff->email,
            'position' => $staff->position,
            'is_admin' => $staff->is_admin
        ]);

        // Log to activity table using new method
        Activity::logUserLogin($staff);

        return redirect(route('staff.equipment.index'))->with('session_sync', [
            'type' => 'login',
            'user' => $staff->toArray(),
            'redirectUrl' => route('staff.equipment.index')
        ]);
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
        $user = Auth::guard('staff')->user();

        // Log logout before actually logging out
        if ($user) {
            Activity::logUserLogout($user);
        }

        Auth::guard('staff')->logout();

        // Invalidate and regenerate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Fast redirect with minimal headers
        return redirect('/login')->withHeaders([
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
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::guard('staff')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|required_with:new_password|current_password:staff',
            'new_password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // Update the user record directly using the User model
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email']
            ];

            if (isset($validated['phone'])) {
                $updateData['phone'] = $validated['phone'];
            }

            // Update password if provided
            if (!empty($validated['new_password'])) {
                $updateData['password'] = bcrypt($validated['new_password']);
            }

            // Update the user record
            \App\Models\User::where('id', $user->id)->update($updateData);

            // Get updated user data
            $updatedUser = \App\Models\User::find($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'user' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'email' => $updatedUser->email,
                    'phone' => $updatedUser->phone
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the technician's profile.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function profile()
    {
        $user = Auth::guard('staff')->user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to view your profile.');
        }

        // Since all users are in the users table, we don't need to query a separate staff table
        // The user object already contains all the necessary information
        $staff = null; // No separate staff record needed

        return view('staff.profile', compact('user', 'staff'));
    }
    
    /**
     * Logout from all guards except the specified one to prevent session conflicts
     *
     * @param string $currentGuard
     * @return void
     */
    private function logoutFromOtherGuards($currentGuard)
    {
        $guards = ['web', 'staff', 'technician'];
        $logoutPerformed = false;
        
        foreach ($guards as $guard) {
            if ($guard !== $currentGuard && Auth::guard($guard)->check()) {
                // Logout without invalidating the session to prevent CSRF issues
                Auth::guard($guard)->logout();
                $logoutPerformed = true;
            }
        }
        
        if ($logoutPerformed) {
            // Mark that guard logout was performed so we don't regenerate session unnecessarily
            session(['guard_logout_performed' => true]);
            // Regenerate session token to prevent session fixation but keep session data
            session()->regenerateToken();
        }
    }
}
