<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordResetOtp;
use App\Notifications\CustomResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;

class ForgotPasswordController extends Controller
{
    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            // Return the same response as if the email was sent to prevent email enumeration
            return back()->with('status', 'If your email exists in our records, you will receive a password reset link.');
        }

        // Generate a password reset token (stored in password_resets table)
        $token = Password::broker()->createToken($user);
        
        // Generate and store OTP using the SAME token, so the OTP flow and password reset use one token
        $otpData = PasswordResetOtp::createOtp($user->email, $token);
        
        // Send the password reset notification with OTP
        $user->notify(new CustomResetPassword($otpData['token'], $otpData['otp']));

        // Immediately redirect user to the OTP verification page
        return redirect()->route('password.verify.otp', [
            'token' => $otpData['token'],
            'email' => $user->email,
        ])->with('status', 'We have emailed your password reset OTP. Please enter it below to continue.');
    }
}
