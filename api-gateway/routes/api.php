<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CorporateServiceController;
use App\Http\Controllers\InvoiceServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorServiceController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/user/register', [UserController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/auth/token-revoke', [AuthController::class, 'tokenRevoke']);
    Route::put('/auth/token-refresh', [AuthController::class, 'tokenRefresh']);

    // Get Logged In User
    Route::get('/user', [AuthController::class, 'getLoggedInUser']);

    // Vendor Routes
    // Route::prefix('/corporate/vendor')->group(function () {
    //     Route::post('/', [VendorServiceController::class, 'store']);
    //     Route::get('/', [VendorServiceController::class, 'index']);
    //     Route::put('/{id}', [VendorServiceController::class, 'update']);
    // });

    // Corporate Routes
    Route::post('/corporate', [CorporateServiceController::class, 'store']);
    Route::get('/corporate', [CorporateServiceController::class, 'index']);
    Route::get('/corporate/{id}', [CorporateServiceController::class, 'show']);

    // // Invoice Routes
    // Route::post('/corporate/{corp_id}/vendor/{vendor_id}/invoice', [InvoiceServiceController::class, 'store']);
    // Route::post('/corporate/{corp_id}/invoices/bulk', [InvoiceServiceController::class, 'bulkStore']);
    // Route::get('/corporate/{corp_id}/vendor/{vendor_id}/invoice', [InvoiceServiceController::class, 'index']);
    // Route::get('/corporate/{corp_id}/vendor/{vendor_id}/invoice/{invoice_id}', [InvoiceServiceController::class, 'show']);
    // Route::put('/corporate/{corp_id}/vendor/{vendor_id}/invoice/{invoice_id}', [InvoiceServiceController::class, 'update']);
});
