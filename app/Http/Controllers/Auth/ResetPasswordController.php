<?php

namespace App\Http\Controllers\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends Controller
{
    /**
     * Show the OTP verification form
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function showVerifyOtpForm(Request $request, $token = null)
    {
        $email = $request->query('email');
        
        if (!$token || !$email) {
            return redirect()->route('password.request')
                ->with('error', 'Invalid password reset link.');
        }

        return view('auth.passwords.verify-otp', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Verify the OTP and show the password reset form
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        // Verify OTP
        $isValidOtp = PasswordResetOtp::verifyOtp($request->email, $request->otp);

        if (!$isValidOtp) {
            return back()
                ->withInput($request->only('email', 'token'))
                ->withErrors(['otp' => 'The provided OTP is invalid or has expired.']);
        }

        // Get the token associated with this OTP
        $token = PasswordResetOtp::getTokenFromOtp($request->email, $request->otp);

        if (!$token) {
            return back()
                ->withInput($request->only('email', 'token'))
                ->withErrors(['otp' => 'Unable to process your request. Please try again.']);
        }

        // Store the verified token in the session
        $request->session()->put('password_reset_token', $token);
        $request->session()->put('password_reset_email', $request->email);

        return redirect()->route('password.reset', ['token' => $token, 'email' => $request->email]);
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetForm(Request $request, $token = null)
    {
        // Check if OTP verification is required and not yet completed
        if (!session()->has('password_reset_token') || 
            !session()->has('password_reset_email') ||
            session('password_reset_token') !== $token) {
            
            return redirect()->route('password.verify.otp', [
                'token' => $token,
                'email' => $request->query('email')
            ]);
        }

        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->query('email')]
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Verify the token from the session
        if (!session()->has('password_reset_token') || 
            !session()->has('password_reset_email') ||
            session('password_reset_token') !== $request->token) {
            
            return redirect()->route('password.request')
                ->with('error', 'Invalid or expired password reset session.');
        }

        // Check if the new password is different from the current one
        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->new_password, $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'The new password must be different from your current password.']);
        }

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
                    ? $this->sendResetResponse($request, $response)
                    : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'new_password' => 'required|confirmed|min:8',
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return [
            'email' => $request->email,
            'password' => $request->new_password,
            'password_confirmation' => $request->new_password_confirmation,
            'token' => $request->token,
        ];
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        // Update the password
        $user->password = Hash::make($password);
        
        // Invalidate all existing sessions
        $user->setRememberToken(Str::random(60));
        
        // Save the user model
        $user->save();

        // Fire password reset event
        event(new PasswordReset($user));

        // Clear the password reset session
        session()->forget(['password_reset_token', 'password_reset_email']);
        
        // Delete all OTPs for this email
        PasswordResetOtp::deleteForEmail($user->email);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        return redirect($this->redirectPath())
            ->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)]);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the post password reset redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        // After password reset, always go back to login page
        return route('login');
    }
}
