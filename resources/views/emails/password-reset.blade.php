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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ $message->embed(public_path('images/SDMDlogo.png')) }}" alt="SDMD Logo">
            <h1>SDMD Equipment Management System</h1>
            <p>Password Reset Request</p>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            <p>You are receiving this email because we received a password reset request for your SDMD Equipment Management System account.</p>
            
            <div class="text-center">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>

            <p class="text-muted">This password reset link will expire in 60 minutes.</p>
            <p>If you did not request a password reset, you can safely ignore this email.</p>
            
            <div class="divider"></div>
            
            <p style="font-size: 12px; color: #a0aec0;">
                If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
                <br>
                <a href="{{ $resetUrl }}" style="word-break: break-all; color: #5ae7ff;">{{ $resetUrl }}</a>
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} SDMD Equipment Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
