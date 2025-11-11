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
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/SDMDlogo.png') }}" alt="SDMD Logo">
        <h1>Reset Your <br><span>Password</span></h1>
        <p>SDMD Equipment Management System</p>
    </div>

    <!-- Right Section (Forgot Password Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Forgot Password</h2>
            <p>No worries, we'll help you reset it</p>
            <h3>Enter your email</h3>

            @if (session('status'))
                <div class="mb-4 text-center">
                    <div class="bg-green-100 border-2 border-green-500 text-green-800 px-4 py-3 rounded-lg">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6">
                @csrf

                <div class="mb-4">
                    <label>Email</label>
                    <input type="email" 
                           name="email" 
                           placeholder="Enter your email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <button type="submit" class="login-button w-full">
                        Send Password Reset Link
                    </button>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-blue-800 hover:underline text-sm">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    Copyright 2025. All Rights Reserved. | <a href="#" class="hover:underline">Terms of Use</a> | <a href="#" class="hover:underline">Privacy Policy</a>
</footer>

<script>
    // Toggle password visibility
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.querySelector('.toggle-password');
        const password = document.querySelector('input[name="password"]');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('bxs-show');
                this.classList.toggle('bxs-hide');
            });
        }
    });
</script>
</body>
</html>
