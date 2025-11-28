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
use App\Http\Controllers\User\AdminController;
use App\Http\Controllers\User\EquipmentController;
use App\Http\Controllers\User\RepairController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\User\OfficeController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\PermissionController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\TechnicianController;
use App\Http\Controllers\User\StaffController;
use App\Http\Controllers\PublicEquipmentController;
use App\Http\Controllers\Auth\TechnicianLoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\User\SystemLogController;

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
    Route::get('/login', [\App\Http\Controllers\Auth\AuthController::class, 'showLoginForm'])
        ->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login'])
        ->name('login.submit');

    // Password Reset
    Route::prefix('password')->group(function () {
        // Show the forgot password form
        Route::get('forgot', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
            ->name('password.request');
            
        // Handle the forgot password form submission
        Route::post('email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('password.email');
            
        // Show the OTP verification form
        Route::get('verify-otp/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showVerifyOtpForm'])
            ->name('password.verify.otp');
            
        // Handle OTP verification form submission
        Route::post('verify-otp', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'verifyOtp'])
            ->name('password.verify.otp.submit');
            
        // Resend OTP
        Route::post('resend-otp', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'resendOtp'])
            ->name('password.resend.otp');
            
        // Show the reset password form (after OTP verification)
        Route::get('reset/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])
            ->name('password.reset');
            
        // Handle the password reset form submission
        Route::post('reset', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])
            ->name('password.update');
    });

    // Admin Login Routes
    Route::get('/login/admin', [\App\Http\Controllers\Auth\AdminLoginController::class, 'showLoginForm'])
        ->name('admin.login.form');
    Route::post('/login/admin', [\App\Http\Controllers\Auth\AdminLoginController::class, 'login'])
        ->name('admin.login');

    // Google OAuth Routes
    Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])
        ->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])
        ->name('auth.google.callback');

// Logout
Route::get('/logout', function() { return redirect('/'); });
Route::post('/logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])
    ->name('logout');

// Staff unlock session
Route::post('/staff/unlock-session', [\App\Http\Controllers\Auth\AuthController::class, 'unlockSessionStaff'])
    ->name('staff.unlock.session')
    ->middleware('auth:staff');

// Staff session settings
Route::get('/staff/session-settings', [\App\Http\Controllers\Auth\AuthController::class, 'getSessionSettings'])
    ->name('staff.session.settings')
    ->middleware('auth:staff');

// Technician unlock session
Route::post('/technician/unlock-session', [AuthController::class, 'unlockSessionTechnician'])
    ->name('technician.unlock.session')
    ->middleware('auth:technician');

// Technician session settings
Route::get('/technician/session-settings', [\App\Http\Controllers\Auth\AuthController::class, 'getSessionSettings'])
    ->name('technician.session.settings')
    ->middleware('auth:technician');

// Technician Login
Route::post('/technician/login', [TechnicianLoginController::class, 'login'])
    ->name('technician.login');

// Staff Login
Route::post('/staff/login', [StaffLoginController::class, 'login'])
    ->name('staff.login');
});

/*
|--------------------------------------------------------------------------
| Technician Routes
|--------------------------------------------------------------------------
*/



Route::middleware(['auth:technician'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        // Dashboard (Profile)
        Route::get('/', [\App\Http\Controllers\Technician\TechnicianController::class, 'profile'])
            ->name('profile');

        // Profile update route
        // Route::match(['put', 'post'], '/profile/update', [\App\Http\Controllers\Auth\TechnicianLoginController::class, 'updateProfile'])
        //     ->name('profile.update');

        // Profile edit form route (for consistency, though modal is used)
        Route::get('/profile/edit', [\App\Http\Controllers\Technician\TechnicianController::class, 'editProfile'])
            ->name('profile.edit');

        // Update profile (admin route for technicians)
        Route::post('/admin/technician/profile/update', [\App\Http\Controllers\Auth\TechnicianLoginController::class, 'updateProfile'])
            ->name('admin.technician.profile.update');


        // QR Scanner (Modal in Equipment)
        Route::get('/qr-scanner', [\App\Http\Controllers\Auth\TechnicianLoginController::class, 'qrScanner'])
            ->name('qr-scanner');

        // Logout
        Route::post('/logout', [\App\Http\Controllers\Auth\TechnicianLoginController::class, 'logout'])
            ->name('logout');

        // Equipment
        Route::prefix('equipment')->middleware('auth:technician')->group(function () {
            Route::get('/', [\App\Http\Controllers\Technician\EquipmentController::class, 'index'])
                ->name('equipment.index');
            
            Route::get('/create', [\App\Http\Controllers\Technician\EquipmentController::class, 'create'])
                ->name('equipment.create');
            Route::post('/', [\App\Http\Controllers\Technician\EquipmentController::class, 'store'])
                ->name('equipment.store');

            // Print QR Codes (moved before parameter routes)
            Route::get('/print-qrcodes', [\App\Http\Controllers\Technician\EquipmentController::class, 'printQrcodes'])
                ->name('equipment.print-qrcodes');
            Route::get('/print-qrcodes/pdf', [\App\Http\Controllers\Technician\EquipmentController::class, 'printQrcodesPdf'])
                ->name('equipment.print-qrcodes.pdf');

            // Parameter routes (must come after specific routes)
            Route::get('/{equipment}', [\App\Http\Controllers\Technician\EquipmentController::class, 'show'])
                ->name('equipment.show');
            Route::put('/{equipment}/status', [\App\Http\Controllers\Technician\EquipmentController::class, 'updateStatus'])
                ->name('equipment.status.update');

            Route::get('/{equipment}/edit', [\App\Http\Controllers\Technician\EquipmentController::class, 'edit'])
                ->name('equipment.edit');
            Route::put('/{equipment}', [\App\Http\Controllers\Technician\EquipmentController::class, 'update'])
                ->name('equipment.update');

            // Get equipment for a specific office (AJAX)
            Route::get('/office/{officeId}', [\App\Http\Controllers\Technician\EquipmentController::class, 'getOfficeEquipment'])
                ->name('equipment.office');

            // QR Code routes
            Route::get('/{equipment}/qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'qrCode'])
                ->name('equipment.qrcode');
            Route::get('/{equipment}/print-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'qrCode'])
                ->name('equipment.print-qrcode');

            // QR Code scanning
            Route::get('/scan', [\App\Http\Controllers\Technician\EquipmentController::class, 'scanView'])
                ->name('equipment.scan.view');
            Route::post('/scan', [\App\Http\Controllers\Technician\EquipmentController::class, 'scanQrCode'])
                ->name('equipment.scan.process');
            Route::post('/decode-qr', [\App\Http\Controllers\Technician\EquipmentController::class, 'decodeQrCode'])
                ->name('equipment.decode-qr');
            
            // QR Code routes
            Route::prefix('{equipment}')->group(function () {
                Route::get('/print-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'qrCode'])
                    ->name('equipment.print-qrcode');
            });
            
            // History routes - outside permission middleware
            Route::prefix('{equipment}')->group(function () {
                Route::get('/history/create', [\App\Http\Controllers\Technician\EquipmentController::class, 'createHistory'])
                    ->name('equipment.history.create');
                    
                Route::post('/history', [\App\Http\Controllers\Technician\EquipmentController::class, 'storeHistory'])
                    ->name('equipment.history.store');
                    
                Route::get('/history/{history}/edit', [\App\Http\Controllers\Technician\EquipmentController::class, 'editHistory'])
                    ->name('equipment.history.edit')->middleware('permission:history.edit');
                    
                Route::put('/history/{history}', [\App\Http\Controllers\Technician\EquipmentController::class, 'updateHistory'])
                    ->name('equipment.history.update')->middleware('permission:history.edit');
                    
                Route::post('/generate-jo', [\App\Http\Controllers\Technician\EquipmentController::class, 'generateJONumber'])
                    ->name('equipment.generate-jo');
                    
                Route::post('/check-latest-repair', [\App\Http\Controllers\Technician\EquipmentController::class, 'checkLatestRepair'])
                    ->name('equipment.check-latest-repair');
                    
                Route::post('/check-sequences', [\App\Http\Controllers\Technician\EquipmentController::class, 'checkSequences'])
                    ->name('equipment.check-sequences');
                    
                Route::post('/clear-history-prompt', [\App\Http\Controllers\Technician\EquipmentController::class, 'clearHistoryPrompt'])
                    ->name('equipment.clear-history-prompt');
            });
        });
        
                
        // Profile
        Route::get('/profile', [\App\Http\Controllers\Technician\TechnicianController::class, 'profile'])->name('profile');
        Route::get('/profile/edit', [\App\Http\Controllers\Technician\TechnicianController::class, 'editProfile'])->name('profile.edit');
        Route::match(['put', 'post'], '/profile', [\App\Http\Controllers\Technician\TechnicianController::class, 'updateProfile'])->name('profile.update');
        
        // Reports
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Technician\ReportController::class, 'index'])->name('index');
            Route::get('/equipment-history', [\App\Http\Controllers\Technician\ReportController::class, 'equipmentHistory'])->name('equipment-history');
            Route::get('/history/{equipment}', [\App\Http\Controllers\Technician\ReportController::class, 'history'])->name('history');
            
            // Equipment History Export Route
            Route::prefix('equipment/{equipment}')->middleware('permission:reports.generate')->group(function () {
                Route::get('/export', [\App\Http\Controllers\Technician\ReportController::class, 'exportEquipmentHistory'])->name('equipment.history.export');
                Route::get('/export-pdf', [\App\Http\Controllers\Technician\ReportController::class, 'exportEquipmentHistoryPdf'])->name('equipment.history.export.pdf');
                Route::get('/history', [\App\Http\Controllers\Technician\ReportController::class, 'history'])->name('equipment.history.view');
            });
        });
        
        // Test route for history form
        Route::get('/test-history/{equipment}', function(\App\Models\Equipment $equipment) {
            return view('technician.equipment.history.create', [
                'equipment' => $equipment->load('office')
            ]);
        })->name('test.history');
        
        // Technician Accounts - REMOVED as requested
    });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Accounts
    Route::get('/accounts', [\App\Http\Controllers\Admin\AdminController::class, 'accounts'])
        ->name('accounts');
    
    // Account management routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::post('/{user}/toggle-status', [\App\Http\Controllers\User\UserController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('permission:users.edit');
        Route::get('/check-status', [\App\Http\Controllers\User\UserController::class, 'checkStatus'])
            ->name('check-status');
    });
    
    // Admin Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\SettingsController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\User\SettingsController::class, 'update'])->name('update');
        Route::get('/api/backup-settings', [\App\Http\Controllers\User\SettingsController::class, 'getBackupSettings'])->name('api.backup-settings');
    });
});

    Route::middleware(['auth:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        // Dashboard (Profile)
        Route::get('/', [\App\Http\Controllers\Staff\StaffController::class, 'profile'])
            ->name('profile');

        // Profile routes
        Route::prefix('profile')->group(function () {
            // Show profile
            Route::get('/', [\App\Http\Controllers\Staff\StaffController::class, 'profile'])
                ->name('profile.show');

            // Edit profile form
            Route::get('/edit', [\App\Http\Controllers\Staff\StaffController::class, 'editProfile'])
                ->name('profile.edit');

            // Update profile
            Route::match(['put', 'post'], '/', [\App\Http\Controllers\Staff\StaffController::class, 'updateProfile'])
                ->name('profile.update');
        });

        // QR Scanner
        Route::get('qr-scanner', [\App\Http\Controllers\Staff\EquipmentController::class, 'qrScanner'])
            ->name('qr-scanner')
            ->middleware('permission:qr.scan');

        // Equipment Management
        Route::prefix('equipment')
            ->name('equipment.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Staff\EquipmentController::class, 'index'])
                    ->name('index');
                Route::get('create', [\App\Http\Controllers\Staff\EquipmentController::class, 'create'])
                    ->name('create');
                Route::post('/', [\App\Http\Controllers\Staff\EquipmentController::class, 'store'])
                    ->name('store');

                // Print QR Codes (moved before parameter routes)
                Route::get('print-qrcodes', [\App\Http\Controllers\Staff\EquipmentController::class, 'printQrcodes'])
                    ->name('print-qrcodes');
                Route::get('print-qrcodes/pdf', [\App\Http\Controllers\Staff\EquipmentController::class, 'printQrcodesPdf'])
                    ->name('print-qrcodes.pdf');

                // QR Scanner
                Route::get('scan', [\App\Http\Controllers\Staff\EquipmentController::class, 'scanView'])
                    ->name('scan.view');
                Route::post('scan', [\App\Http\Controllers\Staff\EquipmentController::class, 'scanQrCode'])
                    ->name('scan.process');

                // Parameter routes (must come after specific routes)
                Route::get('{equipment}', [\App\Http\Controllers\Staff\EquipmentController::class, 'show'])
                    ->name('show');
                Route::get('{equipment}/edit', [\App\Http\Controllers\Staff\EquipmentController::class, 'edit'])
                    ->name('edit');
                Route::put('{equipment}', [\App\Http\Controllers\Staff\EquipmentController::class, 'update'])
                    ->name('update');
                Route::delete('{equipment}', [\App\Http\Controllers\Staff\EquipmentController::class, 'destroy'])
                    ->name('destroy');
                    
                // QR Code routes
                Route::get('{equipment}/qrcode', [\App\Http\Controllers\Staff\EquipmentController::class, 'qrCode'])
                    ->name('qrcode');
                Route::get('{equipment}/print-qrcode', [\App\Http\Controllers\Staff\EquipmentController::class, 'printQRCode'])
                    ->name('print-qrcode');
            });

        // Reports
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Staff\ReportController::class, 'index'])->name('index');
            Route::get('/equipment-history', [\App\Http\Controllers\Staff\ReportController::class, 'equipmentHistory'])->name('equipment-history');
            Route::get('/history/{equipment}', [\App\Http\Controllers\Staff\ReportController::class, 'history'])->name('history');

            // Equipment History Export
            Route::prefix('equipment/{equipment}')->middleware('permission:reports.generate')->group(function () {
                Route::get('/export', [\App\Http\Controllers\Staff\ReportController::class, 'exportEquipmentHistory'])->name('equipment.history.export');
                Route::get('/export-pdf', [\App\Http\Controllers\Staff\ReportController::class, 'exportEquipmentHistoryPdf'])->name('equipment.history.export.pdf');
            });
        });

        // Logout
        Route::post('/logout', [\App\Http\Controllers\Auth\StaffLoginController::class, 'logout'])
            ->name('logout');
    });

/*
|--------------------------------------------------------------------------
| Global Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Accounts - accessible to all authenticated users
    Route::get('/accounts', [AdminController::class, 'accounts'])
        ->name('accounts.index');

    // Unlock Session (for session lock modal) - Allow all authenticated guards
    Route::post('/unlock-session', [\App\Http\Controllers\Auth\AuthController::class, 'unlockSession'])
        ->name('unlock.session');

// Session settings (for dynamic updates) - Allow all authenticated guards
    Route::get('/session-settings', [\App\Http\Controllers\Auth\AuthController::class, 'getSessionSettings'])
        ->name('session.settings');

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
| Admin Routes
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

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Authentication Routes for Staff
        Route::get('logout', function() { return redirect('/'); });
        Route::post('logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])
            ->name('logout');
        Route::post('unlock-session', [\App\Http\Controllers\Auth\AuthController::class, 'unlockSession'])
            ->name('unlock.session');


            // ============================================================================
            // ADMIN DASHBOARD & MAIN FEATURES
            // ============================================================================

            // Main Dashboard Route
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

            // Admin Profile (modal only)
            Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
            Route::get('/profile/edit', [AdminController::class, 'editProfile'])->name('profile.edit');
            Route::match(['put', 'post'], '/profile', [AdminController::class, 'updateProfile'])->name('profile.update');

            // ============================================================================
            // USER & ACCOUNT MANAGEMENT
            // ============================================================================

            // User Accounts Management
            Route::prefix('accounts')->name('accounts.')->middleware('permission:users.view')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('form', [UserController::class, 'create'])->name('form');
                Route::post('store', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
                Route::get('{user}', [UserController::class, 'show'])->name('show');
                Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
                Route::put('{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
                Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');

                // Grant temporary admin access (super-admin only)
                Route::post('{user}/grant-temp-admin', [UserController::class, 'grantTempAdmin'])
                    ->name('grant-temp-admin')->middleware('auth');

                // Email verification management
                Route::post('{user}/resend-verification', [EmailVerificationController::class, 'sendVerificationEmail'])
                    ->name('resend-verification')->middleware('permission:users.edit');
            });

            // Staff Management
            Route::prefix('staff')->name('staff.')->middleware('permission:users.view')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
                Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
                Route::get('{user}', [UserController::class, 'show'])->name('show');
                Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
                Route::put('{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
                Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');

                // Staff Actions
                Route::post('{user}/toggle-status', [UserController::class, 'toggleStatus'])
                    ->name('toggle-status')->middleware('permission:users.edit');
                Route::post('{user}/toggle-admin', [UserController::class, 'toggleAdmin'])
                    ->name('toggle-admin')->middleware('permission:users.edit');
            });

            // Technician Management
            Route::prefix('technicians')->name('technicians.')->middleware('permission:users.view')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
                Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
                Route::get('{user}', [UserController::class, 'show'])->name('show');
                Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
                Route::put('{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
                Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
            });

            // ============================================================================
            // EQUIPMENT MANAGEMENT
            // ============================================================================

            Route::prefix('equipment')->name('equipment.')->middleware('auth')->group(function () {
                Route::get('/', [AdminController::class, 'equipment'])->name('index');
                Route::get('create', [EquipmentController::class, 'create'])->name('create')->middleware('permission:equipment.create');
                Route::post('/', [EquipmentController::class, 'store'])->name('store')->middleware('permission:equipment.create');
                Route::get('print-qrcodes', [EquipmentController::class, 'printQrcodes'])->name('print-qrcodes');
                Route::get('print-qrcodes/pdf', [EquipmentController::class, 'printQrcodesPdf'])->name('print-qrcodes.pdf');
                Route::get('{equipment}', [EquipmentController::class, 'show'])->name('show');
                Route::get('{equipment}/edit', [EquipmentController::class, 'edit'])->name('edit')->middleware('permission:equipment.edit');
                Route::put('{equipment}', [EquipmentController::class, 'update'])->name('update')->middleware('permission:equipment.edit');
                Route::delete('{equipment}', [EquipmentController::class, 'destroy'])->name('destroy')->middleware('permission:equipment.delete');

                // Equipment Actions
                Route::get('{equipment}/qrcode', [EquipmentController::class, 'qrCode'])->name('qrcode');
                Route::get('{equipment}/print-qrcode', [EquipmentController::class, 'qrCode'])->name('print-qrcode');

                // QR Scanner
                Route::get('scan', [EquipmentController::class, 'scanView'])->name('scan.view');
                Route::post('scan', [EquipmentController::class, 'scanQrCode'])->name('scan.process')->middleware('permission:equipment.view');

                // History Management
                Route::prefix('{equipment}')->group(function () {
                    Route::get('history/create', [EquipmentController::class, 'createHistory'])
                        ->name('history.create')->middleware('permission:history.create');
                    Route::post('history', [EquipmentController::class, 'storeHistory'])
                        ->name('history.store')->middleware('permission:history.store');
                    Route::get('history/{history}/edit', [EquipmentController::class, 'editHistory'])
                        ->name('history.edit')->middleware('permission:history.edit');
                    Route::put('history/{history}', [EquipmentController::class, 'updateHistory'])
                        ->name('history.update')->middleware('permission:history.edit');
                    Route::post('generate-jo', [EquipmentController::class, 'generateJONumber'])->name('generate-jo');
                    Route::post('check-latest-repair', [EquipmentController::class, 'checkLatestRepair'])->name('check-latest-repair');
                    Route::post('check-sequences', [EquipmentController::class, 'checkSequences'])->name('check-sequences');
                    Route::post('clear-history-prompt', [EquipmentController::class, 'clearHistoryPrompt'])->name('clear-history-prompt');
                });
            });

            // ============================================================================
            // REPAIRS
            // ============================================================================

            Route::prefix('repairs')->name('repairs.')->middleware('permission:equipment.view')->group(function () {
                Route::get('/', [RepairController::class, 'index'])->name('index');
            });

            // ============================================================================
            // REPORTS
            // ============================================================================

            Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::get('{id}/history', [ReportController::class, 'history'])->name('history')->middleware('permission:reports.generate');
                Route::get('export', [ReportController::class, 'export'])->name('export');

                // Equipment History Reports
                Route::prefix('equipment/{equipment}')->middleware('permission:reports.generate')->group(function () {
                    Route::get('history', [ReportController::class, 'equipmentHistory'])->name('equipment.history.view');
                    Route::get('export', [ReportController::class, 'exportEquipmentHistory'])->name('equipment.history.export');
                });
            });

            Route::resource('reports', ReportController::class)->except(['index'])->middleware('permission:reports.generate');

            // ==========================================================================
            // SYSTEM ADMINISTRATION
            // ==========================================================================

            // RBAC Management (Protected)
            Route::middleware(['rbac.verify'])->group(function () {
                Route::resource('rbac/roles', RoleController::class)
                    ->only(['index', 'edit', 'update'])->names('rbac.roles');

                // Role Permissions Management
                Route::get('rbac/roles/permissions', [RoleController::class, 'permissions'])->name('rbac.roles.permissions');
                Route::post('rbac/roles/permissions', [RoleController::class, 'updatePermissions'])->name('rbac.roles.update-permissions');
            });

            // Office Management
            Route::resource('offices', OfficeController::class);
            Route::post('offices/{office}/toggle-status', [OfficeController::class, 'toggleStatus'])
                ->name('offices.toggle-status')->middleware('permission:offices.edit');

            // System Logs
            Route::prefix('system-logs')->name('system-logs.')->group(function () {
                Route::get('/', [\App\Http\Controllers\User\SystemLogController::class, 'index'])->name('index');
                Route::get('accounts', [\App\Http\Controllers\User\SystemLogController::class, 'accountsLogs'])->name('accounts');
                Route::get('equipment', [\App\Http\Controllers\User\SystemLogController::class, 'equipmentLogs'])->name('equipment');
                Route::get('user-logins', [\App\Http\Controllers\User\SystemLogController::class, 'userLoginLogs'])->name('user-logins');
                Route::get('export', [\App\Http\Controllers\User\SystemLogController::class, 'export'])->name('export');
                Route::delete('clear', [\App\Http\Controllers\User\SystemLogController::class, 'clear'])->name('clear');
            });

            // Settings
            Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
                Route::get('/', [\App\Http\Controllers\User\SettingsController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\User\SettingsController::class, 'update'])->name('update');
                Route::get('/api/backup-settings', [\App\Http\Controllers\User\SettingsController::class, 'getBackupSettings'])->name('api.backup-settings');

                // System Management (Categories & Equipment Types)
                Route::prefix('system')->name('system.')->group(function () {
                    // System Dashboard
                    Route::get('/', [\App\Http\Controllers\User\SettingsController::class, 'systemIndex'])->name('index');

                    // Category Management
                    Route::prefix('categories')->name('categories.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\SettingsController::class, 'categories'])->name('index');
                        Route::get('create', [\App\Http\Controllers\User\SettingsController::class, 'createCategory'])->name('create');
                        Route::post('/', [\App\Http\Controllers\User\SettingsController::class, 'storeCategory'])->name('store');
                        Route::get('{category}/edit', [\App\Http\Controllers\User\SettingsController::class, 'editCategory'])->name('edit');
                        Route::put('{category}', [\App\Http\Controllers\User\SettingsController::class, 'updateCategory'])->name('update');
                        Route::delete('{category}', [\App\Http\Controllers\User\SettingsController::class, 'destroyCategory'])->name('destroy');
                        Route::post('{category}/toggle', [\App\Http\Controllers\User\SettingsController::class, 'toggleCategory'])->name('toggle');
                    });

                    // Equipment Type Management
                    Route::prefix('equipment-types')->name('equipment-types.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\SettingsController::class, 'equipmentTypes'])->name('index');
                        Route::get('create', [\App\Http\Controllers\User\SettingsController::class, 'createEquipmentType'])->name('create');
                        Route::post('/', [\App\Http\Controllers\User\SettingsController::class, 'storeEquipmentType'])->name('store');
                        Route::get('{equipmentType}/edit', [\App\Http\Controllers\User\SettingsController::class, 'editEquipmentType'])->name('edit');
                        Route::put('{equipmentType}', [\App\Http\Controllers\User\SettingsController::class, 'updateEquipmentType'])->name('update');
                        Route::delete('{equipmentType}', [\App\Http\Controllers\User\SettingsController::class, 'destroyEquipmentType'])->name('destroy');
                        Route::post('{equipmentType}/toggle', [\App\Http\Controllers\User\SettingsController::class, 'toggleEquipmentType'])->name('toggle');
                        Route::post('update-sort-order', [\App\Http\Controllers\User\SettingsController::class, 'updateSortOrder'])->name('update-sort-order');
                    });
                });
            });

            // Database Backup & Restore (Super Admin Only)
            Route::prefix('backup')->name('backup.')->middleware('auth')->group(function () {
                Route::get('/', [BackupController::class, 'index'])->name('index');
                Route::get('/list', [BackupController::class, 'list'])->name('list');
                Route::post('/create', [BackupController::class, 'backup'])->name('create');
                Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
                Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
                Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('delete');
            });
            
            // Automatic backup endpoint (no auth required for AJAX calls)
            Route::post('/backup/auto', [BackupController::class, 'autoBackup'])->name('backup.auto');

            // QR Scanner (Global)
            Route::get('qr-scanner', [EquipmentController::class, 'qrScanner'])
                ->name('qr-scanner')->middleware('permission:qr.scan');

        });
