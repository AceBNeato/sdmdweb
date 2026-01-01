<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Controller Imports
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\PublicEquipmentController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\SystemLogController;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// Root route redirects to login
Route::redirect('/', '/login')->name('home');

// Redirect /home to root for backward compatibility
Route::redirect('/home', '/');

// Public QR Code Scanner (no authentication required)
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/qr-scanner', [PublicEquipmentController::class, 'scanner'])->name('qr-scanner');
    // Allow both GET and POST so QR URLs and AJAX can both hit this endpoint
    Route::match(['GET', 'POST'], '/equipment/scan', [PublicEquipmentController::class, 'scanQrCode'])->name('equipment.scan');
    Route::get('/qr-setup', function() {
        return view('equipment.qr-setup-guide');
    })->name('qr-setup');
});

// Email Verification Routes (public)
Route::prefix('email')->name('email.')->group(function () {
    Route::get('/verify/{token}', [EmailVerificationController::class, 'verify'])->name('verify');
    Route::post('/verification/resend', [EmailVerificationController::class, 'resend'])->name('verification.resend');
    Route::get('/verification/notice', [EmailVerificationController::class, 'showVerificationNotice'])->name('verification.notice');
    
    // Temporary debug route
    Route::get('/debug', function() {
        return response()->json([
            'env' => [
                'MAIL_MAILER' => env('MAIL_MAILER'),
                'MAIL_HOST' => env('MAIL_HOST'),
                'MAIL_PORT' => env('MAIL_PORT'),
                'MAIL_USERNAME' => env('MAIL_USERNAME') ? 'SET' : 'NOT SET',
                'MAIL_PASSWORD' => env('MAIL_PASSWORD') ? 'SET' : 'NOT SET',
                'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
            ],
            'config' => [
                'default' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET',
                'password' => config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET',
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ]
        ]);
    })->name('debug');
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
// Session status check for auto-logout (works for any guard)
Route::get('/session/check-status', [UserController::class, 'checkStatus'])->name('session.check-status');

// System updates check (used by system-updates-check.js for silent page reloads)
Route::get('/system/check-updates', [SystemLogController::class, 'checkUpdates'])->name('system.check-updates');

Route::middleware(['guest'])->group(function () {
    // Unified Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('login.session.check')->name('login.submit');

    // Password Reset
    Route::prefix('password')->group(function () {
        // Show the forgot password form
        Route::get('forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        
        // Handle the forgot password form submission
        Route::post('email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        
        // Show the OTP verification form
        Route::get('verify-otp/{token}', [ResetPasswordController::class, 'showVerifyOtpForm'])->name('password.verify.otp');
        
        // Handle OTP verification form submission
        Route::post('verify-otp', [ResetPasswordController::class, 'verifyOtp'])->name('password.verify.otp.submit');
        
        // Resend OTP
        Route::post('resend-otp', [ResetPasswordController::class, 'resendOtp'])->name('password.resend.otp');
        
        // Show the reset password form (after OTP verification)
        Route::get('reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        
        // Handle the password reset form submission
        Route::post('reset', [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    // Admin Login Routes
    Route::get('/login/admin', [AdminLoginController::class, 'showLoginForm'])->name('admin.login.form');
    Route::post('/login/admin', [AdminLoginController::class, 'login'])->middleware('login.session.check')->name('admin.login');

    // Google OAuth Routes
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// Logout routes
Route::get('/logout', function() { return redirect('/'); });
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Session unlock routes
Route::post('/staff/unlock-session', [AuthController::class, 'unlockSessionStaff'])->name('staff.unlock.session')->middleware(['auth:staff', 'guard.access:staff']);
Route::post('/technician/unlock-session', [AuthController::class, 'unlockSessionTechnician'])->name('technician.unlock.session')->middleware(['auth:technician', 'guard.access:technician']);

// Session settings routes
Route::get('/staff/session-settings', [AuthController::class, 'getSessionSettings'])->name('staff.session.settings')->middleware(['auth:staff', 'guard.access:staff']);
Route::get('/technician/session-settings', [AuthController::class, 'getSessionSettings'])->name('technician.session.settings')->middleware(['auth:technician', 'guard.access:technician']);

// Role-specific login routes
Route::post('/technician/login', [\App\Http\Controllers\Auth\TechnicianLoginController::class, 'login'])->middleware('login.session.check')->name('technician.login');
Route::post('/staff/login', [\App\Http\Controllers\Auth\StaffLoginController::class, 'login'])->middleware('login.session.check')->name('staff.login');

/*
|--------------------------------------------------------------------------
| Global Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Accounts - accessible to all authenticated users
    Route::get('/accounts', [\App\Http\Controllers\User\AdminController::class, 'accounts'])->name('accounts.index');

    // Unlock Session (for session lock modal) - Allow all authenticated guards
    Route::post('/unlock-session', [AuthController::class, 'unlockSession'])->name('unlock.session');

    // Session settings (for dynamic updates) - Allow all authenticated guards
    Route::get('/session-settings', [AuthController::class, 'getSessionSettings'])->name('session.settings');

    // Unified Accounts Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/form', [UserController::class, 'create'])->name('form')->middleware('permission:users.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('permission:users.view');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::post('/store', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:users.edit');
    });
});

/*
|--------------------------------------------------------------------------
| Security Routes
|--------------------------------------------------------------------------
*/
Route::middleware('aggressive.back.prevent')->group(function () {
    // Handle blocked access attempts - create infinite redirect loop
    Route::get('/blocked', function() {
        return redirect('/login?blocked=' . time())->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0, no-transform, private, proxy-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Refresh' => '0; url=/login?blocked=' . time()
        ]);
    });
    
    // Logout redirect loop to prevent back button
    Route::get('/logout-redirect', function() {
        return redirect('/login?logout=' . time())->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0, no-transform, private, proxy-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Refresh' => '0; url=/login?logout=' . time()
        ]);
    })->name('logout.redirect');
});

/*
|--------------------------------------------------------------------------
| Automatic Backup Route (Public)
|--------------------------------------------------------------------------
*/
// Automatic backup endpoint (no auth required for AJAX calls)
Route::post('/backup/auto', [BackupController::class, 'autoBackup'])->name('backup.auto');

/*
|--------------------------------------------------------------------------
| Include Role-Specific Route Files
|--------------------------------------------------------------------------
*/
require __DIR__.'/admin.php';
require __DIR__.'/technician.php';
require __DIR__.'/staff.php';
