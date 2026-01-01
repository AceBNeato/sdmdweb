<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Staff\EquipmentController;
use App\Http\Controllers\Staff\ReportController;
use App\Http\Controllers\Auth\StaffLoginController;

/*
|--------------------------------------------------------------------------
| Staff Routes
|--------------------------------------------------------------------------
|
| Routes for staff users with standard permissions
|
*/

Route::middleware(['auth:staff', 'guard.access:staff'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        
        // Dashboard (Profile)
        Route::get('/', [StaffController::class, 'profile'])->name('profile');
        
        // Profile routes
        Route::prefix('profile')->group(function () {
            // Show profile
            Route::get('/', [StaffController::class, 'profile'])->name('profile.show');
            // Edit profile form
            Route::get('/edit', [StaffController::class, 'editProfile'])->name('profile.edit');
            // Update profile
            Route::match(['put', 'post'], '/', [StaffController::class, 'updateProfile'])->name('profile.update');
        });
        
        // QR Scanner
        Route::get('qr-scanner', [EquipmentController::class, 'qrScanner'])->name('qr-scanner')->middleware('permission:qr.scan');
        
        // Logout
        Route::post('/logout', [StaffLoginController::class, 'logout'])->name('logout');
        
        // ============================================================================
        // EQUIPMENT MANAGEMENT
        // ============================================================================
        
        Route::prefix('equipment')->name('equipment.')->group(function () {
            Route::get('/', [EquipmentController::class, 'index'])->name('index');
            Route::get('create', [EquipmentController::class, 'create'])->name('create');
            Route::post('/', [EquipmentController::class, 'store'])->name('store');
            
            // Print QR Codes (moved before parameter routes)
            Route::get('print-qrcodes', [EquipmentController::class, 'printQrcodes'])->name('print-qrcodes');
            Route::get('print-qrcodes/pdf', [EquipmentController::class, 'printQrcodesPdf'])->name('print-qrcodes.pdf');
            
            // QR Scanner
            Route::get('scan', [EquipmentController::class, 'scanView'])->name('scan.view');
            Route::post('scan', [EquipmentController::class, 'scanQrCode'])->name('scan.process');
            
            // Parameter routes (must come after specific routes)
            Route::get('{equipment}', [EquipmentController::class, 'show'])->name('show');
            Route::get('{equipment}/edit', [EquipmentController::class, 'edit'])->name('edit');
            Route::put('{equipment}', [EquipmentController::class, 'update'])->name('update');
            Route::delete('{equipment}', [EquipmentController::class, 'destroy'])->name('destroy');
            
            // QR Code routes
            Route::get('{equipment}/qrcode', [EquipmentController::class, 'qrCode'])->name('qrcode');
            Route::get('{equipment}/print-qrcode', [EquipmentController::class, 'printQRCode'])->name('print-qrcode');
        });
        
        // ============================================================================
        // REPORTS
        // ============================================================================
        
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/equipment-history', [ReportController::class, 'equipmentHistory'])->name('equipment-history');
            Route::get('/history/{equipment}', [ReportController::class, 'history'])->name('history');
            
            // Equipment History Export
            Route::prefix('equipment/{equipment}')->middleware('permission:reports.generate')->group(function () {
                Route::get('/export', [ReportController::class, 'exportEquipmentHistory'])->name('equipment.history.export');
                Route::get('/export-pdf', [ReportController::class, 'exportEquipmentHistoryPdf'])->name('equipment.history.export.pdf');
            });
        });
    });
