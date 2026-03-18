<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobTypeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\WorkshopJobController;

// Ruta raíz → redirige al dashboard (el middleware auth se encarga del resto)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ─── Rutas protegidas por autenticación ───────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Gestión de Inventario (Traceability)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/purchase', [InventoryController::class, 'createPurchase'])->name('purchase');
        Route::post('/purchase', [InventoryController::class, 'storePurchase'])->name('store-purchase');
        Route::get('/adjustment', [InventoryController::class, 'createAdjustment'])->name('adjustment');
        Route::post('/adjustment', [InventoryController::class, 'storeAdjustment'])->name('store-adjustment');
    });

    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::resource('mechanics', MechanicController::class);

    Route::resource('jobs', WorkshopJobController::class)->only(['index', 'destroy']);
    Route::post('jobs/store-standalone', [WorkshopJobController::class, 'storeStandalone'])->name('jobs.store_individual');
    Route::post('jobs/{job}/complete', [WorkshopJobController::class, 'complete'])->name('jobs.complete');

    Route::resource('service-orders', ServiceOrderController::class);
    Route::post('/service-orders/{serviceOrder}/jobs', [WorkshopJobController::class, 'store'])->name('service-orders.jobs.store');

    Route::resource('sales', SaleController::class);
    Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/generate/{serviceOrder}', [InvoiceController::class, 'generateFromServiceOrder'])->name('invoices.generate');
    Route::resource('job-types', JobTypeController::class);

    // Settings
    Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings');
    Route::put('/settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

});
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__.'/auth.php';
