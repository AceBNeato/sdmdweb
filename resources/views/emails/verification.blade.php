<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification - SDMD Equipment Management System</title>
    <link rel="stylesheet" href="{{ asset('css/emails.css') }}">
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ $message->embed(public_path('images/SDMDlogo.png')) }}" alt="SDMD Logo">
            <h1>SDMD Equipment Management System</h1>
            <p>Email Verification</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
            <p>Your account has been created by an administrator. To activate your account and start using the system, please verify your email address.</p>

            <div class="note">
                <p><strong>Click the button below to verify your email:</strong></p>
                <div class="text-center mt-4">
                    <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
                </div>
                <p class="text-center text-muted mt-4">This verification link will expire in 24 hours for security reasons.</p>
            </div>

            <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
            <p class="text-muted">{{ $verificationUrl }}</p>

            <p class="text-muted">If you did not request this account, please ignore this email or contact support if you have any concerns.</p>

        </div>

        <div class="footer">
            <p>© {{ date('Y') }} SDMD Equipment Management System. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
