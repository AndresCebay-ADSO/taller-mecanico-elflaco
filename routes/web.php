<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InvoiceController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('suppliers', SupplierController::class);
Route::resource('products', ProductController::class);
Route::resource('mechanics', MechanicController::class);
Route::resource('service-orders', ServiceOrderController::class);
Route::resource('sales', SaleController::class);
Route::resource('invoices', InvoiceController::class);
