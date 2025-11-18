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

        /* Full-screen loading modal when sending OTP */
        .loading-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .loading-backdrop.active {
            display: flex;
        }

        .loading-card {
            background: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.25);
        }

        .loading-spinner {
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            border: 3px solid rgba(37, 99, 235, 0.2);
            border-top-color: #2563eb;
            animation: spin 0.9s linear infinite;
        }

        .loading-text {
            font-size: 0.95rem;
            color: #1f2937;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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

            <form method="POST" action="{{ route('password.email') }}" id="sendOtpForm">
                @csrf

                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <button type="submit" class="login-button" id="sendOtpButton">
                        Send OTP
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

<!-- Loading modal for Send OTP -->
<div class="loading-backdrop" id="otpLoadingModal">
    <div class="loading-card">
        <div class="loading-spinner"></div>
        <div class="loading-text">Sending OTP, please wait...</div>
    </div>
    </div>

<!-- Footer -->
<footer>
    Copyright {{ date('Y') }}. All Rights Reserved. | <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
</footer>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('sendOtpForm');
        const button = document.getElementById('sendOtpButton');
        const modal = document.getElementById('otpLoadingModal');

        if (form && button && modal) {
            form.addEventListener('submit', function () {
                // Show blocking loading modal
                modal.classList.add('active');

                // Disable button to prevent double submit
                button.disabled = true;

                // Optional: change button text while loading
                button.textContent = 'Sending...';
            });
        }
    });
</script>

</body>
</html>
