<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification - SDMD Equipment Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            line-height: 1.6;
            color: #f3f4f6;
            margin: 0;
            padding: 0;
            background: linear-gradient(180deg, #1a1f26, #3f4b5d);
            min-height: 100vh;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #1a1f26 0%, #3f4b5d 100%);
            padding: 40px 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header img {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
        }
        .header h1 {
            color: #ffffff;
            margin: 10px 0 5px;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            color: #9ca3af;
            margin: 0;
            font-size: 16px;
            font-weight: 400;
        }
        .content {
            padding: 30px;
            background: rgba(26, 31, 38, 0.8);
            color: #e5e7eb;
        }
        .note {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 25px 0;
            border-radius: 0 8px 8px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .button:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            text-decoration: none;
            color: #ffffff !important;
        }
        .footer {
            text-align: center;
            padding: 25px 20px;
            font-size: 13px;
            color: #9ca3af;
            background: rgba(15, 23, 42, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .text-muted {
            color: #9ca3af;
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 1rem;
        }
        strong {
            color: #ffffff;
            font-weight: 600;
        }
        a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.2s;
        }
        a:hover {
            color: #60a5fa;
            text-decoration: underline;
        }
        .verification-info {
            background: rgba(34, 197, 94, 0.1);
            border-left: 4px solid #22c55e;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .verification-info p {
            margin: 5px 0;
            color: #e5e7eb;
        }
        .verification-icon {
            font-size: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
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

            <div class="verification-info">
                <p><span class="verification-icon">🔑</span><strong>Your Login Credentials:</strong></p>
                <p>Email: {{ $user->email }}</p>
                <p>Password: {{ $password ?? '[Default Password]' }}</p>
            </div>

            <div class="note">
                <p><strong>Click the button below to verify your email:</strong></p>
                <div class="text-center mt-4">
                    <a href="{{ $verificationUrl }}" class="button">Verify Email Address</a>
                </div>
                <p class="text-center text-muted mt-4">This verification link will expire in 24 hours for security reasons.</p>
            </div>

            <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
            <p class="text-muted" style="word-break: break-all;">{{ $verificationUrl }}</p>

            <div class="note" style="background: rgba(251, 191, 36, 0.1); border-left-color: #fbbf24;">
                <p><strong>🔔 Important:</strong> After verification, you'll be able to:</p>
                <ul style="margin: 10px 0; padding-left: 20px; color: #e5e7eb;">
                    <li>Log in to your account with the credentials above</li>
                    <li>Access equipment management features</li>
                    <li>Change your password for better security (recommended)</li>
                    <li>Manage your profile settings</li>
                </ul>
            </div>

            <p class="text-muted">If you did not request this account, please ignore this email or contact support if you have any concerns.</p>

        </div>

        <div class="footer">
            <p>© {{ date('Y') }} SDMD Equipment Management System. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
            <p style="margin-top: 10px; font-size: 11px; opacity: 0.7;">
                <a href="#" style="color: #9ca3af; text-decoration: none;">Terms of Use</a> | 
                <a href="#" style="color: #9ca3af; text-decoration: none;">Privacy Policy</a>
            </p>
        </div>
    </div>
</body>
</html>
