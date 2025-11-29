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

            <script>
            // Show lockout warning as SweetAlert if present
            @if(session('lockout'))
                Swal.fire({
                    title: '🚨 ADMIN ACCOUNT LOCKED! 🚨',
                    html: 'Too many failed admin login attempts.<br><br>Please wait <strong><span id="swal-countdown">{{ session('remaining_seconds', 60) }}</span></strong> seconds before trying again.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545',
                    backdrop: 'rgba(0,0,0,0.7)',
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
                    title: '⚠️ ADMIN ACCESS DENIED ⚠️',
                    html: '{{ $errors->first('email') }}<br><br><small>Attempts remaining: <strong>{{ session('remaining_attempts', 3) }}/3</strong></small>',
                    icon: 'warning',
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#ffc107',
                    backdrop: 'rgba(0,0,0,0.7)'
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

</body>

</html>
