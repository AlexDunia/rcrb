<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AllblogpostController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\TREBController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\API\FavoritesController;

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
Route::get('/trebmembers', [TREBController::class, 'fetchMembers']);
Route::get('/trebmember/{memberKey}', [TREBController::class, 'fetchSingleMember']);

Route::get('/trebmedia/{listingKey}', [TREBController::class, 'fetchMedia']);

Route::get('/trebsearch', [TREBController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'getCurrentUser']);
    Route::get('/auth/verify', [AuthController::class, 'verifyToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/favorites', [FavoritesController::class, 'toggle']);
    Route::get('/favorites', [FavoritesController::class, 'index']);
    Route::delete('/favorites', [FavoritesController::class, 'clear']);
});


Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');



