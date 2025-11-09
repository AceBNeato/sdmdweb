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
    <link href="{{ asset('css/phone.css') }}" rel="stylesheet">
    <style>
        /* Admin Login Custom Styles */
        body {
            background: linear-gradient(180deg, #0f1419, #1a1f26, #2a2f36) !important;
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            animation: fadeIn 1s ease-in-out;
        }

        .left-section {
            flex: 1;
            position: relative;
            color: #fff;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .left-section img {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 70%;
            opacity: 0.08;
            pointer-events: none;
            filter: brightness(0.8);
        }

        .left-section h1 {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1.2;
            position: relative;
            z-index: 10;
            margin-bottom: 10px;
        }

        .left-section span {
            color: #f87171;
        }

        .left-section p {
            font-size: 0.875rem;
            margin-top: 10px;
            letter-spacing: 2px;
            position: relative;
            z-index: 10;
            color: #f87171 !important;
        }

        .admin-badge {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            color: white !important;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
            border: 1px solid #991b1b !important;
            position: relative;
            z-index: 10;
        }

        .right-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-card {
            background: linear-gradient(135deg, #1f2937, #374151) !important;
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid #4b5563;
            animation: slideInUp 0.8s ease-in-out;
        }

        .login-card h2 {
            font-size: 1.25rem;
            color: #f87171 !important;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }

        .login-card p {
            font-size: 0.875rem;
            color: #d1d5db !important;
            margin: 5px 0 20px 0;
            text-align: center;
        }

        .login-card h3 {
            font-size: 1rem;
            color: #ef4444 !important;
            font-weight: bold;
            margin-bottom: 25px;
            text-align: center;
        }

        .login-card label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e5e7eb !important;
            font-size: 13px;
            font-weight: 500;
        }

        .login-card input {
            width: 100%;
            height: 40px;
            margin-bottom: 1rem;
            border: 1px solid #4b5563 !important;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #374151 !important;
            color: #f9fafb !important;
            padding: 10px;
            transition: all 0.2s;
        }

        .login-card input:focus {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
            outline: none;
        }

        .login-card input::placeholder {
            color: #9ca3af !important;
        }

        .login-card .password-container {
            position: relative;
        }

        .login-card .password-container i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
        }

        .login-card .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            font-size: 13px;
            color: #d1d5db !important;
        }

        .login-card input[type="checkbox"] {
            width: 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            margin-right: 175px;
        }

        .login-card button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 8px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease !important;
            font-weight: 600;
        }

        .login-card button:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3) !important;
        }

        /* Locked button styling for dark theme */
        .login-card button:disabled,
        .login-card button[style*="opacity: 0.6"] {
            color: #fca5a5 !important;
            background: linear-gradient(135deg, #7f1d1d, #991b1b) !important;
            border: 2px solid #fca5a5 !important;
        }

        /* Error message styling for dark theme */
        .text-red-500 {
            color: #fca5a5 !important;
        }

        /* Link styling */
        .options a {
            color: #f87171 !important;
        }

        .options a:hover {
            color: #fca5a5 !important;
        }

        footer {
            text-align: center;
            color: #9ca3af;
            font-size: 0.75rem;
            padding: 15px;
            margin-top: auto;
        }

        footer a {
            color: #f87171;
            text-decoration: underline;
            margin: 0 5px;
        }

        footer a:hover {
            color: #fca5a5;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 10px;
            }

            .left-section {
                flex: none;
                min-height: 200px;
            }

            .left-section h1 {
                font-size: 2rem;
            }

            .right-section {
                flex: none;
                width: 100%;
            }

            .login-card {
                max-width: 100%;
                padding: 20px;
            }
        }

        /* Keyframe Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
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
                    <label class="block text-sm text-gray-700">Admin Email</label>
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
                    <label>Remember Me</label>
                    <input type="checkbox" name="remember" class="checkbox" id="remember">
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
