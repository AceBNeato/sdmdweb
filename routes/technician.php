<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Technician\TechnicianController;
use App\Http\Controllers\Technician\EquipmentController;
use App\Http\Controllers\Technician\ReportController;
use App\Http\Controllers\Auth\TechnicianLoginController;

/*
|--------------------------------------------------------------------------
| Technician Routes
|--------------------------------------------------------------------------
|
| Routes for technician users with equipment and maintenance access
|
*/

Route::middleware(['auth:technician', 'guard.access:technician'])
    ->prefix('technician')
    ->name('technician.')
    ->group(function () {
        
        // Dashboard (Profile)
        Route::get('/', [TechnicianController::class, 'profile'])->name('profile');
        
        // Profile routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [TechnicianController::class, 'profile'])->name('profile.show');
            Route::get('/edit', [TechnicianController::class, 'editProfile'])->name('profile.edit');
            Route::match(['put', 'post'], '/', [TechnicianController::class, 'updateProfile'])->name('profile.update');
        });
        
        // QR Scanner
        Route::get('/qr-scanner', [TechnicianLoginController::class, 'qrScanner'])->name('qr-scanner');
        
        // Logout
        Route::post('/logout', [TechnicianLoginController::class, 'logout'])->name('logout');
        
        // ============================================================================
        // EQUIPMENT MANAGEMENT
        // ============================================================================
        
        Route::prefix('equipment')->group(function () {
            Route::get('/', [EquipmentController::class, 'index'])->name('equipment.index');
            Route::get('/create', [EquipmentController::class, 'create'])->name('equipment.create');
            Route::post('/', [EquipmentController::class, 'store'])->name('equipment.store');
            
            // Print QR Codes (moved before parameter routes)
            Route::get('/print-qrcodes', [EquipmentController::class, 'printQrcodes'])->name('equipment.print-qrcodes');
            Route::get('/print-qrcodes/pdf', [EquipmentController::class, 'printQrcodesPdf'])->name('equipment.print-qrcodes.pdf');
            
            // Parameter routes (must come after specific routes)
            Route::get('/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show');
            Route::put('/{equipment}/status', [EquipmentController::class, 'updateStatus'])->name('equipment.status.update');
            Route::get('/{equipment}/edit', [EquipmentController::class, 'edit'])->name('equipment.edit');
            Route::put('/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
            
            // Get equipment for a specific office (AJAX)
            Route::get('/office/{officeId}', [EquipmentController::class, 'getOfficeEquipment'])->name('equipment.office');
            
            // QR Code routes
            Route::get('/{equipment}/qrcode', [EquipmentController::class, 'qrCode'])->name('equipment.qrcode');
            Route::get('/{equipment}/print-qrcode', [EquipmentController::class, 'qrCode'])->name('equipment.print-qrcode');
            
            // QR Code scanning
            Route::get('/scan', [EquipmentController::class, 'scanView'])->name('equipment.scan.view');
            Route::post('/scan', [EquipmentController::class, 'scanQrCode'])->name('equipment.scan.process');
            Route::post('/decode-qr', [EquipmentController::class, 'decodeQrCode'])->name('equipment.decode-qr');
            
            // QR Code routes (duplicate - keeping for compatibility)
            Route::prefix('{equipment}')->group(function () {
                Route::get('/print-qrcode', [EquipmentController::class, 'qrCode'])->name('equipment.print-qrcode');
            });
            
            // History routes
            Route::prefix('{equipment}')->group(function () {
                Route::get('/history/create', [EquipmentController::class, 'createHistory'])->name('equipment.history.create');
                Route::post('/history', [EquipmentController::class, 'storeHistory'])->name('equipment.history.store');
                Route::get('/history/{history}/edit', [EquipmentController::class, 'editHistory'])->name('equipment.history.edit')->middleware('permission:history.edit');
                Route::put('/history/{history}', [EquipmentController::class, 'updateHistory'])->name('equipment.history.update')->middleware('permission:history.edit');
                Route::post('/generate-jo', [EquipmentController::class, 'generateJONumber'])->name('equipment.generate-jo');
                Route::post('/check-latest-repair', [EquipmentController::class, 'checkLatestRepair'])->name('equipment.check-latest-repair');
                Route::post('/check-sequences', [EquipmentController::class, 'checkSequences'])->name('equipment.check-sequences');
                Route::post('/clear-history-prompt', [EquipmentController::class, 'clearHistoryPrompt'])->name('equipment.clear-history-prompt');
            });
        });
        
        // ============================================================================
        // REPORTS
        // ============================================================================
        
        Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/equipment-history', [ReportController::class, 'equipmentHistory'])->name('equipment-history');
            Route::get('/history/{equipment}', [ReportController::class, 'history'])->name('history');
            
            // Equipment History Export Route
            Route::prefix('equipment/{equipment}')->middleware('permission:reports.generate')->group(function () {
                Route::get('/export', [ReportController::class, 'exportEquipmentHistory'])->name('equipment.history.export');
                Route::get('/export-pdf', [ReportController::class, 'exportEquipmentHistoryPdf'])->name('equipment.history.export.pdf');
                Route::get('/history', [ReportController::class, 'history'])->name('equipment.history.view');
            });
        });
        
        // Test route for history form
        Route::get('/test-history/{equipment}', function(\App\Models\Equipment $equipment) {
            return view('technician.equipment.history.create', [
                'equipment' => $equipment->load('office')
            ]);
        })->name('test.history');
    });
