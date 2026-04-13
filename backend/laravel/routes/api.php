<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ShippingLabelController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public — authentication
// ---------------------------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ---------------------------------------------------------------------------
// Protected — require a valid Sanctum token
// ---------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Shipping labels
    Route::get('/labels',                          [ShippingLabelController::class, 'index']);
    Route::post('/labels',                         [ShippingLabelController::class, 'store']);
    Route::get('/labels/{label}',                  [ShippingLabelController::class, 'show']);
    Route::get('/labels/{label}/download',         [ShippingLabelController::class, 'download']);
});
