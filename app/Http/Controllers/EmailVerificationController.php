<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailService;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Show email verification notice
     */
    public function showVerificationNotice(Request $request)
    {
        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('info', 'Your email is already verified.');
        }

        return view('auth.verify-email');
    }

    /**
     * Verify email address
     */
    public function verify(Request $request, $token)
    {
        try {
            // Find user by verification token
            $user = User::where('email_verification_token', $token)->first();

            if (!$user) {
                // Clear all session data and cookies to prevent login conflicts
            $request->session()->flush();
            $request->session()->regenerateToken();
            
            // Clear all authentication cookies
            \Cookie::queue(\Cookie::forget(config('session.cookie')));
            \Cookie::queue(\Cookie::forget('remember_web'));
            \Cookie::queue(\Cookie::forget('remember_staff')); 
            \Cookie::queue(\Cookie::forget('remember_technician'));

            return redirect()->route('login')
                ->with('error', 'Invalid verification token.');
            }

            // Check if token is expired
            if ($user->email_verification_token_expires_at && Carbon::now()->isAfter($user->email_verification_token_expires_at)) {
                // Clear all session data and cookies to prevent login conflicts
            $request->session()->flush();
            $request->session()->regenerateToken();
            
            // Clear all authentication cookies
            \Cookie::queue(\Cookie::forget(config('session.cookie')));
            \Cookie::queue(\Cookie::forget('remember_web'));
            \Cookie::queue(\Cookie::forget('remember_staff')); 
            \Cookie::queue(\Cookie::forget('remember_technician'));

            return redirect()->route('login')
                    ->with('error', 'Verification token has expired. Please contact your administrator to resend verification email.');
            }

            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                // Clear all session data and cookies to prevent login conflicts
            $request->session()->flush();
            $request->session()->regenerateToken();
            
            // Clear all authentication cookies
            \Cookie::queue(\Cookie::forget(config('session.cookie')));
            \Cookie::queue(\Cookie::forget('remember_web'));
            \Cookie::queue(\Cookie::forget('remember_staff')); 
            \Cookie::queue(\Cookie::forget('remember_technician'));

            return redirect()->route('login')
                    ->with('info', 'Your email is already verified. You can now login.');
            }

            // Verify the email
            $user->email_verified_at = Carbon::now();
            $user->email_verification_token = null;
            $user->email_verification_token_expires_at = null;
            $user->save();

            // Send welcome email
            $this->emailService->sendWelcomeEmail($user);

            Log::info('Email verified successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Clear all session data and cookies to prevent login conflicts
            $request->session()->flush();
            $request->session()->regenerateToken();
            
            // Clear all authentication cookies
            \Cookie::queue(\Cookie::forget(config('session.cookie')));
            \Cookie::queue(\Cookie::forget('remember_web'));
            \Cookie::queue(\Cookie::forget('remember_staff')); 
            \Cookie::queue(\Cookie::forget('remember_technician'));

            return redirect()->route('login')
                ->with('success', 'Email verified successfully! You can now login to your account.');

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            // Clear all session data and cookies to prevent login conflicts
            $request->session()->flush();
            $request->session()->regenerateToken();
            
            // Clear all authentication cookies
            \Cookie::queue(\Cookie::forget(config('session.cookie')));
            \Cookie::queue(\Cookie::forget('remember_web'));
            \Cookie::queue(\Cookie::forget('remember_staff')); 
            \Cookie::queue(\Cookie::forget('remember_technician'));

            return redirect()->route('login')
                ->with('error', 'Email verification failed. Please try again or contact support.');
        }
    }

    /**
     * Resend verification email
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('info', 'Your email is already verified.');
        }

        // Generate new verification token
        $token = Str::random(64);
        $user->email_verification_token = $token;
        $user->email_verification_token_expires_at = Carbon::now()->addHours(24);
        $user->save();

        // Create verification URL
        $verificationUrl = route('email.verify', ['token' => $token]);

        // Send verification email
        $user->notify(new EmailVerificationNotification($verificationUrl, $user));

        return back()->with('success', 'Verification email sent successfully. Please check your email.');
    }

    /**
     * Generate and send verification email for a user (admin function)
     */
    public function sendVerificationEmail(User $user)
    {
        try {
            // Check if already verified
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User email is already verified.'
                ]);
            }

            // Generate verification token
            $token = Str::random(64);
            $user->email_verification_token = $token;
            $user->email_verification_token_expires_at = Carbon::now()->addHours(24);
            $user->save();

            // Create verification URL
            $verificationUrl = route('email.verify', ['token' => $token]);

            // Send verification email
            $user->notify(new EmailVerificationNotification($verificationUrl, $user));

            Log::info('Verification email sent by admin', [
                'user_id' => $user->id,
                'admin_id' => auth()->id(),
                'email' => $user->email
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the verification email.'
            ]);
        }
    }
}
