<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class LoginController extends Controller
{
    protected $maxAttempts = 3;
    protected $decayMinutes = 15;

    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('admin.accounts');
        }
        return view('auth.welcome');
    }

    /**
     * The path to redirect to after login.
     *
     * @return string
     */
    protected function redirectPath()
    {
        return '/home';
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $throttleKey = $this->throttleKey($request);

        if ($this->hasTooManyLoginAttempts($throttleKey)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->clearLoginAttempts($throttleKey);
            $request->session()->regenerate();

            // Update user's active status
            $user = Auth::user();
            User::where('id', $user->id)->update(['is_active' => 1]);

            // Redirect based on user role/position
            if ($user->position === 'Admin') {
                return redirect()->intended(route('admin.accounts'));
            } elseif ($user->position === 'Technician') {
                return redirect()->intended(route('technician.profile'));
            } elseif ($user->position === 'Staff') {
                return redirect()->intended(route('staff.profile'));
            }

            // Default redirect for other roles
            return redirect()->intended($this->redirectPath());
        }

        $this->incrementLoginAttempts($throttleKey);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Get the throttle key for the request.
     */
    protected function throttleKey(Request $request)
    {
        return strtolower($request->input('email')) . '|' . $request->ip();
    }

    /**
     * Determine if the user has too many failed login attempts.
     */
    protected function hasTooManyLoginAttempts($throttleKey)
    {
        return Cache::get($throttleKey, 0) >= $this->maxAttempts;
    }

    /**
     * Increment the login attempts for the user.
     */
    protected function incrementLoginAttempts($throttleKey)
    {
        Cache::add($throttleKey, 0, now()->addMinutes($this->decayMinutes));
        Cache::increment($throttleKey);
    }

    /**
     * Clear the login attempts for the user.
     */
    protected function clearLoginAttempts($throttleKey)
    {
        Cache::forget($throttleKey);
    }

    /**
     * Send the lockout response.
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->decayMinutes * 60;

        return back()->withErrors([
            'email' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect()->route('welcome');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|null
     */
    protected function loggedOut(Request $request)
    {
        // Can be overridden in child classes if needed
        return null;
    }
}
