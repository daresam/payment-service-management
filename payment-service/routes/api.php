<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ServiceAccountController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// Vendor Routes
Route::prefix('/corporate/vendor')->group(function () {
    Route::post('/', [VendorController::class, 'store']);
    Route::get('/', [VendorController::class, 'index']);
    Route::put('/{id}', [VendorController::class, 'update']);
});

// Invoice Routes
Route::post('/corporate/{corp_id}/vendor/{vendor_id}/invoice', [InvoiceController::class, 'store']);
Route::post('/corporate/{corp_id}/invoices/bulk', [InvoiceController::class, 'bulkStore']);
Route::get('/corporate/{corp_id}/vendor/{vendor_id}/invoice', [InvoiceController::class, 'index']);
Route::get('/corporate/{corp_id}/vendor/{vendor_id}/invoice/{invoice_id}', [InvoiceController::class, 'show']);
Route::put('/corporate/{corp_id}/vendor/{vendor_id}/invoice/{invoice_id}', [InvoiceController::class, 'update']);

Route::post('service-accounts/token', [ServiceAccountController::class, 'issueToken']);
