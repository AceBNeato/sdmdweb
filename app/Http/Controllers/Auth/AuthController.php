<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Staff;
use App\Models\Technician;
use App\Models\Activity;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /**
     * Display the password reset view for the given token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetPasswordForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Handle a password reset request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showLoginForm()
    {
        // Check if this is a blocked access attempt
        if (request()->has('blocked') || request()->has('logout')) {
            // Create an infinite redirect loop to prevent back button access
            return redirect('/login?blocked=' . time())->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0, no-transform, private, proxy-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Refresh' => '0; url=/login?blocked=' . time()
            ]);
        }

        return view('auth.login');
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function ensureIsNotRateLimited(Request $request)
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
            return;
        }

        event(new Lockout($request));

        // Use 60 seconds (1 minute) lockout for regular login
        $seconds = 60;

        // Store lockout data in session
        session([
            'lockout' => true,
            'remaining_seconds' => $seconds,
            'remaining_attempts' => 0
        ]);

        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            // 'g-recaptcha-response' => 'recaptcha', // Temporarily disabled for testing
        ]);

        // Clear any previous lockout session data
        session()->forget(['lockout', 'remaining_seconds', 'remaining_attempts']);

        // Get current rate limiting status
        $throttleKey = $this->throttleKey($request);
        $attempts = RateLimiter::attempts($throttleKey);
        $remainingAttempts = max(0, 3 - $attempts); // 3 max attempts

        try {
            // Check for too many login attempts
            $this->ensureIsNotRateLimited($request);

            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            // First, try to find the user by email
            $user = User::where('email', $request->email)->first();

            // Handle non-existent or soft-deleted users
            if (!$user) {
                // Add delay to prevent timing attacks
                Hash::check('dummy-password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
                RateLimiter::hit($throttleKey);

                $newAttempts = RateLimiter::attempts($throttleKey);
                $newRemaining = max(0, 3 - $newAttempts);

                return back()->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])->withInput($request->only('email'))->with([
                    'remaining_attempts' => $newRemaining,
                    'lockout' => $newRemaining === 0,
                    'remaining_seconds' => $newRemaining === 0 ? RateLimiter::availableIn($throttleKey) : null,
                ]);
            }

            // Block deactivated accounts immediately (applies to all guards)
            if (!$user->is_active) {
                RateLimiter::hit($throttleKey);
                $newAttempts = RateLimiter::attempts($throttleKey);
                $newRemaining = max(0, 3 - $newAttempts);

                return back()->withErrors([
                    'email' => 'Account has been deactivated. Please contact the SDMD administrator.',
                ])->withInput($request->only('email'))->with([
                    'remaining_attempts' => $newRemaining,
                    'lockout' => $newRemaining === 0,
                    'remaining_seconds' => $newRemaining === 0 ? RateLimiter::availableIn($throttleKey) : null,
                ]);
            }

            // Check if user is an admin - reject them from this login page but count as attempt
            if ($user->is_admin) {
                // Still increment the attempts for security
                RateLimiter::hit($throttleKey);
                $newAttempts = RateLimiter::attempts($throttleKey);
                $newRemaining = max(0, 3 - $newAttempts);

                return back()->withErrors([
                    'email' => 'The provided credentials do not match our records.',
                ])->withInput($request->only('email'))->with([
                    'remaining_attempts' => $newRemaining,
                    'lockout' => $newRemaining === 0,
                    'remaining_seconds' => $newRemaining === 0 ? RateLimiter::availableIn($throttleKey) : null,
                    'lockout_start_time' => $newRemaining === 0 ? now()->timestamp : null,
                    'current_attempts' => $newAttempts,
                ]);
            }

            // Determine which guard to use based on user role attributes
            $guard = 'web'; // Default fallback
            if ($user->is_technician) {
                $guard = 'technician';
            } elseif ($user->is_staff) {
                $guard = 'staff';
            } else {
                // User has no valid role for this login page
                return back()->withErrors([
                    'email' => 'Access denied. Please use the appropriate login page.',
                ])->withInput($request->only('email'));
            }

            // Try to authenticate with the appropriate guard
            if (Auth::guard($guard)->attempt($credentials, $remember)) {
                $request->session()->regenerate();

                // Clear rate limiter on successful login
                RateLimiter::clear($throttleKey);

                // Clear any lockout session data
                session()->forget(['lockout', 'remaining_seconds', 'remaining_attempts']);

                // Check user active status based on role
                $user = Auth::guard($guard)->user();
                if ($user->is_technician && !$user->is_available) {
                    Auth::guard($guard)->logout();
                    return back()->withErrors([
                        'email' => 'Account has been disabled. Please contact the SDMD administrator.',
                    ])->withInput($request->only('email'));
                } elseif ($user->is_staff && !$user->is_active) {
                    Auth::guard($guard)->logout();
                    return back()->withErrors([
                        'email' => 'Account has been disabled. Please contact the SDMD administrator.',
                    ])->withInput($request->only('email'));
                } elseif (($user->is_admin) && !$user->is_active) {
                    Auth::guard($guard)->logout();
                    return back()->withErrors([
                        'email' => 'Account has been disabled. Please contact the SDMD administrator.',
                    ])->withInput($request->only('email'));
                }

                // Log successful login
                Log::info('User logged in', [
                    'email' => $user->email,
                    'guard' => $guard,
                    'role' => $user->role ? $user->role->name : null
                ]);

                // Log to activity table using new method
                Activity::logUserLogin($user);

                // Redirect based on user roles
                if ($user->is_admin) {
                    return redirect()->intended(route('admin.qr-scanner'));
                } elseif ($user->is_technician) {
                    return redirect()->intended(route('technician.qr-scanner'));
                } elseif ($user->is_staff) {
                    return redirect()->intended(route('staff.equipment.index'));
                } else {
                    return redirect()->intended(route('welcome'));
                }
            }

            // If we get here, authentication failed
            RateLimiter::hit($throttleKey);

            $newAttempts = RateLimiter::attempts($throttleKey);
            $newRemaining = max(0, 3 - $newAttempts);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records or you do not have permission to access this area.',
            ])->withInput($request->only('email'))->with([
                'remaining_attempts' => $newRemaining,
                'lockout' => $newRemaining === 0,
                'remaining_seconds' => $newRemaining === 0 ? RateLimiter::availableIn($throttleKey) : null,
                'lockout_start_time' => $newRemaining === 0 ? now()->timestamp : null,
                'current_attempts' => $newAttempts,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $lockout = session('lockout', false);
            $remainingSeconds = session('remaining_seconds', null);

            return back()->withErrors($errors)->withInput($request->only('email'))->with([
                'remaining_attempts' => 0,
                'lockout' => $lockout,
                'remaining_seconds' => $remainingSeconds,
            ]);
        }
    }

    /**
     * Handle successful authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        // Determine the redirect path based on user roles
        if ($user->is_super_admin || $user->is_admin) {
            $route = 'accounts.index';
        } elseif ($user->hasRole('technician')) {
            $route = 'technician.profile';
        } elseif ($user->hasRole('staff')) {
            $route = 'staff.profile';
        } else {
            $route = 'welcome';
        }

        return redirect()->intended(route($route));
    }

    public function logout(Request $request)
    {
        // Get the current user before logout
        $user = null;
        $guards = ['web', 'staff', 'technician'];
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                break;
            }
        }

        // Log logout before actually logging out
        if ($user) {
            Activity::logUserLogout($user);
        }

        // Fast logout - only logout authenticated guards
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();
            }
        }

        // Quick session cleanup for speed
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Ultra-fast redirect with minimal headers
        return redirect('/login?logout=' . time())->withHeaders([
            'Cache-Control' => 'no-cache, no-store',
            'Pragma' => 'no-cache'
        ]);
    }

    /**
     * Unlock session for authenticated users by verifying the password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlockSession(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Check all guards to find the authenticated user
        $user = null;
        $guards = ['web', 'staff', 'technician'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                break;
            }
        }

        if (!$user) {
            \Log::info('No authenticated user for unlock attempt');
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        \Log::info('Unlock session attempt for user ID: ' . $user->id . ', Email: ' . $user->email);

        if (Hash::check($request->password, $user->password)) {
            \Log::info('Password check passed for user ID: ' . $user->id);
            // Reset the session's "locked" state or update last activity
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        // Fallback for plain text passwords (if not hashed)
        if ($request->password === $user->password) {
            \Log::info('Plain text password matched for user ID: ' . $user->id . ', hashing it now');
            $user->password = $request->password; // This will be hashed by the model cast
            $user->save();
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        \Log::info('Password check failed for user ID: ' . $user->id . ', Provided password length: ' . strlen($request->password));
        return response()->json(['success' => false, 'message' => 'Invalid password.'], 401);
    }

    /**
     * Unlock session for staff users by verifying the password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlockSessionStaff(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::guard('staff')->user();

        if (!$user) {
            \Log::info('No staff user authenticated for unlock attempt');
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        \Log::info('Staff unlock session attempt for user ID: ' . $user->id . ', Email: ' . $user->email);

        if (Hash::check($request->password, $user->password)) {
            \Log::info('Password check passed for staff user ID: ' . $user->id);
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        // Fallback for plain text passwords (if not hashed)
        if ($request->password === $user->password) {
            \Log::info('Plain text password matched for staff user ID: ' . $user->id . ', hashing it now');
            $user->password = $request->password; // This will be hashed by the model cast
            $user->save();
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        \Log::info('Password check failed for staff user ID: ' . $user->id . ', Provided password length: ' . strlen($request->password));
        return response()->json(['success' => false, 'message' => 'Invalid password.'], 401);
    }

    /**
     * Unlock session for technician users by verifying the password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlockSessionTechnician(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::guard('technician')->user();

        if (!$user) {
            \Log::info('No technician user authenticated for unlock attempt');
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        \Log::info('Technician unlock session attempt for user ID: ' . $user->id . ', Email: ' . $user->email);

        if (Hash::check($request->password, $user->password)) {
            \Log::info('Password check passed for technician user ID: ' . $user->id);
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        // Fallback for plain text passwords (if not hashed)
        if ($request->password === $user->password) {
            \Log::info('Plain text password matched for technician user ID: ' . $user->id . ', hashing it now');
            $user->password = $request->password; // This will be hashed by the model cast
            $user->save();
            session(['last_activity' => now()]);
            return response()->json(['success' => true]);
        }

        \Log::info('Password check failed for technician user ID: ' . $user->id . ', Provided password length: ' . strlen($request->password));
        return response()->json(['success' => false, 'message' => 'Invalid password.'], 401);
    }

    /**
     * Get session settings for dynamic updates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSessionSettings(Request $request)
    {
        // Check if user is authenticated with any guard
        $user = null;
        $guards = ['web', 'staff', 'technician'];

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                break;
            }
        }

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'lockoutTimeoutMinutes' => \App\Models\Setting::getSessionLockoutMinutes(),
            'timeoutTimeoutMinutes' => \App\Models\Setting::getSessionTimeoutMinutes(),
        ]);
    }
}
