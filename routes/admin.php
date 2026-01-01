<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\AdminController;
use App\Http\Controllers\User\EquipmentController;
use App\Http\Controllers\User\RepairController;
use App\Http\Controllers\User\ReportController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\OfficeController;
use App\Http\Controllers\User\SystemLogController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\BackupController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for admin users with full system access
|
*/

Route::middleware(['auth', 'guard.access:web'])->prefix('admin')->name('admin.')->group(function () {
    
    // ============================================================================
    // ADMIN DASHBOARD & MAIN FEATURES
    // ============================================================================
    
    // Main Dashboard Route
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Admin Profile
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [AdminController::class, 'editProfile'])->name('profile.edit');
    Route::match(['put', 'post'], '/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
    
    // Authentication
    Route::post('logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
    Route::post('unlock-session', [\App\Http\Controllers\Auth\AuthController::class, 'unlockSession'])->name('unlock.session');
    
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
        
        // User Actions
        Route::post('{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:users.edit');
        Route::post('{user}/grant-temp-admin', [UserController::class, 'grantTempAdmin'])->name('grant-temp-admin')->middleware('auth');
        Route::post('{user}/resend-verification', [EmailVerificationController::class, 'sendVerificationEmail'])->name('resend-verification')->middleware('permission:users.edit');
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
        Route::post('{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:users.edit');
        Route::post('{user}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('toggle-admin')->middleware('permission:users.edit');
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
    
    Route::prefix('equipment')->name('equipment.')->group(function () {
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
        Route::get('scan', [EquipmentController::class, 'scanView'])->name('scan.view');
        Route::post('scan', [EquipmentController::class, 'scanQrCode'])->name('scan.process')->middleware('permission:equipment.view');
        
        // History Management
        Route::prefix('{equipment}')->group(function () {
            Route::get('history/create', [EquipmentController::class, 'createHistory'])->name('history.create')->middleware('permission:history.create');
            Route::post('history', [EquipmentController::class, 'storeHistory'])->name('history.store')->middleware('permission:history.store');
            Route::get('history/{history}/edit', [EquipmentController::class, 'editHistory'])->name('history.edit')->middleware('permission:history.edit');
            Route::put('history/{history}', [EquipmentController::class, 'updateHistory'])->name('history.update')->middleware('permission:history.edit');
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
    
    // ============================================================================
    // SYSTEM ADMINISTRATION
    // ============================================================================
    
    // RBAC Management
    Route::middleware(['rbac.verify'])->group(function () {
        Route::resource('rbac/roles', RoleController::class)->only(['index', 'edit', 'update'])->names('rbac.roles');
        
        // Role Permissions Management
        Route::get('rbac/roles/permissions', [RoleController::class, 'permissions'])->name('rbac.roles.permissions');
        Route::post('rbac/roles/permissions', [RoleController::class, 'updatePermissions'])->name('rbac.roles.update-permissions');
    });
    
    // Office Management
    Route::resource('offices', OfficeController::class);
    Route::post('offices/{office}/toggle-status', [OfficeController::class, 'toggleStatus'])->name('offices.toggle-status')->middleware('permission:offices.edit');
    
    // System Logs
    Route::prefix('system-logs')->name('system-logs.')->group(function () {
        Route::get('/', [SystemLogController::class, 'index'])->name('index');
        Route::get('accounts', [SystemLogController::class, 'accountsLogs'])->name('accounts');
        Route::get('equipment', [SystemLogController::class, 'equipmentLogs'])->name('equipment');
        Route::get('user-logins', [SystemLogController::class, 'userLoginLogs'])->name('user-logins');
        Route::get('export', [SystemLogController::class, 'export'])->name('export');
        Route::delete('clear', [SystemLogController::class, 'clear'])->name('clear');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::get('/api/backup-settings', [SettingsController::class, 'getBackupSettings'])->name('api.backup-settings');
        
        // Category Management
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [SettingsController::class, 'categories'])->name('index');
            Route::get('create', [SettingsController::class, 'createCategory'])->name('create');
            Route::post('/', [SettingsController::class, 'storeCategory'])->name('store');
            Route::get('{category}/edit', [SettingsController::class, 'editCategory'])->name('edit');
            Route::put('{category}', [SettingsController::class, 'updateCategory'])->name('update');
            Route::delete('{category}', [SettingsController::class, 'destroyCategory'])->name('destroy');
            Route::post('{category}/toggle', [SettingsController::class, 'toggleCategory'])->name('toggle');
        });
        
        // Equipment Type Management
        Route::prefix('equipment-types')->name('equipment-types.')->group(function () {
            Route::get('/', [SettingsController::class, 'equipmentTypes'])->name('index');
            Route::get('create', [SettingsController::class, 'createEquipmentType'])->name('create');
            Route::post('/', [SettingsController::class, 'storeEquipmentType'])->name('store');
            Route::get('{equipmentType}/edit', [SettingsController::class, 'editEquipmentType'])->name('edit');
            Route::put('{equipmentType}', [SettingsController::class, 'updateEquipmentType'])->name('update');
            Route::delete('{equipmentType}', [SettingsController::class, 'destroyEquipmentType'])->name('destroy');
            Route::post('{equipmentType}/toggle', [SettingsController::class, 'toggleEquipmentType'])->name('toggle');
        });
        Route::post('update-sort-order', [SettingsController::class, 'updateSortOrder'])->name('update-sort-order');
    });
    
    // Database Backup & Restore
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::get('/list', [BackupController::class, 'list'])->name('list');
        Route::post('/create', [BackupController::class, 'backup'])->name('create');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
        Route::delete('/delete/{filename}', [BackupController::class, 'delete'])->name('delete');
    });
    
    // QR Scanner
    Route::get('qr-scanner', [EquipmentController::class, 'qrScanner'])->name('qr-scanner')->middleware('permission:qr.scan');
});
