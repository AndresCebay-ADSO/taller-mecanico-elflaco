<?php

use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchTransferController;

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

    Route::patch('batches/{batch}', [BatchController::class, 'update'])->name('batches.update');

    Route::resource('suppliers', SupplierController::class);
    Route::patch('suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggleActive');
    Route::patch('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
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
    // ─── Rutas protegidas (Solo Administradores) ──────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::resource('job-types', JobTypeController::class);

        // Settings
        Route::get('/settings', [App\Http\Controllers\SettingController::class, 'index'])->name('settings');
        Route::put('/settings', [App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

        // Branches
        Route::resource('branches', BranchController::class)->except(['show', 'edit', 'create']);
        Route::post('branches/{branch}/toggle-active', [BranchController::class, 'toggleActive'])->name('branches.toggle-active');

        // Branch Transfers (Only admins should move stock between branches)
        Route::prefix('branch-transfers')->name('branch-transfers.')->group(function () {
            Route::get('/', [BranchTransferController::class, 'index'])->name('index');
            Route::get('/create', [BranchTransferController::class, 'create'])->name('create');
            Route::post('/', [BranchTransferController::class, 'store'])->name('store');
            Route::patch('/{transfer}/status', [BranchTransferController::class, 'updateStatus'])->name('update-status');
        });
    });

    // Switch current branch
    Route::post('switch-branch', [BranchController::class, 'switch'])->name('switch-branch');

});
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__.'/auth.php';
