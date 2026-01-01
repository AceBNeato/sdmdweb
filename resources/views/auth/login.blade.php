<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Cache Prevention Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0, no-transform, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div id="particles-js"></div>
<div class="gradient-overlay"></div>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <img src="{{ asset('images/SDMDlogo.png') }}" alt="Background Image">
        <h1>Login into <br><span>your account</span></h1>
        <p>SDMD Equipment Management System</p>
    </div>

    <!-- Right Section (Login Form) -->
    <div class="right-section">
        <div class="login-card">
            <h2>Hello</h2>
            <p>Welcome Back!</p>
            <h3>Login your account</h3>

            <form id="login-form" method="POST" action="{{ route('login.submit') }}">
                @csrf

                <div class="mb-4">
                    <label>Email </label>
                    <input type="email" name="email" placeholder="Enter your email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-4 password-container">
                    <label>Password</label>
                    <div class="relative">
                        <input type="password" name="password" placeholder="Enter your password" required>
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
                <button type="submit" class="login-button">Login</button>
                </div>

                <!-- Google Login Button - Temporarily Hidden -->
                @if(false)
                <div class="mb-4">
                    <div class="flex items-center">
                        <div class="flex-1 border-t border-gray-300"></div>
                        <span class="px-3 bg-white text-gray-500 text-sm">or</span>
                        <div class="flex-1 border-t border-gray-300"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <a href="{{ route('auth.google') }}" class="google-login-button">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>
                @endif
            </form>

            <script>
            // Show lockout warning as SweetAlert if present
            @if(session('lockout'))
                Swal.fire({
                    title: '🚨 ACCOUNT LOCKED! 🚨',
                    html: 'Too many failed login attempts.<br><br>Please wait <strong><span id="swal-countdown">{{ session('remaining_seconds', 60) }}</span></strong> seconds before trying again.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545',
                    backdrop: 'rgba(0,0,0,0.5)',
                    didOpen: () => {
                        const countdownEl = document.getElementById('swal-countdown');
                        let seconds = {{ session('remaining_seconds', 60) }};
                        const timer = setInterval(() => {
                            seconds--;
                            if (seconds > 0) {
                                countdownEl.textContent = seconds;
                            } else {
                                clearInterval(timer);
                            }
                        }, 1000);
                    }
                });
            @endif

            // Show invalid credentials warning as SweetAlert if present
            @if($errors->has('email') && !session('lockout'))
                Swal.fire({
                    title: '⚠️ INVALID CREDENTIALS ⚠️',
                    html: '{{ $errors->first('email') }}<br><br><small>Attempts remaining: <strong>{{ session('remaining_attempts', 3) }}/3</strong></small>',
                    icon: 'warning',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#ffc107',
                    backdrop: 'rgba(0,0,0,0.5)'
                });
            @endif
            </script>

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
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
particlesJS('particles-js', {
  particles: {
    number: { value: 80, density: { enable: true, value_area: 800 } },
    color: { value: "#ffffff" },
    shape: { type: "circle" },
    opacity: { value: 0.5, random: true },
    size: { value: 3, random: true },
    line_linked: {
      enable: true,
      distance: 150,
      color: "#ffffff",
      opacity: 0.5,
      width: 1
    },
    move: { enable: true, speed: 2, direction: "none", random: true }
  },
  interactivity: {
    detect_on: "canvas",
    events: {
      onhover: { enable: true, mode: "repulse" },
      onclick: { enable: true, mode: "push" }
    }
  }
});
</script>
<script src="{{ asset('js/login.js') }}"></script>
<script src="{{ asset('js/session-sync.js') }}"></script>

<!-- Show SweetAlert if role was changed -->
@if(session('swal'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '{{ session('swal.icon') }}',
        title: '{{ session('swal.title') }}',
        text: '{{ session('swal.text') }}',
        timer: {{ session('swal.timer') ?? 3000 }},
        showConfirmButton: {{ session('swal.showConfirmButton') ? 'true' : 'false' }}
    });
});
</script>
@endif

</body>

</html>
