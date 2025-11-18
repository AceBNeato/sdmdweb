<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/icon.png') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/welcome.css') }}" rel="stylesheet">
    <link href="{{ asset('css/phone.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/backpic.png') }}" alt="Background Image">
        <h1>Create new <br><span>password</span></h1>
        <p>SDMD Equipment Management System</p>
    </div>

    <!-- Right Section (Reset Password Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Reset Password</h2>
            <p>Enter your new password below</p>

            <form action="{{ route('password.update') }}" method="POST" autocomplete="off">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                
                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-sm text-gray-700">Email Address</label>
                    <div class="input-group">
                        <span class="input-icon"><i class='bx bx-envelope'></i></span>
                        <input type="email" name="email" placeholder="Enter your email address" value="{{ old('email', request()->email) }}" required>
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="mb-4 password-container">
                    <label class="block text-sm text-gray-700">New Password</label>
                    <div class="relative">
                        <div class="input-group">
                            <span class="input-icon"><i class='bx bx-lock-alt'></i></span>
                            <input type="password" name="password" placeholder="Enter new password" required class="w-full password-input">
                            <i class='bx bx-hide toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer'></i>
                        </div>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-4 password-container">
                    <label class="block text-sm text-gray-700">Confirm Password</label>
                    <div class="relative">
                        <div class="input-group">
                            <span class="input-icon"><i class='bx bx-lock-alt'></i></span>
                            <input type="password" name="password_confirmation" placeholder="Confirm new password" required class="w-full password-input">
                            <i class='bx bx-hide toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer'></i>
                        </div>
                    </div>
                </div>

                <!-- Reset Password Button -->
                <button type="submit" class="login-button">
                    <i class='bx bx-refresh'></i>
                    Reset Password
                </button>

                <!-- Back to Login -->
                <div class="text-center mt-4">
                    <a href="{{ route('admin.login') }}" class="back-link">
                        <i class='bx bx-arrow-back'></i>
                        Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    Copyright 2025. All Rights Reserved. | <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(function(toggleButton) {
            toggleButton.addEventListener('click', function() {
                const passwordInput = this.parentElement.querySelector('.password-input');
                
                if (passwordInput) {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    // Toggle between show/hide icons
                    if (type === 'password') {
                        this.classList.remove('bx-show');
                        this.classList.add('bx-hide');
                    } else {
                        this.classList.remove('bx-hide');
                        this.classList.add('bx-show');
                    }
                }
            });
        });
    });
</script>

<style>
    .input-group {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .input-icon {
        position: absolute;
        left: 12px;
        z-index: 2;
        color: #6b7280;
    }
    
    .input-group input {
        padding-left: 40px;
        padding-right: 40px;
    }
    
    .back-link {
        color: #1e40af;
        text-decoration: none;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: color 0.2s ease;
    }
    
    .back-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    .login-button {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .login-button i {
        font-size: 16px;
    }
    
    .toggle-password {
        z-index: 3;
    }
</style>

</body>
</html>
