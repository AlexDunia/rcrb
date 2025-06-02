<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AllblogpostController;
use App\Http\Controllers\API\AuthController;

// Auth routes
Route::get('/auth/init', [AuthController::class, 'initializeAuth']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'getCurrentUser']);
    Route::get('/auth/verify', [AuthController::class, 'verifyToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Blog routes
Route::get('/allblogposts', [AllblogpostController::class, 'index']);
