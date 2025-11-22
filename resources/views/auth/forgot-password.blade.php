<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - SDMD Equipment Management System</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link href="{{ asset('css/welcome.css') }}" rel="stylesheet">
    <link href="{{ asset('css/phone.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
<div class="container">
    <!-- Left Section (Image + Text) -->
    <div class="left-section">
        <div class="left-content">
            <div class="icon-wrapper">
                <i class='bx bx-shield-check'></i>
            </div>
            <img src="{{ asset('images/backpic.png') }}" alt="Background Image" class="background-image">
            <div class="text-content">
                <h1>Reset your <br><span>password</span></h1>
                <p>Secure access to your SDMD Equipment Management System</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class='bx bx-check-circle'></i>
                        <span>Secure password recovery</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bx-check-circle'></i>
                        <span>Email verification required</span>
                    </div>
                    <div class="feature-item">
                        <i class='bx bx-check-circle'></i>
                        <span>Quick and easy process</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section (Forgot Password Form) -->
    <div class="right-section">
        <div class="login-card enhanced-card">
            <div class="card-header">
                <div class="lock-icon">
                    <i class='bx bx-lock-open-alt'></i>
                </div>
                <h2>Forgot Password?</h2>
                <p>No worries! Enter your email and we'll send you a link to reset your password.</p>
            </div>

            <form action="{{ route('password.email') }}" method="POST" class="enhanced-form" id="forgotPasswordForm">
                @csrf
                <div class="form-group text-center">
                    <p>Enter your account email below to receive a password reset OTP.</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class='bx bx-envelope'></i>
                        <span>Email</span>
                    </label>
                    <div class="input-wrapper">
                        <input id="email" type="email" name="email" class="enhanced-input" value="{{ old('email') }}" required autofocus>
                        <span class="input-focus-line"></span>
                    </div>

                    @error('email')
                        <div class="error-message animate-slide-in">
                            <i class='bx bx-error-circle'></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Send OTP Button -->
                <button type="submit" class="enhanced-button" id="submitBtn">
                    <span class="button-content">
                        <i class='bx bx-paper-plane'></i>
                        <span class="button-text">Send OTP</span>
                    </span>
                    <div class="button-loader">
                        <div class="spinner"></div>
                    </div>
                </button>

                <!-- Back to Login -->
                <div class="form-footer">
                    <a href="{{ route('admin.login') }}" class="back-link">
                        <i class='bx bx-arrow-back'></i>
                        <span>Back to Login</span>
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

<style>
    /* Enhanced Card Styling */
    .enhanced-card {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-radius: 20px;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
    }
    
    .enhanced-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #06b6d4);
        border-radius: 20px 20px 0 0;
    }
    
    /* Card Header */
    .card-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .lock-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        animation: pulse-icon 2s infinite;
    }
    
    .lock-icon i {
        font-size: 2rem;
        color: white;
    }
    
    @keyframes pulse-icon {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .card-header h2 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
    }
    
    .card-header p {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
    }
    
    /* Enhanced Form */
    .enhanced-form {
        animation: fade-in-up 0.6s ease-out;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
    }
    
    .form-label i {
        color: #3b82f6;
    }
    
    /* Enhanced Input */
    .input-wrapper {
        position: relative;
    }
    
    .enhanced-input {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        background: #ffffff;
        transition: all 0.3s ease;
        outline: none;
    }
    
    .enhanced-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        transform: translateY(-1px);
    }
    
    .enhanced-input.error {
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .input-focus-line {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 2px;
        width: 0;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        transition: width 0.3s ease;
        border-radius: 0 0 12px 12px;
    }
    
    .enhanced-input:focus + .input-focus-line {
        width: 100%;
    }
    
    /* Enhanced Button */
    .enhanced-button {
        width: 100%;
        padding: 1rem 2rem;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }
    
    .enhanced-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
    }
    
    .enhanced-button:active {
        transform: translateY(0);
    }
    
    .button-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: opacity 0.3s ease;
    }
    
    .button-loader {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .enhanced-button.loading .button-content {
        opacity: 0;
    }
    
    .enhanced-button.loading .button-loader {
        opacity: 1;
    }
    
    .spinner {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Enhanced Alert */
    .alert {
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        border: 1px solid;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #065f46;
        border-color: #10b981;
    }
    
    .alert-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .alert-icon i {
        font-size: 1.25rem;
    }
    
    .alert-content strong {
        display: block;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .alert-content p {
        margin: 0;
        font-size: 0.875rem;
    }
    
    /* Error Message */
    .error-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #fef2f2;
        border-radius: 8px;
        border-left: 4px solid #ef4444;
    }
    
    /* Form Footer */
    .form-footer {
        text-align: center;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .back-link:hover {
        color: #3b82f6;
        background: #f1f5f9;
        transform: translateX(-2px);
    }
    
    /* Left Section Enhancements */
    .left-content {
        position: relative;
        z-index: 2;
    }
    
    .icon-wrapper {
        position: absolute;
        top: 2rem;
        right: 2rem;
        width: 60px;
        height: 60px;
        background: rgba(59, 130, 246, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }
    
    .icon-wrapper i {
        font-size: 1.5rem;
        color: #3b82f6;
    }
    
    .background-image {
        opacity: 0.8;
        filter: brightness(1.1);
    }
    
    .text-content {
        position: relative;
        z-index: 3;
    }
    
    .feature-list {
        margin-top: 2rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: white;
        font-size: 0.875rem;
        padding: 0.5rem 0;
    }
    
    .feature-item i {
        color: #10b981;
        font-size: 1rem;
        flex-shrink: 0;
    }
    
    /* Animations */
    @keyframes fade-in-up {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slide-in {
        0% {
            opacity: 0;
            transform: translateX(-20px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .animate-slide-in {
        animation: slide-in 0.4s ease-out;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .enhanced-card {
            padding: 1.5rem;
            margin: 1rem;
        }
        
        .lock-icon {
            width: 60px;
            height: 60px;
        }
        
        .lock-icon i {
            font-size: 1.5rem;
        }
        
        .card-header h2 {
            font-size: 1.5rem;
        }
        
        .feature-list {
            margin-top: 1rem;
        }
        
        .icon-wrapper {
            display: none;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('forgotPasswordForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function() {
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Re-enable button after 3 seconds to prevent permanent disable
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }, 3000);
        });
        
        // Enhanced input interactions
        const inputs = document.querySelectorAll('.enhanced-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
        
        // Add floating animation to card
        const card = document.querySelector('.enhanced-card');
        if (card) {
            card.style.animation = 'fade-in-up 0.8s ease-out';
        }
    });
</script>

</body>
</html>
