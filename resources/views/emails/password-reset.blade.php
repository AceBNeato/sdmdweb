<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - SDMD Equipment Management System</title>
    <link rel="stylesheet" href="{{ asset('css/emails.css') }}">
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ $message->embed(public_path('images/SDMDlogo.png')) }}" alt="SDMD Logo">
            <h1>SDMD Equipment Management System</h1>
            <p>Password Reset Verification</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            <p>You are receiving this email because we received a password reset request for your SDMD Equipment Management System account.</p>
            
            <div class="note">
                <p>Please use the following One-Time Password (OTP) to verify your identity:</p>
                <div class="otp-code">{{ $otp }}</div>
                <p class="text-center">This OTP is valid until: <strong>{{ $expiresAt }}</strong></p>
            </div>

            <p>To complete your password reset:</p>
            <ol class="steps">
                <li>Return to the password reset page</li>
                <li>Enter the OTP shown above</li>
                <li>Create your new password</li>
            </ol>

            <p class="text-muted">If you didn't request a password reset, please ignore this email or contact support if you have any concerns.</p>
            
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} SDMD Equipment Management System. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
