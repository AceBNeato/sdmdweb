<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Check if user exists with this email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // User not found - redirect with error
                return redirect()->route('login')->with('error',
                    'Your email address is not registered in the system. ' .
                    'Please contact an administrator to be added as a user.'
                );
            }

            // Check if user has verified email
            if (!$user->hasVerifiedEmail()) {
                return redirect()->route('login')->with('error',
                    'Your account email is not verified. Please check your email for verification instructions.'
                );
            }

            // Check if user is active
            if (!$user->is_active) {
                return redirect()->route('login')->with('error',
                    'Your account has been deactivated. Please contact an administrator.'
                );
            }

            // Log the user in
            Auth::login($user, true);

            // Log the successful Google login
            \Illuminate\Support\Facades\Log::info('User logged in via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'ip' => request()->ip(),
            ]);

            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth error: ' . $e->getMessage());

            return redirect()->route('login')->with('error',
                'Unable to login with Google. Please try again or use your email and password.'
            );
        }
    }
}
