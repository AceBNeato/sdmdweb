<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - SDMD Equipment Management System</title>
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
        .otp-code {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 0.5rem;
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #ffffff;
            font-family: 'Courier New', monospace;
        }
        .steps {
            padding-left: 20px;
            margin: 20px 0;
        }
        .steps li {
            margin-bottom: 10px;
            color: #e5e7eb;
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
    </style>
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
            <p style="margin-top: 10px; font-size: 11px; opacity: 0.7;">
                <a href="#" style="color: #9ca3af; text-decoration: none;">Terms of Use</a> | 
                <a href="#" style="color: #9ca3af; text-decoration: none;">Privacy Policy</a>
            </p>
        </div>
    </div>
</body>
</html>
