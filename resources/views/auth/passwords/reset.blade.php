<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Cache Prevention Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0, no-transform, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Reset Password - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-login a {
            color: #1e40af;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/SDMDlogo.png') }}" alt="SDMD Logo">
        <h1>Reset Your <br><span>Password</span></h1>
        <p>SDMD Equipment Management System</p>
    </div>

    <!-- Right Section (Reset Password Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Create New Password</h2>
            <p>Enter your new password below to reset your account access.</p>
            
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $email) }}" 
                           required 
                           autofocus
                           class="@error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label>New Password</label>
                    <input type="password" 
                           name="password" 
                           required 
                           autocomplete="new-password"
                           class="@error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label>Confirm New Password</label>
                    <input type="password" 
                           name="password_confirmation" 
                           required 
                           autocomplete="new-password">
                </div>

                <div class="mb-4">
                    <button type="submit" class="login-button">
                        Reset Password
                    </button>
                </div>
            </form>

            <div class="back-to-login">
                <a href="{{ route('login') }}">
                    <i class='bx bx-arrow-back'></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    Copyright {{ date('Y') }}. All Rights Reserved. | <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
</footer>
</body>
</html>
