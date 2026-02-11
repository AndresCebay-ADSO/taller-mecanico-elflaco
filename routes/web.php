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

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/reports', [ReportController::class, 'index'])->name('reports');

Route::resource('suppliers', SupplierController::class);
Route::resource('products', ProductController::class);
Route::resource('mechanics', MechanicController::class);
Route::resource('service-orders', ServiceOrderController::class);
Route::resource('sales', SaleController::class);
Route::resource('invoices', InvoiceController::class);
Route::resource('job-types', JobTypeController::class);

// Stub routes for navigation
Route::get('/inventory', fn() => redirect()->route('products.index'))->name('inventory');
Route::get('/tasks', fn() => redirect()->route('service-orders.index'))->name('tasks');
Route::get('/reports', fn() => view('dashboard'))->name('reports');
Route::get('/settings', fn() => view('dashboard'))->name('settings');
