<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Reset - SDMD Equipment Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background: linear-gradient(180deg, #1a1f26, #3f4b5d);
            min-height: 100vh;
        }
        .email-container {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }
        .header {
            background: linear-gradient(180deg, #1a1f26, #3f4b5d);
            padding: 40px 20px;
            text-align: center;
            color: #fff;
        }
        .header img {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 1.8rem;
            margin: 10px 0 5px;
        }
        .header p {
            color: #a0aec0;
            margin: 0;
            letter-spacing: 1px;
        }
        .content {
            padding: 30px;
            background: #fff;
        }
        .otp-code {
            font-size: 2.5rem;
            font-weight: bold;
            letter-spacing: 0.5rem;
            text-align: center;
            margin: 25px 0;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            border: 1px dashed #cbd5e0;
            color: #2d3748;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #5ae7ff;
            color: #1a1f26 !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #4ad7ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(90, 231, 255, 0.3);
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #a0aec0;
            background: #1a1f26;
            border-top: 1px solid #2d3748;
        }
        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
            opacity: 0.2;
        }
        .text-primary {
            color: #5ae7ff;
        }
        .text-muted {
            color: #718096;
        }
        .text-center {
            text-align: center;
        }
        .mt-4 {
            margin-top: 1rem;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
        .steps {
            margin: 25px 0;
            padding: 0;
            list-style: none;
        }
        .steps li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        .steps li:before {
            content: '→';
            position: absolute;
            left: 0;
            color: #5ae7ff;
            font-weight: bold;
        }
        .note {
            background: #f7fafc;
            border-left: 4px solid #5ae7ff;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 0.9em;
            border-radius: 0 4px 4px 0;
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
            
            <div class="divider"></div>
            
            <p style="font-size: 12px; color: #a0aec0;">
                Having trouble? You can also click the link below to verify your email:
                <br>
                <a href="{{ $resetUrl }}" style="word-break: break-all; color: #5ae7ff;">{{ $resetUrl }}</a>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} SDMD Equipment Management System. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
