<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LoginController;
use Illuminate\Support\Facades\Route;

// Public Route for Authentication
Route::post('/login', [LoginController::class, 'login']);

// Tenant-specific Protected Routes
Route::middleware(['tenant', 'auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);
});
