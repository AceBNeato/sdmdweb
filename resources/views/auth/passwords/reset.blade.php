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

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Simple modal for password confirmation error */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-backdrop.active {
            display: flex;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem 1.75rem;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.25);
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title i {
            color: #ef4444;
        }

        .modal-message {
            font-size: 0.95rem;
            color: #4b5563;
            margin-bottom: 1.25rem;
        }

        .modal-actions {
            text-align: right;
        }

        .modal-button {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            background: #1d4ed8;
            color: #ffffff;
            font-size: 0.9rem;
            cursor: pointer;
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
            
            @if (session('status'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" >
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <input type="hidden" name="email" value="{{ old('email', $email) }}">
                <div class="mb-4">
                    <label>New Password <span class="text-danger">*required</span></label>
                    <div class="password-group">
                        <input type="password" 
                               name="new_password" 
                               required 
                          
                               class="@error('new_password') border-red-500 @enderror password-input">
                        <span class="password-toggle" data-target="new_password">
                            <i class='bx bx-hide'></i>
                        </span>
                    </div>
                    @error('new_password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label>Confirm New Password <span class="text-danger">*required</span></label>
                    <div class="password-group">
                        <input type="password" 
                               name="new_password_confirmation" 
                               required 
                         
                               class="@error('new_password') border-red-500 @enderror password-input">
                        <span class="password-toggle" data-target="new_password_confirmation">
                            <i class='bx bx-hide'></i>
                        </span>
                    </div>
                    @error('new_password')
                        <p class="error-message">{{ $message }}</p>
                    @enderror
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

<!-- Password error modal -->
@error('new_password')
<div class="modal-backdrop active" id="passwordErrorModal">
    <div class="modal-card">
        <div class="modal-title">
            <i class='bx bx-error-circle'></i>
            <span>Password Mismatch</span>
        </div>
        <div class="modal-message">
            {{ $message }}
        </div>
        <div class="modal-actions">
            <button type="button" class="modal-button" id="closePasswordErrorModal">
                OK
            </button>
        </div>
    </div>
    </div>
@enderror

<!-- Footer -->
<footer>
    Copyright {{ date('Y') }}. All Rights Reserved. | <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password show/hide toggles
        const toggles = document.querySelectorAll('.password-toggle');
        toggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const group = this.closest('.password-group');
                if (!group) return;

                const input = group.querySelector('.password-input');
                if (!input) return;

                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('bx-hide');
                        icon.classList.add('bx-show');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('bx-show');
                        icon.classList.add('bx-hide');
                    }
                }
            });
        });

        // Password error modal close handler
        const modal = document.getElementById('passwordErrorModal');
        const closeBtn = document.getElementById('closePasswordErrorModal');
        if (modal && closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.classList.remove('active');
            });
            // Also close when clicking outside the card
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }

        // Prevent going back in history from this page: if user presses Back,
        // immediately send them to the login page instead of previous screens.
        if (window.history && window.history.pushState) {
            // Push a new state so that the first back triggers popstate.
            window.history.pushState({ page: 'reset-password-main' }, '', window.location.href);

            window.addEventListener('popstate', function () {
                window.location.href = '{{ route('login') }}';
            });
        }
    });
</script>

</body>
</html>
