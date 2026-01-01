<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Cache Prevention Headers -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0, no-transform, private">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Verify OTP - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .otp-input {
            letter-spacing: 0.5rem;
            font-size: 1.5rem;
            text-align: center;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            width: 100%;
            transition: border-color 0.3s ease;
        }
        .otp-input:focus {
            border-color: #5ae7ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(90, 231, 255, 0.2);
        }
        .otp-input.error {
            border-color: #f56565;
        }
        .resend-otp {
            margin-top: 1rem;
            text-align: center;
            color: #718096;
        }
        .resend-otp a {
            color: #5ae7ff;
            text-decoration: none;
            font-weight: 500;
        }
        .resend-otp a:hover {
            text-decoration: underline;
        }
        .otp-instructions {
            margin-bottom: 1.5rem;
            color: #4a5568;
            font-size: 0.9375rem;
            line-height: 1.5;
        }
        .otp-timer {
            color: #e53e3e;
            font-weight: 500;
        }

        /* Back to login link - match email.blade.php style */
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
        .back-to-login a {
            color: #1e40af;
            text-decoration: none;
            font-size: 14px;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Left Section (Image + Text) -->
        <div class="left-section">
            <img src="{{ asset('images/SDMDlogo.png') }}" alt="SDMD Logo">
            <h1>Verify Your <br><span>Identity</span></h1>
            <p>SDMD Equipment Management System</p>
        </div>

        <!-- Right Section (OTP Verification Form) -->
        <div class="right-section">
            <div class="login-card">
                <h2>Verify OTP</h2>
                <p class="otp-instructions">We've sent a 6-digit verification code to <strong>{{ $email }}</strong>. Please enter it below to continue.</p>
                
                @if (session('status'))
                    <div class="mb-4 font-medium text-sm text-green-600">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4">
                        <div class="font-medium text-red-600">
                            {{ __('Whoops! Something went wrong.') }}
                        </div>

                        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.verify.otp.submit') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-4">
                        <label>Enter 6-digit OTP <span class="text-danger">*required</span></label>
                        <input type="text" 
                               name="otp" 
                               class="otp-input @error('otp') error @enderror" 
                               placeholder="••••••" 
                               maxlength="6" 
                               pattern="\d{6}" 
                               inputmode="numeric" 
                               autocomplete="one-time-code"
                               required 
                               autofocus>
                        @error('otp')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="login-button">
                            Verify & Continue
                        </button>
                    </div>
                </form>

                <div class="resend-otp">
                    <p>Didn't receive the code? 
                        <a href="{{ route('password.resend.otp', ['email' => $email, 'token' => $token]) }}" 
                           id="resendOtpLink">
                            Resend OTP
                        </a>
                        <span id="otpTimer" class="otp-timer"></span>
                    </p>
                </div>

                <div class="back-to-login">
                    <a href="{{ route('login') }}">
                        <i class='bx bx-arrow-back'></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // OTP Input handling
        const otpInput = document.querySelector('input[name="otp"]');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                // Remove any non-digit characters
                this.value = this.value.replace(/\D/g, '');
                
                // Limit to 6 digits
                if (this.value.length > 6) {
                    this.value = this.value.slice(0, 6);
                }
                
                // Auto-submit when 6 digits are entered
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
            
            // Focus the input on page load
            otpInput.focus();
        }

        // OTP Resend Timer
        const resendOtpLink = document.getElementById('resendOtpLink');
        const otpTimer = document.getElementById('otpTimer');
        
        if (resendOtpLink && otpTimer) {
            let timeLeft = 60; // 60 seconds cooldown
            
            function updateTimer() {
                if (timeLeft <= 0) {
                    otpTimer.textContent = '';
                    resendOtpLink.style.pointerEvents = 'auto';
                    resendOtpLink.style.opacity = '1';
                    return;
                }
                
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                otpTimer.textContent = ` (${minutes}:${seconds.toString().padStart(2, '0')})`;
                
                timeLeft--;
                setTimeout(updateTimer, 1000);
                
                // Disable the resend link while counting down
                resendOtpLink.style.pointerEvents = 'none';
                resendOtpLink.style.opacity = '0.6';
            }
            
            // Start the timer when the page loads
            updateTimer();
            
            // Handle resend OTP click
            resendOtpLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading state
                const originalText = this.textContent;
                this.textContent = 'Sending...';
                this.style.pointerEvents = 'none';
                
                // Make an AJAX request to resend OTP
                fetch(this.href, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reset the timer
                        timeLeft = 60;
                        updateTimer();
                        
                        // Show success message
                        alert('A new OTP has been sent to your email.');
                    } else {
                        alert(data.message || 'Failed to resend OTP. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resending OTP. Please try again.');
                })
                .finally(() => {
                    this.textContent = originalText;
                    this.style.pointerEvents = 'auto';
                });
            });
        }
    </script>
</body>
</html>
