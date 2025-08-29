<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AllblogpostController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PropertyController;
use App\Http\Controllers\API\TREBController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\YahooController;
use App\Http\Controllers\API\FavoritesController;

Route::prefix('auth')->group(function () {
    Route::get('/init', [AuthController::class, 'initializeAuth']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
    Route::get('/yahoo/redirect', [YahooController::class, 'redirect'])->name('yahoo.redirect');
    Route::get('/yahoo/callback', [YahooController::class, 'callback'])->name('yahoo.callback');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'getCurrentUser']);
        Route::get('/verify', [AuthController::class, 'verifyToken']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/allblogposts', [AllblogpostController::class, 'index']);
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/featured', [PropertyController::class, 'featured']);
Route::get('/properties/{id}', [PropertyController::class, 'show']);
Route::get('/trebdata', [TREBController::class, 'fetch']);
Route::get('/trebmembers', [TREBController::class, 'fetchMembers']);
Route::get('/trebmember/{memberKey}', [TREBController::class, 'fetchSingleMember']);
Route::get('/trebmedia/{listingKey}', [TREBController::class, 'fetchMedia']);
Route::get('/trebsearch', [TREBController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/favorites', [FavoritesController::class, 'toggle']);
    Route::get('/favorites', [FavoritesController::class, 'index']);
    Route::delete('/favorites', [FavoritesController::class, 'clear']);
});
