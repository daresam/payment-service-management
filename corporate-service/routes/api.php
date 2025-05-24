<?php

use App\Http\Controllers\CorporateController;
use App\Http\Controllers\ServiceAccountController;
use Illuminate\Support\Facades\Route;

// Corporate Routes
Route::post('/corporate', [CorporateController::class, 'store']);
Route::get('/corporate', [CorporateController::class, 'index']);
Route::get('/corporate/{id}', [CorporateController::class, 'show']);

Route::post('service-accounts/token', [ServiceAccountController::class, 'issueToken']);
