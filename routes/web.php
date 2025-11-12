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
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TechnicianController;
use App\Http\Controllers\Admin\EquipmentController;
use App\Http\Controllers\Admin\RepairController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\PublicEquipmentController;
use App\Http\Controllers\Auth\TechnicianLoginController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\SocialAuthController;

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
    Route::post('/equipment/scan', [PublicEquipmentController::class, 'scanQrCode'])->name('equipment.scan');
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
Route::middleware(['guest'])->group(function () {
    // Unified Login
    Route::get('/login', [\App\Http\Controllers\Auth\AuthController::class, 'showLoginForm'])
        ->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthController::class, 'login'])
        ->name('login.submit');

    // Password Reset
    Route::prefix('password')->group(function () {
        Route::get('forgot', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])
            ->name('password.request');
        Route::post('email', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('password.email');
        Route::get('reset/{token}', [\App\Http\Controllers\Auth\AuthController::class, 'showResetPasswordForm'])
            ->name('password.reset');
        Route::post('update', [\App\Http\Controllers\Auth\AuthController::class, 'resetPassword'])
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

// Technician unlock session
Route::post('/technician/unlock-session', [AuthController::class, 'unlockSessionTechnician'])
    ->name('technician.unlock.session')
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
            Route::get('/{equipment}/download-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'downloadQrCode'])
                ->name('equipment.download-qrcode');
            Route::get('/{equipment}/print-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'qrCode'])
                ->name('equipment.print-qrcode');

            // QR Code scanning
            Route::get('/scan', [\App\Http\Controllers\Technician\EquipmentController::class, 'scanView'])
                ->name('equipment.scan.view');
            Route::post('/scan', [\App\Http\Controllers\Technician\EquipmentController::class, 'scanQrCode'])
                ->name('equipment.scan')->middleware('permission:equipment.view');
            Route::post('/decode-qr', [\App\Http\Controllers\Technician\EquipmentController::class, 'decodeQrCode'])
                ->name('equipment.decode-qr')->middleware('permission:equipment.view');
            
            // QR Code routes
            Route::prefix('{equipment}')->group(function () {
                Route::get('/download-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'downloadQrCode'])
                    ->name('equipment.download-qrcode');
                
                Route::get('/print-qrcode', [\App\Http\Controllers\Technician\EquipmentController::class, 'qrCode'])
                    ->name('equipment.print-qrcode');
            });
            
            // History routes - outside permission middleware
            Route::prefix('{equipment}')->group(function () {
                Route::get('/history/create', [\App\Http\Controllers\Technician\EquipmentController::class, 'createHistory'])
                    ->name('equipment.history.create');
                    
                Route::post('/history', [\App\Http\Controllers\Technician\EquipmentController::class, 'storeHistory'])
                    ->name('equipment.history.store');
                    
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
        
        // Maintenance Logs
        Route::resource('maintenance-logs', \App\Http\Controllers\Technician\MaintenanceLogController::class);
        
        // Profile
        Route::get('/profile', [\App\Http\Controllers\Technician\TechnicianController::class, 'profile'])->name('profile.show');
        Route::post('/profile', [\App\Http\Controllers\Technician\TechnicianController::class, 'updateProfile'])->name('profile.update');
        
        // Reports
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Technician\ReportController::class, 'index'])->name('index');
            Route::get('/equipment-history', [\App\Http\Controllers\Technician\ReportController::class, 'equipmentHistory'])->name('equipment-history');
            Route::get('/maintenance', [\App\Http\Controllers\Technician\ReportController::class, 'maintenanceReport'])->name('maintenance');
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
            Route::post('/update', [\App\Http\Controllers\Staff\StaffController::class, 'updateProfile'])
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
                    ->name('scan');
                Route::post('scan', [\App\Http\Controllers\Staff\EquipmentController::class, 'scanQrCode'])
                    ->name('scan');

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
                Route::get('{equipment}/download-qrcode', [\App\Http\Controllers\Staff\EquipmentController::class, 'downloadQrCode'])
                    ->name('download-qrcode');
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

    // Unified Accounts Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/form', [UserController::class, 'create'])->name('form')->middleware('permission:users.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('permission:users.view');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::post('/store', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
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
            Route::post('/profile/update', [AdminController::class, 'updateProfile'])->name('profile.update');

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
                Route::get('/', [StaffController::class, 'index'])->name('index');
                Route::get('create', [StaffController::class, 'create'])->name('create')->middleware('permission:users.create');
                Route::post('/', [StaffController::class, 'store'])->name('store')->middleware('permission:users.create');
                Route::get('{staff}', [StaffController::class, 'show'])->name('show');
                Route::get('{staff}/edit', [StaffController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
                Route::put('{staff}', [StaffController::class, 'update'])->name('update')->middleware('permission:users.edit');
                Route::delete('{staff}', [StaffController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');

                // Staff Actions
                Route::post('{staff}/toggle-status', [StaffController::class, 'toggleStatus'])
                    ->name('toggle-status')->middleware('permission:users.edit');
                Route::post('{staff}/toggle-admin', [StaffController::class, 'toggleAdmin'])
                    ->name('toggle-admin')->middleware('permission:users.edit');
            });

            // Technician Management
            Route::prefix('technicians')->name('technicians.')->middleware('permission:users.view')->group(function () {
                Route::get('/', [TechnicianController::class, 'index'])->name('index');
                Route::get('create', [TechnicianController::class, 'create'])->name('create')->middleware('permission:users.create');
                Route::post('/', [TechnicianController::class, 'store'])->name('store')->middleware('permission:users.create');
                Route::get('{technician}', [TechnicianController::class, 'show'])->name('show');
                Route::get('{technician}/edit', [TechnicianController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
                Route::put('{technician}', [TechnicianController::class, 'update'])->name('update')->middleware('permission:users.edit');
                Route::delete('{technician}', [TechnicianController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
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
                Route::get('{equipment}/download-qrcode', [EquipmentController::class, 'downloadQrCode'])->name('download-qrcode');
                Route::get('{equipment}/print-qrcode', [EquipmentController::class, 'qrCode'])->name('print-qrcode');

                // QR Scanner
                Route::get('scan', [EquipmentController::class, 'scanView'])->name('scan');
                Route::post('scan', [EquipmentController::class, 'scanQrCode'])->name('scan')->middleware('permission:equipment.view');

                // History Management
                Route::prefix('{equipment}')->group(function () {
                    Route::get('history/create', [EquipmentController::class, 'createHistory'])
                        ->name('history.create')->middleware('permission:history.create');
                    Route::post('history', [EquipmentController::class, 'storeHistory'])
                        ->name('history.store')->middleware('permission:history.store');
                    Route::post('generate-jo', [EquipmentController::class, 'generateJONumber'])->name('generate-jo');
                    Route::post('check-latest-repair', [EquipmentController::class, 'checkLatestRepair'])->name('check-latest-repair');
                    Route::post('check-sequences', [EquipmentController::class, 'checkSequences'])->name('check-sequences');
                    Route::post('clear-history-prompt', [EquipmentController::class, 'clearHistoryPrompt'])->name('clear-history-prompt');
                });
            });

            // ============================================================================
            // MAINTENANCE & REPAIRS
            // ============================================================================

            Route::prefix('maintenance')->name('maintenance.')->middleware('permission:equipment.view')->group(function () {
                Route::get('/', [MaintenanceController::class, 'index'])->name('index');
            });

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

            // ============================================================================
            // SYSTEM ADMINISTRATION
            // ============================================================================

            // RBAC Management (Protected)
            Route::middleware(['rbac.verify'])->group(function () {
                Route::resource('rbac/roles', RoleController::class)
                    ->only(['index', 'edit', 'update'])->names('rbac.roles');
                Route::resource('rbac/permissions', PermissionController::class)
                    ->only(['index'])->names('rbac.permissions');

                // Role Permissions Management
                Route::get('rbac/roles/permissions', [RoleController::class, 'permissions'])->name('rbac.roles.permissions');
                Route::post('rbac/roles/permissions', [RoleController::class, 'updatePermissions'])->name('rbac.roles.update-permissions');

                // RBAC User Management
                Route::prefix('rbac/users')->name('rbac.users.')->group(function () {
                    Route::get('/', [UserController::class, 'index'])->name('index');
                    Route::get('create', [UserController::class, 'create'])->name('create');
                    Route::post('/', [UserController::class, 'store'])->name('store');
                    Route::get('{user}', [UserController::class, 'show'])->name('show');
                    Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
                    Route::put('{user}', [UserController::class, 'update'])->name('update');
                    Route::get('{user}/edit-roles', [UserController::class, 'editRoles'])->name('edit-roles');
                    Route::put('{user}/update-roles', [UserController::class, 'updateRoles'])->name('update-roles');
                    Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
                });
            });

            // Office Management
            Route::resource('offices', OfficeController::class)->middleware('permission:settings.manage');
            Route::post('offices/{office}/toggle-status', [OfficeController::class, 'toggleStatus'])
                ->name('offices.toggle-status')->middleware('permission:settings.manage');

            // System Logs
            Route::prefix('system-logs')->name('system-logs.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\SystemLogController::class, 'index'])->name('index');
                Route::get('accounts', [\App\Http\Controllers\Admin\SystemLogController::class, 'accountsLogs'])->name('accounts');
                Route::get('equipment', [\App\Http\Controllers\Admin\SystemLogController::class, 'equipmentLogs'])->name('equipment');
                Route::get('user-logins', [\App\Http\Controllers\Admin\SystemLogController::class, 'userLoginLogs'])->name('user-logins');
                Route::get('downloads', [\App\Http\Controllers\Admin\SystemLogController::class, 'downloadLogs'])->name('downloads');
                Route::get('export', [\App\Http\Controllers\Admin\SystemLogController::class, 'export'])->name('export');
                Route::delete('clear', [\App\Http\Controllers\Admin\SystemLogController::class, 'clear'])->name('clear');
            });

            // Settings
            Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
                Route::get('/', function() {
                    $settings = [
                        'session_lockout_minutes' => \App\Models\Setting::getSessionLockoutMinutes(),
                    ];
                    return view('settings.index', compact('settings'));
                })->name('index');
                Route::post('/', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
            });

            // QR Scanner (Global)
            Route::get('qr-scanner', [EquipmentController::class, 'qrScanner'])
                ->name('qr-scanner')->middleware('permission:qr.scan');

        });

    // ============================================================================
    // STAFF ROUTES
    // ============================================================================

    // Duplicate staff routes removed - see consolidated routes above
