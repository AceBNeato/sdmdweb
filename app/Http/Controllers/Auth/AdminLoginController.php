<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the admin login form.
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
        
        // Check if this is a blocked access attempt
        if (request()->has('blocked') || request()->has('logout')) {
            // Mark session as blocked to prevent back button access
            session(['access_blocked' => true]);
            
            // Clear any existing session data
            session()->invalidate();
            session()->regenerateToken();
            
            // Return to login with a clean state
            return redirect('/login/admin')->with('message', 'Access blocked for security reasons.');
        }

        // Check if session was previously blocked
        if (session('access_blocked')) {
            // Clear the block after one successful login page load
            session()->forget('access_blocked');
        }

        return view('auth.admin-login');
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
     * Admin login uses 1-minute lockout period
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

        // Use 60 seconds (1 minute) lockout for admin login
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

            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            // First, try to find the user by email
            $user = User::where('email', $request->email)->first();

            // Handle non-existent or soft-deleted users
            if (!$user) {
                // Add delay to prevent timing attacks
                \Illuminate\Support\Facades\Hash::check('dummy-password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
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

            // Check if user is an admin
            if (!$user->is_admin) {
                RateLimiter::hit($throttleKey);
                $newAttempts = RateLimiter::attempts($throttleKey);
                $newRemaining = max(0, 3 - $newAttempts);

                return back()->withErrors([
                    'email' => 'Access denied. Admin login required.',
                ])->withInput($request->only('email'))->with([
                    'remaining_attempts' => $newRemaining,
                    'lockout' => $newRemaining === 0,
                    'remaining_seconds' => $newRemaining === 0 ? RateLimiter::availableIn($throttleKey) : null,
                ]);
            }

            // Try to authenticate with the web guard
            if (Auth::guard('web')->attempt($credentials, $remember)) {
                $request->session()->regenerate();

                // Clear rate limiter on successful login
                RateLimiter::clear($throttleKey);

                // Clear any lockout session data
                session()->forget(['lockout', 'remaining_seconds', 'remaining_attempts']);
                
                // IMPORTANT: Logout from all other guards to prevent session conflicts
                $this->logoutFromOtherGuards('web');

                // Check if admin is active
                if (!$user->is_active) {
                    Auth::guard('web')->logout();
                    return back()->withErrors([
                        'email' => 'Your admin account has been deactivated. Please contact the administrator.',
                    ])->withInput($request->only('email'));
                }

                // Log successful login
                Log::info('Admin logged in', [
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : null
                ]);

                // Log to activity table using new method
                Activity::logUserLogin($user);

                // Redirect to admin QR scanner
                return redirect()->intended(route('admin.qr-scanner'))->with('session_sync', [
                    'type' => 'login',
                    'user' => $user->toArray(),
                    'redirectUrl' => route('admin.qr-scanner')
                ]);
            }

            // If we get here, authentication failed
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
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // Get the current user before logout
        $user = Auth::user();

        // Log logout before actually logging out
        if ($user) {
            Activity::logUserLogout($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login/admin');
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
