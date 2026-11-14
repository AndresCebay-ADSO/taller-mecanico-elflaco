<?php

use App\Http\Controllers\BatchController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchTransferController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\JobTypeController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkshopJobController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

// Ruta raíz → redirige al dashboard (el middleware auth se encarga del resto)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// ─── Rutas protegidas por autenticación ───────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Perfil propio
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Cambiar sucursal activa
    Route::post('switch-branch', [BranchController::class, 'switch'])->name('switch-branch');

    // Gestión de Inventario (Traceability)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/purchase', [InventoryController::class, 'createPurchase'])->name('purchase');
        Route::post('/purchase', [InventoryController::class, 'storePurchase'])->name('store-purchase');
    });

    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update']);
    Route::resource('mechanics', MechanicController::class)->except(['show', 'destroy']);

    // Órdenes y trabajos
    Route::resource('service-orders', ServiceOrderController::class);
    Route::post('/service-orders/{serviceOrder}/jobs', [WorkshopJobController::class, 'store'])->name('service-orders.jobs.store');
    Route::get('jobs', [WorkshopJobController::class, 'index'])->name('jobs.index');
    Route::post('jobs/store-standalone', [WorkshopJobController::class, 'storeStandalone'])->name('jobs.store_individual');
    Route::post('jobs/{job}/complete', [WorkshopJobController::class, 'complete'])->name('jobs.complete');
    Route::delete('jobs/{job}', [WorkshopJobController::class, 'destroy'])->name('jobs.destroy');

    // Ventas y facturación
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::post('invoices/generate/{serviceOrder}', [InvoiceController::class, 'generateFromServiceOrder'])->name('invoices.generate');

    // Caja diaria: base, gastos y pagos, cierre e historial.
    Route::prefix('cash')->name('cash.')->group(function () {
        Route::get('/', [CashController::class, 'index'])->name('index');
        Route::get('/history', [CashController::class, 'history'])->name('history');
        Route::put('/opening', [CashController::class, 'updateOpening'])->name('opening');
        Route::post('/movements', [CashController::class, 'storeMovement'])->name('movements.store');
        Route::post('/close', [CashController::class, 'close'])->name('close');
    });

    // Documentos imprimibles (página aparte, tamaño carta) y logo del taller.
    Route::get('invoices/{invoice}/print', [DocumentController::class, 'invoice'])->name('documents.invoice');
    Route::get('sales/{sale}/print', [DocumentController::class, 'sale'])->name('documents.sale');
    Route::get('service-orders/{serviceOrder}/intake', [DocumentController::class, 'serviceOrderIntake'])->name('documents.intake');
    Route::get('branding/logo', [DocumentController::class, 'logo'])->name('branding.logo');

    // ─── Solo Administradores: acciones que alteran dinero, stock o permisos ──
    Route::middleware('admin')->group(function () {
        Route::get('inventory/adjustment', [InventoryController::class, 'createAdjustment'])->name('inventory.adjustment');
        Route::post('inventory/adjustment', [InventoryController::class, 'storeAdjustment'])->name('inventory.store-adjustment');
        Route::patch('batches/{batch}', [BatchController::class, 'update'])->name('batches.update');

        // Carga inicial desde la plantilla de Excel: revisar primero, importar después.
        Route::get('imports', [DataImportController::class, 'index'])->name('imports.index');
        Route::get('imports/template', [DataImportController::class, 'template'])->name('imports.template');
        Route::post('imports/preview', [DataImportController::class, 'preview'])->name('imports.preview');
        Route::post('imports/confirm', [DataImportController::class, 'confirm'])->name('imports.confirm');
        Route::post('imports/discard', [DataImportController::class, 'discard'])->name('imports.discard');

        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('suppliers/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('suppliers.toggleActive');
        Route::delete('mechanics/{mechanic}', [MechanicController::class, 'destroy'])->name('mechanics.destroy');

        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        // Caja: borrar un movimiento o reabrir un día cerrado cambia cifras ya revisadas.
        Route::delete('cash/movements/{movement}', [CashController::class, 'destroyMovement'])->name('cash.movements.destroy');
        Route::post('cash/{cashDay}/reopen', [CashController::class, 'reopen'])->name('cash.reopen');

        // Usuarios
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::resource('job-types', JobTypeController::class)->except(['show']);

        // Settings
        Route::get('/settings', [SettingController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

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
});
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__.'/auth.php';
