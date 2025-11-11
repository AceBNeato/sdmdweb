<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Cache Prevention Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0, no-transform, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Login - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-login.css') }}" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/SDMDlogo.png') }}" alt="Background Image">
        <h1>Admin Login<br><span>SDMD System</span></h1>
        <p>Administrative Access Only</p>
        <div class="admin-badge">ADMIN PANEL</div>
    </div>

    <!-- Right Section (Login Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Hello Admin</h2>
            <p>Welcome Back!</p>
            <h3>Login to Admin Panel</h3>

            <form action="{{ route('admin.login') }}" method="POST" id="admin-login-form">
                @csrf

                <div class="mb-4">
                    <label class="block text-sm text-gray-700">Email</label>
                    <input type="email" name="email" placeholder="Enter your admin email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           autofocus>
                </div>

                <div class="mb-4 password-container">
                    <label class="block text-sm text-gray-700">Password</label>
                    <div class="relative">
                        <input type="password" name="password" placeholder="Enter your password" required
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class='bx bx-hide toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-500'></i>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="options">
                    <a href="{{ route('password.request') }}" style="color: #1e40af; text-decoration: none; font-size: 13px;">Forgot password?</a>
                </div>
                <div class="mb-4">
                <!-- Honeypot field for bot detection -->
                <input type="text" name="website" style="display:none !important;" tabindex="-1" autocomplete="off">
                <button type="submit" class="login-button">Login as Admin</button>
                </div>
            </form>

                @if(session('lockout'))
                <!-- Lockout message with countdown -->
                <div class="mt-4 text-center">
                    <div id="lockout-message" class="bg-red-900 border-2 border-red-600 text-red-200 px-4 py-3 rounded-lg relative shadow-lg animate-pulse">
                        <div class="flex items-center justify-center mb-2">
                            <span class="text-2xl mr-2">🚨</span>
                            <strong class="font-bold text-red-100 text-lg" style="color: #fca5a5 !important;">ACCOUNT LOCKED!</strong>
                            <span class="text-2xl ml-2">🚨</span>
                        </div>
                        <span class="block text-red-300 font-semibold" style="color: #fca5a5 !important;">Too many failed login attempts. Please wait <span id="countdown" class="font-bold text-red-100 text-xl" style="color: #fecaca !important;">{{ session('remaining_seconds') }}</span> seconds before trying again.</span>
                    </div>
                </div>
                @endif

                @if($errors->has('email') && !session('lockout'))
                <!-- Wrong credentials alarm -->
                <div class="mt-4 text-center">
                    <div class="bg-red-900 border-2 border-red-600 text-red-200 px-4 py-3 rounded-lg relative shadow-lg">
                        <div class="flex items-center justify-center mb-2">
                            <span class="text-2xl mr-2">⚠️</span>
                            <strong class="font-bold text-red-100 text-lg" style="color: #fca5a5 !important;">INVALID CREDENTIALS</strong>
                            <span class="text-2xl ml-2">⚠️</span>
                        </div>
                        <span class="block text-red-300 font-semibold" style="color: #fca5a5 !important;">{{ $errors->first('email') }}</span>
                        <span class="block text-red-400 text-sm mt-1" style="color: #fca5a5 !important;">Attempts remaining: <span class="attempts-counter">{{ session('remaining_attempts', 3) }}/3</span></span>
                    </div>
                </div>
                @endif

        </div>
    </div>
</div>

<!-- Footer -->
<footer>
    Copyright  2025. All Rights Reserved. | <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
</footer>
<script>
// Pass login data to JavaScript
window.loginData = {
    remaining_attempts: {{ session('remaining_attempts', 3) }},
    lockout: {{ session('lockout', false) ? 'true' : 'false' }},
    remaining_seconds: {{ session()->has('remaining_seconds') ? session('remaining_seconds') : 'null' }}
};
</script>
<script src="{{ asset('js/login.js') }}"></script>

</body>

</html>
