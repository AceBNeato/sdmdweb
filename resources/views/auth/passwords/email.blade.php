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
        .forgot-password-link {
            text-align: center;
            margin-top: 1rem;
        }
        .forgot-password-link a {
            color: #1e40af;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/SDMDlogo.png') }}" alt="Background Image">
        <h1>Reset Your <br><span>Password</span></h1>
        <p>SDMD Equipment Management System</p>
    </div>

    <!-- Right Section (Forgot Password Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Forgot Password?</h2>
            <p>No worries! Enter your email and we'll send you a link to reset your password.</p>
            
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <button type="submit" class="login-button">
                        Send Password Reset Link
                    </button>
                </div>
            </form>

            <div class="forgot-password-link">
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

<!-- Scripts -->
<script>
    // Add any necessary JavaScript here
</script>
</body>
</html>
