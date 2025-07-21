<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AllblogpostController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropertyController;

use App\Http\Controllers\API\TREBController;

use App\Http\Controllers\API\MediaController;




// Auth routes
Route::get('/auth/init', [AuthController::class, 'initializeAuth']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'getCurrentUser']);
    Route::get('/auth/verify', [AuthController::class, 'verifyToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

Route::get('/trebdata', [TREBController::class, 'fetch']);
// Blog routes
Route::get('/allblogposts', [AllblogpostController::class, 'index']);

// Property routes
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/featured', [PropertyController::class, 'featured']);
Route::get('/properties/{id}', [PropertyController::class, 'show']);

// Media routes
Route::get('/media/property', [MediaController::class, 'getPropertyMedia']);
Route::get('/media/proxy', [MediaController::class, 'proxyImage']);
Route::get('/media/{mediaKey}', [MediaController::class, 'show']);
Route::post('/media', [MediaController::class, 'store']);
Route::put('/media/{mediaKey}', [MediaController::class, 'update']);
Route::delete('/media/{mediaKey}', [MediaController::class, 'destroy']);
Route::get('/trebmedia/{mlsNumber}', [MediaController::class, 'getTrebMedia']);
